<?php
/*
===========================================
FILE: Fix Incorrect Completed Bookings
PURPOSE: Find and fix bookings that were incorrectly marked as completed
===========================================
*/

require_once('includes/db.php');

try {
    $db = getDB();
    
    echo "<h2>🔧 Fix Incorrect Completed Bookings</h2>\n";
    
    // Step 1: Find bookings that are marked as completed but have future departure times
    echo "<h3>🔍 Finding Incorrectly Completed Bookings:</h3>\n";
    
    $find_sql = "SELECT 
        b.id,
        b.booking_id,
        b.status,
        b.user_id,
        s.departure_time,
        bu.bus_number,
        r.source,
        r.destination,
        TIMESTAMPDIFF(HOUR, NOW(), s.departure_time) as hours_until_departure
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bu ON s.bus_id = bu.id
    JOIN routes r ON s.route_id = r.id
    WHERE b.status = 'Completed' 
    AND s.departure_time > NOW()
    ORDER BY s.departure_time ASC";
    
    $stmt = $db->query($find_sql);
    $incorrect_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($incorrect_bookings)) {
        echo "<p>✅ No incorrectly completed bookings found!</p>\n";
    } else {
        echo "<p style='color: red;'><strong>⚠️ Found " . count($incorrect_bookings) . " bookings incorrectly marked as completed:</strong></p>\n";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr style='background: #f0f0f0;'>\n";
        echo "<th>Booking ID</th><th>Route</th><th>Departure Time</th><th>Hours Until</th><th>Status</th>\n";
        echo "</tr>\n";
        
        foreach ($incorrect_bookings as $booking) {
            echo "<tr style='background: #ffebee;'>\n";
            echo "<td>{$booking['booking_id']}</td>\n";
            echo "<td>{$booking['source']} → {$booking['destination']}</td>\n";
            echo "<td>{$booking['departure_time']}</td>\n";
            echo "<td>{$booking['hours_until_departure']} hours</td>\n";
            echo "<td><strong>{$booking['status']}</strong></td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // Step 2: Fix the incorrect bookings
        if (isset($_GET['fix']) && $_GET['fix'] === 'yes') {
            echo "<h3>🔧 Fixing Incorrect Bookings:</h3>\n";
            
            $fix_sql = "UPDATE bookings b
                       JOIN schedules s ON b.schedule_id = s.id
                       SET b.status = 'Booked'
                       WHERE b.status = 'Completed' 
                       AND s.departure_time > NOW()";
            
            $fix_stmt = $db->prepare($fix_sql);
            $result = $fix_stmt->execute();
            $fixed_count = $fix_stmt->rowCount();
            
            if ($result) {
                echo "<p style='color: green;'><strong>✅ Fixed {$fixed_count} bookings!</strong></p>\n";
                echo "<p>All future bookings have been changed from 'Completed' back to 'Booked'.</p>\n";
                echo "<p><a href='fix_incorrect_completed_bookings.php'>🔄 Refresh to verify</a></p>\n";
            } else {
                echo "<p style='color: red;'>❌ Failed to fix bookings</p>\n";
            }
        } else {
            echo "<p><a href='?fix=yes' style='background: #f44336; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>🔧 Fix These Bookings</a></p>\n";
            echo "<p><small>This will change all future bookings from 'Completed' back to 'Booked'</small></p>\n";
        }
    }
    
    // Step 3: Show current time comparison
    echo "<h3>⏰ Time Comparison:</h3>\n";
    $time_info = $db->query("SELECT 
        NOW() as mysql_now,
        UTC_TIMESTAMP() as mysql_utc,
        @@session.time_zone as mysql_timezone
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>MySQL NOW()</th><td>{$time_info['mysql_now']}</td></tr>\n";
    echo "<tr><th>MySQL UTC</th><td>{$time_info['mysql_utc']}</td></tr>\n";
    echo "<tr><th>MySQL Timezone</th><td>{$time_info['mysql_timezone']}</td></tr>\n";
    echo "<tr><th>PHP Time</th><td>" . date('Y-m-d H:i:s') . "</td></tr>\n";
    echo "<tr><th>PHP Timezone</th><td>" . date_default_timezone_get() . "</td></tr>\n";
    echo "</table>\n";
    
    // Step 4: Show all bookings status summary
    echo "<h3>📊 Booking Status Summary:</h3>\n";
    $summary_sql = "SELECT 
        b.status,
        COUNT(*) as count,
        COUNT(CASE WHEN s.departure_time > NOW() THEN 1 END) as future_trips,
        COUNT(CASE WHEN s.departure_time <= NOW() THEN 1 END) as past_trips
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    GROUP BY b.status
    ORDER BY b.status";
    
    $summary_stmt = $db->query($summary_sql);
    $summary_data = $summary_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr style='background: #f0f0f0;'>\n";
    echo "<th>Status</th><th>Total Count</th><th>Future Trips</th><th>Past Trips</th>\n";
    echo "</tr>\n";
    
    foreach ($summary_data as $row) {
        $highlight = ($row['status'] === 'Completed' && $row['future_trips'] > 0) ? 'background: #ffebee;' : '';
        echo "<tr style='{$highlight}'>\n";
        echo "<td><strong>{$row['status']}</strong></td>\n";
        echo "<td>{$row['count']}</td>\n";
        echo "<td>{$row['future_trips']}</td>\n";
        echo "<td>{$row['past_trips']}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    if (isset($_GET['fix']) && $_GET['fix'] === 'yes') {
        echo "<hr>\n";
        echo "<p><strong>🔗 Next Steps:</strong></p>\n";
        echo "<ul>\n";
        echo "<li><a href='views/bookings.php'>📋 Check User Bookings Page</a></li>\n";
        echo "<li><a href='views/dashboard.php'>🏠 Go to Dashboard</a></li>\n";
        echo "<li><a href='debug_booking_status.php'>🔍 Run Debug Script</a></li>\n";
        echo "</ul>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
