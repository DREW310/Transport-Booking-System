<?php
/*
===========================================
FILE: Feedback Model (feedback.php)
PURPOSE: Handle all feedback-related database operations
TECHNOLOGIES: PHP, MySQL, PDO
===========================================
*/

// Include necessary files for database connection
require_once('../includes/db.php');

/*
CLASS: Feedback
PURPOSE: This class handles all feedback-related operations in our transport booking system
*/
class Feedback {
    
    // Private property to store database connection
    // We use private to demonstrate encapsulation concept
    private $db;
    
    /*
    CONSTRUCTOR: __construct()
    PURPOSE: Initialize the database connection when object is created
    */
    public function __construct() {
        // Get database connection using our custom function
        $this->db = getDB();
    }
    
    /*
    METHOD: submitFeedback()
    PURPOSE: Add new feedback to the database
    PARAMETERS: $userId, $busId, $bookingId, $rating, $review
    */
    public function submitFeedback($userId, $busId, $bookingId, $rating, $comment, $tags = '') {
        try {
            // Step 1: Prepare SQL statement to prevent SQL injection
            // This is important for security - never trust user input!
            $sql = "INSERT INTO feedback (user_id, bus_id, booking_id, rating, comment, tags)
                    VALUES (?, ?, ?, ?, ?, ?)";

            // Step 2: Prepare the statement
            $stmt = $this->db->prepare($sql);

            // Step 3: Execute with parameters
            // Using ? placeholders makes our query safe from SQL injection
            $result = $stmt->execute([$userId, $busId, $bookingId, $rating, $comment, $tags]);
            
            // Step 4: Return success status
            return $result;
            
        } catch (PDOException $e) {
            // Handle database errors gracefully
            // In a real application, we would log this error
            echo "Error submitting feedback: " . $e->getMessage();
            return false;
        }
    }
    
    /*
    METHOD: getFeedbackByBus()
    PURPOSE: Get all feedback for a specific bus
    PARAMETER: $busId - the ID of the bus
    */
    public function getFeedbackByBus($busId) {
        try {
            // SQL query with JOIN to get user information along with feedback
            // This demonstrates relational database concepts
            $sql = "SELECT f.*, u.username, p.full_name 
                    FROM feedback f 
                    JOIN users u ON f.user_id = u.id 
                    LEFT JOIN profiles p ON u.id = p.user_id 
                    WHERE f.bus_id = ? 
                    ORDER BY f.date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$busId]);
            
            // Fetch all results as associative array
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            echo "Error retrieving feedback: " . $e->getMessage();
            return [];
        }
    }
    
    /*
    METHOD: getAllFeedback()
    PURPOSE: Get all feedback for admin dashboard
    */
    public function getAllFeedback() {
        try {
            // Complex query joining multiple tables
            // This shows advanced SQL concepts for second-year students
            $sql = "SELECT f.*, u.username, p.full_name, b.bus_number, b.company 
                    FROM feedback f 
                    JOIN users u ON f.user_id = u.id 
                    LEFT JOIN profiles p ON u.id = p.user_id 
                    JOIN buses b ON f.bus_id = b.id 
                    ORDER BY f.date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            echo "Error retrieving all feedback: " . $e->getMessage();
            return [];
        }
    }
    
    /*
    METHOD: deleteFeedback()
    PURPOSE: Allow admin to delete inappropriate feedback
    PARAMETER: $feedbackId - ID of feedback to delete
    */
    public function deleteFeedback($feedbackId) {
        try {
            $sql = "DELETE FROM feedback WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$feedbackId]);
            
        } catch (PDOException $e) {
            echo "Error deleting feedback: " . $e->getMessage();
            return false;
        }
    }
    
    /*
    METHOD: getAverageRating()
    PURPOSE: Calculate average rating for a bus
    PARAMETER: $busId - ID of the bus
    */
    public function getAverageRating($busId) {
        try {
            // Use AVG() function to calculate average rating
            $sql = "SELECT AVG(rating) as average_rating, COUNT(*) as total_reviews 
                    FROM feedback WHERE bus_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$busId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            echo "Error calculating average rating: " . $e->getMessage();
            return ['average_rating' => 0, 'total_reviews' => 0];
        }
    }
}

/*
END OF FEEDBACK MODEL CLASS
This file demonstrates:
1. Object-Oriented Programming (OOP) concepts
2. Database interaction using PDO
3. Prepared statements for security
4. Error handling with try-catch
5. SQL JOIN operations
6. Aggregate functions (AVG, COUNT)
*/
?>
