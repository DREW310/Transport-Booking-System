<?php
require_once('../views/header.php');
session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
require_once('../includes/db.php');
$db = getDB();
$feedbacks = [];
$sql = 'SELECT f.id, b.booking_id, u.username, bu.bus_number, r.source, r.destination, f.rating, f.review, f.date
        FROM feedback f
        JOIN bookings b ON f.booking_id = b.id
        JOIN users u ON b.user_id = u.id
        JOIN schedules s ON b.schedule_id = s.id
        JOIN buses bu ON s.bus_id = bu.id
        JOIN routes r ON s.route_id = r.id
        ORDER BY f.date DESC';
$stmt = $db->query($sql);
if ($stmt) {
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2rem 2rem 2.5rem;min-width:600px;max-width:1100px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <a href="admin_dashboard.php" class="btn btn-warning" style="margin-bottom:1.2rem;"><i class="fa fa-arrow-left"></i> Return to Admin Panel</a>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-comments icon-red"></i> All Feedback</h1>
        <div class="table-responsive">
            <?php if (empty($feedbacks)): ?>
                <div class="alert alert-warning" style="margin:1rem 0;">No feedback yet.</div>
            <?php else: ?>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Booking ID</th>
                        <th>Bus</th>
                        <th>Route</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feedbacks as $fb): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fb['username']); ?></td>
                        <td><?php echo htmlspecialchars($fb['booking_id']); ?></td>
                        <td><?php echo htmlspecialchars($fb['bus_number']); ?></td>
                        <td><?php echo htmlspecialchars($fb['source'] . ' → ' . $fb['destination']); ?></td>
                        <td><?php echo htmlspecialchars($fb['rating']); ?></td>
                        <td><?php echo htmlspecialchars($fb['review']); ?></td>
                        <td><?php echo htmlspecialchars($fb['date']); ?></td>
                        <td>
                            <a href="admin_feedback_edit.php?id=<?php echo $fb['id']; ?>" class="btn btn-success btn-sm"><i class="fa fa-edit"></i> Edit</a>
                            <a href="admin_feedback_delete.php?id=<?php echo $fb['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this feedback? This action cannot be undone.');"><i class="fa fa-trash"></i> Delete</a>
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