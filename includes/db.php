<?php
require_once('config.php'); // Include the configuration file

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "Transport-Booking-System";

// Function to establish a database connection
function getDB() {
    $dbConnection = null;
    try {
        // Use PDO to connect to the MySQL database using constants from config.php
        $dbConnection = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_DATABASE, DB_USERNAME, DB_PASSWORD);
        
        // Set error handling mode to exception for better error reporting
        $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        // If there's a connection error, print the message
        echo "Connection failed: " . $e->getMessage();
    }
    return $dbConnection;
}
?>
