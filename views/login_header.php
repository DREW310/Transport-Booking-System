<?php
/*
===========================================
FILE: Login Header Template (login_header.php)
PURPOSE: Header for login pages with appropriate navigation
STUDENT LEARNING: Template inclusion, clean navigation for login pages
TECHNOLOGIES: HTML5, CSS3, Font Awesome
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
    <meta name="description" content="Transport Booking System - Book bus tickets online easily and securely">
    <meta name="keywords" content="transport, booking, bus, tickets, travel">
    <meta name="author" content="TWT6223 Student Project">

    <title>Transport Booking System</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body>
    <header role="banner">
        <nav class="navbar main-navbar" role="navigation" aria-label="Main Navigation">
            <!-- GUEST USER NAVIGATION FOR LOGIN PAGES -->
            <div class="navbar-left">
                <a href="../public/index.php" class="brand-link" aria-label="Go to Homepage">
                    <span class="brand-icon" aria-hidden="true">
                        <i class="fa fa-bus"></i>
                    </span>
                    <span class="brand-text">Transport Booking System</span>
                </a>
            </div>

            <div class="navbar-right btn-nav-row">
                <!-- Show appropriate alternative login link -->
                <?php if (basename($_SERVER['PHP_SELF']) === 'user_login.php'): ?>
                    <a class="btn btn-nav" href="../public/admin_login.php" aria-label="Admin Login">
                        <i class="fa fa-shield-alt" aria-hidden="true"></i> Admin Login
                    </a>
                <?php elseif (basename($_SERVER['PHP_SELF']) === 'admin_login.php'): ?>
                    <a class="btn btn-nav" href="../public/user_login.php" aria-label="User Login">
                        <i class="fa fa-user" aria-hidden="true"></i> User Login
                    </a>
                <?php endif; ?>
                
                <!-- Back to Home -->
                <a class="btn btn-nav" href="../public/index.php" aria-label="Back to Home">
                    <i class="fa fa-home" aria-hidden="true"></i> Home
                </a>
            </div>
        </nav>
    </header>
