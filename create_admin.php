<?php
/*
===========================================
FILE: Create Admin User (create_admin.php)
PURPOSE: Simple script to create first admin user for coursework
STUDENT LEARNING: Shows database interaction and user management
TECHNOLOGIES: PHP, MySQL, Security
===========================================
*/

// Include database configuration and connection
require_once 'includes/config.php';
require_once 'includes/db.php';

// Get database connection
$pdo = getDB();

// Check if database connection failed
if ($pdo === null) {
    echo "<h2>❌ Database Connection Failed</h2>";
    echo "<p>Please make sure:</p>";
    echo "<ul>";
    echo "<li>XAMPP MySQL service is running</li>";
    echo "<li>Database has been imported from transport_booking_tables.sql</li>";
    echo "</ul>";
    exit;
}

// Check if admin already exists
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE is_superuser = 1");
    $stmt->execute();
    $adminCount = $stmt->fetchColumn();

    if ($adminCount > 0) {
        echo "<h2>✅ Admin user already exists!</h2>";
        echo "<p>Go to: <a href='public/admin_login.php'>Admin Login</a></p>";
        exit;
    }
} catch (PDOException $e) {
    echo "<h2>❌ Database Error</h2>";
    echo "<p>Make sure you've imported the database first!</p>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);
    
    if (empty($username) || empty($password) || empty($email)) {
        $error = "All fields are required!";
    } else {
        try {
            // Create admin user
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, is_staff, is_superuser) VALUES (?, ?, ?, 1, 1)");
            $stmt->execute([$username, $password, $email]);
            
            // Create profile
            $userId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO profiles (user_id, full_name) VALUES (?, ?)");
            $stmt->execute([$userId, $username]);
            
            $success = true;
        } catch (PDOException $e) {
            $error = "Error creating admin: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User - TWT Transport</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 500px; 
            margin: 50px auto; 
            padding: 20px; 
            background: #f5f5f5;
        }
        .container { 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"] { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            box-sizing: border-box;
        }
        .btn {
            background: #5A9FD4;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        .btn:hover { background: #4A8BC2; }
        .success { 
            color: green; 
            background: #d4edda; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 15px 0; 
            text-align: center;
        }
        .error { 
            color: red; 
            background: #f8d7da; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 15px 0; 
        }
        .header { text-align: center; margin-bottom: 30px; }
        .links { text-align: center; margin-top: 20px; }
        .links a { color: #5A9FD4; text-decoration: none; margin: 0 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚌 TWT Transport System</h1>
            <h2>Create Admin User</h2>
            <p>Create your first administrator account</p>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="success">
                <h3>🎉 Admin User Created Successfully!</h3>
                <p><strong>Username:</strong> <?= htmlspecialchars($username) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
                <p>You can now login to the admin panel.</p>
            </div>
            <div class="links">
                <a href="public/admin_login.php">👨‍💼 Admin Login</a>
                <a href="public/index.php">🏠 Main Site</a>
            </div>
        <?php else: ?>
            <?php if (isset($error)): ?>
                <div class="error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label for="username">Admin Username:</label>
                    <input type="text" id="username" name="username" required 
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Admin Email:</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Admin Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn">Create Admin User</button>
            </form>
            
            <div class="links">
                <a href="public/index.php">🏠 Back to Main Site</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
