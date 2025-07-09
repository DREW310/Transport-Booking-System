# 🚌 TWT Transport Booking System - Complete Implementation

A comprehensive bus booking system built for Malaysian transport companies with modern features and user-friendly design.

## 🌟 **Core Features**

### 👤 **User Module**
- ✅ **User Registration & Login** - Secure authentication with validation
- ✅ **Profile Management** - View and edit personal information
- ✅ **Password Recovery** - Simple 2-step password reset system
- ✅ **Schedule Browsing** - View available buses with real-time updates
- ✅ **Advanced Booking** - Interactive seat selection with visual seat map
- ✅ **Booking Management** - View history, cancel bookings with business rules
- ✅ **Feedback System** - 5-star rating with tags and detailed reviews
- ✅ **Notification System** - Real-time updates for schedule changes

### 🔧 **Admin Module**
- ✅ **Admin Dashboard** - Comprehensive management interface with analytics
- ✅ **Bus Management** - Add, edit, delete buses with Malaysian license plate validation
- ✅ **Route Management** - Complete route administration with duplicate prevention
- ✅ **Schedule Management** - Advanced scheduling with seat map visualization
- ✅ **Booking Oversight** - View, manage, and cancel bookings with data integrity
- ✅ **User Administration** - Complete user management and role assignment
- ✅ **Analytics & Reports** - Revenue tracking, booking statistics, performance metrics
- ✅ **Feedback Management** - Read-only feedback viewing with search and filters

### 🎫 **Booking System**
- ✅ **Real-time Seat Selection** - Interactive seat map with availability status
- ✅ **Smart Search & Filters** - Filter by destination, date, time, company
- ✅ **Booking Confirmation** - Automatic unique ticket ID generation
- ✅ **Payment Integration** - Mock payment system for demonstration
- ✅ **Business Rules Engine** - Automated restriction enforcement
- ✅ **Auto-completion** - Automatic status updates for completed trips

### 🚀 **Advanced Features**
- ✅ **Notification System** - Bell notifications for schedule/bus changes
- ✅ **Business Rules** - Comprehensive data integrity and booking restrictions
- ✅ **Responsive Design** - Mobile-friendly, user-friendly interface
- ✅ **Security Features** - Password hashing, SQL injection prevention, input validation
- ✅ **Malaysian Compliance** - Local bus types, license plate formats (ABC1234)
- ✅ **Consistent UI/UX** - Color-coded status badges, consistent table styling

## ⚡ **Quick Setup Guide (5 Minutes)**

### **Prerequisites**
- ✅ XAMPP installed and running
- ✅ Apache and MySQL services started
- ✅ PHP 7.4+ enabled

### **Step 1: Download & Extract**
1. Download project files
2. Extract to web server directory:
   - **XAMPP:** `C:\xampp\htdocs\TWT-Transport-Booking-System\`

### **Step 2: Database Setup**
1. **Open phpMyAdmin:** `http://localhost/phpmyadmin`
2. **Create Database:**
   - Click "New" → Database name: `transport_booking_system` → Create
3. **Import Schema:**
   - Select your database → Import tab
   - Choose file: `transport_booking_tables.sql` → Go

### **Step 3: Access Application**
- **Homepage:** `http://localhost/TWT-Transport-Booking-System/public/index.php`
- **User Registration:** `http://localhost/TWT-Transport-Booking-System/public/register.php`
- **User Login:** `http://localhost/TWT-Transport-Booking-System/public/user_login.php`
- **Admin Login:** `http://localhost/TWT-Transport-Booking-System/public/admin_login.php`

### **Step 4: Create Admin Account**
1. **Option 1 (Recommended):** Use the admin creation script:
   - Go to: `http://localhost/TWT-Transport-Booking-System/create_admin.php`
   - Fill in admin details and click "Create Admin User"
2. **Option 2 (Manual):** Register a normal user first, then in phpMyAdmin:
   - `UPDATE users SET is_staff = 1, is_superuser = 1 WHERE username = 'your_username';`

---

## 📋 **Detailed Feature List**

### **1. 👤 User Experience**
- **Registration & Authentication:** Secure user registration with validation, login/logout
- **Profile Management:** View and edit personal information, password change
- **Password Recovery:** Simple 2-step password reset without email complexity
- **Dashboard:** Personal booking history, upcoming trips, quick actions
- **Schedule Browsing:** Real-time bus schedules with availability status
- **Advanced Booking:** Interactive seat map, passenger details, payment simulation
- **Booking Management:** View history, cancel bookings (with 1-day restriction)
- **Feedback System:** Rate completed trips with 5-star system and detailed comments
- **Notifications:** Real-time updates for schedule/bus changes via notification bell

### **2. 🔧 Admin Management**
- **Admin Dashboard:** Analytics overview, quick stats, recent activities
- **Bus Management:** Add/edit/delete buses with Malaysian license plate validation
- **Route Management:** Create routes with duplicate prevention, pricing management
- **Schedule Management:** Create schedules with seat map visualization
- **Booking Oversight:** View all bookings, cancel bookings, seat map access
- **User Administration:** Manage user accounts, role assignments
- **Analytics & Reports:** Revenue tracking, booking statistics, performance metrics
- **Feedback Management:** Read-only feedback viewing with search and filter capabilities

