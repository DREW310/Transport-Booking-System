<?php
require_once('../includes/db.php');
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

// If user not found, redirect to login
if (!$user) {
    header('Location: ../public/login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Validate required fields
    if (empty($full_name)) {
        $error = 'Full name is required.';
    } elseif (empty($phone)) {
        $error = 'Phone number is required.';
    } elseif (empty($address)) {
        $error = 'Address is required.';
    } else {
        try {
            // Check if profile exists
            $check_stmt = $db->prepare('SELECT id FROM profiles WHERE user_id = ?');
            $check_stmt->execute([$user_id]);

            if ($check_stmt->rowCount() > 0) {
                // Update existing profile
                $stmt = $db->prepare('UPDATE profiles SET full_name=?, phone=?, address=? WHERE user_id=?');
                $stmt->execute([$full_name, $phone, $address, $user_id]);
            } else {
                // Create new profile
                $stmt = $db->prepare('INSERT INTO profiles (user_id, full_name, phone, address) VALUES (?, ?, ?, ?)');
                $stmt->execute([$user_id, $full_name, $phone, $address]);
            }

            header('Location: profile.php?success=1');
            exit();
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<?php require_once('header.php'); ?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2rem 2rem 2.5rem;min-width:400px;max-width:600px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-user icon-red"></i> Edit Profile</h1>
        <?php if ($success): ?><div class="alert alert-info">Profile updated successfully!</div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="post" action="" style="display:flex;flex-direction:column;gap:1rem;align-items:stretch;">
            <div style="background:#fffde7;border-radius:8px;padding:1.5rem 2rem;margin-bottom:2rem;">
                <div><label style="font-weight:700;color:#5A9FD4;">Username:</label> <?php echo htmlspecialchars($user['username']); ?> <span style="color:#888;">(Cannot be changed)</span></div>
                <div><label style="font-weight:700;color:#5A9FD4;">Role:</label> <?php echo ($user['is_superuser'] ? 'Admin' : ($user['is_staff'] ? 'Admin' : 'User')); ?> <span style="color:#888;">(Cannot be changed)</span></div>
                <div><label style="font-weight:700;color:#5A9FD4;">Email:</label> <?php echo htmlspecialchars($user['email']); ?> <span style="color:#888;">(Cannot be changed)</span></div>
            </div>
            <label class="profile-label">Full name:</label>
            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
            <label class="profile-label">Phone:</label>
            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
            <label class="profile-label">Address:</label>
            <textarea name="address" class="form-control" style="height:80px;min-height:38px;max-height:120px;" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
            <div class="profile-form-actions">
                <button type="submit" class="bus-action-btn"><i class="fa fa-save"></i> Update Profile</button>
                <a href="profile.php" class="btn btn-warning">Discard</a>
            </div>
        </form>
    </div>
</main>
<?php require_once('footer.php'); ?> 