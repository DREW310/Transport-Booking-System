<?php
require_once('../includes/db.php');
require_once('../views/header.php');
session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
$db = getDB();
$schedules = [];
$sql = 'SELECT s.*, b.bus_number, r.source, r.destination FROM schedules s
        JOIN buses b ON s.bus_id = b.id
        JOIN routes r ON s.route_id = r.id';
$stmt = $db->query($sql);
if ($stmt) {
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Fetch booking counts for each schedule
$booking_counts = [];
if (!empty($schedules)) {
    $ids = array_column($schedules, 'id');
    $in = str_repeat('?,', count($ids) - 1) . '?';
    $stmt2 = $db->prepare('SELECT schedule_id, COUNT(*) as cnt FROM bookings WHERE schedule_id IN (' . $in . ') GROUP BY schedule_id');
    $stmt2->execute($ids);
    foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $booking_counts[$row['schedule_id']] = $row['cnt'];
    }
}
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:600px;max-width:900px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
            <a href="admin_dashboard.php" class="btn btn-warning"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
            <a href="admin_schedule_form.php" class="btn btn-primary"><i class="fa fa-plus-circle"></i> Add Schedule</a>
        </div>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-calendar-alt icon-red"></i> Manage Schedules</h1>
        <div class="table-responsive">
            <?php if (empty($schedules)): ?>
                <div class="alert alert-warning" style="margin:1rem 0;">No schedules yet, create one!</div>
            <?php else: ?>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Bus ID</th>
                        <th>Route</th>
                        <th>Departure Time</th>
                        <th>Available Seats</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($schedule['bus_number']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['source'] . ' → ' . $schedule['destination']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['departure_time']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['available_seats']); ?></td>
                        <td>
                            <a href="admin_schedule_form.php?id=<?php echo $schedule['id']; ?>" class="btn btn-success btn-sm"><i class="fa fa-edit"></i> Edit</a>
                            <?php $hasBookings = !empty($booking_counts[$schedule['id']]); ?>
                            <a href="admin_schedule_delete.php?id=<?php echo $schedule['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this schedule?<?php echo $hasBookings ? ' This schedule has existing bookings. Deleting it will also remove all related bookings.' : ''; ?>');"><i class="fa fa-trash"></i> Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php require_once('../views/footer.php'); ?> 