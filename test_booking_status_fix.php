<?php
/*
===========================================
FILE: Test Booking Status Fix
PURPOSE: Verify that the booking status logic works correctly
===========================================
*/

require_once('includes/db.php');

try {
    $db = getDB();
    
    echo "<h2>🧪 Test Booking Status Fix</h2>\n";
    
    // Step 1: Show current time information
    echo "<h3>⏰ Time Information:</h3>\n";
    $time_info = $db->query("SELECT 
        NOW() as mysql_now,
        DATE_SUB(NOW(), INTERVAL 1 HOUR) as one_hour_ago,
        DATE_ADD(NOW(), INTERVAL 1 HOUR) as one_hour_future
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Current MySQL Time</th><td>{$time_info['mysql_now']}</td></tr>\n";
    echo "<tr><th>1 Hour Ago</th><td>{$time_info['one_hour_ago']}</td></tr>\n";
    echo "<tr><th>1 Hour Future</th><td>{$time_info['one_hour_future']}</td></tr>\n";
    echo "<tr><th>PHP Time</th><td>" . date('Y-m-d H:i:s') . "</td></tr>\n";
    echo "</table>\n";
    
    // Step 2: Test the new auto-completion logic
    echo "<h3>🔧 Testing New Auto-Completion Logic:</h3>\n";
    echo "<p><strong>New Logic:</strong> Only mark bookings as completed if departure was more than 1 hour ago</p>\n";
    
    $test_sql = "SELECT 
        b.id,
        b.booking_id,
        b.status,
        s.departure_time,
        CASE 
            WHEN s.departure_time <= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 'WOULD_BE_COMPLETED'
            ELSE 'WOULD_REMAIN_BOOKED'
        END as new_logic_result,
        CASE 
            WHEN s.departure_time <= NOW() THEN 'OLD_LOGIC_COMPLETED'
            ELSE 'OLD_LOGIC_BOOKED'
        END as old_logic_result,
        TIMESTAMPDIFF(HOUR, NOW(), s.departure_time) as hours_until_departure
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    WHERE b.status IN ('Booked', 'Completed')
    ORDER BY s.departure_time DESC
    LIMIT 10";
    
    $stmt = $db->query($test_sql);
    $test_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($test_results)) {
        echo "<p>No bookings found to test</p>\n";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr style='background: #f0f0f0;'>\n";
        echo "<th>Booking ID</th><th>Current Status</th><th>Departure Time</th><th>Hours Until</th><th>New Logic</th><th>Old Logic</th>\n";
        echo "</tr>\n";
        
        foreach ($test_results as $result) {
            // Highlight problematic cases
            $row_color = '';
            if ($result['new_logic_result'] !== $result['old_logic_result']) {
                $row_color = 'background: #fff3cd;'; // Different results
            }
            if ($result['hours_until_departure'] > 0 && $result['old_logic_result'] === 'OLD_LOGIC_COMPLETED') {
                $row_color = 'background: #ffebee;'; // Future booking that old logic would complete
            }
            
            echo "<tr style='{$row_color}'>\n";
            echo "<td>{$result['booking_id']}</td>\n";
            echo "<td><strong>{$result['status']}</strong></td>\n";
            echo "<td>{$result['departure_time']}</td>\n";
            echo "<td>{$result['hours_until_departure']}</td>\n";
            echo "<td><strong>{$result['new_logic_result']}</strong></td>\n";
            echo "<td>{$result['old_logic_result']}</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // Count improvements
        $improvements = array_filter($test_results, function($r) {
            return $r['hours_until_departure'] > -1 && $r['old_logic_result'] === 'OLD_LOGIC_COMPLETED' && $r['new_logic_result'] === 'WOULD_REMAIN_BOOKED';
        });
        
        if (!empty($improvements)) {
            echo "<p style='color: green;'><strong>✅ Improvement: " . count($improvements) . " bookings will now correctly remain as 'Booked' instead of being prematurely completed!</strong></p>\n";
        }
    }
    
    // Step 3: Show current booking status distribution
    echo "<h3>📊 Current Booking Status Distribution:</h3>\n";
    $status_sql = "SELECT 
        b.status,
        COUNT(*) as total,
        COUNT(CASE WHEN s.departure_time > NOW() THEN 1 END) as future_bookings,
        COUNT(CASE WHEN s.departure_time <= NOW() AND s.departure_time > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as recent_past,
        COUNT(CASE WHEN s.departure_time <= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as truly_past
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    GROUP BY b.status
    ORDER BY b.status";
    
    $status_stmt = $db->query($status_sql);
    $status_data = $status_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr style='background: #f0f0f0;'>\n";
    echo "<th>Status</th><th>Total</th><th>Future Trips</th><th>Recent Past (≤1h)</th><th>Truly Past (>1h)</th>\n";
    echo "</tr>\n";
    
    foreach ($status_data as $row) {
        $highlight = ($row['status'] === 'Completed' && $row['future_bookings'] > 0) ? 'background: #ffebee;' : '';
        echo "<tr style='{$highlight}'>\n";
        echo "<td><strong>{$row['status']}</strong></td>\n";
        echo "<td>{$row['total']}</td>\n";
        echo "<td>{$row['future_bookings']}</td>\n";
        echo "<td>{$row['recent_past']}</td>\n";
        echo "<td>{$row['truly_past']}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Step 4: Action buttons
    echo "<h3>🔧 Actions:</h3>\n";
    echo "<p><a href='fix_incorrect_completed_bookings.php' style='background: #f44336; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>🔧 Fix Incorrect Completed Bookings</a></p>\n";
    echo "<p><a href='debug_booking_status.php' style='background: #2196f3; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>🔍 Run Full Debug</a></p>\n";
    echo "<p><a href='views/bookings.php' style='background: #4caf50; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>📋 View Bookings Page</a></p>\n";
    
    echo "<hr>\n";
    echo "<h3>📝 Summary of Changes Made:</h3>\n";
    echo "<ul>\n";
    echo "<li>✅ <strong>Fixed auto-completion logic:</strong> Now uses MySQL NOW() instead of PHP date()</li>\n";
    echo "<li>✅ <strong>Added 1-hour safety margin:</strong> Only marks bookings as completed 1 hour after departure</li>\n";
    echo "<li>✅ <strong>Fixed time comparison:</strong> Uses consistent database time for filtering</li>\n";
    echo "<li>✅ <strong>Prevents timezone issues:</strong> All time comparisons now use MySQL functions</li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
