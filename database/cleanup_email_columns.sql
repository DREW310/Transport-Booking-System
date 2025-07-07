-- Cleanup Script: Remove Email-Related Columns
-- Run this script to remove unused email reset columns from existing databases

USE `Transport-Booking-System`;

-- Remove reset token columns (no longer needed with simple password reset)
ALTER TABLE users 
DROP COLUMN IF EXISTS reset_token,
DROP COLUMN IF EXISTS reset_expires;

-- Remove index if it exists
DROP INDEX IF EXISTS idx_reset_token ON users;

SELECT 'Email-related columns removed successfully! Simple password reset is now active.' as message;
