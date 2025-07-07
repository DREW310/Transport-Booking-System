# 🧪 Comprehensive Testing Guide - Transport Booking System

## 📋 **Testing Overview**

This guide provides step-by-step testing procedures for all system features. Follow each section carefully to verify complete functionality.

---

## 🚀 **Pre-Testing Setup**

### **1. Environment Requirements**
- ✅ XAMPP/WAMP running (Apache + MySQL)
- ✅ PHP 7.4+ enabled
- ✅ Database imported successfully
- ✅ All files in correct directory structure

### **2. Database Verification**
```sql
-- Run these queries in phpMyAdmin to verify setup
USE `Transport-Booking-System`;
SHOW TABLES;
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM buses;
SELECT COUNT(*) FROM routes;
SELECT COUNT(*) FROM schedules;
```

**Expected Results:**
- 8+ tables should exist
- Sample data should be present in all tables

### **3. Access Points to Test**
- **Homepage:** `http://localhost/TWT_Transport_Booking_System/public/index.php`
- **User Login:** `http://localhost/TWT_Transport_Booking_System/public/user_login.php`
- **Admin Login:** `http://localhost/TWT_Transport_Booking_System/public/admin_login.php`
- **Registration:** `http://localhost/TWT_Transport_Booking_System/public/register.php`

---

## 🔐 **Module 1: User Authentication Testing**

### **Test 1.1: User Registration**
**Steps:**
1. Go to: `http://localhost/TWT_Transport_Booking_System/public/register.php`
2. Fill in registration form:
   - Username: `testuser1`
   - Email: `testuser1@example.com`
   - Password: `password123`
   - Confirm Password: `password123`
   - Full Name: `Test User One`
   - Phone: `0123456789`
   - Address: `123 Test Street`
3. Click "Register"

**Expected Results:**
- ✅ Success message appears
- ✅ User redirected to login page
- ✅ New user appears in database

**Error Testing:**
- Try duplicate username → Should show error
- Try weak password → Should show validation error
- Try mismatched passwords → Should show error

### **Test 1.2: User Login**
**Steps:**
1. Go to: `http://localhost/TWT_Transport_Booking_System/public/user_login.php`
2. Login with:
   - Email: `testuser1@example.com`
   - Password: `password123`
3. Click "Login"

**Expected Results:**
- ✅ Successful login
- ✅ Redirected to user dashboard
- ✅ Username appears in navigation

**Error Testing:**
- Try wrong password → Should show error
- Try non-existent email → Should show error

### **Test 1.3: Password Reset (Simple Version)**
**Steps:**
1. Go to: `http://localhost/TWT_Transport_Booking_System/public/forgot_password.php`
2. **Step 1 - Account Verification:**
   - Email: `testuser1@example.com`
   - Username: `testuser1`
   - Click "Verify Account"
3. **Step 2 - Reset Password:**
   - New Password: `newpassword123`
   - Confirm Password: `newpassword123`
   - Click "Reset Password"
4. **Test New Password:**
   - Logout and login with new password

**Expected Results:**
- ✅ Step 1: Account verified successfully
- ✅ Step 2: Password reset confirmation
- ✅ Can login with new password
- ✅ Cannot login with old password

---

## 👤 **Module 2: User Profile Management**

### **Test 2.1: View Profile**
**Steps:**
1. Login as regular user
2. Go to: `http://localhost/TWT_Transport_Booking_System/views/profile.php`

**Expected Results:**
- ✅ Profile information displayed correctly
- ✅ All fields populated from database
- ✅ Edit button available

### **Test 2.2: Edit Profile**
**Steps:**
1. From profile page, click "Edit Profile"
2. Update information:
   - Full Name: `Updated Test User`
   - Phone: `0987654321`
   - Address: `456 Updated Street`
3. Click "Update Profile"

**Expected Results:**
- ✅ Success message appears
- ✅ Updated information displayed
- ✅ Changes saved in database

---

