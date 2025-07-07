<?php
session_start();
require_once 'config/database.php';
require_once 'includes/booking_cancellation_handler.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: admin_bookings.php?error=Invalid booking ID');
    exit();
}

$booking_id = $_GET['id'];

// Use the comprehensive cancellation handler
$cancellation_reason = $_GET['reason'] ?? 'Cancelled by admin';
$result = cancelBookingComprehensive($booking_id, $_SESSION['user_id'], $cancellation_reason, true);

if ($result['success']) {
    header('Location: admin_bookings.php?success=' . urlencode($result['message']));
} else {
    header('Location: admin_bookings.php?error=' . urlencode($result['message']));
}
exit();
?>
