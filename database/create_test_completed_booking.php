<?php
/*
===========================================
FILE: Create Test Completed Booking (create_test_completed_booking.php)
PURPOSE: Create a test completed booking to test business rules
STUDENT LEARNING: Testing business logic with completed bookings
TECHNOLOGIES: PHP, MySQL, PDO
===========================================
*/

require_once('../includes/db.php');

try {
    $db = getDB();
    
    // Get a test user (first passenger)
    $user_stmt = $db->query("SELECT id FROM users WHERE role = 'passenger' LIMIT 1");
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ No passenger users found. Please create a user account first.\n";
        exit;
    }
    
    // Get a test schedule
    $schedule_stmt = $db->query("SELECT id FROM schedules LIMIT 1");
    $schedule = $schedule_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        echo "❌ No schedules found. Please create a schedule first.\n";
        exit;
    }
    
    $user_id = $user['id'];
    $schedule_id = $schedule['id'];
    
    echo "Creating test completed booking...\n";
    echo "User ID: {$user_id}\n";
    echo "Schedule ID: {$schedule_id}\n\n";
    
    // Create a completed booking
    $booking_id = 'BK' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    $insert_sql = "INSERT INTO bookings (booking_id, user_id, schedule_id, seat_number, status, booking_time, payment_method) 
                   VALUES (?, ?, ?, ?, 'Completed', NOW(), 'Credit Card')";
    
    $insert_stmt = $db->prepare($insert_sql);
    $success = $insert_stmt->execute([$booking_id, $user_id, $schedule_id, 'A1']);
    
    if ($success) {
        echo "✅ Test completed booking created successfully!\n";
        echo "Booking ID: {$booking_id}\n";
        echo "Seat: A1\n";
        echo "Status: Completed\n\n";
        
        echo "Now you can test:\n";
        echo "1. Schedule form should be read-only for this schedule\n";
        echo "2. Schedule cannot be deleted\n";
        echo "3. Route changes should NOT send notifications to this user\n";
        echo "4. Bus changes should NOT send notifications to this user\n";
    } else {
        echo "❌ Failed to create test booking\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