## 🚌 **Module 3: Bus Schedule and Booking System**

### **Test 3.1: View Available Schedules**
**Steps:**
1. Login as regular user
2. Go to: `http://localhost/TWT_Transport_Booking_System/views/schedule.php`

**Expected Results:**
- ✅ List of available schedules displayed
- ✅ Search and filter options available
- ✅ "Book Now" buttons visible
- ✅ Seat availability shown

### **Test 3.2: Search and Filter Functionality**
**Steps:**
1. On schedule page, test filters:
   - **Source Filter:** Select "Kuala Lumpur"
   - **Destination Filter:** Select "Penang"
   - **Date Filter:** Select tomorrow's date
2. Click "Search"

**Expected Results:**
- ✅ Results filtered correctly
- ✅ Only matching schedules shown
- ✅ "No results" message if no matches

### **Test 3.3: Seat Selection and Booking**
**Steps:**
1. From schedule page, click "Book Now" on any schedule
2. **Seat Selection:**
   - View seat map
   - Click on available seat (green)
   - Try clicking booked seat (red) - should be disabled
3. **Passenger Details:**
   - Payment Method: Select "Credit Card"
   - Click "Confirm Booking"

**Expected Results:**
- ✅ Seat map displays correctly
- ✅ Available seats clickable, booked seats disabled
- ✅ Booking confirmation page appears
- ✅ Unique booking ID generated
- ✅ Seat becomes unavailable for other users

### **Test 3.4: Booking History**
**Steps:**
1. Go to: `http://localhost/TWT_Transport_Booking_System/views/booking_history.php`

**Expected Results:**
- ✅ All user bookings displayed
- ✅ Booking details accurate
- ✅ Status shown correctly
- ✅ Cancel buttons available (where applicable)

### **Test 3.5: Booking Cancellation**
**Steps:**
1. From booking history, find a future booking
2. Click "Cancel Booking"
3. Confirm cancellation

**Expected Results:**
- ✅ Cancellation confirmation dialog
- ✅ Booking status changed to "Cancelled"
- ✅ Seat becomes available again
- ✅ Cannot cancel within 1 day of departure

---

## 🎯 **Module 4: Feedback System**

### **Test 4.1: Submit Feedback**
**Steps:**
1. Go to: `http://localhost/TWT_Transport_Booking_System/views/feedback_submit.php`
2. Select a completed booking
3. Fill feedback form:
   - Rating: 4 stars
   - Tags: Select "Comfortable", "On Time"
   - Comment: "Great service, comfortable journey"
4. Click "Submit Feedback"

**Expected Results:**
- ✅ Feedback submitted successfully
- ✅ Success message displayed
- ✅ Feedback appears in system

### **Test 4.2: View Feedback**
**Steps:**
1. Go to: `http://localhost/TWT_Transport_Booking_System/views/feedback_view.php`

**Expected Results:**
- ✅ All feedback displayed
- ✅ Ratings and comments visible
- ✅ Filter options work
- ✅ Average ratings calculated correctly

---

## 🛡️ **Module 5: Admin System Testing**

### **Test 5.1: Admin Login**
**Steps:**
1. Create admin user:
   - Register normally, then set `is_staff = 1` in database
   - OR use existing admin account
2. Go to: `http://localhost/TWT_Transport_Booking_System/public/admin_login.php`
3. Login with admin credentials

**Expected Results:**
- ✅ Admin dashboard loads
- ✅ Admin navigation menu appears
- ✅ Dashboard statistics displayed

### **Test 5.2: Bus Management**
**Steps:**
1. Go to: `http://localhost/TWT_Transport_Booking_System/views/admin_buses.php`
2. **Add New Bus:**
   - Bus Number: `TEST-001`
   - License Plate: `ABC1234` (Malaysian format)
   - Bus Type: `Express Bus`
   - Capacity: `45`
   - Company: `Test Transport`
   - Click "Add Bus"
3. **Edit Bus:**
   - Click "Edit" on any bus
   - Update details
   - Click "Update Bus"
