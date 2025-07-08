# TWT6223 - 1AG5 Transport Booking System - Complete Implementation

##  **Core Features**

### ** User Module**
- ✅ **User Registration & Login** - Complete authentication system
- ✅ **Profile Management** - View and edit user profiles
- ✅ **Simple Password Reset** - 2-step verification (no email complexity)
- ✅ **Schedule Browsing** - View available buses and schedules
- ✅ **Advanced Booking** - Seat selection with visual seat map
- ✅ **Booking Management** - View history and cancel bookings
- ✅ **Feedback System** - Rate and review completed trips

### ** Admin Module**
- ✅ **Admin Dashboard** - Comprehensive management interface
- ✅ **Bus Management** - Add, edit, delete with business rules
- ✅ **Route Management** - Complete route administration
- ✅ **Schedule Management** - Advanced scheduling with seat maps
- ✅ **Booking Oversight** - View and manage all bookings
- ✅ **User Administration** - Complete user management
- ✅ **Analytics & Reports** - Revenue, booking, and performance reports
- ✅ **Feedback Management** - View and analyze user feedback

### ** Booking System**
- ✅ **Real-time Seat Selection** - Interactive seat map interface
- ✅ **Smart Search & Filters** - By destination, date, time, company
- ✅ **Booking Confirmation** - Automatic ticket generation
- ✅ **Payment Integration** - Mock payment system for demonstration
- ✅ **Business Rules Engine** - Automated restriction enforcement

### ** Advanced Features**
- ✅ **Notification System** - Real-time updates for users and admins
- ✅ **Business Rules** - Comprehensive data integrity protection
- ✅ **Responsive Design** - Mobile-friendly interface
- ✅ **Security Features** - Password hashing, SQL injection prevention
- ✅ **Malaysian Compliance** - License plate validation, local bus types

##  **Quick Setup Guide (5 Minutes)**

### **Prerequisites**
- ✅ XAMPP installed and running
- ✅ Apache and MySQL services started
- ✅ PHP 7.4+ enabled

### **Step 1: Download & Extract**
1. Download project files
2. Extract to web server directory:
   - **XAMPP:** `C:\xampp\htdocs\TWT_Transport_Booking_System\`

### **Step 2: Database Setup**
1. **Open phpMyAdmin:** `http://localhost/phpmyadmin`
2. **Create Database:**
   - Click "New" → Database name: `Transport-Booking-System` → Create
3. **Import Schema:**
   - Select your database → Import tab
   - Choose file: `transport_booking_tables.sql` → Go

### **Step 3: Access Application**
- **Homepage:** `http://localhost/TWT_Transport_Booking_System/public/index.php`
- **User Login:** `http://localhost/TWT_Transport_Booking_System/public/user_login.php`
- **Admin Login:** `http://localhost/TWT_Transport_Booking_System/public/admin_login.php`

### **Step 4: Create Admin Account**
1. Register a normal user first
2. In phpMyAdmin: `UPDATE users SET is_staff = 1 WHERE username = 'your_username';`

---

## ** Feature List**

#### **1. User Module**
- User registration and login with validation
- Profile management (view/edit personal information)
- Simple password reset (2-step verification)
- Browse available buses and schedules
- Advanced booking with seat selection
- Booking history and management
- Trip cancellation (with business rules)

#### **2. Admin Module**
- Comprehensive admin dashboard
- Bus management (add/edit/delete with restrictions)
- Route management with duplicate prevention
- Schedule management with seat maps
- Complete booking oversight and management
- User administration and role management
- Advanced analytics and reporting system

#### **3. Booking System**
- Real-time schedule display with availability
- Interactive seat selection with visual seat map
- Comprehensive booking form with passenger details
- Mock payment system integration
- Automatic unique ticket generation
- Instant booking confirmation

#### **4. Bus and Route Management**
- Complete bus information management (ID, type, capacity, license plates)
- Route management (source, destination, fare, time)
- Schedule assignment and management
- Malaysian license plate validation (ABC1234 format)
- Business rules enforcement for data integrity

#### **5. Search and Filter Features**
- Advanced filtering by destination, date, and time
- Search by bus company and route
- Real-time results with availability updates
- Smart search suggestions and auto-complete

#### **6. Feedback System**
- 5-star rating system for completed trips
- Tag-based feedback categorization
- Comment system with word limit validation
- Admin feedback management and analytics

### ** Additional Advanced Features:**
- **Notification System** - Real-time updates for users and admins
- **Business Rules Engine** - Automated data integrity protection
- **Responsive Design** - Mobile-friendly interface
- **Security Features** - Password hashing, SQL injection prevention
- **Malaysian Compliance** - Local bus types and license plate formats

---

### **Common Issues:**
1. **Database Connection Failed**
   - Verify MySQL is running in XAMPP
   - Check database name in `includes/db.php`

2. **Page Not Found Errors**
   - Verify correct directory structure
   - Check file paths in URLs

3. **Blank Pages**
   - Check PHP error logs
   - Verify PHP version compatibility

4. **Permission Errors**
   - Ensure proper folder permissions
   - Check Apache configuration