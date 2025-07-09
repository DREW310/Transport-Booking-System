<?php
/*
===========================================
FILE: Notification Debug Test
PURPOSE: Debug notification issues
===========================================
*/

require_once('../includes/db.php');

if (session_status() === PHP_SESSION_NONE) session_start();

echo "<h2>🔍 Notification System Debug</h2>\n";

// Test 1: Check session
echo "<h3>1. Session Check:</h3>\n";
if (isset($_SESSION['user'])) {
    echo "<p>✅ User session exists</p>\n";
    echo "<p>User ID: " . ($_SESSION['user']['id'] ?? 'NOT SET') . "</p>\n";
    echo "<p>Username: " . ($_SESSION['user']['username'] ?? 'NOT SET') . "</p>\n";
    echo "<p>Role: " . ($_SESSION['user']['role'] ?? 'NOT SET') . "</p>\n";
} else {
    echo "<p>❌ No user session found</p>\n";
    exit;
}

// Test 2: Check database connection
echo "<h3>2. Database Connection:</h3>\n";
try {
    $db = getDB();
    if ($db) {
        echo "<p>✅ Database connection successful</p>\n";
    } else {
        echo "<p>❌ Database connection failed</p>\n";
        exit;
    }
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>\n";
    exit;
}

$user_id = $_SESSION['user']['id'];

// Test 3: Check notifications table
echo "<h3>3. Notifications Table Check:</h3>\n";
try {
    $stmt = $db->prepare('SELECT COUNT(*) as total FROM notifications WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>✅ Total notifications for user: {$total}</p>\n";
    
    $stmt = $db->prepare('SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$user_id]);
    $unread = $stmt->fetch(PDO::FETCH_ASSOC)['unread'];
    echo "<p>✅ Unread notifications: {$unread}</p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Notifications query error: " . $e->getMessage() . "</p>\n";
}

// Test 4: Test mark all as read functionality
echo "<h3>4. Mark All as Read Test:</h3>\n";
if (isset($_GET['test_mark_all'])) {
    try {
        $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        $result = $stmt->execute([$user_id]);
        $affected = $stmt->rowCount();
        
        if ($result) {
            echo "<p>✅ Mark all as read successful</p>\n";
            echo "<p>Rows affected: {$affected}</p>\n";
        } else {
            echo "<p>❌ Mark all as read failed</p>\n";
        }
        
        // Check result
        $stmt = $db->prepare('SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$user_id]);
        $final_unread = $stmt->fetch(PDO::FETCH_ASSOC)['unread'];
        echo "<p>Final unread count: {$final_unread}</p>\n";
        
    } catch (Exception $e) {
        echo "<p>❌ Mark all as read error: " . $e->getMessage() . "</p>\n";
    }
} else {
    echo "<p><a href='?test_mark_all=1'>🧪 Click here to test mark all as read</a></p>\n";
}

// Test 5: Show recent notifications
echo "<h3>5. Recent Notifications:</h3>\n";
try {
    $stmt = $db->prepare('SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($notifications)) {
        echo "<p>No notifications found</p>\n";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>ID</th><th>Message</th><th>Read</th><th>Created</th></tr>\n";
        foreach ($notifications as $notif) {
            $read_status = $notif['is_read'] ? 'Yes' : 'No';
            echo "<tr><td>{$notif['id']}</td><td>" . substr($notif['message'], 0, 40) . "...</td><td>{$read_status}</td><td>{$notif['created_at']}</td></tr>\n";
        }
        echo "</table>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Error fetching notifications: " . $e->getMessage() . "</p>\n";
}

echo "<p><a href='notifications.php'>← Back to Notifications</a></p>\n";
?>
