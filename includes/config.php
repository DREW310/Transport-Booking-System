<?php
/*
===========================================
FILE: Database Configuration (config.php)
PURPOSE: Store all database connection settings in one place
TECHNOLOGIES: PHP Constants, Database Configuration
===========================================
*/

/*
SECTION: Database Configuration Constants
PURPOSE: Define database connection parameters
WHY CONSTANTS: Constants cannot be changed during script execution, making them secure
*/

// Database server hostname (usually localhost for local development)
define('DB_SERVER', 'localhost');

// Database username (default is 'root' for XAMPP)
define('DB_USERNAME', 'root');

// Database password (empty by default in XAMPP)
define('DB_PASSWORD', '');

// Database name - this is our transport booking system database
define('DB_DATABASE', 'Transport-Booking-System');

/*
SECTION: Environment Detection and Configuration
PURPOSE: Detect if we're running locally or in production
*/

// Detect if we're running in local development environment
define('IS_LOCAL_DEVELOPMENT',
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
    strpos($_SERVER['HTTP_HOST'], '::1') !== false
);

// Email configuration
define('EMAIL_ENABLED', !IS_LOCAL_DEVELOPMENT); // Disable real email sending in local development

// Error reporting settings for local development
if (IS_LOCAL_DEVELOPMENT) {
    // For local development, suppress mail-related warnings but show other errors
    error_reporting(E_ALL & ~E_WARNING);
    ini_set('display_errors', 1);

    // Suppress mail function warnings specifically to prevent SMTP errors
    ini_set('sendmail_path', '');
} else {
    // For production, enable proper error reporting
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Application settings
define('APP_NAME', 'Transport Booking System');
define('SESSION_TIMEOUT', 3600); // 1 hour
?>
