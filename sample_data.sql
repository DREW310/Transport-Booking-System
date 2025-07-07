-- Sample Data for Transport Booking System
-- Run this after creating your admin user to test the application

USE `Transport-Booking-System`;

-- Insert sample buses (with proper Malaysian license plate format: 3 letters + 4 numbers)
INSERT INTO buses (bus_number, license_plate, bus_type, capacity, company) VALUES
('TWT-001', 'WMY1234', 'express-bus', 45, 'TWT Transport'),
('TWT-002', 'WMY5678', 'luxury-coach', 40, 'TWT Transport'),
('TWT-003', 'WMY9012', 'vip-bus', 27, 'TWT Transport'),
('TWT-004', 'WMY3456', 'super-vip', 18, 'TWT Transport'),
('TWT-005', 'WMY7890', 'city-bus', 60, 'TWT Transport');

-- Insert sample routes
INSERT INTO routes (source, destination, fare) VALUES
('London', 'Manchester', 25.50),
('London', 'Birmingham', 18.75),
('Manchester', 'Liverpool', 12.00),
('Birmingham', 'Nottingham', 15.25),
('London', 'Leeds', 32.00),
('Manchester', 'Sheffield', 14.50),
('Birmingham', 'Leicester', 11.75),
('London', 'Bristol', 28.25);

-- Insert sample schedules (next 7 days)
INSERT INTO schedules (bus_id, route_id, departure_time, available_seats) VALUES
-- London to Manchester
(1, 1, '2025-07-07 08:00:00', 40),
(1, 1, '2025-07-07 14:00:00', 40),
(1, 1, '2025-07-07 20:00:00', 40),
(4, 1, '2025-07-08 09:00:00', 40),
(4, 1, '2025-07-08 15:00:00', 40),

-- London to Birmingham
(2, 2, '2025-07-07 07:30:00', 45),
(2, 2, '2025-07-07 13:30:00', 45),
(2, 2, '2025-07-07 19:30:00', 45),
(5, 2, '2025-07-08 08:30:00', 50),
(5, 2, '2025-07-08 16:30:00', 50),

-- Manchester to Liverpool
(2, 3, '2025-07-07 10:00:00', 45),
(2, 3, '2025-07-07 16:00:00', 45),
(5, 3, '2025-07-08 11:00:00', 50),
(5, 3, '2025-07-08 17:00:00', 50),

-- Birmingham to Nottingham
(3, 4, '2025-07-07 09:15:00', 32),
(3, 4, '2025-07-07 15:15:00', 32),
(1, 4, '2025-07-08 10:15:00', 40),

-- London to Leeds
(3, 5, '2025-07-07 06:45:00', 32),
(3, 5, '2025-07-07 18:45:00', 32),
(4, 5, '2025-07-08 07:45:00', 40),

-- Manchester to Sheffield
(2, 6, '2025-07-07 11:30:00', 45),
(5, 6, '2025-07-08 12:30:00', 50),

-- Birmingham to Leicester
(1, 7, '2025-07-07 08:45:00', 40),
(4, 7, '2025-07-08 09:45:00', 40),

-- London to Bristol
(3, 8, '2025-07-07 07:00:00', 32),
(3, 8, '2025-07-07 19:00:00', 32);

-- Insert a sample regular user for testing
INSERT INTO users (username, password, email, is_staff, is_superuser) VALUES
('testuser', 'password123', 'testuser@example.com', 0, 0);

-- Get the user ID and create profile
SET @user_id = LAST_INSERT_ID();
INSERT INTO profiles (user_id, full_name, phone, address) VALUES
(@user_id, 'Test User', '07123456789', '123 Test Street, Test City, TC1 2AB');

-- Insert a sample booking for demonstration
INSERT INTO bookings (user_id, schedule_id, seat_number, booking_time, payment_method, status, booking_id) VALUES
(@user_id, 1, 15, NOW(), 'Credit Card', 'booked', 'BK001');

-- Update available seats for the booked schedule
UPDATE schedules SET available_seats = available_seats - 1 WHERE id = 1;

-- Insert sample feedback
INSERT INTO feedback (user_id, bus_id, booking_id, rating, comment, tags) VALUES
(@user_id, 1, 1, 5, 'Excellent service! Very comfortable journey and on-time departure.', 'Punctuality,Seat comfort,Staff behavior');

SELECT 'Sample data inserted successfully!' as message;
SELECT 'You can now test the application with:' as info;
SELECT 'Test User Login - Username: testuser, Password: password123' as login_info;
