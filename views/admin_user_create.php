<?php
/*
===========================================
FILE: Create New User (admin_user_create.php)
PURPOSE: Allow superuser to create new staff/users through admin panel
TECHNOLOGIES: PHP, MySQL, Security, Form Validation
===========================================
*/

require_once('../includes/db.php');
if (session_status() === PHP_SESSION_NONE) session_start();

// Check if user is logged in and is a superuser
if (!isset($_SESSION['user']) || !$_SESSION['user']['is_superuser']) {
    header('Location: ../public/admin_login.php');
    exit();
}

$db = getDB();
$success = false;
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        try {
            // Check if username already exists
            $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Username already exists. Please choose a different username.';
            } else {
                // Check if email already exists
                $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Email already exists. Please use a different email address.';
                } else {
                    // Set role flags
                    $is_staff = ($role === 'Admin' || $role === 'Superuser') ? 1 : 0;
                    $is_superuser = ($role === 'Superuser') ? 1 : 0;
                    
                    // Create user
                    $stmt = $db->prepare('INSERT INTO users (username, password, email, is_staff, is_superuser) VALUES (?, ?, ?, ?, ?)');
                    $result = $stmt->execute([$username, $password, $email, $is_staff, $is_superuser]);
                    
                    if ($result) {
                        // Get the new user ID
                        $user_id = $db->lastInsertId();
                        
                        // Create profile
                        $stmt = $db->prepare('INSERT INTO profiles (user_id, full_name, phone, address) VALUES (?, ?, ?, ?)');
                        $profile_result = $stmt->execute([$user_id, $full_name, $phone, $address]);
                        
                        if ($profile_result) {
                            $success = true;
                            $success_message = "User '$username' created successfully with $role role!";
                        } else {
                            $error = 'User created but failed to create profile. Please edit the user to add profile information.';
                        }
                    } else {
                        $error = 'Failed to create user. Please try again.';
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<?php require_once('../views/header.php'); ?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:400px;max-width:600px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <a href="admin_users.php" class="back-btn"><i class="fa fa-arrow-left"></i> Return to Users</a>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-user-plus icon-red"></i> Create New User</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:1.5rem; padding:1.5rem; border-radius:12px; border:none; background:#d4edda; color:#155724; font-weight:600; box-shadow:0 4px 16px rgba(40,167,69,0.2); text-align:center;">
                <div style="font-size:3rem; color:#28a745; margin-bottom:1rem;">
                    <i class="fa fa-check-circle"></i>
                </div>
                <h3 style="color:#155724; margin-bottom:0.5rem;">User Created Successfully!</h3>
                <p style="margin-bottom:1.5rem; color:#155724; opacity:0.9;"><?php echo htmlspecialchars($success_message); ?></p>
                <div style="margin-top: 10px;">
                    <a href="admin_users.php" class="bus-action-btn" style="margin-right: 10px;">
                        <i class="fa fa-users"></i> View All Users
                    </a>
                    <a href="admin_user_create.php" class="bus-action-btn">
                        <i class="fa fa-plus"></i> Create Another User
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-bottom:1.5rem; padding:1.5rem; border-radius:12px; border:none; background:#f8d7da; color:#721c24; font-weight:600; box-shadow:0 4px 16px rgba(220,53,69,0.2); text-align:center;">
                    <div style="font-size:2.5rem; color:#dc3545; margin-bottom:1rem;">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                    <h4 style="color:#721c24; margin-bottom:0.5rem;">Error Creating User</h4>
                    <p style="margin-bottom:0; color:#721c24;"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="" style="display:flex;flex-direction:column;gap:1rem;align-items:stretch;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label><strong>Username:</strong> <span style="color: red;">*</span></label>
                        <input type="text" name="username" class="form-control" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               required placeholder="Enter username">
                    </div>
                    <div>
                        <label><strong>Email:</strong> <span style="color: red;">*</span></label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               required placeholder="Enter email address">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label><strong>Password:</strong> <span style="color: red;">*</span></label>
                        <input type="password" name="password" class="form-control" required 
                               placeholder="Minimum 6 characters">
                    </div>
                    <div>
                        <label><strong>Confirm Password:</strong> <span style="color: red;">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" required 
                               placeholder="Re-enter password">
                    </div>
                </div>
                
                <div>
                    <label><strong>Role:</strong> <span style="color: red;">*</span></label>
                    <select name="role" class="form-control" required>
                        <option value="">Select Role</option>
                        <option value="User" <?php echo (isset($_POST['role']) && $_POST['role'] === 'User') ? 'selected' : ''; ?>>
                            Regular User (Can book tickets)
                        </option>
                        <option value="Admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Admin') ? 'selected' : ''; ?>>
                            Admin Staff (Can manage buses, routes, bookings)
                        </option>
                        <option value="Superuser" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Superuser') ? 'selected' : ''; ?>>
                            Superuser (Full system access)
                        </option>
                    </select>
                </div>
                
                <hr style="margin: 0.5rem 0;">
                <h3 style="margin: 0.5rem 0; color: #666;"><i class="fa fa-user"></i> Profile Information</h3>
                
                <div>
                    <label><strong>Full Name:</strong> <span style="color: red;">*</span></label>
                    <input type="text" name="full_name" class="form-control" 
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                           required placeholder="Enter full name">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label><strong>Phone:</strong></label>
                        <input type="text" name="phone" class="form-control" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                               placeholder="Enter phone number">
                    </div>
                    <div>
                        <label><strong>Address:</strong></label>
                        <textarea name="address" class="form-control" rows="3" 
                                  placeholder="Enter address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" class="bus-action-btn">
                        <i class="fa fa-save"></i> Create User
                    </button>
                    <a href="admin_users.php" class="bus-action-btn" style="text-decoration: none;">
                        <i class="fa fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>
<?php require_once('../views/footer.php'); ?>
