<?php
/*
===========================================
FILE: Main Landing Page (index.php)
PURPOSE: Welcome page for Transport Booking System with login options
TECHNOLOGIES: HTML5, CSS3, Font Awesome Icons
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Simple Navigation Header -->
    <header class="navbar main-navbar">
        <div class="navbar-left">
            <a href="index.php" class="brand-link">
                <span class="brand-icon"><i class="fa fa-bus"></i></span>
                <span class="brand-text">Transport Booking System</span>
            </a>
        </div>
        <div class="navbar-right">
            <a href="user_login.php" class="btn-nav"><i class="fa fa-user"></i> User Login</a>
            <a href="admin_login.php" class="btn-nav"><i class="fa fa-cog"></i> Admin Login</a>
        </div>
    </header>

<!--
HTML5 SEMANTIC ELEMENT: <main>
PURPOSE: Defines the main content area of the page
-->
<main class="landing-page">
    <!--
    HTML5 SEMANTIC ELEMENT: <section>
    PURPOSE: Groups related content together
    -->
    <section class="welcome-section">
        <!-- Welcome card container with modern styling -->
        <div class="welcome-card">
            <!--
            ICON: Font Awesome bus icon
            PURPOSE: Visual representation of transport theme
            -->
            <i class="fa fa-bus welcome-icon" aria-label="Bus Icon"></i>

            <!--
            HTML5 ELEMENT: <h1> - Main heading
            PURPOSE: Primary heading for SEO and accessibility
            -->
            <h1 class="welcome-title">
                Welcome to<br>
                <span class="system-name">Transport Booking System</span>
            </h1>

            <!--
            SECTION: Login Options
            PURPOSE: Provide different login paths for users and admins
            -->
            <div class="btn-row">
                <!--
                USER LOGIN BUTTON
                Uses semantic HTML with proper accessibility attributes
                -->
                <a href="user_login.php" class="btn btn-user" role="button" aria-label="Login as Passenger">
                    <i class="fa fa-user" aria-hidden="true"></i>
                    Passenger Login
                </a>

                <!--
                ADMIN LOGIN BUTTON
                Separate styling to distinguish admin access
                -->
                <a href="admin_login.php" class="btn btn-admin" role="button" aria-label="Login as Administrator">
                    <i class="fa fa-shield-alt" aria-hidden="true"></i>
                    Admin Login
                </a>
            </div>

            <!--
            REGISTRATION LINK
            PURPOSE: Guide new users to registration
            -->
            <div class="register-link">
                <p>New user? <a href="register.php" class="register-btn">Register here</a></p>
            </div>
        </div>
    </section>


</main>

</body>
</html>
