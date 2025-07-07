<?php
require_once('../controllers/bookingController.php');
require_once('../controllers/feedbackController.php');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../public/login.php');
    exit();
}

// Redirect admin/staff to admin dashboard
if ((isset($_SESSION['user']['is_staff']) && $_SESSION['user']['is_staff']) ||
    (isset($_SESSION['user']['is_superuser']) && $_SESSION['user']['is_superuser'])) {
    header('Location: admin_dashboard.php');
    exit();
}

$userId = $_SESSION['user']['id'];
$bookingController = new BookingController();
$bookings = $bookingController->getBookingsByUser($userId);
$feedbackController = new FeedbackController();

// Fetch unread notifications
$db = getDB();
$stmt = $db->prepare('SELECT id, message, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC');
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!empty($notifications)) {
    // Mark as read
    $ids = array_column($notifications, 'id');
    $in = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE id IN (' . $in . ')');
    $stmt->execute($ids);
}
?>

<?php require_once('header.php'); ?>

<main style="display:flex;justify-content:center;align-items:flex-start;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:600px;max-width:900px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <?php if (!empty($notifications)): ?>
            <div class="alert alert-warning" style="margin-bottom:1.5rem;">
                <b>Important Notification:</b><br>
                <ul style="margin:0 0 0 1.2rem;">
                    <?php foreach ($notifications as $n): ?>
                        <li><?php echo htmlspecialchars($n['message'] ?? ''); ?> <span style="color:#888;font-size:0.95em;">(<?php echo date('d M Y, H:i', strtotime($n['created_at'] ?? '')); ?>)</span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <h1 style="text-align:center;margin-bottom:1.5rem;"><i class="fa fa-bus icon-red"></i> Welcome, <span style="color:#5A9FD4;"><?php echo htmlspecialchars($_SESSION['user']['username'] ?? ''); ?>!</span></h1>
        <h2 style="margin-bottom:1.2rem;"><i class="fa fa-ticket-alt icon-red"></i> Your Bookings</h2>

        <!-- Important Trip Information -->
        <div class="alert alert-info" style="margin-bottom:1.5rem;">
            <h5><i class="fa fa-info-circle"></i> Important Trip Information:</h5>
            <ul style="margin:0.5rem 0 0 1.2rem;">
                <li><strong>License Plate:</strong> Look for this number on the bus at the station</li>
                <li><strong>Bus Type:</strong> Indicates comfort level and amenities available</li>
                <li><strong>Departure Time:</strong> Arrive at least 15 minutes before departure</li>
                <li><strong>Seat Number:</strong> Your assigned seat - boarding is usually by seat number</li>
                <li><strong>Payment Method:</strong> Keep your payment receipt for reference</li>
            </ul>
            <small style="color:#666;"><i class="fa fa-exclamation-triangle"></i> <strong>Note:</strong> Bookings cannot be cancelled within 24 hours of departure time.</small><br>
            <small style="color:#666;"><i class="fa fa-phone"></i> <strong>Need Help?</strong> Contact customer support at <strong>+60 3-1234 5678</strong> or email <strong>support@twt-transport.com</strong></small>
        </div>

        <div style="text-align:right;margin-bottom:1.2rem;">
            <a href="booking.php" class="btn btn-primary"><i class="fa fa-plus-circle"></i> Create New Booking</a>
        </div>
        <?php if (empty($bookings)): ?>
            <div class="alert alert-info">You have no bookings yet. Book your first trip now!</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered" style="font-size: 0.85rem;">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th><i class="fa fa-ticket-alt"></i> Booking ID</th>
                        <th><i class="fa fa-bus"></i> Bus Details</th>
                        <th><i class="fa fa-route"></i> Route</th>
                        <th><i class="fa fa-clock"></i> Departure</th>
                        <th><i class="fa fa-chair"></i> Seat</th>
                        <th><i class="fa fa-money-bill"></i> Fare</th>
                        <th><i class="fa fa-credit-card"></i> Payment</th>
                        <th><i class="fa fa-info-circle"></i> Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($booking['booking_id'] ?? ''); ?></strong></td>
                            <td>
                                <div style="font-size: 0.9em;">
                                    <strong><?php echo htmlspecialchars($booking['bus_number'] ?? ''); ?></strong><br>
                                    <span style="color: #666;">License: <?php echo htmlspecialchars($booking['license_plate'] ?? 'N/A'); ?></span><br>
                                    <span style="color: #666;"><?php echo htmlspecialchars($booking['bus_type'] ?? 'N/A'); ?></span><br>
                                    <span style="color: #666;"><?php echo htmlspecialchars($booking['company'] ?? ''); ?></span>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($booking['source'] ?? ''); ?></strong><br>
                                <i class="fa fa-arrow-down" style="color: #5A9FD4;"></i><br>
                                <strong><?php echo htmlspecialchars($booking['destination'] ?? ''); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo date('M j, Y', strtotime($booking['departure_time'] ?? '')); ?></strong><br>
                                <span style="color: #666;"><?php echo date('g:i A', strtotime($booking['departure_time'] ?? '')); ?></span>
                            </td>
                            <td><strong>Seat <?php echo htmlspecialchars($booking['seat_number'] ?? ''); ?></strong></td>
                            <td><strong style="color: #28a745;">RM <?php echo number_format($booking['fare'] ?? 0, 2); ?></strong></td>
                            <td>
                                <span style="color: #666; font-size: 0.85em;">
                                    <?php echo htmlspecialchars($booking['payment_method'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($booking['status'] === 'booked' || $booking['status'] === 'Completed'): ?>
                                    <span class="badge bg-success">Completed</span>
                                <?php elseif ($booking['status'] === 'cancelled' || $booking['status'] === 'Cancelled'): ?>
                                    <span class="badge bg-danger">Cancelled</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($booking['status'] ?? ''); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once('footer.php'); ?>
