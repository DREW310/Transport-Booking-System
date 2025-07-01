# Transport Booking System (PHP/MySQL)

A modern, full-featured transport booking system built with PHP and MySQL. Supports user and admin roles, bus and schedule management, seat booking, feedback, and more.

## 🚀 Features
- User registration, login, and profile management
- Admin panel for managing buses, routes, schedules, users, bookings, and feedback
- Responsive, modern UI/UX
- Booking with seat selection and real-time seat availability
- Feedback system with ratings and comments
- Dashboard notifications for important events

## 🖥️ How to Run This Application in Your Browser

### 1. Prerequisites
- [XAMPP](https://www.apachefriends.org/index.html) (or any Apache+MySQL stack)
- PHP 7.4 or newer
- MySQL 5.7 or newer

### 2. Setup Steps

#### a. Clone or Download the Project
- Place the folder `Transport-Booking-System-PHP` inside your XAMPP `htdocs` directory:
  - Example: `C:/xampp/htdocs/Transport-Booking-System-PHP`

#### b. Start XAMPP Services
- Open XAMPP Control Panel
- Start **Apache** and **MySQL**

#### c. Import the Database
1. Open [phpMyAdmin](http://localhost/phpmyadmin)
2. Create a new database, e.g., `transport_booking_system`
3. Click the database, then go to **Import**
4. Select the file `transport_booking_tables.sql` from this project and import it

#### d. Configure Database Connection (if needed)
- Open `includes/db.php` and check the database name, username, and password. Default is:
  ```php
  $db = new PDO('mysql:host=localhost;dbname=transport_booking_system', 'root', '');
  ```
- Change if your MySQL uses a different user/password.

#### e. Access the Application
- In your browser, go to: [http://localhost/Transport-Booking-System-PHP/public/index.php](http://localhost/Transport-Booking-System-PHP/public/index.php)

### 3. Default Admin Account
- After importing the database, create an admin user directly in the database or register a user and set `is_staff` or `is_superuser` to `1` in the `users` table via phpMyAdmin.

### 4. Usage
- **Register** as a new user or login as admin
- **Admins** can manage buses, routes, schedules, users, bookings, and feedback
- **Users** can search schedules, book seats, view/cancel bookings, and leave feedback

### 5. Troubleshooting
- If you see a blank page or error, check your PHP error log (XAMPP Control Panel > Logs)
- Make sure Apache and MySQL are running
- Ensure your database credentials in `includes/db.php` are correct

---

**Enjoy your Transport Booking System!**

For questions or issues, open an issue on the [GitHub repository](https://github.com/Yxkong04/TWT-Transport-Booking-System.git). 