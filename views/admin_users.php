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
    <div class="card" style="margin-top:2.5rem;padding:2rem;min-width:600px;max-width:1200px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
            <a href="admin_dashboard.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
            <?php if ($_SESSION['user']['is_superuser']): ?>
                <a href="admin_user_create.php" class="add-bus-btn"><i class="fa fa-plus-circle"></i> Create New User</a>
            <?php endif; ?>
        </div>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-users icon-red"></i> All Users</h1>
        <div class="table-responsive">
            <?php if (empty($users)): ?>
                <div class="alert alert-info" style="margin:1.5rem 0; padding:2rem; border-radius:12px; border:none; background:#d1ecf1; color:#0c5460; text-align:center; box-shadow:0 2px 8px rgba(23,162,184,0.15);">
                    <div style="font-size:3rem; color:#17a2b8; margin-bottom:1rem;">
                        <i class="fa fa-users"></i>
                    </div>
                    <h4 style="color:#0c5460; margin-bottom:0.5rem;">No Users Found</h4>
                    <p style="margin-bottom:1rem; color:#0c5460; opacity:0.8;">The user list is empty. Create your first user account.</p>
                    <?php if ($_SESSION['user']['is_superuser']): ?>
                        <a href="admin_user_create.php" class="bus-action-btn">
                            <i class="fa fa-plus"></i> Create First User
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <table class="table table-striped table-bordered" style="font-size: 0.85rem;">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th><i class="fa fa-user"></i> Username</th>
                        <th><i class="fa fa-envelope"></i> Email</th>
                        <th><i class="fa fa-shield-alt"></i> Role</th>
                        <th><i class="fa fa-id-card"></i> Full Name</th>
                        <th><i class="fa fa-phone"></i> Phone</th>
                        <th><i class="fa fa-map-marker-alt"></i> Address</th>
                        <th><i class="fa fa-cogs"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #e53935;">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #2e7d32;">
                                <?php
                                    if ($user['is_superuser']) echo 'Superuser';
                                    elseif ($user['is_staff']) echo 'Admin';
                                    else echo 'User';
                                ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo htmlspecialchars($user['full_name'] ?? '-'); ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo htmlspecialchars($user['phone'] ?? '-'); ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500; max-width: 200px; word-wrap: break-word;">
                                <?php echo htmlspecialchars($user['address'] ?? '-'); ?>
                            </div>
                        </td>
                        <td>
                            <div class="bus-action-group">
                                <a href="admin_user_edit.php?id=<?php echo $user['id']; ?>"
                                   style="font-size: 0.8rem; padding: 6px 12px; background: #4caf50; color: white; border: none; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; box-shadow: 0 2px 4px rgba(76,175,80,0.3); transition: all 0.2s ease;">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                                <a href="admin_user_delete.php?id=<?php echo $user['id']; ?>"
                                   style="font-size: 0.8rem; padding: 6px 12px; background: #f44336; color: white; border: none; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; box-shadow: 0 2px 4px rgba(244,67,54,0.3); transition: all 0.2s ease;"
                                   onclick="return confirm('Are you sure you want to delete this user?');">
                                    <i class="fa fa-trash"></i> Delete
                                </a>
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

<?php require_once('../views/footer.php'); ?>