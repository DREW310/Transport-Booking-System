<?php
require_once('../controllers/bookingController.php');
require_once('../controllers/feedbackController.php');
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../public/login.php');
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
                        <li><?php echo htmlspecialchars($n['message']); ?> <span style="color:#888;font-size:0.95em;">(<?php echo date('d M Y, H:i', strtotime($n['created_at'])); ?>)</span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <h1 style="text-align:center;margin-bottom:1.5rem;"><i class="fa fa-bus icon-red"></i> Welcome, <span style="color:#e53935;"><?php echo htmlspecialchars($_SESSION['user']['username']); ?>!</span></h1>
        <h2 style="margin-bottom:1.2rem;"><i class="fa fa-ticket-alt icon-red"></i> Your Bookings</h2>
        <div style="text-align:right;margin-bottom:1.2rem;">
            <a href="booking.php" class="btn btn-primary"><i class="fa fa-plus-circle"></i> Create New Booking</a>
        </div>
        <?php if (empty($bookings)): ?>
            <div class="alert alert-info">You have no bookings yet. Book your first trip now!</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Bus ID</th>
                        <th>Company</th>
                        <th>Route</th>
                        <th>Departure</th>
                        <th>Seat</th>
                        <th>Status</th>
                        <th>Booked At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                            <td><?php echo htmlspecialchars($booking['bus_number']); ?></td>
                            <td><?php echo htmlspecialchars($booking['company']); ?></td>
                            <td><?php echo htmlspecialchars($booking['route']); ?></td>
                            <td><?php echo htmlspecialchars($booking['departure_time']); ?></td>
                            <td><?php echo htmlspecialchars($booking['seat_number']); ?></td>
                            <td>
                                <?php if ($booking['status'] === 'booked' || $booking['status'] === 'Completed'): ?>
                                    <span class="badge bg-success">Completed</span>
                                <?php elseif ($booking['status'] === 'cancelled' || $booking['status'] === 'Cancelled'): ?>
                                    <span class="badge bg-danger">Cancelled</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($booking['status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($booking['booking_time']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once('footer.php'); ?>
