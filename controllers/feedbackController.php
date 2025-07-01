<?php
require_once('../includes/db.php');

class FeedbackController {
    // Submit feedback
    public function submitFeedback($userId, $bookingId, $feedbackText) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO feedback (user_id, booking_id, feedback) VALUES (:user_id, :booking_id, :feedback)");
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':booking_id', $bookingId);
        $stmt->bindParam(':feedback', $feedbackText);
        $stmt->execute();
    }

    // Get feedback for a booking
    public function getFeedbackByBooking($bookingId) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM feedback WHERE booking_id = :booking_id");
        $stmt->bindParam(':booking_id', $bookingId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
