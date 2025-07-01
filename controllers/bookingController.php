<?php
require_once('../includes/db.php');


class BookingController {
    // Create a new booking
    public function createBooking($userId, $destination, $date) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO bookings (user_id, destination, date) VALUES (:user_id, :destination, :date)");
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':destination', $destination);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
    }

    // Get all bookings for a user
    public function getBookingsByUser($userId) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM bookings WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cancel a booking
    public function cancelBooking($bookingId) {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM bookings WHERE id = :id");
        $stmt->bindParam(':id', $bookingId);
        $stmt->execute();
    }
}
?>
