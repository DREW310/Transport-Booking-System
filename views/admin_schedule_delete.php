<?php
require_once('../includes/db.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
if (isset($_GET['id'])) {
    $db = getDB();
    $schedule_id = $_GET['id'];
    // Get all user_ids with bookings for this schedule
    $stmt = $db->prepare('SELECT DISTINCT user_id FROM bookings WHERE schedule_id = ?');
    $stmt->execute([$schedule_id]);
    $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    // Insert a notification for each user
    foreach ($user_ids as $uid) {
        $msg = 'We sincerely apologize, but your booking(s) for a deleted schedule have been cancelled by admin. Please book another trip at your convenience.';
        $stmt2 = $db->prepare('INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)');
        $stmt2->execute([$uid, $msg]);
    }
    // Now delete the schedule (bookings will be deleted by cascade)
    $stmt = $db->prepare('DELETE FROM schedules WHERE id = ?');
    $stmt->execute([$schedule_id]);
}
header('Location: admin_schedules.php');
exit(); 