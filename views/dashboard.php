<?php
require_once('../controllers/bookingController.php');
require_once('../controllers/feedbackController.php');
require_once('../includes/status_helpers.php');
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

// Add the same booking status fix logic as in bookings.php
require_once('../includes/db.php');
$db = getDB();

// STEP 1: Fix any incorrectly completed future bookings first
$fix_stmt = $db->prepare('UPDATE bookings b
                         JOIN schedules s ON b.schedule_id = s.id
                         SET b.status = ?
                         WHERE b.status = ?
                         AND s.departure_time > NOW()');
$fix_stmt->execute(['Booked', 'Completed']);

// STEP 2: System-wide auto-complete past bookings - SAFE VERSION
// Use MySQL NOW() and add 1 hour safety margin to ensure trip has truly completed
// Only mark as completed if departure was more than 1 hour ago
$stmt = $db->prepare('UPDATE bookings b
                     JOIN schedules s ON b.schedule_id = s.id
                     SET b.status = ?
                     WHERE b.status = ?
                     AND s.departure_time <= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                     AND b.status NOT IN (?, ?)');
$stmt->execute(['Completed', 'Booked', 'Cancelled', 'Completed']);

$bookingController = new BookingController();
$bookings = $bookingController->getBookingsByUser($userId);
$feedbackController = new FeedbackController();

// Note: Notifications are now only displayed in the notifications tab (bell icon)
// This keeps the dashboard clean and focused on booking information
?>

<?php require_once('header.php'); ?>

<main style="display:flex;justify-content:center;align-items:flex-start;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:600px;max-width:900px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">

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
            <a href="schedule.php" class="btn btn-primary"><i class="fa fa-plus-circle"></i> Create New Booking</a>
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
                                <?php
                                // Display status correctly - no incorrect conversion
                                $displayStatus = $booking['status'] ?? '';
                                echo getStatusBadge($displayStatus, 'small');
                                ?>
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
