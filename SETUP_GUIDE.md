# 🚌 TWT Transport Booking System - Complete Setup Guide

A step-by-step guide to get our transport booking system up and running in minutes!

---

## ⚡ **Quick Setup (3 Easy Steps)**

### **Step 1: Start XAMPP Services**
1. Open **XAMPP Control Panel**
2. Click **Start** for:
   - ✅ **Apache** (Web Server) - Must show green "Running" status
   - ✅ **MySQL** (Database) - Must show green "Running" status

> **⚠️ Important:** Both services must be running before proceeding!

### **Step 2: Database Setup**
1. **Open phpMyAdmin:**
   - Go to: `http://localhost/phpmyadmin`
   - If this doesn't work, ensure Apache and MySQL are running

2. **Create Database:**
   - Click **"New"** in the left sidebar
   - Database name: `transport_booking_system`
   - Collation: `utf8mb4_general_ci` (recommended)
   - Click **"Create"**

3. **Import Database Schema:**
   - Select your newly created database from the left sidebar
   - Click **"Import"** tab at the top
   - Click **"Choose File"** and select: `transport_booking_tables.sql`
   - Click **"Go"** at the bottom
   - ✅ You should see "Import has been successfully finished"

### **Step 3: Access the Application**

**🌐 Main Access Points:**
- **Homepage:** `http://localhost/TWT-Transport-Booking-System/public/index.php`
- **User Registration:** `http://localhost/TWT-Transport-Booking-System/public/register.php`
- **User Login:** `http://localhost/TWT-Transport-Booking-System/public/user_login.php`
- **Admin Login:** `http://localhost/TWT-Transport-Booking-System/public/admin_login.php`

---

## 👨‍💼 **Create Your First Admin User**

### **Method 1: Using Admin Creation Script (Recommended)**
1. Go to: `http://localhost/TWT-Transport-Booking-System/create_admin.php`
2. Fill in admin details:
   - **Username:** Choose a unique username
   - **Email:** Your email address
   - **Password:** Strong password (min 6 characters)
3. Click **"Create Admin User"**
4. ✅ Success! Now login at: `http://localhost/TWT-Transport-Booking-System/public/admin_login.php`

### **Method 2: Manual Database Update**
1. First register a normal user at: `/public/register.php`
2. Open phpMyAdmin: `http://localhost/phpmyadmin`
3. Select `transport_booking_system` database
4. Click on `users` table
5. Find your user and click **"Edit"**
6. Set `is_staff = 1` and `is_superuser = 1`
7. Click **"Go"** to save

---

## 📁 **Project Structure**
```
TWT-Transport-Booking-System/
├── 📄 create_admin.php              ← Admin creation script
├── 📄 transport_booking_tables.sql  ← Database schema (IMPORT THIS FIRST)
├── 📄 README.md                     ← Project documentation
├── 📄 SETUP_GUIDE.md               ← This setup guide
├── 📁 public/                       ← Main entry points (START HERE)
│   ├── index.php                    ← Homepage
│   ├── register.php                 ← User registration
│   ├── user_login.php               ← User login
│   ├── admin_login.php              ← Admin login
│   ├── logout.php                   ← Logout handler
│   └── forgot_password.php          ← Password recovery
├── 📁 includes/                     ← Configuration files
│   ├── db.php                       ← Database connection settings
│   └── functions.php                ← Helper functions
├── 📁 views/                        ← User interface pages
│   ├── header.php                   ← Common header
│   ├── footer.php                   ← Common footer
│   ├── dashboard.php                ← User dashboard
│   ├── admin_dashboard.php          ← Admin dashboard
│   ├── booking.php                  ← Booking system
│   ├── feedback.php                 ← Feedback system
│   └── [other interface files]
└── 📁 assets/                       ← Static resources (CSS, JS, images)
```

---

## 🧪 **Testing Your Setup**

### **1. Test Basic Access**
- ✅ Homepage loads: `http://localhost/TWT-Transport-Booking-System/public/index.php`
- ✅ Registration works: Create a test user account
- ✅ Login works: Login with your test account
- ✅ Admin creation works: Create admin via `create_admin.php`
- ✅ Admin login works: Login to admin panel

### **2. Test Core Features**
- ✅ **User Dashboard:** View bookings and profile
- ✅ **Admin Dashboard:** Access admin management tools
- ✅ **Booking System:** Try making a test booking
- ✅ **Feedback System:** Submit feedback for completed trips

---

## 🚨 **Troubleshooting Guide**

### **❌ "Can't connect to database"**
**Possible Causes:**
- MySQL service not running
- Wrong database name
- Database not created

**Solutions:**
1. ✅ Check XAMPP Control Panel - MySQL must show "Running"
2. ✅ Verify database name is `transport_booking_system`
3. ✅ Re-import the SQL file if database is empty

### **❌ "Database doesn't exist"**
**Solution:**
1. ✅ Go to phpMyAdmin: `http://localhost/phpmyadmin`
2. ✅ Create database: `transport_booking_system`
3. ✅ Re-import: `transport_booking_tables.sql`

### **❌ "Page not found" (404 Error)**
**Possible Causes:**
- Apache not running
- Wrong URL path
- Files in wrong directory

**Solutions:**
1. ✅ Check XAMPP Control Panel - Apache must show "Running"
2. ✅ Verify project is in: `C:\xampp\htdocs\TWT-Transport-Booking-System\`
3. ✅ Use correct URLs (no `/public/` in path)

### **❌ "Blank white page"**
**Possible Causes:**
- PHP errors
- Missing files
- Wrong PHP version

**Solutions:**
1. ✅ Check XAMPP error logs
2. ✅ Ensure PHP 7.4+ is enabled
3. ✅ Verify all project files are present

### **❌ "Admin login not working"**
**Solutions:**
1. ✅ Use `create_admin.php` to create admin account
2. ✅ Or manually set user as admin in database:
   ```sql
   UPDATE users SET is_staff = 1, is_superuser = 1 WHERE username = 'your_username';
   ```

### **❌ "Booking system not working"**
**Solutions:**
1. ✅ Ensure JavaScript is enabled in browser
2. ✅ Check browser console for errors (F12)
3. ✅ Verify database has sample data (buses, routes, schedules)

---

## 📞 **Need Help?**

If you're still having issues:
1. 📋 Check the **README.md** for detailed feature information
2. 🔍 Review error messages carefully
3. 🌐 Ensure all URLs use the correct path structure
4. 💾 Verify database import was successful

**Common Success Indicators:**
- ✅ XAMPP shows Apache and MySQL as "Running"
- ✅ phpMyAdmin opens without errors
- ✅ Database contains tables after import
- ✅ Homepage loads with navigation menu
- ✅ User registration and login work
- ✅ Admin panel is accessible

---

## 🎉 **You're All Set!**

Once everything is working:
1. 👤 **Create user accounts** for testing
2. 👨‍💼 **Set up admin account** for management
3. 🚌 **Add buses and routes** via admin panel
4. 📅 **Create schedules** for booking
5. 🎫 **Test the booking system** end-to-end
6. ⭐ **Try the feedback system** with completed bookings

**Enjoy your new transport booking system!** 🚀