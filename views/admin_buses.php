<?php
require_once('../includes/db.php');
require_once('../views/header.php');
session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
$db = getDB();
$buses = [];
$stmt = $db->query('SELECT * FROM buses');
if ($stmt) {
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function formatBusId($id) {
    return 'B' . str_pad($id, 4, '0', STR_PAD_LEFT);
}
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:600px;max-width:900px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
            <a href="admin_dashboard.php" class="btn btn-warning"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
            <a href="admin_bus_form.php" class="btn btn-primary"><i class="fa fa-plus-circle"></i> Add Bus</a>
        </div>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-bus icon-red"></i> Manage Buses</h1>
        <div class="table-responsive">
            <?php if (empty($buses)): ?>
                <div class="alert alert-warning" style="margin:1rem 0;">No buses yet, create one!</div>
            <?php else: ?>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Bus ID</th>
                        <th>Bus Type</th>
                        <th>Capacity</th>
                        <th>Company</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($buses as $bus): ?>
                    <tr>
                        <td><?php echo formatBusId($bus['id']); ?></td>
                        <td><?php echo htmlspecialchars($bus['bus_type']); ?></td>
                        <td><?php echo htmlspecialchars($bus['capacity']); ?></td>
                        <td><?php echo htmlspecialchars($bus['company']); ?></td>
                        <td>
                            <a href="admin_bus_form.php?id=<?php echo $bus['id']; ?>" class="btn btn-success btn-sm"><i class="fa fa-edit"></i> Edit</a>
                            <a href="admin_bus_delete.php?id=<?php echo $bus['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this bus?');"><i class="fa fa-trash"></i> Delete</a>
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