### **3. 🎫 Booking System**
- **Real-time Seat Selection:** Interactive visual seat map with availability status
- **Smart Search & Filters:** Filter by destination, date, time, bus company
- **Booking Process:** Step-by-step booking with passenger details and payment
- **Unique Ticket Generation:** Automatic booking ID generation (BK20250708-XXXXXX format)
- **Payment Integration:** Mock payment system for demonstration purposes
- **Booking Confirmation:** Instant confirmation with booking details
- **Auto-completion:** Automatic status updates for completed trips (1 hour after departure)

### **4. 🚌 Bus & Route Management**
- **Bus Information:** Complete bus details (type, capacity, license plate, company)
- **Malaysian Compliance:** License plate validation (ABC1234 format), local bus types
- **Route Management:** Source/destination management with fare pricing
- **Schedule Assignment:** Link buses to routes with departure times
- **Business Rules:** Prevent deletion of buses/routes/schedules with active bookings
- **Data Integrity:** Comprehensive validation and constraint enforcement

### **5. 🔍 Search & Filter Features**
- **Advanced Filtering:** Multi-criteria search (destination, date, time, company)
- **Real-time Results:** Live availability updates and seat counts
- **Schedule Visibility:** Hide schedules 1 hour before departure
- **User-friendly Interface:** Clean, organized search results with clear information

### **6. ⭐ Feedback System**
- **5-Star Rating:** Interactive star rating with hover effects
- **Tag-based Categories:** Predefined aspects (Vehicle Condition, Punctuality, etc.)
- **Comment System:** Detailed feedback with 80-word limit and real-time word count
- **Admin Analytics:** Read-only feedback management with search and filter
- **User-friendly Design:** Modern, intuitive feedback form with success animations

### **7. 🔔 Notification System**
- **Real-time Updates:** Notification bell for schedule/bus changes
- **Change Tracking:** Show what changed from previous state to new state
- **User-specific:** Only passengers receive notifications, not admin users
- **Clean Interface:** Organized notification display with clear messaging

### **8. 🛡️ Security & Business Rules**
- **Authentication:** Secure password hashing, session management
- **Input Validation:** SQL injection prevention, XSS protection
- **Business Logic:** Booking restrictions, cancellation rules, data integrity
- **Role-based Access:** Separate user and admin interfaces with proper permissions
- **Malaysian Standards:** Local compliance for license plates and bus types

---

## 📁 **Project Structure**
```
TWT-Transport-Booking-System/
├── 📄 create_admin.php              ← Admin creation script
├── 📄 transport_booking_tables.sql  ← Database schema (IMPORT THIS)
├── 📁 public/                       ← Main entry points (START HERE)
│   ├── index.php                    ← Homepage
│   ├── register.php                 ← User registration
│   ├── user_login.php               ← User login
│   ├── admin_login.php              ← Admin login
│   ├── logout.php                   ← Logout handler
│   └── forgot_password.php          ← Password recovery
├── 📁 includes/                     ← Configuration files
│   ├── db.php                       ← Database connection
│   └── functions.php                ← Helper functions
├── 📁 views/                        ← User interface files
│   ├── dashboard.php                ← User dashboard
│   ├── admin_dashboard.php          ← Admin dashboard
│   ├── booking.php                  ← Booking system
│   ├── feedback.php                 ← Feedback system
│   ├── header.php                   ← Common header
│   ├── footer.php                   ← Common footer
│   └── [other interface files]
├── 📁 assets/                       ← Static resources
│   ├── css/                         ← Stylesheets
│   ├── js/                          ← JavaScript files
│   └── images/                      ← Image assets
└── 📄 README.md                     ← This file
```

## 🔧 **Default Login Credentials**
After setting up the database and creating an admin account:

**Admin Access:**
- Username: `admin` (or whatever you created)
- Password: `[your chosen password]`

**Test User Account:**
- Create via registration form at `/views/register.php`

---

## 🚨 **Troubleshooting**

### **Common Issues:**
1. **❌ Database Connection Failed**
   - ✅ Verify MySQL is running in XAMPP Control Panel
   - ✅ Check database name is `transport_booking_system` in `includes/db.php`
   - ✅ Ensure database was imported correctly

2. **❌ Page Not Found (404) Errors**
   - ✅ Verify project is in correct directory: `C:\xampp\htdocs\TWT-Transport-Booking-System\`
   - ✅ Check Apache is running in XAMPP
   - ✅ Use correct URLs (no `/public/` directory)

3. **❌ Blank Pages or PHP Errors**
   - ✅ Check PHP error logs in XAMPP
   - ✅ Verify PHP 7.4+ is enabled
   - ✅ Ensure all required files are present

4. **❌ Admin Login Issues**
   - ✅ Use `create_admin.php` to create admin account
   - ✅ Or manually update user: `UPDATE users SET is_staff = 1, is_superuser = 1 WHERE username = 'your_username';`

5. **❌ Booking/Seat Selection Not Working**
   - ✅ Ensure JavaScript is enabled in browser
   - ✅ Check browser console for JavaScript errors
   - ✅ Verify database has sample data (buses, routes, schedules)

---

## 🎯 **Key URLs for Testing**
- **Homepage:** `http://localhost/TWT-Transport-Booking-System/public/index.php`
- **User Registration:** `http://localhost/TWT-Transport-Booking-System/public/register.php`
- **User Login:** `http://localhost/TWT-Transport-Booking-System/public/user_login.php`
- **Admin Login:** `http://localhost/TWT-Transport-Booking-System/public/admin_login.php`
- **Create Admin:** `http://localhost/TWT-Transport-Booking-System/create_admin.php`

---

## 📞 **Support**
If you encounter any issues during setup or usage, please check the troubleshooting section above or refer to the `SETUP_GUIDE.md` for detailed installation instructions.