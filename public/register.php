<?php
require_once('../includes/db.php');
require_once('../controllers/userController.php');
require_once('../views/header.php');

$username = $email = $password = $password2 = $full_name = $phone = $address = '';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password2 = $_POST['password2'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Username validation
    if (!preg_match('/^[\w.@+-]{1,150}$/', $username)) {
        $errors[] = 'Username must be 150 characters or fewer. Letters, digits and @/./+/-/_ only.';
    }
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    // Password validation
    if ($password !== $password2) {
        $errors[] = 'Passwords do not match.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must contain at least 8 characters.';
    }
    if (preg_match('/^[0-9]+$/', $password)) {
        $errors[] = 'Password cannot be entirely numeric.';
    }
    if (stripos($password, $username) !== false || stripos($password, $full_name) !== false) {
        $errors[] = "Password can't be too similar to your other personal information.";
    }
    // Check for duplicate username or email
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $errors[] = 'Username or email already exists!';
    }
    if (empty($errors)) {
        // Insert into users
        $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        if ($stmt->execute()) {
            $user_id = $db->lastInsertId();
            // Insert into profiles
            $stmt2 = $db->prepare("INSERT INTO profiles (user_id, full_name, phone, address) VALUES (:user_id, :full_name, :phone, :address)");
            $stmt2->bindParam(':user_id', $user_id);
            $stmt2->bindParam(':full_name', $full_name);
            $stmt2->bindParam(':phone', $phone);
            $stmt2->bindParam(':address', $address);
            $stmt2->execute();
            $success = 'Registration successful! <a href=\'user_login.php\'>Login here</a>';
            $username = $email = $password = $password2 = $full_name = $phone = $address = '';
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>
<style>
    body { background: #fcf8e3; }
    .register-card { background: #fffbe6; max-width: 540px; margin: 2vw auto; border-radius: 12px; box-shadow: 0 8px 40px #e0dcc7; padding: 2.5rem 2.5rem 2rem 2.5rem; }
    .register-card h2 { color: #e53935; font-size: 2.3rem; font-weight: 800; text-align: center; margin-bottom: 1.2rem; }
    .register-card .icon { text-align: center; font-size: 2.5rem; color: #e53935; margin-bottom: 0.5rem; }
    .register-card label { font-weight: 700; color: #e53935; margin-top: 1rem; display: block; }
    .register-card input, .register-card textarea { width: 100%; padding: 0.7rem; border-radius: 7px; border: 2px solid #ffd6d6; margin-bottom: 0.5rem; background: #fff; font-size: 1.1rem; }
    .register-card input:focus, .register-card textarea:focus { outline: none; border-color: #e53935; }
    .register-card .help { color: #a67c52; font-size: 0.98rem; margin-bottom: 0.7rem; }
    .register-card .error-list { color: #e53935; margin-bottom: 1rem; }
    .register-card .error-list li { margin-bottom: 0.2rem; }
    .register-card .success { color: #388e3c; font-weight: 700; margin-bottom: 1rem; text-align: center; }
    .register-card .btn { width: 100%; background: #e53935; color: #fff; font-weight: 700; font-size: 1.2rem; border: none; border-radius: 7px; padding: 0.8rem 0; margin-top: 0.7rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.7rem; }
    .register-card .btn:hover { background: #b71c1c; }
    .register-card .login-link { text-align: center; margin-top: 1.2rem; font-size: 1.08rem; }
    .register-card .login-link a { color: #e53935; font-weight: 700; text-decoration: underline; }
</style>
<div class="register-card">
    <div class="icon"><i class="fa fa-user-plus"></i></div>
    <h2>Create Your Account</h2>
    <?php if (!empty($errors)) { echo '<ul class="error-list">'; foreach ($errors as $e) echo '<li>'.$e.'</li>'; echo '</ul>'; } ?>
    <?php if ($success) { echo '<div class="success">'.$success.'</div>'; } ?>
    <form action="register.php" method="post" autocomplete="off">
        <label for="username">Username:<span style="color:#e53935">*</span></label>
        <input type="text" name="username" id="username" maxlength="150" value="<?php echo htmlspecialchars($username); ?>" required>
        <div class="help">Required. 150 characters or fewer. Letters, digits and @/./+/-/_ only.</div>
        <label for="email">Email:<span style="color:#e53935">*</span></label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>
        <label for="password">Password:<span style="color:#e53935">*</span></label>
        <input type="password" name="password" id="password" required>
        <ul class="help" style="color:#e53935;">
            <li>Your password can't be too similar to your other personal information.</li>
            <li>Your password must contain at least 8 characters.</li>
            <li>Your password can't be a commonly used password.</li>
            <li>Your password can't be entirely numeric.</li>
        </ul>
        <label for="password2">Password confirmation:<span style="color:#e53935">*</span></label>
        <input type="password" name="password2" id="password2" required>
        <div class="help">Enter the same password as before, for verification.</div>
        <label for="full_name">Full Name:</label>
        <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($full_name); ?>">
        <label for="phone">Phone:</label>
        <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($phone); ?>">
        <label for="address">Address:</label>
        <textarea name="address" id="address"><?php echo htmlspecialchars($address); ?></textarea>
        <button type="submit" class="btn"><i class="fa fa-user-plus"></i> Register</button>
    </form>
    <div class="login-link">Already have an account? <a href="user_login.php">Login here.</a></div>
</div>
