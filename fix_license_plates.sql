-- Fix existing license plates to proper Malaysian format
USE `Transport-Booking-System`;

-- Update existing buses with proper Malaysian license plates (3 letters + 4 numbers)
UPDATE buses SET license_plate = CONCAT('WMY', LPAD(id, 4, '0')) 
WHERE LENGTH(license_plate) != 7 OR license_plate NOT REGEXP '^[A-Z]{3}[0-9]{4}$';

SELECT 'License plates updated to Malaysian format!' as message;
SELECT bus_number, license_plate FROM buses;
