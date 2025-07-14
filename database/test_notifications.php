<?php
/*
===========================================
FILE: Test Notifications Generator (test_notifications.php)
PURPOSE: Generate sample notifications to test the enhanced display
TECHNOLOGIES: PHP, MySQL, PDO
===========================================
*/

require_once('../includes/db.php');

try {
    $db = getDB();
    
    // Get a test user (first user in the system)
    $user_stmt = $db->query("SELECT id FROM users WHERE role = 'passenger' LIMIT 1");
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ No passenger users found. Please create a user account first.\n";
        exit;
    }
    
    $user_id = $user['id'];
    
    echo "Creating test notifications for user ID: {$user_id}\n\n";
    
    // Sample notifications with different types and change details
    $test_notifications = [
        [
            'message' => 'Bus Updated: B001 - License Plate: ABC1234 → DEF5678; Bus Type: Express → VIP; Capacity: 40 → 45 seats',
            'type' => 'bus-update'
        ],
        [
            'message' => 'Route Updated: Kuala Lumpur → Johor Bahru - Fare: RM45.00 → RM50.00',
            'type' => 'route-update'
        ],
        [
            'message' => 'Schedule Updated: Kuala Lumpur → Penang - Departure: Dec 15, 2024 2:00 PM → Dec 15, 2024 3:30 PM; Available Seats: 25 → 30',
            'type' => 'schedule-update'
        ],
        [
            'message' => 'Booking Cancelled: Your booking for Bus B002 (Kuala Lumpur → Ipoh) on Dec 20, 2024 has been cancelled by admin. Refund will be processed within 3-5 business days.',
            'type' => 'booking-cancelled'
        ],
        [
            'message' => 'Bus Updated: B003 - Company: Express Travel → Premium Transport',
            'type' => 'bus-update'
        ]
    ];
    
    // Insert test notifications
    $insert_stmt = $db->prepare('INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)');

    foreach ($test_notifications as $notification) {
        $insert_stmt->execute([$user_id, $notification['message']]);
        echo "✓ Added: {$notification['type']} notification\n";
    }
    
    echo "\n✅ Test notifications created successfully!\n";
    echo "Visit the notifications page to see the enhanced display.\n";
    
} catch (Exception $e) {
    echo "❌ Error creating test notifications: " . $e->getMessage() . "\n";
}
?>
