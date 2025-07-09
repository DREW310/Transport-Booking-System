<?php
require_once('../includes/db.php');
require_once('../includes/booking_cancellation_handler.php');

if (session_status() === PHP_SESSION_NONE) session_start();

// Check if user is admin
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}

// Check if this is a POST request with booking_id
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['booking_id'])) {
    header('Location: admin_bookings.php?error=Invalid request');
    exit();
}

$booking_id = $_POST['booking_id'];
$cancellation_reason = $_POST['reason'] ?? 'Cancelled by admin';

// Use the comprehensive cancellation handler
$result = cancelBookingComprehensive($booking_id, $_SESSION['user']['id'], $cancellation_reason, true);

if ($result['success']) {
    header('Location: admin_bookings.php?success=' . urlencode($result['message']));
} else {
    header('Location: admin_bookings.php?error=' . urlencode($result['message']));
}
exit();
?>
