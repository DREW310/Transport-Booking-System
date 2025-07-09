<?php
require_once('../includes/db.php');
require_once('../includes/booking_cancellation_handler.php');
require_once('../includes/status_helpers.php');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../public/login.php');
    exit();
}

// Prevent admin/staff from accessing booking features
if ((isset($_SESSION['user']['is_staff']) && $_SESSION['user']['is_staff']) ||
    (isset($_SESSION['user']['is_superuser']) && $_SESSION['user']['is_superuser'])) {
    header('Location: admin_dashboard.php');
    exit();
}
$db = getDB();
$user_id = $_SESSION['user']['id'];
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
$bookings = [];
$sql = 'SELECT b.booking_id, b.status, b.seat_number, b.booking_time, b.payment_method,
               s.departure_time, s.route_id, s.bus_id, bu.bus_number, bu.license_plate, bu.bus_type, bu.company,
               r.source, r.destination, r.fare
        FROM bookings b
        JOIN schedules s ON b.schedule_id = s.id
        JOIN buses bu ON s.bus_id = bu.id
        JOIN routes r ON s.route_id = r.id
        WHERE b.user_id = ?
        ORDER BY s.departure_time DESC';
$stmt = $db->prepare($sql);
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cancellation_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    $cancel_id = $_POST['cancel_booking_id'];

    // Find the booking ID by booking_id and user_id
    $stmt = $db->prepare('SELECT id FROM bookings WHERE booking_id = ? AND user_id = ?');
    $stmt->execute([$cancel_id, $user_id]);
    $booking_row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($booking_row) {
        // Use the comprehensive cancellation handler
        $result = cancelBookingComprehensive($booking_row['id'], $user_id, 'Cancelled by user', false);

        if ($result['success']) {
            header('Location: bookings.php?success=' . urlencode($result['message']));
            exit();
        } else {
            $cancellation_error = $result['message'];
        }
    } else {
        $cancellation_error = "Booking not found or you don't have permission to cancel it.";
    }
}
// Use consistent time comparison - get current time from database
$current_time_result = $db->query("SELECT NOW() as current_datetime")->fetch(PDO::FETCH_ASSOC);
$now = $current_time_result['current_datetime'];

$upcoming = array_filter($bookings, function($b) use ($now) {
    return $b['departure_time'] > $now && $b['status'] !== 'Cancelled';
});
$past = array_filter($bookings, function($b) use ($now) {
    return $b['departure_time'] <= $now || $b['status'] === 'Cancelled';
});

