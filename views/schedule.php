<?php
require_once('../includes/db.php');
require_once('header.php');
if (session_status() === PHP_SESSION_NONE) session_start();

// Prevent admin/staff from accessing booking features
if (isset($_SESSION['user']) &&
    ((isset($_SESSION['user']['is_staff']) && $_SESSION['user']['is_staff']) ||
     (isset($_SESSION['user']['is_superuser']) && $_SESSION['user']['is_superuser']))) {
    header('Location: admin_dashboard.php');
    exit();
}

$db = getDB();

$states = [
    'Johor', 'Kedah', 'Kelantan', 'Kuala Lumpur', 'Labuan', 'Melaka', 'Negeri Sembilan',
    'Pahang', 'Penang', 'Perak', 'Perlis', 'Putrajaya', 'Selangor', 'Terengganu'
];

/*
ENHANCED SEARCH FUNCTIONALITY
PURPOSE: Advanced filtering with time periods and company search
STUDENT LEARNING: Complex SQL queries, user experience enhancement
*/

// Get filter values from form
$filter_source = isset($_GET['source']) ? $_GET['source'] : '';
$filter_destination = isset($_GET['destination']) ? $_GET['destination'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$filter_time = isset($_GET['time_period']) ? $_GET['time_period'] : '';
$filter_company = isset($_GET['company']) ? $_GET['company'] : '';

// Build enhanced query with all filters
$sql = 'SELECT s.id, b.bus_number, b.company, b.bus_type, r.source, r.destination, r.fare, s.departure_time, s.available_seats
        FROM schedules s
        JOIN buses b ON s.bus_id = b.id
        JOIN routes r ON s.route_id = r.id
        WHERE s.departure_time > DATE_ADD(NOW(), INTERVAL 1 HOUR)';
$params = [];

// Source filter
if ($filter_source) {
    $sql .= ' AND r.source = ?';
    $params[] = $filter_source;
}

// Destination filter
if ($filter_destination) {
    $sql .= ' AND r.destination = ?';
    $params[] = $filter_destination;
}

// Date filter
if ($filter_date) {
    $sql .= ' AND DATE(s.departure_time) = ?';
    $params[] = $filter_date;
}

// Time period filter (Morning, Afternoon, Evening)
if ($filter_time) {
    switch ($filter_time) {
        case 'morning':
            $sql .= ' AND HOUR(s.departure_time) >= 6 AND HOUR(s.departure_time) < 12';
            break;
        case 'afternoon':
            $sql .= ' AND HOUR(s.departure_time) >= 12 AND HOUR(s.departure_time) < 18';
            break;
        case 'evening':
            $sql .= ' AND HOUR(s.departure_time) >= 18 AND HOUR(s.departure_time) < 24';
            break;
        case 'night':
            $sql .= ' AND (HOUR(s.departure_time) >= 0 AND HOUR(s.departure_time) < 6)';
            break;
    }
}

// Company filter
if ($filter_company) {
    $sql .= ' AND b.company LIKE ?';
    $params[] = '%' . $filter_company . '%';
}
$sql .= ' ORDER BY s.departure_time ASC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all companies for filter dropdown
$companies_sql = 'SELECT DISTINCT company FROM buses ORDER BY company';
$companies_stmt = $db->query($companies_sql);
$companies = $companies_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!--
ENHANCED SCHEDULE SEARCH PAGE
PURPOSE: Advanced search with multiple filter options
STUDENT LEARNING: Complex form handling, user experience design
-->
<main class="schedule-main">
    <!--
    ENHANCED SEARCH FORM
    PURPOSE: Provide comprehensive search options for users
    STUDENT LEARNING: Advanced form design, user interface enhancement
    -->
    <div class="schedule-search-container">
        <form class="schedule-search-form" method="get" action="">
            <h2 class="search-title">
                <i class="fa fa-search"></i>
                Find Your Bus
            </h2>

            <div class="search-row">
                <div class="search-field">
                    <label for="source">
                        <i class="fa fa-map-marker-alt"></i>
                        Source
                    </label>
                    <select name="source" id="source" class="form-control" required>
                        <option value="">From</option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?php echo $state; ?>" <?php if ($filter_source === $state) echo 'selected'; ?>><?php echo $state; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="switch-container">
                    <button type="button" id="switchBtn" class="switch-btn" aria-label="Switch source and destination">
                        <i class="fa fa-exchange-alt"></i>
                    </button>
                </div>

                <div class="search-field">
                    <label for="destination">
                        <i class="fa fa-map-marker-alt"></i>
                        Destination
                    </label>
                    <select name="destination" id="destination" class="form-control" required>
                        <option value="">To</option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?php echo $state; ?>" <?php if ($filter_destination === $state) echo 'selected'; ?>><?php echo $state; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="search-row">
                <div class="search-field">
                    <label for="date-picker-display">
                        <i class="fa fa-calendar-alt"></i>
                        Date
                    </label>
                    <input type="date" class="form-control" id="date-picker-display" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
                </div>

                <div class="search-field">
                    <label for="time_period">
                        <i class="fa fa-clock"></i>
                        Time
                    </label>
                    <select name="time_period" id="time_period" class="form-control">
                        <option value="">Any Time</option>
                        <option value="morning" <?php if ($filter_time === 'morning') echo 'selected'; ?>>Morning (6AM-12PM)</option>
                        <option value="afternoon" <?php if ($filter_time === 'afternoon') echo 'selected'; ?>>Afternoon (12PM-6PM)</option>
                        <option value="evening" <?php if ($filter_time === 'evening') echo 'selected'; ?>>Evening (6PM-12AM)</option>
                        <option value="night" <?php if ($filter_time === 'night') echo 'selected'; ?>>Night (12AM-6AM)</option>
                    </select>
                </div>

                <div class="search-field">
                    <label for="company">
                        <i class="fa fa-bus"></i>
                        Company
                    </label>
                    <select name="company" id="company" class="form-control">
                        <option value="">Any Company</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo htmlspecialchars($company); ?>" <?php if ($filter_company === $company) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($company); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="search-actions">
                <button type="submit" class="btn btn-search">
                    <i class="fa fa-search"></i> SEARCH BUSES
                </button>
                <a href="schedule.php" class="btn btn-clear">
                    <i class="fa fa-times"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>
    <!-- Results Section -->
    <div class="schedule-results-container">
        <div class="results-header">
            <h2 class="results-title">
                <i class="fa fa-bus"></i>
                Available Buses
            </h2>
            <?php if (!empty($schedules)): ?>
                <span class="results-count"><?php echo count($schedules); ?> buses found</span>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered" style="font-size: 0.85rem;">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th><i class="fa fa-id-card"></i> Bus ID</th>
                        <th><i class="fa fa-building"></i> Company</th>
                        <th><i class="fa fa-bus"></i> Type</th>
                        <th><i class="fa fa-route"></i> Route</th>
                        <th><i class="fa fa-clock"></i> Departure</th>
                        <th><i class="fa fa-money-bill"></i> Fare</th>
                        <th><i class="fa fa-users"></i> Available Seats</th>
                        <th><i class="fa fa-ticket-alt"></i> Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($schedules)): ?>
                        <tr class="no-results">
                            <td colspan="8">
                                <div class="no-results-content">
                                    <i class="fa fa-search"></i>
                                    <h3>No buses found</h3>
                                    <p>Try adjusting your search criteria or check different dates.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: foreach ($schedules as $schedule): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #e53935;">
                                <?php echo htmlspecialchars($schedule['bus_number']); ?>
                            </div>
                            <small style="color: #666;">
                                Bus Number
                            </small>
                        </td>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo htmlspecialchars($schedule['company']); ?>
                            </div>
                            <small style="color: #666;">
                                Company
                            </small>
                        </td>
                        <td>
                            <div style="font-weight: 500; color: #007bff;">
                                <?php echo htmlspecialchars($schedule['bus_type']); ?>
                            </div>
                            <small style="color: #666;">
                                Bus Type
                            </small>
                        </td>
                        <td>
                            <div style="font-weight: 500; color: #28a745;">
                                <?php echo htmlspecialchars($schedule['source']); ?>
                            </div>
                            <div style="color: #666; margin: 2px 0;">
                                <i class="fa fa-arrow-right" style="color: #5A9FD4;"></i>
                            </div>
                            <div style="font-weight: 500; color: #dc3545;">
                                <?php echo htmlspecialchars($schedule['destination']); ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo date('M j, Y', strtotime($schedule['departure_time'])); ?>
                            </div>
                            <small style="color: #666;">
                                <?php echo date('g:i A', strtotime($schedule['departure_time'])); ?>
                            </small>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #28a745;">
                                RM <?php echo number_format($schedule['fare'], 2); ?>
                            </div>
                            <small style="color: #666;">
                                Fare
                            </small>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: <?php echo $schedule['available_seats'] <= 5 ? '#dc3545' : '#28a745'; ?>;">
                                <?php echo htmlspecialchars($schedule['available_seats']); ?>
                            </div>
                            <small style="color: #666;">
                                <?php echo $schedule['available_seats'] <= 5 ? 'Few Left' : 'Available'; ?>
                            </small>
                        </td>
                        <td>
                            <div class="bus-action-group">
                                <?php if ($schedule['available_seats'] > 0): ?>
                                    <a href="booking.php?schedule_id=<?php echo urlencode($schedule['id']); ?>"
                                       style="font-size: 0.8rem; padding: 6px 12px; background: #28a745; color: white; border: none; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; box-shadow: 0 2px 4px rgba(40,167,69,0.3); transition: all 0.2s ease;">
                                        <i class="fa fa-ticket-alt"></i> Book Now
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; padding: 6px 12px; background: #dc3545; color: white; border: none; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; opacity: 0.8;">
                                        <i class="fa fa-times-circle"></i> Sold Out
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<style>
.bus-action-group {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.bus-action-group a:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
}
</style>

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
const searchForm = document.querySelector('.schedule-search-form');
if (searchForm) {
    searchForm.addEventListener('submit', function(e) {
        if (sourceSel.value && destSel.value && sourceSel.value === destSel.value) {
            alert('Source and destination cannot be the same.');
            e.preventDefault();
        }
    });
}
</script>
<?php require_once('footer.php'); ?> 