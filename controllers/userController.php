<?php
require_once('../includes/db.php');

class UserController {
    // Register a new user
    public function register($username, $email, $password) {
        $db = getDB();
        // Hash password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->execute();
    }

    // Login user
    public function login($email, $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify password (supports both hashed and plain text for backward compatibility)
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                // If plain text password matched, upgrade to hashed
                if ($password === $user['password']) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update_stmt->execute([$hashed_password, $user['id']]);
                }
                return $user;
            }
        }
        return null;
    }

    // Get a user's details by their ID
    public function getUserById($userId) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindParam(':id', $userId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
