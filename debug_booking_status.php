<?php
/*
===========================================
FILE: Debug Booking Status Logic
PURPOSE: Debug why bookings are marked as completed prematurely
===========================================
*/

require_once('includes/db.php');

try {
    $db = getDB();
    
    echo "<h2>🔍 Booking Status Debug</h2>\n";
    
    // Check current server time
    echo "<h3>⏰ Server Time Information:</h3>\n";
    echo "<p><strong>Current PHP time:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
    echo "<p><strong>Current MySQL time:</strong> ";
    $mysql_time = $db->query("SELECT NOW() as current_time")->fetch(PDO::FETCH_ASSOC);
    echo $mysql_time['current_time'] . "</p>\n";
    echo "<p><strong>PHP Timezone:</strong> " . date_default_timezone_get() . "</p>\n";
    
    // Check problematic bookings
    echo "<h3>📋 Problematic Bookings Analysis:</h3>\n";
    $problem_sql = "SELECT 
        b.id,
        b.booking_id,
        b.status,
        b.user_id,
        s.departure_time,
        bu.bus_number,
        r.source,
        r.destination,
        CASE 
            WHEN s.departure_time <= NOW() THEN 'PAST'
            ELSE 'FUTURE'
        END as time_status,
        TIMESTAMPDIFF(HOUR, NOW(), s.departure_time) as hours_until_departure
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bu ON s.bus_id = bu.id
    JOIN routes r ON s.route_id = r.id
    WHERE b.status = 'Completed'
    ORDER BY s.departure_time DESC
    LIMIT 10";
    
    $stmt = $db->query($problem_sql);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($bookings)) {
        echo "<p>✅ No completed bookings found</p>\n";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr style='background: #f0f0f0;'>\n";
        echo "<th>Booking ID</th><th>Status</th><th>Departure Time</th><th>Time Status</th><th>Hours Until</th><th>Route</th>\n";
        echo "</tr>\n";
        
        foreach ($bookings as $booking) {
            $row_color = ($booking['time_status'] === 'FUTURE') ? 'background: #ffebee;' : '';
            echo "<tr style='{$row_color}'>\n";
            echo "<td>{$booking['booking_id']}</td>\n";
            echo "<td>{$booking['status']}</td>\n";
            echo "<td>{$booking['departure_time']}</td>\n";
            echo "<td><strong>{$booking['time_status']}</strong></td>\n";
            echo "<td>{$booking['hours_until_departure']}</td>\n";
            echo "<td>{$booking['source']} → {$booking['destination']}</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // Count future bookings marked as completed
        $future_completed = array_filter($bookings, function($b) { return $b['time_status'] === 'FUTURE'; });
        if (!empty($future_completed)) {
            echo "<p style='color: red;'><strong>⚠️ PROBLEM FOUND: " . count($future_completed) . " future bookings are incorrectly marked as completed!</strong></p>\n";
        }
    }
    
    // Test the auto-completion logic
    echo "<h3>🧪 Testing Auto-Completion Logic:</h3>\n";
    $now = date('Y-m-d H:i:s');
    echo "<p><strong>Current PHP time used in logic:</strong> {$now}</p>\n";
    
    $test_sql = "SELECT 
        b.id,
        b.booking_id,
        b.status,
        s.departure_time,
        CASE 
            WHEN s.departure_time <= ? THEN 'WOULD_BE_COMPLETED'
            ELSE 'WOULD_REMAIN_BOOKED'
        END as logic_result
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    WHERE b.status IN ('Booked', 'Completed')
    ORDER BY s.departure_time DESC
    LIMIT 5";
    
    $stmt = $db->prepare($test_sql);
    $stmt->execute([$now]);
    $test_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr style='background: #f0f0f0;'>\n";
    echo "<th>Booking ID</th><th>Current Status</th><th>Departure Time</th><th>Logic Result</th>\n";
    echo "</tr>\n";
    
    foreach ($test_results as $result) {
        $row_color = ($result['logic_result'] === 'WOULD_BE_COMPLETED' && strtotime($result['departure_time']) > time()) ? 'background: #ffebee;' : '';
        echo "<tr style='{$row_color}'>\n";
        echo "<td>{$result['booking_id']}</td>\n";
        echo "<td>{$result['status']}</td>\n";
        echo "<td>{$result['departure_time']}</td>\n";
        echo "<td><strong>{$result['logic_result']}</strong></td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Show the exact query that's causing the problem
    echo "<h3>🔍 Problematic Query Analysis:</h3>\n";
    echo "<p><strong>The problematic query in bookings.php:</strong></p>\n";
    echo "<pre style='background: #f0f0f0; padding: 1rem; border-radius: 4px;'>\n";
    echo "UPDATE bookings b \n";
    echo "JOIN schedules s ON b.schedule_id = s.id \n";
    echo "SET b.status = 'Completed' \n";
    echo "WHERE b.user_id = ? \n";
    echo "AND s.departure_time <= '{$now}' \n";
    echo "AND b.status NOT IN ('Cancelled', 'Completed')\n";
    echo "</pre>\n";
    
    // Check if there are any timezone mismatches
    echo "<h3>🌍 Timezone Analysis:</h3>\n";
    $timezone_test = $db->query("SELECT 
        NOW() as mysql_now,
        UTC_TIMESTAMP() as mysql_utc,
        @@session.time_zone as mysql_timezone,
        @@global.time_zone as mysql_global_timezone
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>MySQL NOW()</th><td>{$timezone_test['mysql_now']}</td></tr>\n";
    echo "<tr><th>MySQL UTC</th><td>{$timezone_test['mysql_utc']}</td></tr>\n";
    echo "<tr><th>MySQL Session TZ</th><td>{$timezone_test['mysql_timezone']}</td></tr>\n";
    echo "<tr><th>MySQL Global TZ</th><td>{$timezone_test['mysql_global_timezone']}</td></tr>\n";
    echo "<tr><th>PHP Time</th><td>" . date('Y-m-d H:i:s') . "</td></tr>\n";
    echo "<tr><th>PHP Timezone</th><td>" . date_default_timezone_get() . "</td></tr>\n";
    echo "</table>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
