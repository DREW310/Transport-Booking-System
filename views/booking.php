<?php
require_once('../includes/db.php');
require_once('header.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../public/user_login.php');
    exit();
}
$db = getDB();
$schedule_id = isset($_GET['schedule_id']) ? $_GET['schedule_id'] : null;
if (!$schedule_id) {
    header('Location: schedule.php');
    exit();
}
// Fetch schedule details
$sql = 'SELECT s.*, b.bus_number, b.company, b.capacity, r.source, r.destination, r.fare FROM schedules s
        JOIN buses b ON s.bus_id = b.id
        JOIN routes r ON s.route_id = r.id
        WHERE s.id = ?';
$stmt = $db->prepare($sql);
$stmt->execute([$schedule_id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$schedule) {
    header('Location: schedule.php');
    exit();
}
// Fetch booked seats
$stmt = $db->prepare('SELECT seat_number FROM bookings WHERE schedule_id = ?');
$stmt->execute([$schedule_id]);
$booked_seats = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'seat_number');
$available_seats = $schedule['available_seats'];
$capacity = $schedule['capacity'];
$seat_options = [];
for ($i = 1; $i <= $capacity; $i++) {
    if (!in_array($i, $booked_seats)) {
        $seat_options[] = $i;
    }
}
$success = false;
$error = '';
$new_booking_id = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seat_number = (int)$_POST['seat_number'];
    // Double-check seat is still available
    if (in_array($seat_number, $booked_seats)) {
        $error = 'Selected seat is already booked.';
    } elseif ($available_seats <= 0) {
        $error = 'No seats available.';
    } else {
        // Insert booking
        $status = 'Booked';
        // Generate a unique booking ID (e.g., BK + date + random)
        $new_booking_id = 'BK' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $stmt = $db->prepare('INSERT INTO bookings (booking_id, user_id, schedule_id, seat_number, status, booking_time) VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$new_booking_id, $_SESSION['user']['id'], $schedule_id, $seat_number, $status]);
        // Update available seats
        $stmt = $db->prepare('UPDATE schedules SET available_seats = available_seats - 1 WHERE id = ?');
        $stmt->execute([$schedule_id]);
        $success = true;
        // Refresh booked seats
        $stmt = $db->prepare('SELECT seat_number FROM bookings WHERE schedule_id = ?');
        $stmt->execute([$schedule_id]);
        $booked_seats = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'seat_number');
        $seat_options = [];
        for ($i = 1; $i <= $capacity; $i++) {
            if (!in_array($i, $booked_seats)) {
                $seat_options[] = $i;
            }
        }
    }
}
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:350px;max-width:500px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <h1 style="color:#e53935;margin-bottom:1.5rem;">Book a Seat</h1>
        <div style="margin-bottom:1.5rem;">
            <b>Bus:</b> <?php echo htmlspecialchars($schedule['bus_number']); ?><br>
            <b>Company:</b> <?php echo htmlspecialchars($schedule['company']); ?><br>
            <b>Route:</b> <?php echo htmlspecialchars($schedule['source'] . ' → ' . $schedule['destination']); ?><br>
            <b>Departure:</b> <?php echo htmlspecialchars($schedule['departure_time']); ?><br>
            <b>Fare:</b> RM <?php echo htmlspecialchars($schedule['fare']); ?><br>
            <b>Available Seats:</b> <?php echo htmlspecialchars($schedule['available_seats']); ?><br>
        </div>
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:1rem;">Booking successful!<br>Your Booking ID: <b><?php echo htmlspecialchars($new_booking_id); ?></b></div>
            <a href="schedule.php" class="btn btn-secondary">Back to Schedules</a>
        <?php elseif ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:1rem;">Error: <?php echo htmlspecialchars($error); ?></div>
        <?php elseif (empty($seat_options)): ?>
            <div class="alert alert-warning" style="margin-bottom:1rem;">No seats available for this schedule.</div>
            <a href="schedule.php" class="btn btn-secondary">Back to Schedules</a>
        <?php else: ?>
        <form method="post" id="bookingForm" action="">
            <label for="seat_number">Seat Number:</label>
            <select name="seat_number" id="seat_number" class="form-control" required>
                <?php foreach ($seat_options as $seat): ?>
                    <option value="<?php echo $seat; ?>"><?php echo $seat; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-primary" style="margin-top:1.2rem;" onclick="confirmBooking()">Book Now</button>
            <a href="schedule.php" class="btn btn-secondary" style="margin-top:1.2rem;">Back to Schedules</a>
        </form>
        <script>
        function confirmBooking() {
            if (confirm('Are you sure you want to book this seat?')) {
                document.getElementById('bookingForm').submit();
            }
        }
        </script>
        <?php endif; ?>
    </div>
</main>
<?php require_once('footer.php'); ?> 