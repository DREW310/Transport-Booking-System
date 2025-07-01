<?php
require_once('../includes/db.php');
require_once('header.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../public/login.php');
    exit();
}
$db = getDB();
$user_id = $_SESSION['user']['id'];
// Eligible for feedback: completed bookings without feedback
$sql_eligible = 'SELECT b.id, b.booking_id, bu.bus_number, r.source, r.destination, s.departure_time, b.seat_number
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bu ON s.bus_id = bu.id
    JOIN routes r ON s.route_id = r.id
    WHERE b.user_id = ? AND b.status = "Completed" AND b.id NOT IN (SELECT booking_id FROM feedback WHERE user_id = ?)';
$stmt = $db->prepare($sql_eligible);
$stmt->execute([$user_id, $user_id]);
$eligible = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Previous feedback
$sql_prev = 'SELECT b.booking_id AS booking_code, bu.bus_number, bu.company, r.source, r.destination, f.rating, f.review, f.date
    FROM feedback f
    JOIN bookings b ON f.booking_id = b.id
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bu ON s.bus_id = bu.id
    JOIN routes r ON s.route_id = r.id
    WHERE f.user_id = ?';
$stmt2 = $db->prepare($sql_prev);
$stmt2->execute([$user_id]);
$previous = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:600px;max-width:1100px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-comments icon-red"></i> User Feedback</h1>
        <section style="margin-bottom:2rem;">
            <div class="card-header bg-warning" style="font-size:1.1rem;font-weight:600;color:#e53935;border-radius:8px 8px 0 0;">Eligible for Feedback</div>
            <div class="card-body bg-light" style="padding:1.2rem;">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Bus ID</th>
                            <th>Route</th>
                            <th>Departure</th>
                            <th>Seat</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($eligible)): ?>
                        <tr><td colspan="6" style="text-align:center;">No eligible bookings for feedback.</td></tr>
                        <?php else: foreach ($eligible as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['booking_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['bus_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['source'] . ' → ' . $row['destination']); ?></td>
                            <td><?php echo htmlspecialchars($row['departure_time']); ?></td>
                            <td><?php echo htmlspecialchars($row['seat_number']); ?></td>
                            <td><a href="feedback_submit.php?booking_id=<?php echo urlencode($row['id']); ?>" class="btn btn-primary btn-sm"><i class="fa fa-comment"></i> Feedback</a></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <section>
            <div class="card-header bg-primary" style="font-size:1.1rem;font-weight:600;color:#fff;border-radius:8px 8px 0 0;">My Previous Feedback</div>
            <div class="card-body bg-light" style="padding:1.2rem;">
                <?php if (empty($previous)): ?>
                    <div class="alert alert-info" style="text-align:center;">You haven't left any feedback yet. Share your experience to help us improve!</div>
                <?php else: ?>
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Bus</th>
                            <th>Route</th>
                            <th>Rating</th>
                            <th>Review</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($previous as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['booking_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['bus_number']); ?><?php if (!empty($row['company'])) echo ' (' . htmlspecialchars($row['company']) . ')'; ?></td>
                            <td><?php echo htmlspecialchars($row['source'] . ' → ' . $row['destination']); ?></td>
                            <td><?php echo htmlspecialchars($row['rating']); ?></td>
                            <td><?php echo htmlspecialchars($row['review']); ?></td>
                            <td><?php echo htmlspecialchars($row['date']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>
<?php require_once('footer.php'); ?> 