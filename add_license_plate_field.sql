-- Database Update Script: Add License Plate Field to Buses Table
-- Run this script to add the missing license plate field to existing databases

USE `Transport-Booking-System`;

-- Add license plate column to buses table (Malaysian format: 3 letters + 4 numbers)
ALTER TABLE buses
ADD COLUMN license_plate VARCHAR(7) NOT NULL UNIQUE COMMENT 'Malaysian license plate: 3 letters + 4 numbers (e.g., ABC1234)'
AFTER bus_number;

-- Create index for faster license plate lookups
CREATE INDEX idx_license_plate ON buses(license_plate);

-- Update existing buses with sample Malaysian license plates (3 letters + 4 numbers)
-- You should update these with real license plates
UPDATE buses SET license_plate = CONCAT('WMY', LPAD(id, 4, '0')) WHERE license_plate = '' OR license_plate IS NULL;

SELECT 'License plate field added successfully!' as message;
SELECT 'Please update existing buses with their actual license plate numbers.' as note;
