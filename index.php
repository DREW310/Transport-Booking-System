<?php
/*
===========================================
FILE: Main Entry Point (index.php)
PURPOSE: Welcome page for Transport Booking System
===========================================
*/

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Booking System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation Header -->
    <header class="navbar main-navbar">
        <div class="navbar-left">
            <a href="index.php" class="brand-link">
                <span class="brand-icon"><i class="fa fa-bus"></i></span>
                <span class="brand-text">Transport Booking System</span>
            </a>
        </div>
        <div class="navbar-right">
            <a href="public/user_login.php" class="btn-nav"><i class="fa fa-user"></i> User Login</a>
            <a href="public/admin_login.php" class="btn-nav"><i class="fa fa-cog"></i> Admin Login</a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="landing-page">
        <section class="welcome-section">
            <div class="welcome-card">
                <!-- System Title with Icon -->
                <h1 class="welcome-title">
                    <i class="fa fa-bus" aria-hidden="true"></i>
                    Welcome to Transport Booking System
                </h1>
                
                <!-- System Description -->
                <p class="welcome-description">
                    Your reliable partner for bus ticket booking. Book your journey with ease and comfort.
                </p>

                <!-- Login Options -->
                <div class="btn-row">
                    <a href="public/user_login.php" class="btn btn-user" role="button" aria-label="Login as Passenger">
                        <i class="fa fa-user" aria-hidden="true"></i>
                        Passenger Login
                    </a>
                    <a href="public/admin_login.php" class="btn btn-admin" role="button" aria-label="Login as Administrator">
                        <i class="fa fa-cog" aria-hidden="true"></i>
                        Admin Login
                    </a>
                </div>

                <!-- Registration Link -->
                <div class="register-link">
                    <p>Don't have an account? <a href="public/register.php">Register here</a></p>
                </div>
            </div>
        </section>

        <!-- Features Preview -->
        <section class="features-preview">
            <h2>System Features</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fa fa-search feature-icon"></i>
                    <h3>Search & Book</h3>
                    <p>Find and book bus tickets easily</p>
                </div>
                <div class="feature-card">
                    <i class="fa fa-calendar feature-icon"></i>
                    <h3>Schedule Management</h3>
                    <p>View and manage bus schedules</p>
                </div>
                <div class="feature-card">
                    <i class="fa fa-ticket-alt feature-icon"></i>
                    <h3>Booking History</h3>
                    <p>Track your booking history</p>
                </div>
                <div class="feature-card">
                    <i class="fa fa-star feature-icon"></i>
                    <h3>Feedback System</h3>
                    <p>Rate and review your trips</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-content">
            <p>&copy; 2024 Transport Booking System. All rights reserved.</p>
            <p>Developed for Web Technology Coursework</p>
        </div>
    </footer>
</body>
</html>
