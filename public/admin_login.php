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
            $error = "This login is for admin only.";
        }
    } else {
        $error = "Invalid credentials!";
    }
}
?>
<div class="login-card admin-login-card">
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