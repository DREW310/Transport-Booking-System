# ⚡ Quick Testing Checklist - For Team Members

## 🚀 **Setup Verification (5 minutes)**

### **1. Environment Check**
- [ ] XAMPP/WAMP running
- [ ] Apache and MySQL services started
- [ ] Database `Transport-Booking-System` exists
- [ ] Sample data imported

### **2. Quick Access Test**
- [ ] Homepage loads: `http://localhost/TWT_Transport_Booking_System/public/index.php`
- [ ] No PHP errors displayed
- [ ] Navigation links work

---

## 👤 **User Features (15 minutes)**

### **3. Registration & Login**
- [ ] Register new user successfully
- [ ] Login with new credentials
- [ ] Password reset works (email + username verification)

### **4. Booking Process**
- [ ] View available schedules
- [ ] Search/filter schedules
- [ ] Select seat from seat map
- [ ] Complete booking successfully
- [ ] View booking in history

### **5. User Management**
- [ ] Edit profile information
- [ ] Cancel future booking
- [ ] Submit feedback for completed trip

---

## 🛡️ **Admin Features (20 minutes)**

### **6. Admin Access**
- [ ] Create admin user (set `is_staff = 1` in database)
- [ ] Login to admin dashboard
- [ ] Dashboard statistics display correctly

### **7. Bus Management**
- [ ] Add new bus with Malaysian license plate (ABC1234 format)
- [ ] Edit existing bus details
- [ ] Try deleting bus with bookings (should fail)
- [ ] Delete bus without bookings (should succeed)

### **8. Route & Schedule Management**
- [ ] Add new route
- [ ] Create schedule for route
- [ ] View seat map for schedule
- [ ] Try editing schedule with completed bookings (should be read-only)

### **9. Booking & User Management**
- [ ] View all bookings with filters
- [ ] Cancel user booking as admin
- [ ] View and manage users
- [ ] Generate booking reports

---

## 🔍 **Business Rules Testing (10 minutes)**

### **10. Booking Restrictions**
- [ ] Cannot book seat that's already taken
- [ ] Cannot cancel booking within 1 day of departure
- [ ] Schedules disappear 1 hour before departure

### **11. Admin Restrictions**
- [ ] Cannot delete bus/route with existing bookings
- [ ] Cannot edit schedule with completed bookings
- [ ] Proper error messages displayed

---

## 🔔 **Notification System (5 minutes)**

### **12. Notifications**
- [ ] User receives notification when admin updates their bus/route
- [ ] Only active bookings get notifications (not completed ones)
- [ ] Notification bell shows correct count

---

## 🎨 **UI/UX Quick Check (5 minutes)**

### **13. Design & Responsiveness**
- [ ] Pages look professional and consistent
- [ ] Mobile-friendly design
- [ ] No broken layouts or missing styles
- [ ] Error messages are user-friendly

---

## ⚠️ **Error Testing (5 minutes)**

### **14. Error Handling**
- [ ] Try invalid login credentials
- [ ] Try accessing admin pages as regular user
- [ ] Try duplicate registration
- [ ] All errors handled gracefully

---

## ✅ **Final Verification**

### **15. Complete Feature Check**
- [ ] **User Module:** Registration, login, profile, booking, history ✅
- [ ] **Admin Module:** Dashboard, management, reports ✅
- [ ] **Booking System:** Schedules, seats, confirmation ✅
- [ ] **Search & Filter:** All search options work ✅
- [ ] **Feedback System:** Submit and view feedback ✅
- [ ] **Business Rules:** All restrictions enforced ✅

---

## 🚨 **If Something Doesn't Work**

### **Common Issues & Solutions:**

1. **"Database connection failed"**
   - Check MySQL is running in XAMPP
   - Verify database name in `includes/db.php`

2. **"Page not found" errors**
   - Check file paths and directory structure
   - Ensure files are in correct locations

3. **"Permission denied" errors**
   - Check folder permissions
   - Ensure Apache has read/write access

4. **Blank pages or PHP errors**
   - Check PHP error logs
   - Verify PHP version compatibility

5. **Booking/seat issues**
   - Check sample data is imported
   - Verify schedules have future dates

### **Quick Fixes:**
```sql
-- Reset sample data if needed
USE `Transport-Booking-System`;
SOURCE sample_data.sql;

-- Create admin user quickly
UPDATE users SET is_staff = 1 WHERE username = 'your_username';
```

---

## 📞 **Testing Report Template**

**Tester Name:** _______________  
**Date:** _______________  
**Environment:** _______________

### **Results:**
- [ ] ✅ All tests passed
- [ ] ⚠️ Minor issues found (list below)
- [ ] ❌ Major issues found (list below)

### **Issues Found:**
1. _________________________________
2. _________________________________
3. _________________________________

### **Overall Assessment:**
- [ ] Ready for submission
- [ ] Needs minor fixes
- [ ] Needs major fixes

**Additional Notes:**
_________________________________
_________________________________

---

## 🎯 **Success Criteria**

**System is ready when:**
- ✅ All 6 modules working correctly
- ✅ No critical errors or crashes
- ✅ Business rules properly enforced
- ✅ Professional appearance maintained
- ✅ All team members can complete testing successfully

**Total Testing Time:** ~60 minutes per person  
**Recommended:** Have 2-3 team members test independently
