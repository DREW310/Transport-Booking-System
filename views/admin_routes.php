<?php
require_once('../includes/db.php');
require_once('../views/header.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
$db = getDB();
$routes = [];
$stmt = $db->query('SELECT * FROM routes');
if ($stmt) {
    $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:600px;max-width:900px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
            <a href="admin_dashboard.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
            <a href="admin_route_form.php" class="add-bus-btn"><i class="fa fa-plus-circle"></i> Add Route</a>
        </div>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-road icon-red"></i> Manage Routes</h1>

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
            <?php if (empty($routes)): ?>
                <div class="alert alert-warning" style="margin:1rem 0;">No routes yet, create one!</div>
            <?php else: ?>
            <table class="table table-striped table-bordered" style="font-size: 0.85rem;">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th><i class="fa fa-map-marker-alt"></i> Source</th>
                        <th><i class="fa fa-flag-checkered"></i> Destination</th>
                        <th><i class="fa fa-money-bill"></i> Fare</th>
                        <th><i class="fa fa-cogs"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($routes as $route): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #28a745;">
                                <?php echo htmlspecialchars($route['source'] ?? ''); ?>
                            </div>
                            <small style="color: #666;">
                                Origin City
                            </small>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #dc3545;">
                                <?php echo htmlspecialchars($route['destination'] ?? ''); ?>
                            </div>
                            <small style="color: #666;">
                                Destination City
                            </small>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #2e7d32;">
                                RM <?php echo number_format($route['fare'] ?? 0, 2); ?>
                            </div>
                            <small style="color: #666;">
                                Ticket Price
                            </small>
                        </td>
                        <td>
                            <?php
                            // Check if this route has any bookings (active or completed)
                            $booking_check = $db->prepare('SELECT COUNT(*) FROM bookings b JOIN schedules s ON b.schedule_id = s.id WHERE s.route_id = ?');
                            $booking_check->execute([$route['id']]);
                            $has_bookings = $booking_check->fetchColumn() > 0;
                            ?>
                            <div class="bus-action-group">
                                <!-- Edit button - Always available -->
                                <a href="admin_route_form.php?id=<?php echo $route['id']; ?>"
                                   style="font-size: 0.8rem; padding: 6px 12px; background: #4caf50; color: white; border: none; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; box-shadow: 0 2px 4px rgba(76,175,80,0.3); transition: all 0.2s ease;">
                                    <i class="fa fa-edit"></i> Edit
                                </a>

                                <?php if ($has_bookings): ?>
                                    <!-- Disabled delete for routes with booking history -->
                                    <span style="font-size: 0.8rem; padding: 6px 12px; background: #f44336; color: white; border: none; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; opacity: 0.5; cursor: not-allowed;"
                                          title="Cannot delete - Route has booking history">
                                        <i class="fa fa-ban"></i> Has Records
                                    </span>
                                <?php else: ?>
                                    <!-- Enabled delete for routes without bookings -->
                                    <a href="admin_route_delete.php?id=<?php echo $route['id']; ?>"
                                       style="font-size: 0.8rem; padding: 6px 12px; background: #f44336; color: white; border: none; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; box-shadow: 0 2px 4px rgba(244,67,54,0.3); transition: all 0.2s ease;"
                                       onclick="return confirm('Are you sure you want to delete this route?');">
                                        <i class="fa fa-trash"></i> Delete
                                    </a>
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