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
<div class="register-card">
    <div class="icon"><i class="fa fa-user-plus"></i></div>
    <h2>Create Your Account</h2>
    <?php if (!empty($errors)) { echo '<ul class="error-list">'; foreach ($errors as $e) echo '<li>'.$e.'</li>'; echo '</ul>'; } ?>
    <?php if ($success) { echo '<div class="success">'.$success.'</div>'; } ?>
    <form action="register.php" method="post" autocomplete="off">
        <label for="username">Username:<span class="required-asterisk">*</span></label>
        <input type="text" name="username" id="username" maxlength="150" value="<?php echo htmlspecialchars($username); ?>" required>
        <div class="help">Required. 150 characters or fewer. Letters, digits and @/./+/-/_ only.</div>
        <label for="email">Email:<span class="required-asterisk">*</span></label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>
        <label for="password">Password:<span class="required-asterisk">*</span></label>
        <input type="password" name="password" id="password" required>
        <ul class="help">
            <li>Your password can't be too similar to your other personal information.</li>
            <li>Your password must contain at least 8 characters.</li>
            <li>Your password can't be a commonly used password.</li>
            <li>Your password can't be entirely numeric.</li>
        </ul>
        <label for="password2">Password confirmation:<span class="required-asterisk">*</span></label>
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
