<?php
require_once('../includes/db.php');
require_once('header.php');
if (session_status() === PHP_SESSION_NONE) session_start();
$db = getDB();

$states = [
    'Johor', 'Kedah', 'Kelantan', 'Kuala Lumpur', 'Labuan', 'Melaka', 'Negeri Sembilan',
    'Pahang', 'Penang', 'Perak', 'Perlis', 'Putrajaya', 'Selangor', 'Terengganu'
];

// Get filter values
$filter_source = isset($_GET['source']) ? $_GET['source'] : '';
$filter_destination = isset($_GET['destination']) ? $_GET['destination'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

// Build query with filters
$sql = 'SELECT s.id, b.bus_number, b.company, b.bus_type, r.source, r.destination, r.fare, s.departure_time, s.available_seats
        FROM schedules s
        JOIN buses b ON s.bus_id = b.id
        JOIN routes r ON s.route_id = r.id
        WHERE 1';
$params = [];
if ($filter_source) {
    $sql .= ' AND r.source = ?';
    $params[] = $filter_source;
}
if ($filter_destination) {
    $sql .= ' AND r.destination = ?';
    $params[] = $filter_destination;
}
if ($filter_date) {
    $sql .= ' AND DATE(s.departure_time) = ?';
    $params[] = $filter_date;
}
$sql .= ' ORDER BY s.departure_time ASC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <form class="search-bar-card" method="get" action="" style="margin-top:3.5rem;">
        <div class="search-segment">
            <label for="source">Source</label>
            <select name="source" id="source" class="form-control" required>
                <option value="">From</option>
                <?php foreach ($states as $state): ?>
                    <option value="<?php echo $state; ?>" <?php if ($filter_source === $state) echo 'selected'; ?>><?php echo $state; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="search-segment switch-segment">
            <button type="button" id="switchBtn" class="icon-swap" aria-label="Switch source and destination">⇄</button>
        </div>
        <div class="search-segment">
            <label for="destination">Destination</label>
            <select name="destination" id="destination" class="form-control" required>
                <option value="">To</option>
                <?php foreach ($states as $state): ?>
                    <option value="<?php echo $state; ?>" <?php if ($filter_destination === $state) echo 'selected'; ?>><?php echo $state; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="search-segment">
            <label for="date-picker-display">Date</label>
            <input type="date" class="form-control" id="date-picker-display" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
        </div>
        <div class="search-segment search-btn-segment">
            <button type="submit" class="btn btn-search search-btn-red">SEARCH BUSES</button>
        </div>
    </form>
    <div style="width:100%;max-width:1100px;">
        <h2 style="color:#e53935;font-size:1.5rem;font-weight:700;margin-bottom:1.2rem;">Available Buses</h2>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Bus ID</th>
                    <th>Company</th>
                    <th>Type</th>
                    <th>Route</th>
                    <th>Departure</th>
                    <th>Fare</th>
                    <th>Available Seats</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schedules)): ?>
                    <tr><td colspan="8" style="text-align:center;">No buses found for the selected criteria.</td></tr>
                <?php else: foreach ($schedules as $schedule): ?>
                <tr>
                    <td><?php echo htmlspecialchars($schedule['bus_number']); ?></td>
                    <td><?php echo htmlspecialchars($schedule['company']); ?></td>
                    <td><?php echo htmlspecialchars($schedule['bus_type']); ?></td>
                    <td><?php echo htmlspecialchars($schedule['source'] . ' → ' . $schedule['destination']); ?></td>
                    <td><?php echo htmlspecialchars($schedule['departure_time']); ?></td>
                    <td>RM <?php echo htmlspecialchars($schedule['fare']); ?></td>
                    <td><?php echo htmlspecialchars($schedule['available_seats']); ?></td>
                    <td><a href="booking.php?schedule_id=<?php echo urlencode($schedule['id']); ?>" class="bus-action-btn">Book</a></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</main>
<script>
// Switch source and destination
const switchBtn = document.getElementById('switchBtn');
const sourceSel = document.getElementById('source');
const destSel = document.getElementById('destination');
switchBtn.addEventListener('click', function() {
    const temp = sourceSel.value;
    sourceSel.value = destSel.value;
    destSel.value = temp;
});
// Input validation for search form
const searchForm = document.querySelector('form.search-bar-card');
searchForm.addEventListener('submit', function(e) {
    if (sourceSel.value && destSel.value && sourceSel.value === destSel.value) {
        alert('Source and destination cannot be the same.');
        e.preventDefault();
    }
});
</script>
<?php require_once('footer.php'); ?> 