<?php
require_once('../views/header.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
require_once('../includes/db.php');
require_once('../includes/status_helpers.php');
$db = getDB();

// STEP 1: Fix any incorrectly completed future bookings first
$fix_stmt = $db->prepare('UPDATE bookings b
                         JOIN schedules s ON b.schedule_id = s.id
                         SET b.status = ?
                         WHERE b.status = ?
                         AND s.departure_time > NOW()');
$fix_stmt->execute(['Booked', 'Completed']);

// STEP 2: System-wide auto-complete past bookings (runs on admin page load)
// This ensures all booking statuses are up-to-date when admin views the page
$stmt = $db->prepare('UPDATE bookings b
                     JOIN schedules s ON b.schedule_id = s.id
                     SET b.status = ?
                     WHERE b.status = ?
                     AND s.departure_time <= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                     AND b.status NOT IN (?, ?)');
$stmt->execute(['Completed', 'Booked', 'Cancelled', 'Completed']);

// Get filter parameters
$filter_status = $_GET['status'] ?? '';
$filter_source = $_GET['source'] ?? '';
$filter_destination = $_GET['destination'] ?? '';
$filter_bus = $_GET['bus'] ?? '';
$filter_user = $_GET['user'] ?? '';
$filter_date = $_GET['date'] ?? '';
$search_query = $_GET['search'] ?? '';

// Get unique values for filter dropdowns
$statuses = $db->query('SELECT DISTINCT status FROM bookings ORDER BY status')->fetchAll(PDO::FETCH_COLUMN);
$sources = $db->query('SELECT DISTINCT r.source FROM routes r JOIN schedules s ON r.id = s.route_id JOIN bookings b ON s.id = b.schedule_id ORDER BY r.source')->fetchAll(PDO::FETCH_COLUMN);
$destinations = $db->query('SELECT DISTINCT r.destination FROM routes r JOIN schedules s ON r.id = s.route_id JOIN bookings b ON s.id = b.schedule_id ORDER BY r.destination')->fetchAll(PDO::FETCH_COLUMN);
$buses = $db->query('SELECT DISTINCT bu.bus_number FROM buses bu JOIN schedules s ON bu.id = s.bus_id JOIN bookings b ON s.id = b.schedule_id ORDER BY bu.bus_number')->fetchAll(PDO::FETCH_COLUMN);
$users = $db->query('SELECT DISTINCT u.username FROM users u JOIN bookings b ON u.id = b.user_id ORDER BY u.username')->fetchAll(PDO::FETCH_COLUMN);

// Build enhanced query with filters
$sql = 'SELECT b.id, b.booking_id, u.username, u.email, bu.bus_number, bu.company, bu.license_plate,
               r.source, r.destination, r.fare, s.departure_time, b.seat_number, b.status,
               b.booking_time, b.payment_method
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN schedules s ON b.schedule_id = s.id
        JOIN buses bu ON s.bus_id = bu.id
        JOIN routes r ON s.route_id = r.id
        WHERE 1=1';
$params = [];

// Apply filters
if ($filter_status) {
    $sql .= ' AND b.status = ?';
    $params[] = $filter_status;
}
if ($filter_source) {
    $sql .= ' AND r.source = ?';
    $params[] = $filter_source;
}
if ($filter_destination) {
    $sql .= ' AND r.destination = ?';
    $params[] = $filter_destination;
}
if ($filter_bus) {
    $sql .= ' AND bu.bus_number = ?';
    $params[] = $filter_bus;
}
if ($filter_user) {
    $sql .= ' AND u.username = ?';
    $params[] = $filter_user;
}
if ($filter_date) {
    $sql .= ' AND DATE(s.departure_time) = ?';
    $params[] = $filter_date;
}
if ($search_query) {
    $sql .= ' AND (b.booking_id LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR bu.bus_number LIKE ? OR r.source LIKE ? OR r.destination LIKE ?)';
    $search_param = '%' . $search_query . '%';
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
}

$sql .= ' ORDER BY b.booking_time DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2rem;min-width:600px;max-width:1400px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger" style="margin-bottom:1.5rem; padding:1.5rem; border-radius:12px; border:none; background:#f8d7da; color:#721c24; font-weight:600; box-shadow:0 4px 16px rgba(220,53,69,0.2); text-align:center;">
                <div style="font-size:2.5rem; color:#dc3545; margin-bottom:1rem;">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
                <h4 style="color:#721c24; margin-bottom:0.5rem;">Error!</h4>
                <p style="margin-bottom:0; color:#721c24;"><?php echo htmlspecialchars($_GET['error']); ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" style="margin-bottom:1.5rem; padding:1.5rem; border-radius:12px; border:none; background:#d4edda; color:#155724; font-weight:600; box-shadow:0 4px 16px rgba(40,167,69,0.2); text-align:center;">
                <div style="font-size:2.5rem; color:#28a745; margin-bottom:1rem;">
                    <i class="fa fa-check-circle"></i>
                </div>
                <h4 style="color:#155724; margin-bottom:0.5rem;">Success!</h4>
                <p style="margin-bottom:0; color:#155724;"><?php echo htmlspecialchars($_GET['success']); ?></p>
            </div>
        <?php endif; ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
            <a href="admin_dashboard.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <span class="badge" style="background: #007bff; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem;">
                    <i class="fa fa-ticket-alt"></i> <?php echo count($bookings); ?> Bookings
                </span>
            </div>
        </div>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-ticket-alt icon-red"></i> All Bookings Management</h1>

        <!-- Enhanced Filter and Search Section -->
        <div class="filter-section" style="background: #f8f9fa; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0; color: #495057; font-size: 1.1rem;">
                    <i class="fa fa-filter" style="color: #6c757d; margin-right: 0.5rem;"></i>
                    Filter & Search Bookings
                </h3>
                <div style="display: flex; gap: 0.5rem;">
                    <span class="badge" style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">
                        <?php echo count($bookings); ?> Results
                    </span>
                    <?php if ($filter_status || $filter_source || $filter_destination || $filter_bus || $filter_user || $filter_date || $search_query): ?>
                        <a href="admin_bookings.php" class="btn btn-sm" style="background: #6c757d; color: white; padding: 0.25rem 0.5rem; text-decoration: none; border-radius: 4px; font-size: 0.8rem;">
                            <i class="fa fa-times"></i> Clear All
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                <!-- Search Bar -->
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #495057; font-size: 0.9rem;">
                        <i class="fa fa-search"></i> Search
                    </label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>"
                           placeholder="Booking ID, username, email, bus..."
                           class="form-control" style="font-size: 0.9rem;">
                </div>

                <!-- Status Filter -->
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #495057; font-size: 0.9rem;">
                        <i class="fa fa-info-circle"></i> Status
                    </label>
                    <select name="status" class="form-control" style="font-size: 0.9rem;">
                        <option value="">All Status</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $filter_status === $status ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Source Filter -->
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #495057; font-size: 0.9rem;">
                        <i class="fa fa-map-marker-alt"></i> From
                    </label>
                    <select name="source" class="form-control" style="font-size: 0.9rem;">
                        <option value="">All Sources</option>
                        <?php foreach ($sources as $source): ?>
                            <option value="<?php echo htmlspecialchars($source); ?>" <?php echo $filter_source === $source ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($source); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Destination Filter -->
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #495057; font-size: 0.9rem;">
                        <i class="fa fa-flag-checkered"></i> To
                    </label>
                    <select name="destination" class="form-control" style="font-size: 0.9rem;">
                        <option value="">All Destinations</option>
                        <?php foreach ($destinations as $destination): ?>
                            <option value="<?php echo htmlspecialchars($destination); ?>" <?php echo $filter_destination === $destination ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($destination); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Bus Filter -->
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #495057; font-size: 0.9rem;">
                        <i class="fa fa-bus"></i> Bus
                    </label>
                    <select name="bus" class="form-control" style="font-size: 0.9rem;">
                        <option value="">All Buses</option>
                        <?php foreach ($buses as $bus): ?>
                            <option value="<?php echo htmlspecialchars($bus); ?>" <?php echo $filter_bus === $bus ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($bus); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- User Filter -->
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #495057; font-size: 0.9rem;">
                        <i class="fa fa-user"></i> User
                    </label>
                    <select name="user" class="form-control" style="font-size: 0.9rem;">
                        <option value="">All Users</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo htmlspecialchars($user); ?>" <?php echo $filter_user === $user ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date Filter -->
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #495057; font-size: 0.9rem;">
                        <i class="fa fa-calendar"></i> Travel Date
                    </label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>"
                           class="form-control" style="font-size: 0.9rem;">
                </div>

                <!-- Filter Button -->
                <div>
                    <button type="submit" class="bus-action-btn" style="width: 100%; background: #007bff; color: white; border: none; padding: 0.5rem 1rem; font-size: 0.9rem;">
                        <i class="fa fa-search"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <?php if (empty($bookings)): ?>
                <div class="alert alert-info" style="margin:2rem 0; padding:2rem; text-align:center; border-radius:12px; background:#e3f2fd; color:#1565c0; border:none;">
                    <div style="font-size:3rem; margin-bottom:1rem;">
                        <i class="fa fa-search"></i>
                    </div>
                    <h4>No Bookings Found</h4>
                    <p style="margin-bottom:1rem;">No bookings match your current filters. Try adjusting your search criteria.</p>
                    <a href="admin_bookings.php" class="bus-action-btn" style="background:#1976d2;">
                        <i class="fa fa-refresh"></i> Show All Bookings
                    </a>
                </div>
            <?php else: ?>
            <table class="table table-striped table-bordered" style="font-size: 0.85rem;">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th><i class="fa fa-id-card"></i> Booking</th>
                        <th><i class="fa fa-user"></i> Customer</th>
                        <th><i class="fa fa-route"></i> Route</th>
                        <th><i class="fa fa-clock"></i> Departure</th>
                        <th><i class="fa fa-chair"></i> Seat</th>
                        <th><i class="fa fa-info-circle"></i> Status</th>
                        <th><i class="fa fa-cogs"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking):
                        $is_past = strtotime($booking['departure_time']) < time();
                        // Use database time comparison to avoid timezone issues
                        $current_time_result = $db->query("SELECT NOW() as current_datetime, DATE(NOW()) as today_date")->fetch(PDO::FETCH_ASSOC);
                        $current_datetime = $current_time_result['current_datetime'];
                        $today_date = $current_time_result['today_date'];
                        $is_today = date('Y-m-d', strtotime($booking['departure_time'])) === $today_date;
                        $is_soon = $booking['departure_time'] < date('Y-m-d H:i:s', strtotime($current_datetime) + 3600);

                        // Row styling based on status and time
                        $row_style = getStatusRowStyle($booking['status']);
                        if ($is_past && $booking['status'] !== 'Cancelled') {
                            $row_style .= 'opacity: 0.7;';
                        }
                    ?>
                    <tr style="<?php echo $row_style; ?>">
                        <td>
                            <div style="font-weight: 600; color: #e53935;">
                                <?php echo htmlspecialchars($booking['booking_id']); ?>
                            </div>
                            <small style="color: #666;">
                                <?php echo htmlspecialchars($booking['payment_method'] ?? 'Online Banking'); ?>
                            </small>
                        </td>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo htmlspecialchars($booking['username']); ?>
                            </div>
                            <small style="color: #666;">
                                <?php echo htmlspecialchars($booking['email']); ?>
                            </small>
                        </td>

                        <td>
                            <div style="font-weight: 600; color: #e53935;">
                                <?php echo htmlspecialchars($booking['bus_number']); ?>
                            </div>
                            <small style="color: #666;">
                                <?php echo htmlspecialchars($booking['source']); ?> → <?php echo htmlspecialchars($booking['destination']); ?>
                            </small>
                        </td>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo date('M j, Y', strtotime($booking['departure_time'])); ?>
                            </div>
                            <small style="color: #666;">
                                <?php echo date('g:i A', strtotime($booking['departure_time'])); ?>
                            </small>
                        </td>
                        <td>
                            <div style="font-weight: 600;">
                                <?php echo htmlspecialchars($booking['seat_number']); ?>
                            </div>
                            <small style="color: #666;">
                                RM <?php echo number_format($booking['fare'], 2); ?>
                            </small>
                        </td>

                        <td>
                            <?php echo getStatusBadge($booking['status'], 'medium'); ?>
                            <div style="margin-top: 4px;">
                                <small style="color: #666;">
                                    <?php if ($booking['status'] === 'Cancelled'): ?>
                                        <i class="fa fa-times-circle"></i> Booking cancelled
                                    <?php elseif ($is_past && $booking['status'] === 'Booked'): ?>
                                        <i class="fa fa-clock"></i> Trip completed
                                    <?php elseif ($is_soon && $booking['status'] === 'Booked'): ?>
                                        <i class="fa fa-exclamation-triangle"></i> Departing soon
                                    <?php else: ?>
                                        <i class="fa fa-check-circle"></i> Active booking
                                    <?php endif; ?>
                                </small>
                            </div>
                        </td>
                        <td>
                            <div class="bus-action-group">
                                <?php if ($booking['status'] !== 'Cancelled' && $booking['status'] !== 'Completed'): ?>
                                    <a href="admin_booking_cancel_preview.php?id=<?php echo $booking['id']; ?>"
                                       style="font-size: 0.8rem; padding: 6px 12px; background: #ff9800; color: white; border: none; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; box-shadow: 0 2px 4px rgba(255,152,0,0.3); transition: all 0.2s ease;">
                                        <i class="fa fa-ban"></i> Cancel
                                    </a>
                                <?php elseif ($booking['status'] === 'Cancelled'): ?>
                                    <span style="font-size: 0.8rem; padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; opacity: 0.6;">
                                        <i class="fa fa-times-circle"></i> Cancelled
                                    </span>
                                <?php elseif ($booking['status'] === 'Completed'): ?>
                                    <span style="font-size: 0.8rem; padding: 6px 12px; background: #28a745; color: white; border: none; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; opacity: 0.8;">
                                        <i class="fa fa-check-circle"></i> Completed
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
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
// Auto-hide success/error messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        // Add fade-out animation after 5 seconds
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';

            // Remove from DOM after animation
            setTimeout(function() {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 500);
        }, 5000);
    });
});
</script>

<?php require_once('../views/footer.php'); ?>