4. **Delete Bus:**
   - Try deleting bus with bookings → Should show error
   - Try deleting bus without bookings → Should succeed

**Expected Results:**
- ✅ New bus added successfully
- ✅ Bus edits saved correctly
- ✅ Delete restrictions work (business rules)
- ✅ Error messages clear and helpful

### **Test 5.3: Route Management**
**Steps:**
1. Go to: `http://localhost/TWT_Transport_Booking_System/views/admin_routes.php`
2. **Add New Route:**
   - Source: `Test City A`
   - Destination: `Test City B`
   - Fare: `25.50`
   - Click "Add Route"
3. **Test Duplicate Prevention:**
   - Try adding same route → Should show error
4. **Edit and Delete:**
   - Edit route details
   - Try deleting route with/without bookings

**Expected Results:**
- ✅ Route added successfully
- ✅ Duplicate prevention works
- ✅ Edit/delete business rules enforced

### **Test 5.4: Schedule Management**
**Steps:**
1. Go to: `http://localhost/TWT_Transport_Booking_System/views/admin_schedules.php`
2. **Add New Schedule:**
   - Select Bus: Choose from dropdown
   - Select Route: Choose from dropdown
   - Departure Time: Set future date/time
   - Available Seats: Auto-filled from bus capacity
   - Click "Add Schedule"
3. **View Seat Map:**
   - Click "View Seats" on any schedule
   - Verify seat map accuracy
4. **Edit/Delete Testing:**
   - Try editing schedule with completed bookings
   - Try deleting schedule with bookings

**Expected Results:**
- ✅ Schedule created successfully
- ✅ Seat map displays correctly
- ✅ Business rules enforced (completed bookings)

### **Test 5.5: Booking Management**
**Steps:**
1. Go to: `http://localhost/TWT_Transport_Booking_System/views/admin_bookings.php`
2. **View All Bookings:**
   - Verify all bookings displayed
   - Test filter options
3. **Cancel Booking:**
   - Click "Cancel" on active booking
   - Confirm cancellation
4. **Search and Filter:**
   - Filter by status
   - Search by booking ID
   - Filter by date range

**Expected Results:**
- ✅ All bookings visible to admin
- ✅ Filters work correctly
- ✅ Admin can cancel bookings
- ✅ Seat availability updates correctly

### **Test 5.6: User Management**
**Steps:**
1. Go to: `http://localhost/TWT_Transport_Booking_System/views/admin_users.php`
2. **View Users:**
   - All users displayed
   - User details accurate
3. **Edit User:**
   - Click "Edit" on any user
   - Update user information
   - Change user role (staff/regular)

**Expected Results:**
- ✅ User list complete and accurate
- ✅ User edits save correctly
- ✅ Role changes work properly

### **Test 5.7: Reports and Analytics**
**Steps:**
1. Go to: `http://localhost/TWT_Transport_Booking_System/views/admin_reports.php`
2. **Generate Reports:**
   - Daily booking report
   - Revenue report
   - Popular routes report
3. **Verify Data:**
   - Check report accuracy
   - Test date range filters

**Expected Results:**
- ✅ Reports generate correctly
- ✅ Data matches database
- ✅ Charts/graphs display properly

### **Test 5.8: Feedback Management**
**Steps:**
1. Go to: `http://localhost/TWT_Transport_Booking_System/views/admin_feedback.php`
2. **View Feedback:**
   - All feedback displayed
   - Search and filter options
3. **Manage Feedback:**
   - Verify read-only access (no edit/delete)
   - Test filtering by rating/date

**Expected Results:**
- ✅ All feedback visible
- ✅ Read-only interface (as per requirements)
- ✅ Filters work correctly

---

## 🔔 **Module 6: Notification System**

### **Test 6.1: User Notifications**
**Steps:**
1. Login as regular user
2. Check notification bell in header
3. Have admin update a bus/route for your booking
4. Check for new notifications

