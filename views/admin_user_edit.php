<?php
require_once('../includes/db.php');
session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
$db = getDB();
$user = null;
$success = false;
$error = '';
if (!isset($_GET['id'])) {
    header('Location: admin_users.php');
    exit();
}
$id = $_GET['id'];
$stmt = $db->prepare('SELECT u.*, p.full_name, p.phone, p.address FROM users u LEFT JOIN profiles p ON u.id = p.user_id WHERE u.id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    $error = 'User not found!';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $is_staff = ($role === 'Admin' || $role === 'Superuser') ? 1 : 0;
    $is_superuser = ($role === 'Superuser') ? 1 : 0;
    $stmt = $db->prepare('UPDATE users SET username=?, email=?, is_staff=?, is_superuser=? WHERE id=?');
    $success = $stmt->execute([$username, $email, $is_staff, $is_superuser, $id]);
    // Update profiles table
    $stmt2 = $db->prepare('UPDATE profiles SET full_name=?, phone=?, address=? WHERE user_id=?');
    $stmt2->execute([$full_name, $phone, $address, $id]);
    if ($success) {
        $stmt = $db->prepare('SELECT u.*, p.full_name, p.phone, p.address FROM users u LEFT JOIN profiles p ON u.id = p.user_id WHERE u.id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = 'Failed to update user.';
    }
}
?>
<?php require_once('../views/header.php'); ?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:400px;max-width:600px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <a href="admin_users.php" class="btn btn-warning" style="margin-bottom:1.2rem;"><i class="fa fa-arrow-left"></i> Return to Users</a>
        <a href="admin_dashboard.php" class="btn btn-secondary" style="margin-bottom:1.2rem;margin-left:1rem;"><i class="fa fa-cogs"></i> Return to Admin Panel</a>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-user-edit icon-red"></i> Edit User</h1>
        <?php if ($success): ?>
            <div class="alert alert-info" style="margin-bottom:1rem;">User updated successfully!</div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:1rem;">Error: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($user): ?>
        <form method="post" action="">
            <label>Username:</label>
            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            <label>Email:</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            <label>Role:</label>
            <select name="role" class="form-control" required>
                <option value="User" <?php if (!$user['is_staff'] && !$user['is_superuser']) echo 'selected'; ?>>User</option>
                <option value="Admin" <?php if ($user['is_staff'] && !$user['is_superuser']) echo 'selected'; ?>>Admin</option>
                <option value="Superuser" <?php if ($user['is_superuser']) echo 'selected'; ?>>Superuser</option>
            </select>
            <label>Full name:</label>
            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            <label>Phone:</label>
            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
            <label>Address:</label>
            <textarea name="address" class="form-control" required><?php echo htmlspecialchars($user['address']); ?></textarea>
            <button type="submit" class="btn btn-primary" style="margin-top:1rem;"><i class="fa fa-save"></i> Save</button>
        </form>
        <?php endif; ?>
    </div>
</main>
<?php require_once('../views/footer.php'); ?> 