-- Add type column to notifications table for better categorization
-- This allows us to style different notification types differently

ALTER TABLE notifications 
ADD COLUMN type VARCHAR(50) DEFAULT 'general' AFTER message;

-- Update existing notifications to have appropriate types
UPDATE notifications 
SET type = CASE 
    WHEN message LIKE '%Bus%updated%' OR message LIKE '%Bus%information%' THEN 'bus-update'
    WHEN message LIKE '%Route%updated%' OR message LIKE '%Route%information%' THEN 'route-update'
    WHEN message LIKE '%Schedule%updated%' OR message LIKE '%booking%modified%' THEN 'schedule-update'
    WHEN message LIKE '%cancelled%' OR message LIKE '%Cancelled%' THEN 'booking-cancelled'
    ELSE 'general'
END;
