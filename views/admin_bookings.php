<?php
require_once('../views/header.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
require_once('../includes/db.php');
$db = getDB();
$bookings = [];
$sql = 'SELECT b.booking_id, u.username, bu.bus_number, r.source, r.destination, s.departure_time, b.seat_number, b.status, b.booking_time
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN schedules s ON b.schedule_id = s.id
        JOIN buses bu ON s.bus_id = bu.id
        JOIN routes r ON s.route_id = r.id
        ORDER BY b.booking_time DESC';
$stmt = $db->query($sql);
if ($stmt) {
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:600px;max-width:1100px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <a href="admin_dashboard.php" class="btn btn-warning" style="margin-bottom:1.2rem;"><i class="fa fa-arrow-left"></i> Return to Admin Panel</a>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-ticket-alt icon-red"></i> All Bookings</h1>
        <div class="table-responsive">
            <?php if (empty($bookings)): ?>
                <div class="alert alert-warning" style="margin:1rem 0;">No bookings yet.</div>
            <?php else: ?>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>User</th>
                        <th>Bus</th>
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
                        <td><?php echo htmlspecialchars($booking['username']); ?></td>
                        <td><?php echo htmlspecialchars($booking['bus_number']); ?></td>
                        <td><?php echo htmlspecialchars($booking['source'] . ' → ' . $booking['destination']); ?></td>
                        <td><?php echo htmlspecialchars($booking['departure_time']); ?></td>
                        <td><?php echo htmlspecialchars($booking['seat_number']); ?></td>
                        <td>
                            <?php if ($booking['status'] === 'Completed'): ?>
                                <span class="badge bg-success">Completed</span>
                            <?php elseif ($booking['status'] === 'Cancelled'): ?>
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
            <?php endif; ?>
        </div>
    </div>
</main>
<?php require_once('../views/footer.php'); ?> 