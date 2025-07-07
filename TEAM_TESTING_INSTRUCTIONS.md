# 👥 Team Testing Instructions - Transport Booking System

## 🎯 **For Team Members: How to Test the System**

### **📋 Before You Start**
1. **Get the latest code** from the repository
2. **Set up your environment** (XAMPP/WAMP running)
3. **Import the database** using the provided SQL files
4. **Run the health check** first: `http://localhost/TWT_Transport_Booking_System/database/system_health_check.php`

---

## 🚀 **Quick 30-Minute Testing Session**

### **Phase 1: Setup Verification (5 minutes)**
1. **Health Check:**
   - Go to: `http://localhost/TWT_Transport_Booking_System/database/system_health_check.php`
   - Verify all checks pass (green checkmarks)
   - If any failures, fix before proceeding

2. **Homepage Test:**
   - Go to: `http://localhost/TWT_Transport_Booking_System/public/index.php`
   - Verify page loads without errors
   - Check navigation links work

### **Phase 2: User Features Testing (10 minutes)**
1. **Registration & Login:**
   - Register new user: `testuser_[your_name]@example.com`
   - Login with new credentials
   - Test password reset functionality

2. **Booking Process:**
   - View schedules: Search and filter options
   - Select a schedule and book a seat
   - Check seat map functionality
   - Complete booking and verify confirmation

3. **User Management:**
   - View booking history
   - Edit profile information
   - Submit feedback for a trip

### **Phase 3: Admin Features Testing (10 minutes)**
1. **Admin Setup:**
   - Create admin user (set `is_staff = 1` in database)
   - Login to admin dashboard
   - Verify dashboard statistics

2. **Management Testing:**
   - **Buses:** Add new bus, edit existing, try deleting (test restrictions)
   - **Routes:** Add new route, test duplicate prevention
   - **Schedules:** Create schedule, view seat map
   - **Bookings:** View all bookings, test filters, cancel booking

### **Phase 4: Business Rules Testing (5 minutes)**
1. **Booking Restrictions:**
   - Try booking already booked seat
   - Try cancelling booking close to departure
   - Verify error messages are clear

2. **Admin Restrictions:**
   - Try deleting bus with bookings (should fail)
   - Try editing schedule with completed bookings
   - Verify business rules are enforced

---

## 📝 **Testing Checklist - Mark as Complete**

### **✅ Core Functionality**
- [ ] User registration works
- [ ] User login works
- [ ] Password reset works (simple version)
- [ ] Schedule viewing and searching works
- [ ] Seat selection and booking works
- [ ] Booking history displays correctly
- [ ] Feedback submission works
- [ ] Admin dashboard loads
- [ ] Bus/route/schedule management works
- [ ] Admin reports generate correctly

### **✅ Business Rules**
- [ ] Cannot book already booked seats
- [ ] Cannot cancel bookings close to departure
- [ ] Cannot delete buses/routes with bookings
- [ ] Cannot edit schedules with completed bookings
- [ ] Proper error messages displayed

### **✅ User Experience**
- [ ] Pages load quickly
- [ ] Navigation is intuitive
- [ ] Error messages are helpful
- [ ] Design is consistent and professional
- [ ] Mobile-friendly (test on phone)

### **✅ Security**
- [ ] Cannot access admin pages as regular user
- [ ] Passwords are hashed in database
- [ ] SQL injection protection works
- [ ] Input validation prevents errors

---

## 🐛 **Common Issues & Solutions**

### **Issue: "Database connection failed"**
**Solution:**
1. Check MySQL is running in XAMPP
2. Verify database name: `Transport-Booking-System`
3. Check `includes/db.php` settings

### **Issue: "Page not found" errors**
**Solution:**
1. Verify correct directory structure
2. Check file paths in URLs
3. Ensure files are in correct locations

### **Issue: "No schedules available"**
**Solution:**
1. Import sample data: `SOURCE sample_data.sql;`
2. Check schedule dates are in the future
3. Verify buses and routes exist

### **Issue: "Cannot create admin user"**
**Solution:**
```sql
-- In phpMyAdmin, run this query:
UPDATE users SET is_staff = 1 WHERE username = 'your_username';
```

### **Issue: Booking/seat map problems**
**Solution:**
1. Clear browser cache
2. Check JavaScript console for errors
3. Verify sample data is imported correctly

---

## 📊 **Testing Report Template**

**Copy this template and fill it out:**

```
TESTING REPORT
==============
Tester: [Your Name]
Date: [Date]
Environment: [XAMPP/WAMP version]
Browser: [Chrome/Firefox/etc.]

SETUP VERIFICATION:
[ ] Health check passed
[ ] Database connected
[ ] All files present

USER FEATURES:
[ ] Registration: _______________
[ ] Login: _______________
[ ] Password Reset: _______________
[ ] Booking: _______________
[ ] History: _______________
[ ] Feedback: _______________

ADMIN FEATURES:
[ ] Dashboard: _______________
[ ] Bus Management: _______________
[ ] Route Management: _______________
[ ] Schedule Management: _______________
[ ] User Management: _______________
[ ] Reports: _______________

BUSINESS RULES:
[ ] Booking restrictions: _______________
[ ] Admin restrictions: _______________
[ ] Error handling: _______________

ISSUES FOUND:
1. ________________________________
2. ________________________________
3. ________________________________

OVERALL RATING:
[ ] Ready for submission
[ ] Minor fixes needed
[ ] Major issues found

ADDITIONAL NOTES:
_____________________________________
_____________________________________
```

---

## 🎯 **Success Criteria**

### **System is ready when ALL team members report:**
- ✅ All core features working
- ✅ No critical errors or crashes
- ✅ Business rules properly enforced
- ✅ Professional appearance maintained
- ✅ Can complete full user journey (register → book → feedback)
- ✅ Can complete full admin workflow (login → manage → reports)

---

## 📞 **Communication Protocol**

### **If you find issues:**
1. **Document clearly:** What you did, what happened, what you expected
2. **Take screenshots** if visual issues
3. **Note your environment:** Browser, OS, XAMPP version
4. **Check if others have same issue** before reporting
5. **Suggest solutions** if you know how to fix

### **Reporting format:**
```
ISSUE: [Brief description]
STEPS: [What you did]
EXPECTED: [What should happen]
ACTUAL: [What actually happened]
BROWSER: [Chrome/Firefox/etc.]
SCREENSHOT: [If applicable]
```

---

## 🏆 **Final Team Sign-off**

**When testing is complete, each team member should confirm:**

**Team Member 1:** _________________ ✅ Approved / ❌ Issues Found  
**Team Member 2:** _________________ ✅ Approved / ❌ Issues Found  
**Team Member 3:** _________________ ✅ Approved / ❌ Issues Found  
**Team Member 4:** _________________ ✅ Approved / ❌ Issues Found  

**System Status:** 
- [ ] ✅ Ready for submission
- [ ] ⚠️ Minor fixes needed
- [ ] ❌ Major issues require attention

**Final Notes:**
_________________________________________________
_________________________________________________

---

## 🎉 **Ready for Demonstration!**

Once all team members have completed testing and signed off, the system is ready for:
- **Coursework submission**
- **Live demonstration**
- **Instructor evaluation**
- **Peer review**

**Good luck with your presentation! 🚀**
