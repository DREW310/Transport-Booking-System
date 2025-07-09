<?php
/*
===========================================
STUDENT PROJECT: Simple Password Reset
FILE: forgot_password.php
PURPOSE: Simple password reset without email complexity
STUDENT LEARNING: Basic form processing, database operations, security
TECHNOLOGIES: PHP, MySQL, PDO, HTML5
===========================================
*/

require_once('../includes/db.php');
require_once('../views/auth_header.php');

// Initialize variables
$email = '';
$username = '';
$new_password = '';
$confirm_password = '';
$message = '';
$error = '';
$success = false;
$step = 1; // Step 1: Enter email, Step 2: Reset password

// Simple 2-step password reset process
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = getDB();

    if (isset($_POST['step']) && $_POST['step'] == '1') {
        // STEP 1: Verify email and username
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);

        if (empty($email) || empty($username)) {
            $error = 'Please enter both email and username.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                // Check if email and username match
                $stmt = $db->prepare("SELECT id, username, email, is_staff, is_superuser FROM users WHERE email = ? AND username = ?");
                $stmt->execute([$email, $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Check if user is admin/staff - they cannot use forgot password
                    if (($user['is_staff'] ?? 0) == 1 || ($user['is_superuser'] ?? 0) == 1) {
                        $error = 'Admin accounts cannot use password reset. Please contact the system administrator for password assistance.';
                    } else {
                        // Regular user found, proceed to step 2
                        $step = 2;
                        $email = $user['email'];
                        $username = $user['username'];
                    }
                } else {
                    $error = 'Email and username combination not found. Please check your details.';
                }
            } catch (PDOException $e) {
                $error = 'System error. Please try again later.';
            }
        }

    } elseif (isset($_POST['step']) && $_POST['step'] == '2') {
        // STEP 2: Reset password
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($new_password) || empty($confirm_password)) {
            $error = 'Please enter and confirm your new password.';
            $step = 2;
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long.';
            $step = 2;
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
            $step = 2;
        } else {
            try {
                // Verify user still exists and is not admin/staff
                $stmt = $db->prepare("SELECT id, is_staff, is_superuser FROM users WHERE email = ? AND username = ?");
                $stmt->execute([$email, $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Double-check user is not admin/staff
                    if (($user['is_staff'] ?? 0) == 1 || ($user['is_superuser'] ?? 0) == 1) {
                        $error = 'Admin accounts cannot use password reset. Please contact the system administrator for password assistance.';
                        $step = 1; // Reset to step 1
                    } else {
                        // Update password for regular users only
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$hashed_password, $user['id']]);

                        $success = true;
                        $message = 'Password reset successfully! You can now login with your new password.';
                    }
                } else {
                    $error = 'User verification failed. Please start over.';
                }
            } catch (PDOException $e) {
                $error = 'System error. Please try again later.';
                $step = 2;
            }
        }
    }
}
?>

<main class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon">
                <i class="fa fa-key"></i>
            </div>
            <h2>Reset Password</h2>
            <p class="auth-subtitle">
                <?php if ($step == 1): ?>
                    Enter your email and username to verify your account.
                <?php elseif ($step == 2): ?>
                    Enter your new password.
                <?php endif; ?>
            </p>
        </div>

        <?php if ($success): ?>
            <!-- Success Message -->
            <div class="success-card">
                <div class="success-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                <h3>Password Reset Complete!</h3>
                <p><?php echo htmlspecialchars($message); ?></p>

                <div class="auth-actions">
                    <a href="user_login.php" class="btn btn-primary">
                        <i class="fa fa-sign-in-alt"></i> Login Now
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fa fa-home"></i> Home
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- Error Message -->
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fa fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <!-- Step 1: Verify Account -->
                <form action="forgot_password.php" method="post" class="auth-form">
                    <input type="hidden" name="step" value="1">

                    <div class="form-group">
                        <label for="email">
                            <i class="fa fa-envelope"></i> Email Address
                        </label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="<?php echo htmlspecialchars($email); ?>"
                            placeholder="Enter your registered email"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="username">
                            <i class="fa fa-user"></i> Username
                        </label>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            value="<?php echo htmlspecialchars($username); ?>"
                            placeholder="Enter your username"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fa fa-arrow-right"></i> Verify Account
                    </button>
                </form>

            <?php elseif ($step == 2): ?>
                <!-- Step 2: Set New Password -->
                <form action="forgot_password.php" method="post" class="auth-form">
                    <input type="hidden" name="step" value="2">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">

                    <div class="form-group">
                        <label>Account Verified:</label>
                        <div style="background: #d4edda; padding: 0.75rem; border-radius: 4px; color: #155724;">
                            <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($email); ?>)
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password">
                            <i class="fa fa-lock"></i> New Password
                        </label>
                        <input
                            type="password"
                            name="new_password"
                            id="new_password"
                            placeholder="Enter new password (min 6 characters)"
                            required
                            minlength="6"
                        >
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fa fa-lock"></i> Confirm Password
                        </label>
                        <input
                            type="password"
                            name="confirm_password"
                            id="confirm_password"
                            placeholder="Confirm new password"
                            required
                            minlength="6"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fa fa-save"></i> Reset Password
                    </button>
                </form>
            <?php endif; ?>

            <!-- Navigation Links -->
            <div class="auth-links">
                <p>Remember your password? <a href="user_login.php">Login here</a></p>
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.success-card {
    text-align: center;
    padding: 2rem;
    background: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 8px;
    color: #155724;
}

.success-icon {
    font-size: 3rem;
    color: #28a745;
    margin-bottom: 1rem;
}

.auth-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 1.5rem;
}

.auth-actions .btn {
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
    border: 1px solid #f5c6cb;
}
</style>

<script>
// Simple password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');

    if (newPassword && confirmPassword) {
        function validatePasswords() {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }

        newPassword.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);
    }
});
</script>

<?php require_once('../views/footer.php'); ?>
