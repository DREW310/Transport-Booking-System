<?php
require_once('../views/header.php');
require_once('../includes/db.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
$db = getDB();
$users = [];
$sql = 'SELECT u.id, u.username, u.email, u.is_staff, u.is_superuser, p.full_name, p.phone, p.address FROM users u LEFT JOIN profiles p ON u.id = p.user_id ORDER BY u.username';
$stmt = $db->query($sql);
if ($stmt) {
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:900px;max-width:1300px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <a href="admin_dashboard.php" class="back-btn"><i class="fa fa-arrow-left"></i> Return to Admin Panel</a>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-users icon-red"></i> All Users</h1>
        <div class="table-responsive">
            <?php if (empty($users)): ?>
                <div class="alert alert-warning" style="margin:1rem 0;">No users found.</div>
            <?php else: ?>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php
                                if ($user['is_superuser']) echo 'Superuser';
                                elseif ($user['is_staff']) echo 'Admin';
                                else echo 'User';
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td><?php echo htmlspecialchars($user['address']); ?></td>
                        <td>
                            <div class="bus-action-group">
                                <a href="admin_user_edit.php?id=<?php echo $user['id']; ?>" class="bus-action-btn"><i class="fa fa-edit"></i> Edit</a>
                                <a href="admin_user_delete.php?id=<?php echo $user['id']; ?>" class="bus-action-btn" onclick="return confirm('Are you sure you want to delete this user?');"><i class="fa fa-trash"></i> Delete</a>
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