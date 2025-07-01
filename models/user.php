<?php
require_once('includes/db.php');

class User {
    // Properties
    public $id;
    public $username;
    public $email;
    public $password;

    // Constructor
    public function __construct($id = null, $username = null, $email = null, $password = null) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
    }

    // Create a new user
    public function create() {
        $db = getDB();

        // Prepare the SQL query
        $query = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        $stmt = $db->prepare($query);

        // Bind the values
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);

        // Execute the query
        try {
            $stmt->execute();
            return $db->lastInsertId(); // Return the last inserted ID
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    // Get a user by email
    public static function getByEmail($email) {
        $db = getDB();
        $query = "SELECT * FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // Fetch the user if found
        if ($stmt->rowCount() > 0) {
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            return new User($user_data['id'], $user_data['username'], $user_da_
