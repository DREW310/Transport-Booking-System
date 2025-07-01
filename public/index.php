<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | Transport Booking System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #fcf8e3; min-height: 100vh; margin: 0; font-family: 'Nunito', Arial, sans-serif; }
        .welcome-header { background: #f5f5f5; border-bottom: 3px solid #e53935; padding: 0.7rem 2rem; display: flex; align-items: center; justify-content: space-between; }
        .welcome-header .left { display: flex; align-items: center; font-size: 1.5rem; color: #e53935; font-weight: 700; }
        .welcome-header .left i { margin-right: 10px; }
        .welcome-header .right a { color: #e53935; font-weight: 600; margin-left: 1.5rem; text-decoration: none; font-size: 1.1rem; }
        .welcome-header .right a i { margin-right: 5px; }
        .welcome-card { background: #fffbe6; max-width: 500px; margin: 5vw auto; border-radius: 16px; box-shadow: 0 8px 40px #e0dcc7; padding: 3rem 2.5rem; text-align: center; }
        .welcome-card i { font-size: 3.5rem; color: #e53935; margin-bottom: 1.2rem; }
        .welcome-card h1 { color: #e53935; font-size: 2.7rem; margin-bottom: 0.7rem; font-weight: 800; }
        .welcome-card .btn-row { display: flex; justify-content: center; gap: 1.2rem; margin: 2rem 0 1rem 0; }
        .welcome-card .btn { font-size: 1.3rem; padding: 0.7rem 2.2rem; border-radius: 8px; border: 2px solid #e53935; background: #fff; color: #e53935; font-weight: 700; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 0.7rem; }
        .welcome-card .btn.user { background: #e53935; color: #fff; }
        .welcome-card .btn.user:hover { background: #b71c1c; }
        .welcome-card .btn.admin:hover { background: #fff3e0; }
        .welcome-card .btn.admin { background: #fff; color: #e53935; }
        .welcome-card .btn.register { border: none; background: none; color: #e53935; text-decoration: underline; font-size: 1rem; margin-top: 1.2rem; }
        .welcome-card .btn.register:hover { color: #b71c1c; }
        .welcome-card .register-link { margin-top: 1.5rem; font-size: 1.1rem; }
        .welcome-card .register-link a { color: #e53935; font-weight: 700; text-decoration: underline; }
        @media (max-width: 600px) { .welcome-card { padding: 2rem 0.7rem; } }
    </style>
</head>
<body>
    <div class="welcome-header">
        <div class="left"><i class="fa fa-bus"></i> Welcome!</div>
        <div class="right">
            <a href="../views/schedule.php"><i class="fa fa-calendar-alt"></i> View Schedule</a>
            <a href="register.php"><i class="fa fa-user-plus"></i> Register</a>
            <a href="user_login.php"><i class="fa fa-sign-in-alt"></i> Login</a>
        </div>
    </div>
    <div class="welcome-card">
        <i class="fa fa-bus"></i>
        <h1>Welcome to<br>Transport Booking System</h1>
        <div class="btn-row">
            <a href="user_login.php" class="btn user"><i class="fa fa-user"></i> User Login</a>
            <a href="admin_login.php" class="btn admin"><i class="fa fa-shield-alt"></i> Admin Login</a>
        </div>
        <div class="register-link">New user? <a href="register.php">Register here</a></div>
    </div>
</body>
</html>
