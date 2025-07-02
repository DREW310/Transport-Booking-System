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
$sql = 'SELECT u.username, u.email, u.is_staff, u.is_superuser, p.full_name, p.phone, p.address
        FROM users u
        LEFT JOIN profiles p ON u.id = p.user_id
        WHERE u.id = ?';
$stmt = $db->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:400px;max-width:600px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-user icon-red"></i> Profile</h1>
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-info">Profile updated successfully!</div>
        <?php endif; ?>
        <div style="display:flex;flex-direction:column;gap:0.7rem;background:#fffde7;border-radius:8px;padding:1.5rem 2rem;margin-bottom:2rem;">
            <div style="display:flex;gap:1.2rem;"><span style="font-weight:700;min-width:120px;">Username:</span><span><?php echo htmlspecialchars($user['username']); ?></span></div>
            <div style="display:flex;gap:1.2rem;"><span style="font-weight:700;min-width:120px;">Role:</span><span><?php echo ($user['is_superuser'] ? 'Admin' : ($user['is_staff'] ? 'Admin' : 'User')); ?></span></div>
            <div style="display:flex;gap:1.2rem;"><span style="font-weight:700;min-width:120px;">Full Name:</span><span><?php echo htmlspecialchars($user['full_name']); ?></span></div>
            <div style="display:flex;gap:1.2rem;"><span style="font-weight:700;min-width:120px;">Email:</span><span><?php echo htmlspecialchars($user['email']); ?></span></div>
            <div style="display:flex;gap:1.2rem;"><span style="font-weight:700;min-width:120px;">Phone:</span><span><?php echo htmlspecialchars($user['phone']); ?></span></div>
            <div style="display:flex;gap:1.2rem;"><span style="font-weight:700;min-width:120px;">Address:</span><span><?php echo htmlspecialchars($user['address']); ?></span></div>
        </div>
        <div style="display:flex;justify-content:center;">
            <a href="edit_profile.php" class="bus-action-btn"><i class="fa fa-edit"></i> Edit Profile</a>
        </div>
    </div>
</main>
<?php require_once('footer.php'); ?> 