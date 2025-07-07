<?php
require_once('../includes/db.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
$db = getDB();
$user = null;
$error = '';
$deleted = false;
if (!isset($_GET['id'])) {
    header('Location: admin_users.php');
    exit();
}
$id = $_GET['id'];
$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    $error = 'User not found!';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
    if ($stmt->execute([$id])) {
        $deleted = true;
    } else {
        $error = 'Failed to delete user.';
    }
}
?>
<?php require_once('../views/header.php'); ?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:400px;max-width:600px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <a href="admin_users.php" class="btn btn-warning" style="margin-bottom:1.2rem;"><i class="fa fa-arrow-left"></i> Return to Users</a>
        <a href="admin_dashboard.php" class="btn btn-secondary" style="margin-bottom:1.2rem;margin-left:1rem;"><i class="fa fa-cogs"></i> Back to Dashboard</a>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-user-times icon-red"></i> Delete User</h1>
        <?php if ($deleted): ?>
            <div class="alert alert-success" style="margin-bottom:1rem;">User deleted successfully.</div>
            <a href="admin_users.php" class="btn btn-primary">Back to Users</a>
        <?php elseif ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:1rem;">Error: <?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($user): ?>
            <div class="alert alert-warning" style="margin-bottom:1rem;">Are you sure you want to delete user <strong><?php echo htmlspecialchars($user['username']); ?></strong>?</div>
            <form method="post" action="">
                <button type="submit" class="btn btn-danger"><i class="fa fa-trash"></i> Yes, Delete</button>
                <a href="admin_users.php" class="btn btn-secondary" style="margin-left:1rem;">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</main>
<?php require_once('../views/footer.php'); ?> 