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

    // Get all bookings for a user with complete information
    public function getBookingsByUser($userId) {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT b.id, b.booking_id, b.status, b.seat_number, b.booking_time, b.payment_method,
                   s.departure_time, bu.bus_number, bu.license_plate, bu.bus_type, bu.company,
                   r.source, r.destination, r.fare
            FROM bookings b
            JOIN schedules s ON b.schedule_id = s.id
            JOIN buses bu ON s.bus_id = bu.id
            JOIN routes r ON s.route_id = r.id
            WHERE b.user_id = :user_id
            ORDER BY s.departure_time DESC
        ");
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
