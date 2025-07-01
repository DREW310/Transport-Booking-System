<?php
require_once('../includes/db.php');
require_once('../views/header.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
$db = getDB();
// Fetch buses and routes for dropdowns
$buses = $db->query('SELECT id, bus_number, company, capacity FROM buses')->fetchAll(PDO::FETCH_ASSOC);
$routes = $db->query('SELECT id, source, destination FROM routes')->fetchAll(PDO::FETCH_ASSOC);
$schedule = [
    'bus_id' => '',
    'route_id' => '',
    'departure_time' => '',
    'available_seats' => ''
];
$success = false;
$error = '';
if (isset($_GET['id'])) {
    $stmt = $db->prepare('SELECT * FROM schedules WHERE id = ?');
    $stmt->execute([$_GET['id']]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$schedule) {
        $error = 'Schedule not found!';
        $schedule = ['bus_id'=>'','route_id'=>'','departure_time'=>'','available_seats'=>''];
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bus_id = $_POST['bus_id'];
    $route_id = $_POST['route_id'];
    $departure_time = $_POST['departure_time'];
    $available_seats = $_POST['available_seats'];
    // Fetch bus capacity for validation
    $stmt = $db->prepare('SELECT capacity FROM buses WHERE id = ?');
    $stmt->execute([$bus_id]);
    $bus_capacity = $stmt->fetchColumn();
    if ($available_seats < 0 || $available_seats > $bus_capacity) {
        $error = 'Available seats must be between 0 and the bus capacity (' . $bus_capacity . ').';
    } else {
        if (isset($_GET['id'])) {
            $stmt = $db->prepare('UPDATE schedules SET bus_id=?, route_id=?, departure_time=?, available_seats=? WHERE id=?');
            $success = $stmt->execute([$bus_id, $route_id, $departure_time, $available_seats, $_GET['id']]);
        } else {
            $stmt = $db->prepare('INSERT INTO schedules (bus_id, route_id, departure_time, available_seats) VALUES (?, ?, ?, ?)');
            $success = $stmt->execute([$bus_id, $route_id, $departure_time, $available_seats]);
        }
        // Re-fetch for display
        if (isset($_GET['id'])) {
            $stmt = $db->prepare('SELECT * FROM schedules WHERE id = ?');
            $stmt->execute([$_GET['id']]);
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $schedule = ['bus_id'=>'','route_id'=>'','departure_time'=>'','available_seats'=>''];
        }
    }
}
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2rem 2rem 2.5rem;min-width:400px;max-width:600px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <a href="admin_schedules.php" class="btn btn-warning" style="margin-bottom:1.2rem;"><i class="fa fa-arrow-left"></i> Back to Schedules</a>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-calendar-alt icon-red"></i> <?php echo isset($_GET['id']) ? 'Edit' : 'Add'; ?> Schedule</h1>
        <?php if ($success): ?>
            <div class="alert alert-info" style="margin-bottom:1rem;">Schedule saved successfully!</div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:1rem;">Error: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <label>Bus:</label>
            <select name="bus_id" class="form-control" required>
                <option value="">---------</option>
                <?php foreach ($buses as $bus): ?>
                    <option value="<?php echo $bus['id']; ?>" <?php if ($schedule['bus_id'] == $bus['id']) echo 'selected'; ?>><?php echo $bus['bus_number'] . ' (' . $bus['company'] . ')'; ?></option>
                <?php endforeach; ?>
            </select>
            <label>Route:</label>
            <select name="route_id" class="form-control" required>
                <option value="">---------</option>
                <?php foreach ($routes as $route): ?>
                    <option value="<?php echo $route['id']; ?>" <?php if ($schedule['route_id'] == $route['id']) echo 'selected'; ?>><?php echo $route['source'] . ' to ' . $route['destination']; ?></option>
                <?php endforeach; ?>
            </select>
            <label>Departure Time:</label>
            <input type="datetime-local" name="departure_time" class="form-control" value="<?php echo htmlspecialchars($schedule['departure_time']); ?>" required>
            <label>Available Seats:</label>
            <input type="number" name="available_seats" id="available_seats" class="form-control" value="<?php echo htmlspecialchars($schedule['available_seats']); ?>" min="0" required>
            <script>
            // Prepare bus capacities in JS
            var busCapacities = {};
            <?php foreach ($buses as $bus): ?>
                busCapacities[<?php echo $bus['id']; ?>] = <?php echo $bus['capacity']; ?>;
            <?php endforeach; ?>
            function updateAvailableSeatsMax() {
                var busSelect = document.querySelector('select[name=bus_id]');
                var availableSeatsInput = document.getElementById('available_seats');
                var selectedBusId = busSelect.value;
                if (busCapacities[selectedBusId]) {
                    availableSeatsInput.max = busCapacities[selectedBusId];
                } else {
                    availableSeatsInput.removeAttribute('max');
                }
            }
            document.addEventListener('DOMContentLoaded', function() {
                var busSelect = document.querySelector('select[name=bus_id]');
                busSelect.addEventListener('change', updateAvailableSeatsMax);
                updateAvailableSeatsMax();
            });
            </script>
            <button type="submit" class="btn btn-primary" style="margin-top:1rem;"><i class="fa fa-save"></i> Save</button>
        </form>
    </div>
</main>
<?php require_once('../views/footer.php'); ?> 