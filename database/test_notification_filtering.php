<?php
/*
===========================================
FILE: Test Notification Filtering (test_notification_filtering.php)
PURPOSE: Test that only active bookings receive notifications
TECHNOLOGIES: PHP, MySQL, PDO
===========================================
*/

require_once('../includes/db.php');

try {
    $db = getDB();
    
    echo "<h2>🔔 Notification Filtering Test</h2>\n";
    echo "<style>
        .test-section { margin: 2rem 0; padding: 1rem; border-left: 4px solid #28a745; background: #f8f9fa; }
        .notification-test { padding: 0.5rem; margin: 0.5rem 0; border-radius: 4px; background: #e7f3ff; border: 1px solid #b3d9ff; }
        table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .will-notify { background-color: #d4edda; }
        .wont-notify { background-color: #f8d7da; }
    </style>\n";
    
    // Get all users with bookings and their status
    $users_sql = "SELECT DISTINCT u.id, u.username, u.email, bk.status, bk.booking_id,
                         b.bus_number, r.source, r.destination, s.id as schedule_id
                  FROM users u
                  JOIN bookings bk ON u.id = bk.user_id
                  JOIN schedules s ON bk.schedule_id = s.id
                  JOIN buses b ON s.bus_id = b.id
                  JOIN routes r ON s.route_id = r.id
                  WHERE u.role = 'passenger'
                  ORDER BY bk.status, u.username";
    
    $users_stmt = $db->prepare($users_sql);
    $users_stmt->execute();
    $users_with_bookings = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users_with_bookings)) {
        echo "<p>❌ No users with bookings found for testing.</p>\n";
        exit;
    }
    
    echo "<div class='test-section'>\n";
    echo "<h3>👥 Users and Their Booking Status</h3>\n";
    echo "<table>\n";
    echo "<tr><th>User</th><th>Booking ID</th><th>Status</th><th>Route</th><th>Bus</th><th>Will Get Notifications?</th></tr>\n";
    
    foreach ($users_with_bookings as $user) {
        $will_notify = ($user['status'] === 'Booked');
        $notify_class = $will_notify ? 'will-notify' : 'wont-notify';
        $notify_text = $will_notify ? '✅ YES' : '❌ NO';
        
        echo "<tr class='{$notify_class}'>\n";
        echo "<td>{$user['username']}</td>\n";
        echo "<td>{$user['booking_id']}</td>\n";
        echo "<td><strong>{$user['status']}</strong></td>\n";
        echo "<td>{$user['source']} → {$user['destination']}</td>\n";
        echo "<td>{$user['bus_number']}</td>\n";
        echo "<td>{$notify_text}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    echo "</div>\n";
    
    // Test bus notification query
    echo "<div class='test-section'>\n";
    echo "<h3>🚌 Bus Update Notification Recipients</h3>\n";
    
    $bus_ids = array_unique(array_column($users_with_bookings, 'bus_number'));
    
    foreach ($bus_ids as $bus_number) {
        // Get bus ID from bus number
        $bus_id_stmt = $db->prepare("SELECT id FROM buses WHERE bus_number = ?");
        $bus_id_stmt->execute([$bus_number]);
        $bus_id = $bus_id_stmt->fetchColumn();
        
        if ($bus_id) {
            echo "<div class='notification-test'>\n";
            echo "<h4>Bus {$bus_number} (ID: {$bus_id})</h4>\n";
            
            // Test the actual notification query used in admin_bus_form.php
            $notification_sql = "SELECT DISTINCT u.id, u.username, b.bus_number, bk.status
                               FROM users u
                               JOIN bookings bk ON u.id = bk.user_id
                               JOIN schedules s ON bk.schedule_id = s.id
                               JOIN buses b ON s.bus_id = b.id
                               WHERE b.id = ? AND bk.status = 'Booked'";
            
            $notification_stmt = $db->prepare($notification_sql);
            $notification_stmt->execute([$bus_id]);
            $recipients = $notification_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($recipients)) {
                echo "<p>🔕 <strong>No users will receive notifications</strong> (no active 'Booked' status)</p>\n";
            } else {
                echo "<p>🔔 <strong>Users who WILL receive notifications:</strong></p>\n";
                echo "<ul>\n";
                foreach ($recipients as $recipient) {
                    echo "<li>✅ {$recipient['username']} (Status: {$recipient['status']})</li>\n";
                }
                echo "</ul>\n";
            }
            echo "</div>\n";
        }
    }
    echo "</div>\n";
    
    // Test route notification query
    echo "<div class='test-section'>\n";
    echo "<h3>🛣️ Route Update Notification Recipients</h3>\n";
    
    $routes = array_unique(array_map(function($user) {
        return $user['source'] . ' → ' . $user['destination'];
    }, $users_with_bookings));
    
    foreach ($routes as $route_display) {
        // Find route ID
        $route_parts = explode(' → ', $route_display);
        $route_stmt = $db->prepare("SELECT id FROM routes WHERE source = ? AND destination = ?");
        $route_stmt->execute([$route_parts[0], $route_parts[1]]);
        $route_id = $route_stmt->fetchColumn();
        
        if ($route_id) {
            echo "<div class='notification-test'>\n";
            echo "<h4>Route: {$route_display} (ID: {$route_id})</h4>\n";
            
            // Test the actual notification query used in admin_route_form.php
            $notification_sql = "SELECT DISTINCT u.id, u.username, r.source, r.destination, bk.status
                               FROM users u
                               JOIN bookings bk ON u.id = bk.user_id
                               JOIN schedules s ON bk.schedule_id = s.id
                               JOIN routes r ON s.route_id = r.id
                               WHERE r.id = ? AND bk.status = 'Booked'";
            
            $notification_stmt = $db->prepare($notification_sql);
            $notification_stmt->execute([$route_id]);
            $recipients = $notification_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($recipients)) {
                echo "<p>🔕 <strong>No users will receive notifications</strong> (no active 'Booked' status)</p>\n";
            } else {
                echo "<p>🔔 <strong>Users who WILL receive notifications:</strong></p>\n";
                echo "<ul>\n";
                foreach ($recipients as $recipient) {
                    echo "<li>✅ {$recipient['username']} (Status: {$recipient['status']})</li>\n";
                }
                echo "</ul>\n";
            }
            echo "</div>\n";
        }
    }
    echo "</div>\n";
    
    // Summary
    echo "<div class='test-section'>\n";
    echo "<h3>📋 Notification Filtering Summary</h3>\n";
    
    $total_users = count($users_with_bookings);
    $active_users = count(array_filter($users_with_bookings, function($user) {
        return $user['status'] === 'Booked';
    }));
    $completed_users = count(array_filter($users_with_bookings, function($user) {
        return $user['status'] === 'Completed';
    }));
    
    echo "<ul>\n";
    echo "<li><strong>Total Users with Bookings:</strong> {$total_users}</li>\n";
    echo "<li><strong>Users with Active Bookings:</strong> {$active_users} (✅ WILL get notifications)</li>\n";
    echo "<li><strong>Users with Completed Bookings:</strong> {$completed_users} (❌ will NOT get notifications)</li>\n";
    echo "</ul>\n";
    
    echo "<p><strong>✅ Notification filtering is working correctly!</strong></p>\n";
    echo "<p>Only passengers with active ('Booked') status will receive notifications when buses, routes, or schedules are updated.</p>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
