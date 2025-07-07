<?php
require_once('../includes/db.php');
require_once('../views/header.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
$db = getDB();
$schedules = [];

// Enhanced query to get comprehensive schedule information
$sql = 'SELECT s.id, s.departure_time, s.available_seats,
               b.bus_number, b.license_plate, b.bus_type, b.capacity, b.company,
               r.source, r.destination, r.fare
        FROM schedules s
        JOIN buses b ON s.bus_id = b.id
        JOIN routes r ON s.route_id = r.id
        ORDER BY s.departure_time ASC';
$stmt = $db->query($sql);
if ($stmt) {
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get booking counts for each schedule
$booking_counts = [];
if (!empty($schedules)) {
    $schedule_ids = array_column($schedules, 'id');
    $placeholders = str_repeat('?,', count($schedule_ids) - 1) . '?';
    $booking_sql = "SELECT schedule_id, COUNT(*) as booking_count
                    FROM bookings
                    WHERE schedule_id IN ($placeholders) AND status IN ('Booked', 'Completed')
                    GROUP BY schedule_id";
    $booking_stmt = $db->prepare($booking_sql);
    $booking_stmt->execute($schedule_ids);
    $booking_results = $booking_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($booking_results as $result) {
        $booking_counts[$result['schedule_id']] = $result['booking_count'];
    }
}
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2rem;min-width:600px;max-width:1200px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
            <a href="admin_dashboard.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
            <a href="admin_schedule_form.php" class="add-bus-btn"><i class="fa fa-plus-circle"></i> Add Schedule</a>
        </div>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-calendar-alt icon-red"></i> Manage Schedules</h1>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger" style="margin-bottom:1.5rem; padding:1.5rem; border-radius:12px; border:none; background:#f8d7da; color:#721c24; font-weight:600; box-shadow:0 4px 16px rgba(220,53,69,0.2); text-align:center;">
                <div style="font-size:2.5rem; color:#dc3545; margin-bottom:1rem;">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
                <h4 style="color:#721c24; margin-bottom:0.5rem;">Operation Failed</h4>
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

        <div class="table-responsive">
            <?php if (empty($schedules)): ?>
                <div class="alert alert-warning" style="margin:1rem 0;">No schedules yet, create one!</div>
            <?php else: ?>
            <table class="table table-striped table-bordered" style="font-size: 0.85rem;">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th><i class="fa fa-bus"></i> Bus</th>
                        <th><i class="fa fa-route"></i> Route</th>
                        <th><i class="fa fa-money-bill"></i> Fare</th>
                        <th><i class="fa fa-clock"></i> Departure</th>
                        <th><i class="fa fa-users"></i> Seats</th>
                        <th><i class="fa fa-cogs"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule):
                        $booked_seats = $booking_counts[$schedule['id']] ?? 0;
                        $occupancy_rate = ($booked_seats / $schedule['capacity']) * 100;
                        $is_past = strtotime($schedule['departure_time']) < time();
                        $is_soon = strtotime($schedule['departure_time']) < (time() + 3600); // 1 hour
                    ?>
                    <tr style="<?php echo $is_past ? 'opacity: 0.6; background: #f8f9fa;' : ''; ?>">
                        <td>
                            <div style="font-weight: 600; color: #e53935;">
                                <?php echo htmlspecialchars($schedule['bus_number']); ?>
                            </div>
                            <small style="color: #666;">
                                <?php echo htmlspecialchars($schedule['company']); ?> | <?php echo htmlspecialchars($schedule['license_plate']); ?>
                            </small>
                        </td>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo htmlspecialchars($schedule['source']); ?>
                                <i class="fa fa-arrow-right" style="color: #666; margin: 0 4px;"></i>
                                <?php echo htmlspecialchars($schedule['destination']); ?>
                            </div>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: #2e7d32;">
                                RM <?php echo number_format($schedule['fare'], 2); ?>
                            </span>
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
                            <div style="font-weight: 600;">
                                <?php echo $booked_seats; ?>/<?php echo $schedule['capacity']; ?>
                            </div>
                            <small style="color: #666;">
                                <?php echo $schedule['available_seats']; ?> available
                            </small>
                            <?php if ($is_past): ?>
                                <div style="color: #666; font-size: 0.75rem;">
                                    <i class="fa fa-clock"></i> Past
                                </div>
                            <?php elseif ($is_soon): ?>
                                <div style="color: #ff9800; font-size: 0.75rem; font-weight: 600;">
                                    <i class="fa fa-exclamation-triangle"></i> Soon
                                </div>
                            <?php elseif ($schedule['available_seats'] == 0): ?>
                                <div style="color: #f44336; font-size: 0.75rem; font-weight: 600;">
                                    <i class="fa fa-ban"></i> Full
                                </div>
                            <?php else: ?>
                                <div style="color: #4caf50; font-size: 0.75rem; font-weight: 600;">
                                    <i class="fa fa-check-circle"></i> Active
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            // Check if this schedule has completed bookings
                            $completed_check = $db->prepare('SELECT COUNT(*) FROM bookings WHERE schedule_id = ? AND status = "Completed"');
                            $completed_check->execute([$schedule['id']]);
                            $has_completed = $completed_check->fetchColumn() > 0;

                            $hasBookings = !empty($booking_counts[$schedule['id']]);
                            ?>
                            <div class="bus-action-group">
                                <!-- Seat Map - Always available -->
                                <a href="admin_seat_map.php?schedule_id=<?php echo $schedule['id']; ?>"
                                   style="font-size: 0.8rem; padding: 6px 12px; background: #2196f3; color: white; border: none; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; box-shadow: 0 2px 4px rgba(33,150,243,0.3); transition: all 0.2s ease;">
                                    <i class="fa fa-th"></i> Seat Map
                                </a>

                                <?php if ($has_completed): ?>
                                    <!-- Read-only view for completed schedules -->
                                    <a href="admin_schedule_form.php?id=<?php echo $schedule['id']; ?>"
                                       style="font-size: 0.8rem; padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; box-shadow: 0 2px 4px rgba(108,117,125,0.3); transition: all 0.2s ease;"
                                       title="View only - Schedule has completed bookings">
                                        <i class="fa fa-eye"></i> View
                                    </a>
                                    <!-- No delete button for completed schedules -->
                                    <span style="font-size: 0.8rem; padding: 6px 12px; background: #dc3545; color: white; border: none; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; opacity: 0.5; cursor: not-allowed;"
                                          title="Cannot delete - Schedule has completed bookings">
                                        <i class="fa fa-ban"></i> Locked
                                    </span>
                                <?php else: ?>
                                    <!-- Editable for non-completed schedules -->
                                    <a href="admin_schedule_form.php?id=<?php echo $schedule['id']; ?>"
                                       style="font-size: 0.8rem; padding: 6px 12px; background: #4caf50; color: white; border: none; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; box-shadow: 0 2px 4px rgba(76,175,80,0.3); transition: all 0.2s ease;">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>

                                    <?php if ($hasBookings): ?>
                                        <!-- Disabled delete for schedules with active bookings -->
                                        <span style="font-size: 0.8rem; padding: 6px 12px; background: #f44336; color: white; border: none; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; opacity: 0.5; cursor: not-allowed;"
                                              title="Cannot delete - Schedule has <?php echo $booking_counts[$schedule['id']]; ?> active booking(s)">
                                            <i class="fa fa-ban"></i> Has Bookings
                                        </span>
                                    <?php else: ?>
                                        <!-- Enabled delete for schedules without bookings -->
                                        <a href="admin_schedule_delete.php?id=<?php echo $schedule['id']; ?>"
                                           style="font-size: 0.8rem; padding: 6px 12px; background: #f44336; color: white; border: none; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; box-shadow: 0 2px 4px rgba(244,67,54,0.3); transition: all 0.2s ease;"
                                           onclick="return confirm('Are you sure you want to delete this schedule?');">
                                            <i class="fa fa-trash"></i> Delete
                                        </a>
                                    <?php endif; ?>
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