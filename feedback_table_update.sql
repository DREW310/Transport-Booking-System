-- =====================================================
-- FEEDBACK TABLE STANDARDIZATION UPDATE SCRIPT
-- =====================================================
-- This script updates the feedback table to support the enhanced feedback system
-- Run this script to add missing columns and standardize the table structure

USE `Transport-Booking-System`;

-- Step 1: Add the missing 'tags' column to store user-selected feedback tags
ALTER TABLE feedback 
ADD COLUMN tags TEXT NULL COMMENT 'Comma-separated list of feedback tags selected by user';

-- Step 2: Standardize column names and add additional useful columns
-- Rename 'review' to 'comment' for consistency with the form
ALTER TABLE feedback 
CHANGE COLUMN review comment TEXT NULL COMMENT 'User feedback comment (max 80 words)';

-- Step 3: Add created_at and updated_at timestamps for better data tracking
ALTER TABLE feedback 
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When feedback was created',
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When feedback was last updated';

-- Step 4: Update the existing 'date' column data to created_at (if there's existing data)
UPDATE feedback SET created_at = date WHERE date IS NOT NULL;

-- Step 5: Add helpful status column for admin management
ALTER TABLE feedback 
ADD COLUMN status ENUM('active', 'hidden', 'flagged') DEFAULT 'active' COMMENT 'Feedback visibility status';

-- Step 6: Add helpful indexes for better performance
CREATE INDEX idx_feedback_rating ON feedback(rating);
CREATE INDEX idx_feedback_status ON feedback(status);
CREATE INDEX idx_feedback_created_at ON feedback(created_at);
CREATE INDEX idx_feedback_user_bus ON feedback(user_id, bus_id);

-- Step 7: Add constraint to ensure rating is between 1 and 5
ALTER TABLE feedback 
ADD CONSTRAINT chk_rating CHECK (rating >= 1 AND rating <= 5);

-- Step 8: Update foreign key constraint names for consistency
ALTER TABLE feedback 
DROP FOREIGN KEY feedback_ibfk_1,
DROP FOREIGN KEY feedback_ibfk_2,
DROP FOREIGN KEY feedback_ibfk_3;

ALTER TABLE feedback 
ADD CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
ADD CONSTRAINT fk_feedback_bus FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
ADD CONSTRAINT fk_feedback_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL;

-- Step 9: Show the updated table structure
DESCRIBE feedback;

-- Step 10: Display success message
SELECT 'Feedback table has been successfully updated and standardized!' as message;
SELECT 'New columns added: tags, created_at, updated_at, status' as new_features;
SELECT 'Column renamed: review -> comment' as changes;
SELECT 'Indexes and constraints added for better performance and data integrity' as improvements;