**Expected Results:**
- ✅ Notification bell shows count
- ✅ Notifications appear for relevant changes
- ✅ Only active bookings get notifications

### **Test 6.2: Admin Notifications**
**Steps:**
1. Login as admin
2. Check for system notifications
3. Perform admin actions that trigger notifications

**Expected Results:**
- ✅ Admin receives system notifications
- ✅ Notification content accurate

---

## 🛡️ **Business Rules Testing**

### **Test 7.1: Booking Restrictions**
**Steps:**
1. **Time Restrictions:**
   - Try booking schedule departing in < 1 hour
   - Try cancelling booking < 1 day before departure
2. **Seat Restrictions:**
   - Try booking already booked seat
   - Verify seat map accuracy

**Expected Results:**
- ✅ Time restrictions enforced
- ✅ Seat conflicts prevented
- ✅ Clear error messages

### **Test 7.2: Admin Deletion Restrictions**
**Steps:**
1. **Bus Deletion:**
   - Try deleting bus with active bookings → Should fail
   - Try deleting bus with completed bookings → Should fail
   - Try deleting bus with no bookings → Should succeed
2. **Route Deletion:**
   - Same tests as bus deletion
3. **Schedule Deletion:**
   - Try deleting schedule with any bookings → Should fail

**Expected Results:**
- ✅ All business rules enforced
- ✅ Data integrity maintained
- ✅ Helpful error messages

---

## 🎨 **UI/UX Testing**

### **Test 8.1: Responsive Design**
**Steps:**
1. Test on different screen sizes:
   - Desktop (1920x1080)
   - Tablet (768x1024)
   - Mobile (375x667)
2. Check all major pages

**Expected Results:**
- ✅ Layout adapts to screen size
- ✅ All elements accessible
- ✅ Navigation works on mobile

### **Test 8.2: Cross-Browser Testing**
**Steps:**
1. Test in multiple browsers:
   - Chrome
   - Firefox
   - Edge
   - Safari (if available)

**Expected Results:**
- ✅ Consistent appearance
- ✅ All functionality works
- ✅ No JavaScript errors

---

## 🚨 **Error Handling Testing**

### **Test 9.1: Database Errors**
**Steps:**
1. Temporarily stop MySQL service
2. Try accessing any page
3. Restart MySQL

**Expected Results:**
- ✅ Graceful error handling
- ✅ User-friendly error messages
- ✅ No system crashes

### **Test 9.2: Invalid URLs**
**Steps:**
1. Try accessing non-existent pages
2. Try accessing admin pages as regular user
3. Try SQL injection in URL parameters

**Expected Results:**
- ✅ 404 errors handled gracefully
- ✅ Access control enforced
- ✅ Security measures active

---

## ✅ **Final Verification Checklist**

### **Core Functionality:**
- [ ] User registration and login
- [ ] Password reset (simple version)
- [ ] Profile management
- [ ] Schedule viewing and searching
- [ ] Seat selection and booking
- [ ] Booking history and cancellation
- [ ] Feedback submission and viewing
- [ ] Admin dashboard and management
- [ ] Reports and analytics
- [ ] Notification system

### **Business Rules:**
- [ ] Booking time restrictions
- [ ] Cancellation restrictions
- [ ] Admin deletion restrictions
- [ ] Seat availability accuracy
- [ ] Data integrity maintained

### **Security:**
- [ ] Password hashing
- [ ] SQL injection prevention
- [ ] Access control
- [ ] Input validation

### **User Experience:**
- [ ] Responsive design
- [ ] Intuitive navigation
- [ ] Clear error messages
- [ ] Consistent styling

---

## 🎯 **Testing Completion**

When all tests pass:
1. ✅ System is ready for demonstration
2. ✅ All features working correctly
3. ✅ No critical errors present
4. ✅ Ready for coursework submission

**If any test fails:**
1. Document the issue
2. Check error logs
3. Verify database integrity
4. Re-test after fixes
