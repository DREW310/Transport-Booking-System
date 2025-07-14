<?php
/*
===========================================
FILE: Database Update Script (update_notifications_table.php)
PURPOSE: Add type column to notifications table
TECHNOLOGIES: PHP, MySQL, PDO
===========================================
*/

require_once('../includes/db.php');

try {
    $db = getDB();
    
    // Check if type column already exists
    $check_column = $db->query("SHOW COLUMNS FROM notifications LIKE 'type'");
    
    if ($check_column->rowCount() == 0) {
        echo "Adding type column to notifications table...\n";
        
        // Add type column
        $db->exec("ALTER TABLE notifications ADD COLUMN type VARCHAR(50) DEFAULT 'general' AFTER message");
        echo "✓ Type column added successfully!\n";
        
        // Update existing notifications to have appropriate types
        echo "Updating existing notification types...\n";
        
        $update_sql = "UPDATE notifications 
                      SET type = CASE 
                          WHEN message LIKE '%Bus%updated%' OR message LIKE '%Bus%information%' THEN 'bus-update'
                          WHEN message LIKE '%Route%updated%' OR message LIKE '%Route%information%' THEN 'route-update'
                          WHEN message LIKE '%Schedule%updated%' OR message LIKE '%booking%modified%' THEN 'schedule-update'
                          WHEN message LIKE '%cancelled%' OR message LIKE '%Cancelled%' THEN 'booking-cancelled'
                          ELSE 'general'
                      END";
        
        $affected = $db->exec($update_sql);
        echo "✓ Updated {$affected} existing notifications with appropriate types!\n";
        
    } else {
        echo "Type column already exists in notifications table.\n";
    }
    
    echo "\n✅ Database update completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error updating database: " . $e->getMessage() . "\n";
}
?>
