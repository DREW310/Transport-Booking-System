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
    $route_id = $_GET['id'];
    $db = getDB();

    // Check if route has any bookings through schedules
    $check_sql = "SELECT COUNT(*) as booking_count
                  FROM bookings b
                  JOIN schedules s ON b.schedule_id = s.id
                  WHERE s.route_id = ? AND b.status IN ('Booked', 'Completed')";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->execute([$route_id]);
    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['booking_count'] > 0) {
        // Route has bookings, cannot delete for data consistency
        $error_message = "Cannot delete this route because it has {$result['booking_count']} booking record(s). Routes with booking history cannot be deleted to maintain data consistency and historical records. You can edit the route details instead.";
    } else {
        // Safe to delete - no bookings exist
        try {
            // First delete related schedules (cascade will handle this, but being explicit)
            $delete_schedules = $db->prepare('DELETE FROM schedules WHERE route_id = ?');
            $delete_schedules->execute([$route_id]);

            // Then delete the route
            $delete_route = $db->prepare('DELETE FROM routes WHERE id = ?');
            $delete_route->execute([$route_id]);

            $success_message = "Route deleted successfully!";
        } catch (Exception $e) {
            $error_message = "Error deleting route: " . $e->getMessage();
        }
    }
}

// Redirect with message
if ($error_message) {
    header('Location: admin_routes.php?error=' . urlencode($error_message));
} else if ($success_message) {
    header('Location: admin_routes.php?success=' . urlencode($success_message));
} else {
    header('Location: admin_routes.php');
}
exit();