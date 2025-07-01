<?php
session_start();
require_once('../includes/db.php');
require_once('../controllers/userController.php');
require_once('../views/header.php');

$username = $password = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username AND password = :password");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (($user['is_staff'] ?? 0) == 1 || ($user['is_superuser'] ?? 0) == 1) {
            $_SESSION['user'] = $user;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_staff'] = $user['is_staff'];
            $_SESSION['is_superuser'] = $user['is_superuser'];
            header('Location: ../views/admin_dashboard.php');
            exit();
        } else {
            $error = "This login is for admin/staff only.";
        }
    } else {
        $error = "Invalid credentials!";
    }
}
?>
<style>
    body { background: #fcf8e3; }
    .login-card { background: #ffeaea; max-width: 400px; margin: 5vw auto; border-radius: 16px; box-shadow: 0 8px 40px #e0dcc7; padding: 2.5rem 2rem; text-align: center; }
    .login-card .icon { font-size: 2.5rem; color: #e53935; margin-bottom: 0.7rem; }
    .login-card h2 { color: #e53935; font-size: 2rem; font-weight: 800; margin-bottom: 1.2rem; }
    .login-card label { font-weight: 700; color: #e53935; display: block; text-align: left; margin-bottom: 0.2rem; }
    .login-card input { width: 100%; padding: 0.7rem; border-radius: 7px; border: 2px solid #ffd6d6; margin-bottom: 1rem; background: #f7f7f7; font-size: 1.1rem; }
    .login-card input:focus { outline: none; border-color: #e53935; }
    .login-card .btn { width: 100%; background: #222; color: #fff; font-weight: 700; font-size: 1.1rem; border: none; border-radius: 7px; padding: 0.8rem 0; margin-top: 0.7rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.7rem; }
    .login-card .btn:hover { background: #444; }
    .login-card .error { color: #e53935; margin-bottom: 1rem; font-weight: 600; }
    .login-card .back-link { margin-top: 0.7rem; display: block; }
</style>
<div class="login-card">
    <div class="icon"><i class="fa fa-shield-alt"></i></div>
    <h2>Admin Login</h2>
    <?php if (!empty($error)) echo '<div class="error">'.$error.'</div>'; ?>
    <form action="admin_login.php" method="post" autocomplete="off">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" required>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
        <button type="submit" class="btn"><i class="fa fa-shield-alt"></i> Login as Admin</button>
    </form>
    <a href="index.php" class="back-link">Back</a>
</div> 