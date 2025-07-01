<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Booking System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar main-navbar">
            <?php if (isset($_SESSION['user'])): ?>
                <div class="navbar-left">
                    <span class="icon-red" style="font-size:1.3rem;margin-right:8px;"><i class="fa fa-user-circle"></i></span>
                    <span style="font-weight:600;color:#e53935;">Welcome, <?php echo htmlspecialchars($_SESSION['user']['username']); ?>!</span>
                </div>
                <div class="navbar-right">
                    <?php if ((isset($_SESSION['user']['is_staff']) && $_SESSION['user']['is_staff']) || (isset($_SESSION['user']['is_superuser']) && $_SESSION['user']['is_superuser'])): ?>
                        <a class="nav-link" href="admin_dashboard.php"><i class="fa fa-cogs"></i> Admin Dashboard</a>
                    <?php endif; ?>
                    <a class="nav-link" href="schedule.php"><i class="fa fa-calendar-alt"></i> View Schedule</a>
                    <a class="nav-link" href="bookings.php"><i class="fa fa-ticket-alt"></i> My Bookings</a>
                    <a class="nav-link" href="feedback.php"><i class="fa fa-comments"></i> Feedbacks</a>
                    <a class="nav-link" href="profile.php"><i class="fa fa-user"></i> Profile</a>
                    <a class="nav-link" href="../public/logout.php" onclick="return confirm('Are you sure you want to logout?');"><i class="fa fa-sign-out-alt"></i> Logout</a>
                </div>
            <?php else: ?>
                <div class="navbar-left">
                    <span class="icon-red" style="font-size:1.5rem;margin-right:8px;"><i class="fa fa-bus"></i></span>
                    <span style="font-weight:700;color:#e53935;font-size:1.5rem;">Welcome!</span>
                </div>
                <div class="navbar-right btn-nav-row">
                    <a class="btn btn-nav" href="../views/schedule.php"><i class="fa fa-calendar-alt"></i> View Schedule</a>
                    <a class="btn btn-nav" href="../public/register.php"><i class="fa fa-user-plus"></i> Register</a>
                    <a class="btn btn-nav" href="../public/user_login.php"><i class="fa fa-sign-in-alt"></i> Login</a>
                </div>
            <?php endif; ?>
        </nav>
    </header>
