-- Database Update Script: Add Unique Constraint to Routes Table
-- Run this script to prevent duplicate routes with same source, destination, and fare

USE `Transport-Booking-System`;

-- First, remove any existing duplicate routes (keep the first occurrence)
DELETE r1 FROM routes r1
INNER JOIN routes r2 
WHERE r1.id > r2.id 
AND r1.source = r2.source 
AND r1.destination = r2.destination 
AND r1.fare = r2.fare;

-- Add unique constraint to prevent future duplicates
ALTER TABLE routes 
ADD CONSTRAINT unique_route UNIQUE (source, destination, fare);

-- Create index for better performance
CREATE INDEX idx_route_lookup ON routes(source, destination);

SELECT 'Route duplicate prevention constraint added successfully!' as message;
SELECT 'Duplicate routes have been removed from the database.' as note;
