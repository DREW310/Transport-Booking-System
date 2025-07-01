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
        <div class="table-responsive">
            <?php if (empty($routes)): ?>
                <div class="alert alert-warning" style="margin:1rem 0;">No routes yet, create one!</div>
            <?php else: ?>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Destination</th>
                        <th>Fare (RM)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($routes as $route): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($route['source']); ?></td>
                        <td><?php echo htmlspecialchars($route['destination']); ?></td>
                        <td><?php echo htmlspecialchars($route['fare']); ?></td>
                        <td>
                            <div class="bus-action-group">
                                <a href="admin_route_form.php?id=<?php echo $route['id']; ?>" class="bus-action-btn"><i class="fa fa-edit"></i> Edit</a>
                                <a href="admin_route_delete.php?id=<?php echo $route['id']; ?>" class="bus-action-btn" onclick="return confirm('Are you sure you want to delete this route?');"><i class="fa fa-trash"></i> Delete</a>
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
<?php require_once('../views/footer.php'); ?> 