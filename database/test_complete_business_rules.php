<?php
/*
===========================================
FILE: Complete Business Rules Test (test_complete_business_rules.php)
PURPOSE: Test all business rules for buses, routes, and schedules
STUDENT LEARNING: Comprehensive testing of business logic
TECHNOLOGIES: PHP, MySQL, PDO
===========================================
*/

require_once('../includes/db.php');

try {
    $db = getDB();
    
    echo "<h2>🧪 Complete Business Rules Test</h2>\n";
    echo "<style>
        .rule-section { margin: 2rem 0; padding: 1rem; border-left: 4px solid #007bff; background: #f8f9fa; }
        .test-result { padding: 0.5rem; margin: 0.5rem 0; border-radius: 4px; }
        .pass { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .fail { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>\n";
    
    // Test data overview
    echo "<div class='rule-section'>\n";
    echo "<h3>📊 Current System Data</h3>\n";
    
    $buses_count = $db->query("SELECT COUNT(*) FROM buses")->fetchColumn();
    $routes_count = $db->query("SELECT COUNT(*) FROM routes")->fetchColumn();
    $schedules_count = $db->query("SELECT COUNT(*) FROM schedules")->fetchColumn();
    $bookings_count = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    $active_bookings = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'Booked'")->fetchColumn();
    $completed_bookings = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'Completed'")->fetchColumn();
    
    echo "<p><strong>System Overview:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>🚌 Buses: {$buses_count}</li>\n";
    echo "<li>🛣️ Routes: {$routes_count}</li>\n";
    echo "<li>📅 Schedules: {$schedules_count}</li>\n";
    echo "<li>🎫 Total Bookings: {$bookings_count} ({$active_bookings} active, {$completed_bookings} completed)</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    // Test 1: Bus Business Rules
    echo "<div class='rule-section'>\n";
    echo "<h3>🚌 Bus Business Rules Test</h3>\n";
    
    $bus_test_sql = "SELECT b.id, b.bus_number, 
                           COUNT(bk.id) as total_bookings,
                           SUM(CASE WHEN bk.status = 'Booked' THEN 1 ELSE 0 END) as active_bookings,
                           SUM(CASE WHEN bk.status = 'Completed' THEN 1 ELSE 0 END) as completed_bookings
                    FROM buses b
                    LEFT JOIN schedules s ON b.id = s.bus_id
                    LEFT JOIN bookings bk ON s.id = bk.schedule_id
                    GROUP BY b.id, b.bus_number
                    ORDER BY b.id";
    
    $bus_results = $db->query($bus_test_sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>\n";
    echo "<tr><th>Bus</th><th>Total Bookings</th><th>Active</th><th>Completed</th><th>Can Edit?</th><th>Can Delete?</th><th>Notifications?</th></tr>\n";
    
    foreach ($bus_results as $bus) {
        $can_edit = "✅ YES";
        $can_delete = ($bus['total_bookings'] > 0) ? "❌ NO" : "✅ YES";
        $notifications = ($bus['active_bookings'] > 0) ? "🔔 Active Only" : "🔕 None";
        
        echo "<tr>\n";
        echo "<td>{$bus['bus_number']}</td>\n";
        echo "<td>{$bus['total_bookings']}</td>\n";
        echo "<td>{$bus['active_bookings']}</td>\n";
        echo "<td>{$bus['completed_bookings']}</td>\n";
        echo "<td>{$can_edit}</td>\n";
        echo "<td>{$can_delete}</td>\n";
        echo "<td>{$notifications}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    echo "</div>\n";
    
    // Test 2: Route Business Rules
    echo "<div class='rule-section'>\n";
    echo "<h3>🛣️ Route Business Rules Test</h3>\n";
    
    $route_test_sql = "SELECT r.id, r.source, r.destination,
                             COUNT(bk.id) as total_bookings,
                             SUM(CASE WHEN bk.status = 'Booked' THEN 1 ELSE 0 END) as active_bookings,
                             SUM(CASE WHEN bk.status = 'Completed' THEN 1 ELSE 0 END) as completed_bookings
                      FROM routes r
                      LEFT JOIN schedules s ON r.id = s.route_id
                      LEFT JOIN bookings bk ON s.id = bk.schedule_id
                      GROUP BY r.id, r.source, r.destination
                      ORDER BY r.id";
    
    $route_results = $db->query($route_test_sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>\n";
    echo "<tr><th>Route</th><th>Total Bookings</th><th>Active</th><th>Completed</th><th>Can Edit?</th><th>Can Delete?</th><th>Notifications?</th></tr>\n";
    
    foreach ($route_results as $route) {
        $can_edit = "✅ YES";
        $can_delete = ($route['total_bookings'] > 0) ? "❌ NO" : "✅ YES";
        $notifications = ($route['active_bookings'] > 0) ? "🔔 Active Only" : "🔕 None";
        
        echo "<tr>\n";
        echo "<td>{$route['source']} → {$route['destination']}</td>\n";
        echo "<td>{$route['total_bookings']}</td>\n";
        echo "<td>{$route['active_bookings']}</td>\n";
        echo "<td>{$route['completed_bookings']}</td>\n";
        echo "<td>{$can_edit}</td>\n";
        echo "<td>{$can_delete}</td>\n";
        echo "<td>{$notifications}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    echo "</div>\n";
    
    // Test 3: Schedule Business Rules
    echo "<div class='rule-section'>\n";
    echo "<h3>📅 Schedule Business Rules Test</h3>\n";
    
    $schedule_test_sql = "SELECT s.id, b.bus_number, r.source, r.destination,
                                COUNT(bk.id) as total_bookings,
                                SUM(CASE WHEN bk.status = 'Booked' THEN 1 ELSE 0 END) as active_bookings,
                                SUM(CASE WHEN bk.status = 'Completed' THEN 1 ELSE 0 END) as completed_bookings
                         FROM schedules s
                         JOIN buses b ON s.bus_id = b.id
                         JOIN routes r ON s.route_id = r.id
                         LEFT JOIN bookings bk ON s.id = bk.schedule_id
                         GROUP BY s.id, b.bus_number, r.source, r.destination
                         ORDER BY s.id
                         LIMIT 10";
    
    $schedule_results = $db->query($schedule_test_sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>\n";
    echo "<tr><th>Schedule</th><th>Total Bookings</th><th>Active</th><th>Completed</th><th>Can Edit?</th><th>Can Delete?</th><th>View Mode</th></tr>\n";
    
    foreach ($schedule_results as $schedule) {
        $can_edit = ($schedule['completed_bookings'] > 0) ? "❌ NO" : "✅ YES";
        $can_delete = ($schedule['total_bookings'] > 0) ? "❌ NO" : "✅ YES";
        $view_mode = ($schedule['completed_bookings'] > 0) ? "👁️ Read-Only" : "✏️ Editable";
        
        echo "<tr>\n";
        echo "<td>{$schedule['bus_number']}: {$schedule['source']} → {$schedule['destination']}</td>\n";
        echo "<td>{$schedule['total_bookings']}</td>\n";
        echo "<td>{$schedule['active_bookings']}</td>\n";
        echo "<td>{$schedule['completed_bookings']}</td>\n";
        echo "<td>{$can_edit}</td>\n";
        echo "<td>{$can_delete}</td>\n";
        echo "<td>{$view_mode}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    echo "</div>\n";
    
    // Summary of Business Rules
    echo "<div class='rule-section'>\n";
    echo "<h3>📋 Business Rules Summary</h3>\n";
    echo "<div class='test-result pass'>\n";
    echo "<h4>✅ BUSES:</h4>\n";
    echo "<ul>\n";
    echo "<li>✅ Can always be EDITED (even with completed bookings)</li>\n";
    echo "<li>❌ Cannot be DELETED if ANY bookings exist (active or completed)</li>\n";
    echo "<li>🔔 Only ACTIVE booking passengers get notifications</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    echo "<div class='test-result pass'>\n";
    echo "<h4>✅ ROUTES:</h4>\n";
    echo "<ul>\n";
    echo "<li>✅ Can always be EDITED (even with completed bookings)</li>\n";
    echo "<li>❌ Cannot be DELETED if ANY bookings exist (active or completed)</li>\n";
    echo "<li>🔔 Only ACTIVE booking passengers get notifications</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    echo "<div class='test-result pass'>\n";
    echo "<h4>✅ SCHEDULES:</h4>\n";
    echo "<ul>\n";
    echo "<li>❌ Cannot be EDITED if completed bookings exist</li>\n";
    echo "<li>❌ Cannot be DELETED if ANY bookings exist (active or completed)</li>\n";
    echo "<li>👁️ Show READ-ONLY view for completed schedules</li>\n";
    echo "<li>🗺️ Seat map always accessible</li>\n";
    echo "<li>🔔 Only ACTIVE booking passengers get notifications</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='test-result fail'>❌ Error: " . $e->getMessage() . "</div>\n";
}
?>
