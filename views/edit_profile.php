<?php
require_once('../includes/db.php');
require_once('header.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../public/login.php');
    exit();
}
$db = getDB();
$user_id = $_SESSION['user']['id'];
$success = false;
$error = '';
// Fetch current info
$sql = 'SELECT u.username, u.email, u.is_staff, u.is_superuser, p.full_name, p.phone, p.address FROM users u LEFT JOIN profiles p ON u.id = p.user_id WHERE u.id = ?';
$stmt = $db->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    // Update profiles table
    $stmt = $db->prepare('UPDATE profiles SET full_name=?, phone=?, address=? WHERE user_id=?');
    $stmt->execute([$full_name, $phone, $address, $user_id]);
    header('Location: profile.php?success=1');
    exit();
}
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2rem 2rem 2.5rem;min-width:400px;max-width:600px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-user icon-red"></i> Edit Profile</h1>
        <?php if ($success): ?><div class="alert alert-info">Profile updated successfully!</div><?php endif; ?>
        <form method="post" action="">
            <div style="background:#fffde7;border-radius:8px;padding:1.5rem 2rem;margin-bottom:2rem;">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?> (Cannot be changed)</p>
                <p><strong>Role:</strong> <?php echo ($user['is_superuser'] ? 'Admin' : ($user['is_staff'] ? 'Admin' : 'User')); ?> (Cannot be changed)</p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?> (Cannot be changed)</p>
            </div>
            <label>Full name:</label>
            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            <label>Phone:</label>
            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
            <label>Address:</label>
            <textarea name="address" class="form-control" required><?php echo htmlspecialchars($user['address']); ?></textarea>
            <button type="submit" class="btn btn-primary" style="margin-top:1rem;"><i class="fa fa-save"></i> Update Profile</button>
            <a href="profile.php" class="btn btn-warning" style="margin-top:1rem;">Discard</a>
        </form>
    </div>
</main>
<?php require_once('footer.php'); ?> 