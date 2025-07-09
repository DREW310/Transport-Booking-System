<?php
session_start();
require_once('../includes/db.php');
require_once('../controllers/userController.php');
require_once('../views/login_header.php');

$username = $password = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password (supports both hashed and plain text for backward compatibility)
        $password_valid = false;
        if (password_verify($password, $user['password'])) {
            // Password is hashed and verified
            $password_valid = true;
        } elseif ($password === $user['password']) {
            // Fallback for plain text passwords (legacy support)
            $password_valid = true;

            // Upgrade to hashed password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->execute([$hashed_password, $user['id']]);
        }

        if ($password_valid) {
            if (($user['is_staff'] ?? 0) == 1 || ($user['is_superuser'] ?? 0) == 1) {
                $_SESSION['user'] = $user;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_staff'] = $user['is_staff'];
                $_SESSION['is_superuser'] = $user['is_superuser'];
                header('Location: ../views/admin_dashboard.php');
                exit();
            } else {
                $error = "This login is for admin only.";
            }
        } else {
            $error = "Invalid credentials!";
        }
    } else {
        $error = "Invalid credentials!";
    }
}
?>
<div class="login-card admin-login-card">
    <div class="icon"><i class="fa fa-shield-alt"></i></div>
    <h2>Admin Login</h2>
    <?php if (!empty($error)) echo '<div class="error"><i class="fa fa-exclamation-triangle"></i>'.$error.'</div>'; ?>
    <form action="admin_login.php" method="post" autocomplete="off">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
        <button type="submit" class="btn"><i class="fa fa-shield-alt"></i> Login as Admin</button>
    </form>
    <a href="index.php" class="back-link">Back</a>
</div> 