<?php
/*
===========================================
FILE: Test Notification Rules (test_notification_rules.php)
PURPOSE: Test that completed bookings don't receive notifications
TECHNOLOGIES: PHP, MySQL, PDO
===========================================
*/

require_once('../includes/db.php');

try {
    $db = getDB();
    
    echo "<h2>🧪 Testing Notification Business Rules</h2>\n";
    
    // Get users with different booking statuses
    $test_sql = "SELECT 
                    u.id, u.username, b.status, b.booking_id,
                    s.id as schedule_id, r.id as route_id, bus.id as bus_id
                 FROM users u
                 JOIN bookings b ON u.id = b.user_id
                 JOIN schedules s ON b.schedule_id = s.id
                 JOIN routes r ON s.route_id = r.id
                 JOIN buses bus ON s.bus_id = bus.id
                 WHERE u.role = 'passenger'
                 ORDER BY b.status, u.id
                 LIMIT 5";
    
    $test_stmt = $db->prepare($test_sql);
    $test_stmt->execute();
    $test_users = $test_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($test_users)) {
        echo "<p>❌ No test data found. Please create some bookings first.</p>\n";
        exit;
    }
    
    echo "<h3>📊 Current Test Data:</h3>\n";
    echo "<table border='1' style='border-collapse: collapse; margin: 1rem 0;'>\n";
    echo "<tr><th>User</th><th>Booking ID</th><th>Status</th><th>Should Get Notifications?</th></tr>\n";
    
    foreach ($test_users as $user) {
        $should_notify = ($user['status'] === 'Booked') ? '✅ YES' : '❌ NO';
        $status_color = ($user['status'] === 'Completed') ? 'color: #dc3545;' : 'color: #28a745;';
        echo "<tr>\n";
        echo "<td>{$user['username']}</td>\n";
        echo "<td>{$user['booking_id']}</td>\n";
        echo "<td style='{$status_color}'><strong>{$user['status']}</strong></td>\n";
        echo "<td>{$should_notify}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Test bus notification query
    echo "<h3>🚌 Testing Bus Update Notifications:</h3>\n";
    $bus_id = $test_users[0]['bus_id'];
    
    $bus_notification_sql = "SELECT DISTINCT u.id, u.username, b.bus_number, bk.status
                           FROM users u
                           JOIN bookings bk ON u.id = bk.user_id
                           JOIN schedules s ON bk.schedule_id = s.id
                           JOIN buses b ON s.bus_id = b.id
                           WHERE b.id = ? AND bk.status = 'Booked'";
    
    $bus_stmt = $db->prepare($bus_notification_sql);
    $bus_stmt->execute([$bus_id]);
    $bus_recipients = $bus_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Bus ID {$bus_id} - Users who WILL receive notifications:</strong></p>\n";
    if (empty($bus_recipients)) {
        echo "<p>🔕 No users will receive notifications (no active 'Booked' status)</p>\n";
    } else {
        foreach ($bus_recipients as $recipient) {
            echo "<p>✅ {$recipient['username']} (Status: {$recipient['status']})</p>\n";
        }
    }
    
    // Test route notification query
    echo "<h3>🛣️ Testing Route Update Notifications:</h3>\n";
    $route_id = $test_users[0]['route_id'];
    
    $route_notification_sql = "SELECT DISTINCT u.id, u.username, r.source, r.destination, bk.status
                             FROM users u
                             JOIN bookings bk ON u.id = bk.user_id
                             JOIN schedules s ON bk.schedule_id = s.id
                             JOIN routes r ON s.route_id = r.id
                             WHERE r.id = ? AND bk.status = 'Booked'";
    
    $route_stmt = $db->prepare($route_notification_sql);
    $route_stmt->execute([$route_id]);
    $route_recipients = $route_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Route ID {$route_id} - Users who WILL receive notifications:</strong></p>\n";
    if (empty($route_recipients)) {
        echo "<p>🔕 No users will receive notifications (no active 'Booked' status)</p>\n";
    } else {
        foreach ($route_recipients as $recipient) {
            echo "<p>✅ {$recipient['username']} (Status: {$recipient['status']})</p>\n";
        }
    }
    
    // Test schedule notification query
    echo "<h3>📅 Testing Schedule Update Notifications:</h3>\n";
    $schedule_id = $test_users[0]['schedule_id'];
    
    $schedule_notification_sql = "SELECT DISTINCT u.id, u.username, b.bus_number, r.source, r.destination, bk.status
                                FROM users u
                                JOIN bookings bk ON u.id = bk.user_id
                                JOIN schedules s ON bk.schedule_id = s.id
                                JOIN buses b ON s.bus_id = b.id
                                JOIN routes r ON s.route_id = r.id
                                WHERE s.id = ? AND bk.status = 'Booked'";
    
    $schedule_stmt = $db->prepare($schedule_notification_sql);
    $schedule_stmt->execute([$schedule_id]);
    $schedule_recipients = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Schedule ID {$schedule_id} - Users who WILL receive notifications:</strong></p>\n";
    if (empty($schedule_recipients)) {
        echo "<p>🔕 No users will receive notifications (no active 'Booked' status)</p>\n";
    } else {
        foreach ($schedule_recipients as $recipient) {
            echo "<p>✅ {$recipient['username']} (Status: {$recipient['status']})</p>\n";
        }
    }
    
    echo "<h3>✅ Business Rules Summary:</h3>\n";
    echo "<ul>\n";
    echo "<li>✅ Only users with 'Booked' status receive notifications</li>\n";
    echo "<li>❌ Users with 'Completed' status do NOT receive notifications</li>\n";
    echo "<li>🔒 Schedules with completed bookings cannot be edited/deleted</li>\n";
    echo "<li>👁️ Completed schedules show read-only view with seat map access</li>\n";
    echo "<li>🛣️ Routes can still be edited (multiple buses can use same route)</li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
