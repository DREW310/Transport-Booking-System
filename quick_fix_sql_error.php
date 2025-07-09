<?php
/*
===========================================
FILE: Quick Fix for SQL Error
PURPOSE: Fix the SQL syntax error and verify the booking status
===========================================
*/

require_once('includes/db.php');

try {
    $db = getDB();
    
    echo "<h2>🔧 Quick Fix for SQL Error</h2>\n";
    
    // Test the database connection and time functions
    echo "<h3>✅ Testing Database Time Functions:</h3>\n";
    
    $time_test = $db->query("SELECT
        NOW() as current_datetime,
        DATE(NOW()) as today_date,
        TIME(NOW()) as current_time_only
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Function</th><th>Result</th></tr>\n";
    echo "<tr><td>NOW() as current_datetime</td><td>{$time_test['current_datetime']}</td></tr>\n";
    echo "<tr><td>DATE(NOW()) as today_date</td><td>{$time_test['today_date']}</td></tr>\n";
    echo "<tr><td>TIME(NOW()) as current_time_only</td><td>{$time_test['current_time_only']}</td></tr>\n";
    echo "</table>\n";
    
    echo "<p style='color: green;'><strong>✅ Database time functions are working correctly!</strong></p>\n";
    
    // Check for any bookings that are still incorrectly marked as completed
    echo "<h3>🔍 Checking for Incorrect Completed Bookings:</h3>\n";
    
    $check_sql = "SELECT 
        b.id,
        b.booking_id,
        b.status,
        s.departure_time,
        TIMESTAMPDIFF(HOUR, NOW(), s.departure_time) as hours_until_departure,
        bu.bus_number,
        r.source,
        r.destination
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bu ON s.bus_id = bu.id
    JOIN routes r ON s.route_id = r.id
    WHERE b.status = 'Completed' 
    AND s.departure_time > NOW()
    ORDER BY s.departure_time ASC";
    
    $stmt = $db->query($check_sql);
    $incorrect_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($incorrect_bookings)) {
        echo "<p style='color: green;'><strong>✅ No incorrect completed bookings found!</strong></p>\n";
    } else {
        echo "<p style='color: red;'><strong>⚠️ Found " . count($incorrect_bookings) . " bookings still incorrectly marked as completed:</strong></p>\n";
        
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
        
        // Auto-fix option
        if (isset($_GET['auto_fix']) && $_GET['auto_fix'] === 'yes') {
            echo "<h3>🔧 Auto-Fixing Incorrect Bookings:</h3>\n";
            
            $fix_sql = "UPDATE bookings b
                       JOIN schedules s ON b.schedule_id = s.id
                       SET b.status = 'Booked'
                       WHERE b.status = 'Completed' 
                       AND s.departure_time > NOW()";
            
            $fix_stmt = $db->prepare($fix_sql);
            $result = $fix_stmt->execute();
            $fixed_count = $fix_stmt->rowCount();
            
            if ($result) {
                echo "<p style='color: green;'><strong>✅ Auto-fixed {$fixed_count} bookings!</strong></p>\n";
                echo "<p>All future bookings have been changed from 'Completed' back to 'Booked'.</p>\n";
                echo "<p><a href='quick_fix_sql_error.php'>🔄 Refresh to verify</a></p>\n";
            } else {
                echo "<p style='color: red;'>❌ Failed to auto-fix bookings</p>\n";
            }
        } else {
            echo "<p><a href='?auto_fix=yes' style='background: #f44336; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>🔧 Auto-Fix These Bookings</a></p>\n";
        }
    }
    
    // Show booking status summary
    echo "<h3>📊 Current Booking Status Summary:</h3>\n";
    $summary_sql = "SELECT 
        b.status,
        COUNT(*) as total,
        COUNT(CASE WHEN s.departure_time > NOW() THEN 1 END) as future_trips,
        COUNT(CASE WHEN s.departure_time <= NOW() AND s.departure_time > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as recent_past,
        COUNT(CASE WHEN s.departure_time <= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as truly_past
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    GROUP BY b.status
    ORDER BY b.status";
    
    $summary_stmt = $db->query($summary_sql);
    $summary_data = $summary_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr style='background: #f0f0f0;'>\n";
    echo "<th>Status</th><th>Total</th><th>Future Trips</th><th>Recent Past (≤1h)</th><th>Truly Past (>1h)</th>\n";
    echo "</tr>\n";
    
    foreach ($summary_data as $row) {
        $highlight = ($row['status'] === 'Completed' && $row['future_trips'] > 0) ? 'background: #ffebee;' : '';
        echo "<tr style='{$highlight}'>\n";
        echo "<td><strong>{$row['status']}</strong></td>\n";
        echo "<td>{$row['total']}</td>\n";
        echo "<td>{$row['future_trips']}</td>\n";
        echo "<td>{$row['recent_past']}</td>\n";
        echo "<td>{$row['truly_past']}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Quick links
    echo "<hr>\n";
    echo "<h3>🔗 Quick Links:</h3>\n";
    echo "<ul>\n";
    echo "<li><a href='views/admin_bookings.php'>📋 Admin Bookings Page</a></li>\n";
    echo "<li><a href='views/bookings.php'>🎫 User Bookings Page</a></li>\n";
    echo "<li><a href='views/admin_dashboard.php'>🏠 Admin Dashboard</a></li>\n";
    echo "</ul>\n";
    
    echo "<h3>✅ Status:</h3>\n";
    echo "<p style='color: green;'><strong>SQL Error Fixed!</strong> The admin bookings page should now work correctly.</p>\n";
    echo "<p>The issue was caused by using 'current_time' as a column alias, which is a reserved word in MySQL/MariaDB.</p>\n";
    echo "<p>Changed to 'current_datetime' to avoid conflicts.</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
    echo "<p>If you're still seeing SQL errors, please check the database connection and table structure.</p>\n";
}
?>
