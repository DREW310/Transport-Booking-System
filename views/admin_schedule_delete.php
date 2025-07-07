<?php
require_once('../includes/db.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
$error_message = '';
$success_message = '';

if (isset($_GET['id'])) {
    $schedule_id = $_GET['id'];
    $db = getDB();

    // Check if schedule has any bookings (including completed ones)
    $check_sql = "SELECT
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_bookings,
                    SUM(CASE WHEN status = 'Booked' THEN 1 ELSE 0 END) as active_bookings
                  FROM bookings
                  WHERE schedule_id = ?";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->execute([$schedule_id]);
    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['completed_bookings'] > 0) {
        // Schedule has completed bookings, cannot delete
        $error_message = "Cannot delete this schedule because it has {$result['completed_bookings']} completed booking(s). Completed schedules cannot be deleted to maintain booking history integrity.";
    } elseif ($result['active_bookings'] > 0) {
        // Schedule has active bookings, cannot delete
        $error_message = "Cannot delete this schedule because it has {$result['active_bookings']} active booking(s). Please cancel all bookings first.";
    } else {
        // Safe to delete - no active bookings exist
        try {
            // Get all user_ids with any bookings for this schedule (including cancelled ones for notification)
            $stmt = $db->prepare('SELECT DISTINCT user_id FROM bookings WHERE schedule_id = ?');
            $stmt->execute([$schedule_id]);
            $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Insert a notification for each user (if any)
            foreach ($user_ids as $uid) {
                $msg = 'A schedule you had bookings for has been removed by admin. If you had active bookings, please contact support.';
                $stmt2 = $db->prepare('INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)');
                $stmt2->execute([$uid, $msg]);
            }

            // Now delete the schedule (bookings will be deleted by cascade)
            $delete_schedule = $db->prepare('DELETE FROM schedules WHERE id = ?');
            $delete_schedule->execute([$schedule_id]);

            $success_message = "Schedule deleted successfully!";
        } catch (Exception $e) {
            $error_message = "Error deleting schedule: " . $e->getMessage();
        }
    }
}
// Redirect with message
if ($error_message) {
    header('Location: admin_schedules.php?error=' . urlencode($error_message));
} else if ($success_message) {
    header('Location: admin_schedules.php?success=' . urlencode($success_message));
} else {
    header('Location: admin_schedules.php');
}
exit();