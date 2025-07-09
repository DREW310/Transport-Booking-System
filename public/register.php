<?php
require_once('../includes/db.php');
require_once('../controllers/userController.php');
require_once('../views/auth_header.php');

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

    // Required field validation
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (!preg_match('/^[\w.@+-]{1,150}$/', $username)) {
        $errors[] = 'Username must be 150 characters or fewer. Letters, digits and @/./+/-/_ only.';
    }

    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must contain at least 8 characters.';
    } elseif (preg_match('/^[0-9]+$/', $password)) {
        $errors[] = 'Password cannot be entirely numeric.';
    } elseif (stripos($password, $username) !== false || stripos($password, $full_name) !== false) {
        $errors[] = "Password can't be too similar to your other personal information.";
    }

    if (empty($password2)) {
        $errors[] = 'Password confirmation is required.';
    } elseif ($password !== $password2) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($full_name)) {
        $errors[] = 'Full Name is required.';
    }

    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    }

    if (empty($address)) {
        $errors[] = 'Address is required.';
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
        // Hash password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into users
        $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        if ($stmt->execute()) {
            $user_id = $db->lastInsertId();
            // Insert into profiles
            $stmt2 = $db->prepare("INSERT INTO profiles (user_id, full_name, phone, address) VALUES (:user_id, :full_name, :phone, :address)");
            $stmt2->bindParam(':user_id', $user_id);
            $stmt2->bindParam(':full_name', $full_name);
            $stmt2->bindParam(':phone', $phone);
            $stmt2->bindParam(':address', $address);
            $stmt2->execute();
            $success = 'Registration successful! You can now login with your new account.';
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
    <?php if (!empty($errors)): ?>
        <div class="error-list">
            <div class="error-header">
                <i class="fa fa-exclamation-triangle"></i>
                Please correct the following errors:
            </div>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success">
            <div class="success-header">
                <i class="fa fa-check-circle"></i>
                Registration Successful!
            </div>
            <p><?php echo htmlspecialchars($success); ?></p>
            <div class="success-actions">
                <a href="user_login.php" class="btn btn-success">
                    <i class="fa fa-sign-in-alt"></i> Login Now
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fa fa-home"></i> Go to Home
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form action="register.php" method="post" autocomplete="off">
        <label for="username">Username:<span class="required-asterisk">*</span></label>
        <input type="text" name="username" id="username" maxlength="150" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
        <div class="help">Required. 150 characters or fewer. Letters, digits and @/./+/-/_ only.</div>
        <label for="email">Email:<span class="required-asterisk">*</span></label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
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
        <label for="full_name">Full Name:<span class="required-asterisk">*</span></label>
        <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
        <label for="phone">Phone:<span class="required-asterisk">*</span></label>
        <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" required>
        <label for="address">Address:<span class="required-asterisk">*</span></label>
        <textarea name="address" id="address" required><?php echo htmlspecialchars($address ?? ''); ?></textarea>
        <button type="submit" class="btn"><i class="fa fa-user-plus"></i> Register</button>
    </form>
    <div class="login-link">Already have an account? <a href="user_login.php">Login here.</a></div>
    <?php endif; ?>
</div>