// Include header after POST processing to avoid "headers already sent" error
require_once('header.php');
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="bookings-outer-card">
        <h1 style="margin-bottom:2.5rem;"><i class="fa fa-ticket-alt icon-red"></i> My Bookings</h1>

        <?php if ($cancellation_error): ?>
            <div class="alert alert-danger" style="margin-bottom:1.5rem;">
                <i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($cancellation_error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" style="margin-bottom:1.5rem;">
                <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Important Trip Information -->
        <div class="alert alert-info" style="margin-bottom:2rem;">
            <h5><i class="fa fa-info-circle"></i> Important Trip Information:</h5>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem;">
                <div>
                    <ul style="margin:0 0 0 1.2rem;">
                        <li><strong>License Plate:</strong> Look for this number on the bus</li>
                        <li><strong>Bus Type:</strong> Comfort level and amenities</li>
                        <li><strong>Departure Time:</strong> Arrive 15 minutes early</li>
                    </ul>
                </div>
                <div>
                    <ul style="margin:0 0 0 1.2rem;">
                        <li><strong>Seat Number:</strong> Your assigned seat</li>
                        <li><strong>Fare:</strong> Total amount paid</li>
                        <li><strong>Payment Method:</strong> Keep receipt for reference</li>
                    </ul>
                </div>
            </div>
            <div style="margin-top:1rem;padding:0.75rem;background:#fff3cd;border-radius:4px;">
                <small style="color:#856404;"><i class="fa fa-exclamation-triangle"></i> <strong>Cancellation Policy:</strong> Bookings cannot be cancelled within 24 hours of departure time.</small><br>
                <small style="color:#856404;"><i class="fa fa-phone"></i> <strong>Need Help?</strong> Contact customer support at <strong>+60 3-1234 5678</strong> or email <strong>support@twt-transport.com</strong></small>
            </div>
        </div>

        <!-- Upcoming Trips Section -->
        <section style="margin-bottom:2.5rem;">
            <div class="booking-section-card">
                <div class="booking-section-header"><i class="fa fa-calendar-check-o"></i> Upcoming Trips</div>
                <div class="booking-section-body">
                    <?php if (empty($upcoming)): ?>
                        <div class="alert alert-warning" style="margin-bottom:1.2rem;">No upcoming trips. Plan your next trip today.</div>
                        <a href="schedule.php" class="btn-book-ticket"><i class="fa fa-plus-circle"></i> Book Ticket Now</a>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered upcoming-table" style="font-size: 0.85rem;">
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
                                        <th><i class="fa fa-cogs"></i> Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcoming as $booking): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($booking['booking_id']); ?></strong></td>
                                        <td>
                                            <div style="font-size: 0.9em;">
                                                <strong><?php echo htmlspecialchars($booking['bus_number']); ?></strong><br>
                                                <span style="color: #666;">License: <?php echo htmlspecialchars($booking['license_plate'] ?? 'N/A'); ?></span><br>
                                                <span style="color: #666;"><?php echo htmlspecialchars($booking['bus_type'] ?? 'N/A'); ?></span><br>
                                                <span style="color: #666;"><?php echo htmlspecialchars($booking['company']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($booking['source']); ?></strong><br>
                                            <i class="fa fa-arrow-down" style="color: #5A9FD4;"></i><br>
                                            <strong><?php echo htmlspecialchars($booking['destination']); ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo date('M j, Y', strtotime($booking['departure_time'])); ?></strong><br>
                                            <span style="color: #666;"><?php echo date('g:i A', strtotime($booking['departure_time'])); ?></span>
                                        </td>
                                        <td><strong>Seat <?php echo htmlspecialchars($booking['seat_number']); ?></strong></td>
                                        <td><strong style="color: #28a745;">RM <?php echo number_format($booking['fare'] ?? 0, 2); ?></strong></td>
                                        <td>
                                            <span style="color: #666; font-size: 0.85em;">
                                                <?php echo htmlspecialchars($booking['payment_method'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo getStatusBadge($booking['status'], 'small'); ?>
                                        </td>
                                        <td>
                                            <?php
                                            // Check if booking is completed first
                                            if ($booking['status'] === 'Completed'):
                                            ?>
                                                <button class="btn btn-success" disabled title="Booking is completed">
                                                    <i class="fa fa-check-circle"></i> Completed
                                                </button>
                                            <?php else:
                                                // Check if cancellation is allowed (more than 24 hours before departure)
                                                $departure_time = new DateTime($booking['departure_time']);
                                                $current_time = new DateTime();
                                                $time_difference = $departure_time->getTimestamp() - $current_time->getTimestamp();
                                                $hours_until_departure = $time_difference / 3600;

                                                if ($hours_until_departure > 24):
                                                ?>
                                                    <form method="post" style="display:inline;">
                                                        <input type="hidden" name="cancel_booking_id" value="<?php echo htmlspecialchars($booking['booking_id']); ?>">
                                                        <button type="submit" class="btn-cancel btn btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?');"><i class="fa fa-times"></i> Cancel</button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary" disabled title="Cannot cancel within 24 hours of departure">
                                                        <i class="fa fa-ban"></i> Cannot Cancel
                                                    </button>
                                                    <small class="text-muted d-block">Less than 24h to departure</small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <!-- Past Bookings Section -->
        <section>
            <div class="booking-section-card">
                <div class="booking-section-header"><i class="fa fa-history"></i> Past Bookings</div>
                <div class="booking-section-body">
                    <div style="margin-bottom:1.2rem;display:flex;gap:1rem;">
                        <button class="btn-filter btn btn-warning" onclick="filterPast('all')"><i class="fa fa-list"></i> All</button>
                        <button class="btn-filter btn btn-success" onclick="filterPast('Completed')"><i class="fa fa-check-circle"></i> Completed</button>
                        <button class="btn-filter btn btn-danger" onclick="filterPast('Cancelled')"><i class="fa fa-times-circle"></i> Cancelled</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered past-table" id="pastBookingsTable" style="font-size: 0.85rem;">
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
                                <?php if (empty($past)): ?>
                                    <tr><td colspan="8" style="text-align:center;">No past bookings found.</td></tr>
                                <?php else: foreach ($past as $booking): ?>
                                <tr data-status="<?php echo htmlspecialchars($booking['status']); ?>">
                                    <td><strong><?php echo htmlspecialchars($booking['booking_id']); ?></strong></td>
                                    <td>
                                        <div style="font-size: 0.9em;">
                                            <strong><?php echo htmlspecialchars($booking['bus_number']); ?></strong><br>
                                            <span style="color: #666;">License: <?php echo htmlspecialchars($booking['license_plate'] ?? 'N/A'); ?></span><br>
                                            <span style="color: #666;"><?php echo htmlspecialchars($booking['bus_type'] ?? 'N/A'); ?></span><br>
                                            <span style="color: #666;"><?php echo htmlspecialchars($booking['company']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['source']); ?></strong><br>
                                        <i class="fa fa-arrow-down" style="color: #5A9FD4;"></i><br>
                                        <strong><?php echo htmlspecialchars($booking['destination']); ?></strong>
                                    </td>
                                    <td>
                                        <strong><?php echo date('M j, Y', strtotime($booking['departure_time'])); ?></strong><br>
                                        <span style="color: #666;"><?php echo date('g:i A', strtotime($booking['departure_time'])); ?></span>
                                    </td>
                                    <td><strong>Seat <?php echo htmlspecialchars($booking['seat_number']); ?></strong></td>
                                    <td><strong style="color: #28a745;">RM <?php echo number_format($booking['fare'] ?? 0, 2); ?></strong></td>
                                    <td>
                                        <span style="color: #666; font-size: 0.85em;">
                                            <?php echo htmlspecialchars($booking['payment_method'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo getStatusBadge($booking['status'], 'small'); ?>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <script>
                    function filterPast(status) {
                        var rows = document.querySelectorAll('#pastBookingsTable tbody tr');
                        rows.forEach(function(row) {
                            if (status === 'all' || row.getAttribute('data-status') === status) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        });
                    }
                    </script>
                </div>
            </div>
        </section>
    </div>
</main>
<?php require_once('footer.php'); ?> 