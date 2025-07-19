# 🎓 BUBT Bus Tracker - Demo User Accounts

## 🚀 Quick Start
1. **Start the server:** `php start.php`
2. **Open browser:** `http://localhost:3003`
3. **Login with any demo account below**

---

## 👨‍🎓 Student Demo Accounts

### **Md. Arif Rahman** (Computer Science & Engineering)
- **📧 Email:** `arif.rahman@bubt.edu.bd`
- **🔑 Password:** `student123`
- **🎓 Student ID:** `2021-01-01-001`
- **📱 Phone:** `+880-1712345678`
- **🏫 Department:** CSE

### **Fatima Khatun** (Business Administration)
- **📧 Email:** `fatima.khatun@bubt.edu.bd`
- **🔑 Password:** `student123`
- **🎓 Student ID:** `2021-02-01-015`
- **📱 Phone:** `+880-1798765432`
- **🏫 Department:** BBA

### **Tanvir Ahmed** (Electrical & Electronic Engineering)
- **📧 Email:** `tanvir.ahmed@bubt.edu.bd`
- **🔑 Password:** `student123`
- **🎓 Student ID:** `2020-01-01-045`
- **📱 Phone:** `+880-1634567890`
- **🏫 Department:** EEE

### **Rashida Begum** (English)
- **📧 Email:** `rashida.begum@bubt.edu.bd`
- **🔑 Password:** `student123`
- **🎓 Student ID:** `2022-03-01-008`
- **📱 Phone:** `+880-1556789012`
- **🏫 Department:** English

---

## 👨‍💼 Administrator Account

### **Admin User**
- **📧 Email:** `admin@bubt.edu.bd`
- **🔑 Password:** `admin123`
- **🏫 Role:** Administrator
- **📱 Phone:** `+880-1700000000`

---

## 🚌 Available Bus Routes

### **B1 - Buriganga Route**
- Asad Gate → Shyamoli → Mirpur-1 → Rainkhola → BUBT

### **B2 - Brahmaputra Route**
- Hemayetpur → Amin Bazar → Gabtoli → Mazar Road → Mirpur-1 → Rainkhola → BUBT

### **B3 - Padma Route**
- Shyamoli (Shishu Mela) → Agargaon → Kazipara → Mirpur-10 → Proshikha → BUBT

### **B4 - Meghna Route**
- Mirpur-14 → Mirpur-10 (Original) → Mirpur-11 → Proshikha → BUBT

### **B5 - Jamuna Route**
- ECB Chattar → Kalshi Bridge → Mirpur-12 → Duaripara → BUBT

---

## 🕐 Bus Schedule
- **Morning Trip:** 7:00 AM departure, 4:10 PM return
- **Evening Trip:** 5:00 PM departure, 9:25 PM return

---

## 📱 How to Test the Student Portal

### **1. Login Experience**
- Professional splash screen with BUBT branding
- Clean login/register interface
- Mobile-first responsive design

### **2. Student Dashboard**
- Personal greeting with student info
- Live bus tracking map
- Real-time bus capacity indicators
- Professional bus cards with route information

### **3. Bus Boarding System**
- Click "Board Bus" on any active bus
- Select boarding stop from dropdown
- Choose destination stop (optional)
- Submit boarding request
- View active trips in "Your Active Trips" section
- Cancel boarding requests

### **4. PWA Features**
- Install as mobile app ("Add to Home Screen")
- Offline support with service worker
- Native app-like experience
- Push notification ready (when configured)

### **5. Real-time Features**
- Live bus positions on map
- Auto-refresh every 30 seconds
- Real-time capacity updates
- Instant boarding confirmations

---

## 🔧 Technical Features

- **Database:** MySQL with proper foreign keys and indexes
- **Authentication:** Laravel session-based auth
- **Real-time:** Livewire for reactive UI
- **Maps:** OpenStreetMap with Leaflet.js
- **PWA:** Service worker, manifest, installable
- **Mobile:** Responsive design, touch-friendly
- **Security:** Rate limiting, input validation, CSRF protection

---

## 🎯 Demo Scenarios

### **Scenario 1: Student Boarding**
1. Login as Arif Rahman
2. See available buses with capacity
3. Click "Board Bus" on B1 (Buriganga)
4. Select "Asad Gate" as boarding stop
5. Select "BUBT" as destination
6. Submit request
7. See it appear in "Your Active Trips"

### **Scenario 2: Multiple Students**
1. Open multiple browser tabs/windows
2. Login with different student accounts
3. Make boarding requests on same bus
4. Watch capacity indicators update
5. See real-time changes across sessions

### **Scenario 3: Admin Management**
1. Login as admin@bubt.edu.bd
2. Access admin dashboard at `/admin`
3. Manage buses, trips, and settings
4. View student boarding requests

---

## 📞 Support
For demo support or questions, the system includes:
- **Email:** transport@bubt.edu.bd
- **Phone:** +880-2-9138234

**🎉 Enjoy testing the professional BUBT Bus Tracker!**