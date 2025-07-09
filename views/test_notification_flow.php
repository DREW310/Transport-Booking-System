<?php
/*
===========================================
FILE: Test Notification Flow
PURPOSE: Test the complete notification workflow
===========================================
*/

require_once('../includes/db.php');

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user'])) {
    echo "<p>Please login first</p>";
    exit;
}

$db = getDB();
$user_id = $_SESSION['user']['id'];

echo "<h2>🧪 Notification Flow Test</h2>\n";

// Step 1: Create test notifications if none exist
if (isset($_GET['create_test'])) {
    echo "<h3>Creating test notifications...</h3>\n";
    
    $test_messages = [
        'Test notification 1 - Bus update',
        'Test notification 2 - Route change', 
        'Test notification 3 - Schedule modification'
    ];
    
    $stmt = $db->prepare('INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)');
    
    foreach ($test_messages as $message) {
        $stmt->execute([$user_id, $message]);
        echo "<p>✅ Created: {$message}</p>\n";
    }
    
    echo "<p><a href='test_notification_flow.php'>🔄 Refresh to see results</a></p>\n";
    exit;
}

// Step 2: Show current status
$stmt = $db->prepare('SELECT COUNT(*) as total FROM notifications WHERE user_id = ?');
$stmt->execute([$user_id]);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->prepare('SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0');
$stmt->execute([$user_id]);
$unread = $stmt->fetch(PDO::FETCH_ASSOC)['unread'];

echo "<div style='background: #f0f0f0; padding: 1rem; margin: 1rem 0; border-radius: 8px;'>\n";
echo "<h3>📊 Current Status:</h3>\n";
echo "<p><strong>Total notifications:</strong> {$total}</p>\n";
echo "<p><strong>Unread notifications:</strong> {$unread}</p>\n";
echo "</div>\n";

// Step 3: Show action buttons
echo "<div style='margin: 1rem 0;'>\n";

if ($total == 0) {
    echo "<p><a href='?create_test=1' style='background: #2196f3; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>🧪 Create Test Notifications</a></p>\n";
} else {
    echo "<p><a href='notifications.php' style='background: #4caf50; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>📋 View Notifications Page</a></p>\n";
    
    if ($unread > 0) {
        echo "<p><a href='notifications.php?mark_all_read=1' style='background: #ff9800; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>✅ Mark All as Read</a></p>\n";
    }
    
    echo "<p><a href='?reset_test=1' style='background: #f44336; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>🗑️ Clear All Test Notifications</a></p>\n";
}

echo "</div>\n";

// Step 4: Reset test data
if (isset($_GET['reset_test'])) {
    echo "<h3>Clearing test notifications...</h3>\n";
    $stmt = $db->prepare('DELETE FROM notifications WHERE user_id = ? AND message LIKE "Test notification%"');
    $result = $stmt->execute([$user_id]);
    $deleted = $stmt->rowCount();
    
    if ($result) {
        echo "<p>✅ Deleted {$deleted} test notifications</p>\n";
    } else {
        echo "<p>❌ Failed to delete test notifications</p>\n";
    }
    
    echo "<p><a href='test_notification_flow.php'>🔄 Refresh to see results</a></p>\n";
    exit;
}

// Step 5: Show recent notifications
if ($total > 0) {
    echo "<h3>📋 Recent Notifications:</h3>\n";
    $stmt = $db->prepare('SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Message</th><th>Status</th><th>Created</th></tr>\n";
    
    foreach ($notifications as $notif) {
        $status = $notif['is_read'] ? '✅ Read' : '❌ Unread';
        $row_style = $notif['is_read'] ? '' : 'background: #fff3cd;';
        echo "<tr style='{$row_style}'><td>{$notif['id']}</td><td>" . htmlspecialchars($notif['message']) . "</td><td>{$status}</td><td>{$notif['created_at']}</td></tr>\n";
    }
    
    echo "</table>\n";
}

echo "<hr>\n";
echo "<p><strong>🔗 Quick Links:</strong></p>\n";
echo "<ul>\n";
echo "<li><a href='dashboard.php'>🏠 Dashboard</a></li>\n";
echo "<li><a href='notifications.php'>🔔 Notifications Page</a></li>\n";
echo "<li><a href='test_notification_debug.php'>🔍 Debug Page</a></li>\n";
echo "</ul>\n";
?>
