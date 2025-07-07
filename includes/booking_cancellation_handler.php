<?php
/**
 * Comprehensive Booking Cancellation Handler
 * 
 * This file contains functions to handle all aspects of booking cancellation
 * ensuring that all related data is properly updated across the system.
 * 
 * Components Updated When Booking is Cancelled:
 * 1. Booking status -> 'Cancelled'
 * 2. Schedule available_seats -> +1
 * 3. User notifications -> Added (system notifications only)
 * 4. Audit log -> Recorded
 * 5. Seat map -> Automatically reflects via status
 * 6. Business reports -> Automatically excludes via status filter
 * 7. Revenue calculations -> Automatically excludes via status filter
 * 8. Occupancy rates -> Automatically recalculated via status filter
 */

require_once('db.php');

/**
 * Cancel a booking and update all related components
 * 
 * @param int $booking_id - The booking ID to cancel
 * @param int $cancelled_by_user_id - ID of user performing the cancellation
 * @param string $cancellation_reason - Reason for cancellation
 * @param bool $is_admin_cancellation - Whether this is an admin cancellation
 * @return array - Result with success status and message
 */
function cancelBookingComprehensive($booking_id, $cancelled_by_user_id, $cancellation_reason = '', $is_admin_cancellation = false) {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // 1. Get booking details
        $stmt = $db->prepare("
            SELECT b.*, u.username, u.email, s.departure_time, s.available_seats,
                   bu.bus_number, r.source, r.destination, r.fare
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN schedules s ON b.schedule_id = s.id
            JOIN buses bu ON s.bus_id = bu.id
            JOIN routes r ON s.route_id = r.id
            WHERE b.id = ?
        ");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Booking not found'];
        }
        
        // 2. Validate cancellation is allowed
        if ($booking['status'] === 'Cancelled') {
            $db->rollBack();
            return ['success' => false, 'message' => 'Booking is already cancelled'];
        }
        
        if ($booking['status'] === 'Completed') {
            $db->rollBack();
            return ['success' => false, 'message' => 'Cannot cancel completed bookings'];
        }
        
        // Check 24-hour rule
        $departure_time = strtotime($booking['departure_time']);
        $current_time = time();
        $time_until_departure = $departure_time - $current_time;
        
        if ($time_until_departure < 86400 && !$is_admin_cancellation) { // 24 hours = 86400 seconds
            $hours_left = round($time_until_departure / 3600, 1);
            $db->rollBack();
            return ['success' => false, 'message' => "Cannot cancel booking within 24 hours of departure. Only {$hours_left} hours remaining."];
        }
        
        // Check if trip has already passed
        if ($departure_time < $current_time) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Cannot cancel booking for past trips'];
        }
        
        // 3. Update booking status to cancelled
        $stmt = $db->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
        $stmt->execute([$booking_id]);
        
        // 4. Update schedule available seats
        $stmt = $db->prepare("UPDATE schedules SET available_seats = available_seats + 1 WHERE id = ?");
        $stmt->execute([$booking['schedule_id']]);
        
        // 5. Add user notification
        $cancellation_type = $is_admin_cancellation ? 'admin' : 'user';
        $notification_message = $is_admin_cancellation 
            ? "Your booking (ID: {$booking['booking_id']}) has been cancelled by admin. If you have any questions, please contact support."
            : "You have successfully cancelled your booking (ID: {$booking['booking_id']}). The seat has been released and is now available for other passengers.";
        
        $stmt = $db->prepare("INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)");
        $stmt->execute([$booking['user_id'], $notification_message]);
        
        // 6. Create audit log entry
        $log_details = [
            'booking_id' => $booking['booking_id'],
            'user_id' => $booking['user_id'],
            'cancelled_by' => $cancelled_by_user_id,
            'cancellation_type' => $cancellation_type,
            'reason' => $cancellation_reason,
            'departure_time' => $booking['departure_time'],
            'seat_number' => $booking['seat_number'],
            'route' => $booking['source'] . ' → ' . $booking['destination'],
            'fare' => $booking['fare']
        ];
        
        $log_message = "Booking cancellation: " . json_encode($log_details);
        error_log($log_message); // This logs to PHP error log for audit purposes
        
        // Note: Email notifications removed - only system notifications used
        // Email notifications reserved for password recovery only
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'Booking cancelled successfully',
            'booking_details' => $booking
        ];
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Booking cancellation error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred during cancellation'];
    }
}

/**
 * Get cancellation impact summary for a booking
 * Shows what will be affected when a booking is cancelled
 * 
 * @param int $booking_id - The booking ID to analyze
 * @return array - Impact summary
 */
function getCancellationImpact($booking_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT b.*, s.available_seats, s.departure_time,
                   bu.capacity, r.source, r.destination, r.fare
            FROM bookings b
            JOIN schedules s ON b.schedule_id = s.id
            JOIN buses bu ON s.bus_id = bu.id
            JOIN routes r ON s.route_id = r.id
            WHERE b.id = ?
        ");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            return ['error' => 'Booking not found'];
        }
        
        // Calculate current occupancy
        $current_booked = $booking['capacity'] - $booking['available_seats'];
        $current_occupancy = ($current_booked / $booking['capacity']) * 100;
        
        // Calculate occupancy after cancellation
        $after_booked = $current_booked - 1;
        $after_occupancy = ($after_booked / $booking['capacity']) * 100;
        
        return [
            'booking_id' => $booking['booking_id'],
            'seat_number' => $booking['seat_number'],
            'route' => $booking['source'] . ' → ' . $booking['destination'],
            'departure_time' => $booking['departure_time'],
            'fare_impact' => $booking['fare'],
            'current_occupancy' => round($current_occupancy, 1),
            'after_occupancy' => round($after_occupancy, 1),
            'occupancy_change' => round($current_occupancy - $after_occupancy, 1),
            'available_seats_before' => $booking['available_seats'],
            'available_seats_after' => $booking['available_seats'] + 1,
            'components_affected' => [
                'seat_map' => 'Seat will show as available',
                'occupancy_rate' => 'Will decrease by ' . round($current_occupancy - $after_occupancy, 1) . '%',
                'revenue_reports' => 'Will exclude RM ' . number_format($booking['fare'], 2) . ' from calculations',
                'available_seats' => 'Will increase by 1',
                'user_notifications' => 'User will receive cancellation notification'
            ]
        ];
        
    } catch (Exception $e) {
        return ['error' => 'Error analyzing cancellation impact: ' . $e->getMessage()];
    }
}
?>
