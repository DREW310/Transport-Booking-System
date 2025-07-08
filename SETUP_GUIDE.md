# TWT6223 - 1AG5 Transport Booking System - Setup Guide

---

## ** Quick Setup (3 Easy Steps)**

### **Step 1: Start XAMPP Services**
1. Open **XAMPP Control Panel**
2. Click **Start** for:
   - ✅ **Apache** (Web Server)
   - ✅ **MySQL** (Database)

### **Step 2: Import Database (Automated)**
1. Open your browser
2. Go to: `http://localhost/phpmyadmin`
3. Click **"Import"** tab
4. Choose file: `transport_booking_tables.sql`
5. Click **"Go"**

### **Step 3: Access the Application**

**Main Access Points:**
- **Homepage:** `http://localhost/TWT-Transport-Booking-System/public/index.php`
- **User Login:** `http://localhost/TWT-Transport-Booking-System/public/user_login.php`
- **Admin Login:** `http://localhost/TWT-Transport-Booking-System/public/admin_login.php`
- **Register:** `http://localhost/TWT-Transport-Booking-System/public/register.php`

---

## **🔧 Create Your First Admin User**

After database setup, create your admin account:
1. Go to: `http://localhost/TWT-Transport-Booking-System/create_admin.php`
2. Fill in admin details (username, email, password)
3. Click "Create Admin User"
4. Login at: `http://localhost/TWT-Transport-Booking-System/public/admin_login.php`

## **Project Structure**
```
TWT-Transport-Booking-System/
├── 📄 transport_booking_tables.sql  ← Import this file
├── 📁 public/                       ← Main application files
├── 📁 includes/                     ← Configuration & database
├── 📁 controllers/                  ← Business logic
├── 📁 models/                       ← Data models
├── 📁 views/                        ← User interface
└── 📄 SETUP_GUIDE.md               ← This file
```

---

## ** Troubleshooting**

**Problem:** "Can't connect to database"
**Solution:** Make sure MySQL is running in XAMPP

**Problem:** "Database doesn't exist"
**Solution:** Re-import the `transport_booking_tables.sql` file

**Problem:** "Page not found"
**Solution:** Check that Apache is running and you're using the correct URL