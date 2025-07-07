<?php
/*
===========================================
STUDENT PROJECT: Database Connection File
FILE: db.php
WHAT THIS FILE DOES: Connects our PHP code to the MySQL database
WHY WE NEED THIS: All our data (users, bookings, buses) is stored in the database
WHAT I LEARNED: PDO database connections, error handling, security best practices
COURSE CONCEPTS USED:
- PDO (PHP Data Objects) for secure database connections
- Try-catch blocks for error handling
- Database configuration and connection management
- Security practices for database access
===========================================
*/

// Include our configuration file to get database constants
require_once('config.php');

/*
STUDENT EXPLANATION: Database Connection Function
FUNCTION NAME: getDB()
WHAT THIS DOES: Creates a connection between PHP and MySQL database
WHY WE USE PDO: It's more secure than old mysql functions
WHAT I LEARNED: Database connections, error handling, security practices
*/
function getDB() {
    // STEP 1: Start with empty connection
    $dbConnection = null;

    // STEP 2: Try to connect to database (use try-catch for error handling)
    try {
        /*
        STUDENT NOTE: Why PDO is better than old mysql functions:
        - PDO prevents SQL injection attacks automatically
        - PDO works with different types of databases (MySQL, PostgreSQL, etc.)
        - PDO has better error handling
        - PDO is the modern, recommended way to connect to databases

        MY LEARNING PROCESS WITH DATABASE CONNECTIONS:

        MISTAKE 1: Initially tried using mysql_connect() (deprecated!)
        - Got error messages saying function doesn't exist
        - Learned that mysql_* functions were removed in PHP 7
        - Had to research modern alternatives

        MISTAKE 2: First PDO attempt without error handling
        - Connection failed silently, spent hours debugging
        - Learned importance of try-catch blocks
        - Now I always wrap database operations in error handling

        MISTAKE 3: Hardcoded database credentials in multiple files
        - Made it hard to change settings
        - Learned about centralized configuration
        - Now I use this single db.php file for all connections

        WHAT I UNDERSTAND NOW:
        - PDO is more secure than old methods
        - Always use prepared statements for user input
        - Error handling is crucial for debugging
        - Centralized configuration makes maintenance easier
        */
        $dbConnection = new PDO(
            "mysql:host=" . DB_SERVER . ";dbname=" . DB_DATABASE,
            DB_USERNAME,
            DB_PASSWORD
        );

        /*
        STEP 2: Set PDO attributes for better error handling
        ERRMODE_EXCEPTION: Throws exceptions on errors (easier to handle)
        This helps us catch and handle database errors properly
        */
        $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        /*
        STEP 3: Set charset to UTF-8 for proper character encoding
        This ensures our database can handle special characters properly
        */
        $dbConnection->exec("set names utf8");

    } catch (PDOException $e) {
        /*
        ERROR HANDLING: If connection fails, show user-friendly message
        In production, we would log this error instead of displaying it
        For learning purposes, we show the actual error message
        */
        echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>";
        echo "<strong>Database Connection Error:</strong><br>";
        echo "Could not connect to the database. Please check your configuration.<br>";
        echo "<small>Technical details: " . $e->getMessage() . "</small>";
        echo "</div>";

        // Return null to indicate connection failure
        return null;
    }

    // Return the successful connection
    return $dbConnection;
}

/*
STUDENT NOTES:
1. PDO (PHP Data Objects) is the modern way to connect to databases
2. Always use try-catch for database connections
3. Setting error mode to EXCEPTION helps with debugging
4. UTF-8 charset ensures proper character encoding
5. Never expose database credentials in error messages in production
*/
?>
