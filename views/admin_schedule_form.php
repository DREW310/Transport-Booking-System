<?php
require_once('../includes/db.php');
require_once('../views/header.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
$db = getDB();
// Fetch buses and routes for dropdowns with comprehensive information
$buses = $db->query('SELECT id, bus_number, license_plate, bus_type, company, capacity FROM buses ORDER BY bus_number')->fetchAll(PDO::FETCH_ASSOC);
$routes = $db->query('SELECT id, source, destination, fare FROM routes ORDER BY source, destination')->fetchAll(PDO::FETCH_ASSOC);
$schedule = [
    'bus_id' => '',
    'route_id' => '',
    'departure_time' => '',
    'available_seats' => ''
];
$success = false;
$error = '';
$is_completed_schedule = false;

if (isset($_GET['id'])) {
    $stmt = $db->prepare('SELECT * FROM schedules WHERE id = ?');
    $stmt->execute([$_GET['id']]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$schedule) {
        $error = 'Schedule not found!';
        $schedule = ['bus_id'=>'','route_id'=>'','departure_time'=>'','available_seats'=>''];
    } else {
        // Check if this schedule has any completed bookings
        $completed_check = $db->prepare('SELECT COUNT(*) FROM bookings WHERE schedule_id = ? AND status = "Completed"');
        $completed_check->execute([$_GET['id']]);
        $is_completed_schedule = $completed_check->fetchColumn() > 0;

        if ($is_completed_schedule) {
            $error = 'This schedule cannot be edited because it has completed bookings. You can only view the seat map.';
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_completed_schedule) {
    $bus_id = (int)$_POST['bus_id'];
    $route_id = (int)$_POST['route_id'];
    $departure_time = $_POST['departure_time'];

    // Validate input
    if (empty($bus_id)) {
        $error = 'Please select a bus.';
    } elseif (empty($route_id)) {
        $error = 'Please select a route.';
    } elseif (empty($departure_time)) {
        $error = 'Please select departure time.';
    } else {
        // Check if departure time is in the future using database time
        $time_check = $db->prepare("SELECT CASE WHEN ? <= NOW() THEN 1 ELSE 0 END as is_past");
        $time_check->execute([$departure_time]);
        $is_past = $time_check->fetchColumn();

        if ($is_past) {
            $error = 'Departure time must be in the future.';
        }
    }

    if (empty($error)) {
        // Fetch bus capacity for calculation
        $stmt = $db->prepare('SELECT capacity, bus_number FROM buses WHERE id = ?');
        $stmt->execute([$bus_id]);
        $bus_info = $stmt->fetch(PDO::FETCH_ASSOC);
        $bus_capacity = $bus_info['capacity'];

        // Calculate available seats based on existing bookings (for edit mode)
        if (isset($_GET['id'])) {
            // For existing schedule, count current bookings (exclude cancelled bookings)
            $booking_stmt = $db->prepare('SELECT COUNT(*) FROM bookings WHERE schedule_id = ? AND status IN ("Booked", "Completed")');
            $booking_stmt->execute([$_GET['id']]);
            $booked_count = $booking_stmt->fetchColumn();
            $available_seats = $bus_capacity - $booked_count;
        } else {
            // For new schedule, all seats are available
            $available_seats = $bus_capacity;
        }

        // Check for scheduling conflicts (same bus at similar time)
        $conflict_check_sql = "SELECT COUNT(*) FROM schedules
                               WHERE bus_id = ?
                               AND ABS(TIMESTAMPDIFF(MINUTE, departure_time, ?)) < 120";
        $conflict_params = [$bus_id, $departure_time];

        if (isset($_GET['id'])) {
            $conflict_check_sql .= " AND id != ?";
            $conflict_params[] = $_GET['id'];
        }

        $conflict_stmt = $db->prepare($conflict_check_sql);
        $conflict_stmt->execute($conflict_params);

        if ($conflict_stmt->fetchColumn() > 0) {
            $error = "Scheduling conflict: Bus {$bus_info['bus_number']} already has a schedule within 2 hours of this time. Please choose a different time.";
        } else {
            // Proceed with save if no conflicts
            if (isset($_GET['id'])) {
                // Get old values before updating
                $old_stmt = $db->prepare('SELECT s.*, b.bus_number, r.source, r.destination
                                        FROM schedules s
                                        JOIN buses b ON s.bus_id = b.id
                                        JOIN routes r ON s.route_id = r.id
                                        WHERE s.id = ?');
                $old_stmt->execute([$_GET['id']]);
                $old_schedule = $old_stmt->fetch(PDO::FETCH_ASSOC);

                $stmt = $db->prepare('UPDATE schedules SET bus_id=?, route_id=?, departure_time=?, available_seats=? WHERE id=?');
                $success = $stmt->execute([$bus_id, $route_id, $departure_time, $available_seats, $_GET['id']]);

                // Send system notifications to users with bookings on this schedule
                if ($success && $old_schedule) {
                    // Get new bus and route info
                    $new_stmt = $db->prepare('SELECT b.bus_number, r.source, r.destination
                                            FROM buses b, routes r
                                            WHERE b.id = ? AND r.id = ?');
                    $new_stmt->execute([$bus_id, $route_id]);
                    $new_info = $new_stmt->fetch(PDO::FETCH_ASSOC);

                    // Build change details
                    $changes = [];
                    if ($old_schedule['bus_id'] != $bus_id) {
                        $changes[] = "Bus: {$old_schedule['bus_number']} → {$new_info['bus_number']}";
                    }
                    if ($old_schedule['route_id'] != $route_id) {
                        $changes[] = "Route: {$old_schedule['source']} → {$old_schedule['destination']} to {$new_info['source']} → {$new_info['destination']}";
                    }
                    if ($old_schedule['departure_time'] !== $departure_time) {
                        $old_time = date('M j, Y g:i A', strtotime($old_schedule['departure_time']));
                        $new_time = date('M j, Y g:i A', strtotime($departure_time));
                        $changes[] = "Departure: {$old_time} → {$new_time}";
                    }
                    if ($old_schedule['available_seats'] != $available_seats) {
                        $changes[] = "Available Seats: {$old_schedule['available_seats']} → {$available_seats}";
                    }

                    // Only send notifications if there are actual changes
                    if (!empty($changes)) {
                        $notification_sql = "SELECT DISTINCT u.id, u.username, b.bus_number, r.source, r.destination
                                           FROM users u
                                           JOIN bookings bk ON u.id = bk.user_id
                                           JOIN schedules s ON bk.schedule_id = s.id
                                           JOIN buses b ON s.bus_id = b.id
                                           JOIN routes r ON s.route_id = r.id
                                           WHERE s.id = ? AND bk.status = 'Booked'";
                        $notification_stmt = $db->prepare($notification_sql);
                        $notification_stmt->execute([$_GET['id']]);
                        $affected_users = $notification_stmt->fetchAll(PDO::FETCH_ASSOC);

                        // Send system notifications to affected users (avoid duplicates)
                        foreach ($affected_users as $user) {
                            $change_details = implode('; ', $changes);
                            $message = "Schedule Updated: {$user['source']} → {$user['destination']} - {$change_details}";

                            // Check if similar notification already exists in the last 24 hours
                            $check_duplicate = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND message LIKE ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)');
                            $check_duplicate->execute([$user['id'], "Schedule Updated: {$user['source']} → {$user['destination']}%"]);

                            if ($check_duplicate->fetchColumn() == 0) {
                                $notification_insert = $db->prepare('INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)');
                                $notification_insert->execute([$user['id'], $message]);
                            }
                        }
                    }
                }
            } else {
                $stmt = $db->prepare('INSERT INTO schedules (bus_id, route_id, departure_time, available_seats) VALUES (?, ?, ?, ?)');
                $success = $stmt->execute([$bus_id, $route_id, $departure_time, $available_seats]);
            }
        }
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
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2rem 2rem 2.5rem;min-width:400px;max-width:600px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <a href="admin_schedules.php" class="back-btn" style="margin-bottom:1.2rem;"><i class="fa fa-arrow-left"></i> Back to Schedules</a>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-calendar-alt icon-red"></i> <?php echo isset($_GET['id']) ? 'Edit' : 'Add'; ?> Schedule</h1>
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:1.5rem; padding:1rem 1.5rem; border-radius:8px; border:none; background:#d4edda; color:#155724; font-weight:600; box-shadow:0 2px 8px rgba(40,167,69,0.15);">
                <i class="fa fa-check-circle" style="margin-right:0.5rem; color:#28a745;"></i>
                Schedule saved successfully! All changes have been applied.
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:1.5rem; padding:1rem 1.5rem; border-radius:8px; border:none; background:#f8d7da; color:#721c24; font-weight:600; box-shadow:0 2px 8px rgba(220,53,69,0.15);">
                <i class="fa fa-exclamation-triangle" style="margin-right:0.5rem; color:#dc3545;"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($is_completed_schedule): ?>
            <!-- READ-ONLY VIEW FOR COMPLETED SCHEDULES -->
            <div class="alert alert-info" style="margin-bottom:1.5rem; padding:1rem 1.5rem; border-radius:8px; border:none; background:#d1ecf1; color:#0c5460; font-weight:600; box-shadow:0 2px 8px rgba(23,162,184,0.15);">
                <i class="fa fa-info-circle" style="margin-right:0.5rem; color:#17a2b8;"></i>
                This schedule has completed bookings and cannot be modified. You can view the seat map to see booking details.
            </div>

            <?php
            // Get schedule details for display
            $display_stmt = $db->prepare('SELECT s.*, b.bus_number, b.license_plate, b.bus_type, b.company, b.capacity,
                                                 r.source, r.destination, r.fare
                                         FROM schedules s
                                         JOIN buses b ON s.bus_id = b.id
                                         JOIN routes r ON s.route_id = r.id
                                         WHERE s.id = ?');
            $display_stmt->execute([$_GET['id']]);
            $schedule_details = $display_stmt->fetch(PDO::FETCH_ASSOC);
            ?>

            <div class="schedule-details" style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                <h4 style="margin-bottom: 1rem; color: #495057;"><i class="fa fa-calendar-alt"></i> Schedule Details</h4>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <strong>Bus:</strong><br>
                        <span style="font-family: monospace;"><?php echo $schedule_details['bus_number']; ?> | <?php echo $schedule_details['license_plate']; ?> | <?php echo $schedule_details['bus_type']; ?></span><br>
                        <small class="text-muted"><?php echo $schedule_details['company']; ?> (<?php echo $schedule_details['capacity']; ?> seats)</small>
                    </div>
                    <div>
                        <strong>Route:</strong><br>
                        <span style="font-family: monospace;"><?php echo $schedule_details['source']; ?> → <?php echo $schedule_details['destination']; ?></span><br>
                        <small class="text-muted">Fare: RM <?php echo number_format($schedule_details['fare'], 2); ?></small>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <strong>Departure Time:</strong><br>
                        <span><?php echo date('M j, Y g:i A', strtotime($schedule_details['departure_time'])); ?></span>
                    </div>
                    <div>
                        <strong>Available Seats:</strong><br>
                        <span><?php echo $schedule_details['available_seats']; ?> seats</span>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem;">
                <a href="admin_seat_map.php?schedule_id=<?php echo $_GET['id']; ?>" class="btn btn-primary">
                    <i class="fa fa-th"></i> View Seat Map
                </a>
                <a href="admin_schedules.php" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back to Schedules
                </a>
            </div>

        <?php else: ?>
            <!-- EDITABLE FORM FOR NON-COMPLETED SCHEDULES -->
            <form method="post" action="" style="display:flex;flex-direction:column;gap:1rem;align-items:stretch;">
                <label>Bus:</label>
                <select name="bus_id" class="form-control" required style="font-family: monospace;">
                    <option value="">Select a bus...</option>
                    <?php foreach ($buses as $bus): ?>
                        <option value="<?php echo $bus['id']; ?>"
                                data-capacity="<?php echo $bus['capacity']; ?>"
                                <?php if ($schedule['bus_id'] == $bus['id']) echo 'selected'; ?>>
                            <?php echo $bus['bus_number']; ?> | <?php echo $bus['license_plate']; ?> | <?php echo $bus['bus_type']; ?> | <?php echo $bus['company']; ?> (<?php echo $bus['capacity']; ?> seats)
                        </option>
                    <?php endforeach; ?>
                </select>
            <small class="form-text text-muted" style="margin-bottom: 1rem;">
                <i class="fa fa-info-circle"></i> Format: Bus Number | License Plate | Type | Company (Capacity)
            </small>

            <label>Route:</label>
            <select name="route_id" class="form-control" required style="font-family: monospace;">
                <option value="">Select a route...</option>
                <?php foreach ($routes as $route): ?>
                    <option value="<?php echo $route['id']; ?>"
                            <?php if ($schedule['route_id'] == $route['id']) echo 'selected'; ?>>
                        <?php echo $route['source']; ?> → <?php echo $route['destination']; ?> | RM <?php echo number_format($route['fare'], 2); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="form-text text-muted" style="margin-bottom: 1rem;">
                <i class="fa fa-info-circle"></i> Format: Source → Destination | Fare
            </small>
            <label>Departure Time:</label>
            <input type="datetime-local" name="departure_time" class="form-control"
                   value="<?php echo htmlspecialchars($schedule['departure_time'] ?? ''); ?>"
                   min="<?php echo date('Y-m-d\TH:i'); ?>" required>
            <small class="form-text text-muted" style="margin-bottom: 1rem;">
                <i class="fa fa-info-circle"></i> Schedule must be at least in the future. Avoid conflicts within 2 hours of existing schedules for the same bus.
            </small>

            <label>Available Seats:</label>
            <input type="number" name="available_seats" id="available_seats" class="form-control"
                   value="<?php echo htmlspecialchars($schedule['available_seats'] ?? ''); ?>"
                   readonly style="background-color: #f8f9fa; cursor: not-allowed;">
            <small class="form-text text-muted" style="margin-bottom: 1rem;">
                <i class="fa fa-info-circle"></i> Available seats are automatically calculated: Bus Capacity - Booked Seats.
            </small>
            <script>
            // Prepare bus capacities in JS
            var busCapacities = {};
            <?php foreach ($buses as $bus): ?>
                busCapacities[<?php echo $bus['id']; ?>] = <?php echo $bus['capacity']; ?>;
            <?php endforeach; ?>

            function updateAvailableSeats() {
                var busSelect = document.querySelector('select[name=bus_id]');
                var availableSeatsInput = document.getElementById('available_seats');
                var selectedBusId = busSelect.value;

                if (busCapacities[selectedBusId]) {
                    // For new schedules, available seats = bus capacity
                    // For existing schedules, we'll calculate based on current bookings
                    <?php if (isset($_GET['id'])): ?>
                        // For editing existing schedule, keep current value but show capacity info
                        var currentAvailable = <?php echo $schedule['available_seats'] ?? 0; ?>;
                        var busCapacity = busCapacities[selectedBusId];
                        var bookedSeats = busCapacity - currentAvailable;
                        availableSeatsInput.value = Math.max(0, busCapacity - bookedSeats);
                    <?php else: ?>
                        // For new schedule, set to full bus capacity
                        availableSeatsInput.value = busCapacities[selectedBusId];
                    <?php endif; ?>
                } else {
                    availableSeatsInput.value = '';
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                var busSelect = document.querySelector('select[name=bus_id]');
                busSelect.addEventListener('change', updateAvailableSeats);
                updateAvailableSeats();
            });
            </script>
            <button type="submit" class="bus-action-btn" style="margin-top:0.5rem;align-self:flex-start;"><i class="fa fa-save"></i> Save</button>
        </form>
        <?php endif; ?>
    </div>
</main>
<?php require_once('../views/footer.php'); ?> 