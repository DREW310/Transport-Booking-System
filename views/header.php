<?php
/*
===========================================
FILE: Header Template (header.php)
PURPOSE: Common header section for all pages with navigation and HTML structure
TECHNOLOGIES: HTML5, CSS3, PHP Sessions, Font Awesome
===========================================
*/

// Start session if not already started - important for user authentication
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!--
    HTML5 META TAGS
    PURPOSE: Proper document structure and mobile responsiveness
    -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Transport Booking System - Book bus tickets online easily and securely">
    <meta name="keywords" content="transport, booking, bus, tickets, travel">
    <meta name="author" content="TWT6223 Student Project">

    <!-- Page title -->
    <title>Transport Booking System</title>

    <!--
    CSS STYLESHEETS
    PURPOSE: Load our custom styles and icon library
    -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <?php
    // Include status badge CSS
    require_once('../includes/status_helpers.php');
    echo getStatusBadgeCSS();
    ?>

    <!--
    FAVICON (Optional enhancement)
    PURPOSE: Browser tab icon for professional appearance
    -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">

    <!-- Notification Bell Styles -->
    <style>
    .notification-link {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .notification-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #f44336;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }
    </style>
</head>
<body>
    <!--
    HTML5 SEMANTIC ELEMENT: <header>
    PURPOSE: Contains the site header and navigation
    -->
    <header role="banner">
        <!--
        HTML5 SEMANTIC ELEMENT: <nav>
        PURPOSE: Main navigation area
        -->
        <nav class="navbar main-navbar" role="navigation" aria-label="Main Navigation">

            <?php
            /*
            PHP CONDITIONAL: Check if user is logged in
            PURPOSE: Show different navigation based on user status
            */
            if (isset($_SESSION['user'])):
            ?>
                <!-- LOGGED IN USER NAVIGATION -->
                <div class="navbar-left">
                    <!-- System branding that links to appropriate dashboard -->
                    <?php
                    $dashboard_link = ((isset($_SESSION['user']['is_staff']) && $_SESSION['user']['is_staff']) ||
                                      (isset($_SESSION['user']['is_superuser']) && $_SESSION['user']['is_superuser']))
                                      ? 'admin_dashboard.php' : 'dashboard.php';
                    ?>
                    <a href="<?php echo $dashboard_link; ?>" class="brand-link" aria-label="Go to Dashboard">
                        <span class="brand-icon" aria-hidden="true">
                            <i class="fa fa-bus"></i>
                        </span>
                        <span class="brand-text">Transport Booking System</span>
                    </a>
                </div>

                <div class="navbar-right">
                    <?php
                    /*
                    PHP CONDITIONAL: Check if user is admin
                    PURPOSE: Show admin dashboard link only to administrators
                    */
                    if ((isset($_SESSION['user']['is_staff']) && $_SESSION['user']['is_staff']) ||
                        (isset($_SESSION['user']['is_superuser']) && $_SESSION['user']['is_superuser'])):
                    ?>
                        <a class="nav-link" href="admin_dashboard.php" aria-label="Access Admin Dashboard">
                            <i class="fa fa-cogs" aria-hidden="true"></i> Admin Dashboard
                        </a>
                    <?php endif; ?>

                    <?php
                    /*
                    ROLE-BASED NAVIGATION
                    PURPOSE: Show different navigation options based on user role
                    */
                    $is_admin = (isset($_SESSION['user']['is_staff']) && $_SESSION['user']['is_staff']) ||
                                (isset($_SESSION['user']['is_superuser']) && $_SESSION['user']['is_superuser']);

                    if (!$is_admin): // Only show booking features to regular users
                    ?>
                        <!-- Main navigation links for regular users -->
                        <a class="nav-link" href="dashboard.php" aria-label="View Dashboard">
                            <i class="fa fa-tachometer-alt" aria-hidden="true"></i> Dashboard
                        </a>
                        <a class="nav-link" href="bookings.php" aria-label="View My Bookings">
                            <i class="fa fa-ticket-alt" aria-hidden="true"></i> My Bookings
                        </a>
                        <a class="nav-link" href="feedback.php" aria-label="View and Submit Feedback">
                            <i class="fa fa-comments" aria-hidden="true"></i> Feedbacks
                        </a>
                    <?php endif; ?>

                    <!-- Notification Bell for passengers only (not admin) -->
                    <?php if (!$is_admin): ?>
                        <?php
                        // Get unread notification count with error handling
                        $unread_count = 0;
                        try {
                            // Try to get database connection (may already be included)
                            if (!function_exists('getDB')) {
                                if (file_exists('../includes/db.php')) {
                                    require_once('../includes/db.php');
                                } elseif (file_exists('includes/db.php')) {
                                    require_once('includes/db.php');
                                }
                            }

                            if (function_exists('getDB')) {
                                $db = getDB();
                                $stmt = $db->prepare('SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0');
                                $stmt->execute([$_SESSION['user']['id']]);
                                $notification_data = $stmt->fetch(PDO::FETCH_ASSOC);
                                $unread_count = $notification_data['unread_count'] ?? 0;
                            }
                        } catch (Exception $e) {
                            // Silently fail - notification bell will just show 0
                            $unread_count = 0;
                        }
                        ?>
                        <a class="nav-link notification-link" href="notifications.php" aria-label="View Notifications">
                            <i class="fa fa-bell" aria-hidden="true"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="notification-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                            <span class="nav-text">Notifications</span>
                        </a>
                    <?php endif; ?>

                    <!-- Profile link available to all users -->
                    <a class="nav-link" href="profile.php" aria-label="View My Profile">
                        <i class="fa fa-user" aria-hidden="true"></i> Profile
                    </a>

                    <!-- Logout link with confirmation -->
                    <a class="nav-link logout-link"
                       href="../public/logout.php"
                       onclick="return confirm('Are you sure you want to logout?');"
                       aria-label="Logout from System">
                        <i class="fa fa-sign-out-alt" aria-hidden="true"></i> Logout
                    </a>
                </div>

            <?php else: ?>
                <!-- GUEST USER NAVIGATION -->
                <div class="navbar-left">
                    <!-- System branding for non-logged users -->
                    <a href="../public/index.php" class="brand-link" aria-label="Go to Homepage">
                        <span class="brand-icon" aria-hidden="true">
                            <i class="fa fa-bus"></i>
                        </span>
                        <span class="brand-text">Transport Booking System</span>
                    </a>
                </div>

                <div class="navbar-right btn-nav-row">
                    <!-- Guest navigation links -->
                    <a class="btn btn-nav" href="../public/register.php" aria-label="Register New Account">
                        <i class="fa fa-user-plus" aria-hidden="true"></i> Register
                    </a>
                    <a class="btn btn-nav" href="../public/user_login.php" aria-label="Login to Account">
                        <i class="fa fa-sign-in-alt" aria-hidden="true"></i> Login
                    </a>
                </div>
            <?php endif; ?>
        </nav>
    </header>
