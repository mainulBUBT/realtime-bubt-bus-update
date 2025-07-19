# 🚌 BUBT Bus Tracker - Professional Student PWA

**"Know Where Is My University Bus"** - A complete real-time university bus tracking system for Bangladesh University of Business and Technology (BUBT) with professional student portal and mobile app experience.

![BUBT Bus Tracker](https://img.shields.io/badge/Laravel-12-red?style=for-the-badge&logo=laravel)
![PWA Ready](https://img.shields.io/badge/PWA-Ready-blue?style=for-the-badge)
![Mobile First](https://img.shields.io/badge/Mobile-First-green?style=for-the-badge)
![MySQL](https://img.shields.io/badge/MySQL-Database-orange?style=for-the-badge&logo=mysql)

## 📱 Live Demo
- **🌐 URL:** `http://localhost:3003` (after setup)
- **👤 Student Login:** `arif.rahman@bubt.edu.bd` / `student123`
- **👨‍💼 Admin Login:** `admin@bubt.edu.bd` / `admin123`

## ✨ Features

- **Real-time GPS tracking** with clustering (ε = 60m, minPts = 2)
- **Progressive Web App (PWA)** - installable on mobile devices
- **Live map** using OpenStreetMap and Leaflet
- **WebSocket updates** via Laravel Reverb
- **Push notifications** for bus arrivals
- **Admin dashboard** for trip and bus management
- **Rate-limited API** (4 requests/minute) for GPS pings
- **Zero hardware required** - uses smartphones for tracking
- **100% free and open source**

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & NPM (optional, for advanced features)

### Installation

**🚀 One-Command Setup:**
```bash
# Install dependencies and start everything
composer install && php start.php
```

**📋 Manual Setup (if needed):**
```bash
# 1. Install dependencies
composer install

# 2. Setup database (no artisan needed!)
php setup-database.php

# 3. Start server
php -S localhost:8000 -t public

# OR use the bootstrap script
php bootstrap.php
```

**🧪 Test the API:**
```bash
php test-api.php
```

## 📱 Usage

### For Students/Staff
- Visit `http://localhost:8000` to see live bus tracking
- Install as PWA for mobile app experience
- Enable notifications for bus arrival alerts

### For Administrators
- Visit `http://localhost:8000/admin` for management dashboard
- Add/edit buses and routes
- Schedule trips and manage settings

### For GPS Tracking (Mobile Apps)
Send POST requests to `/api/ping`:
```json
{
  "bus_id": 1,
  "latitude": 23.8103,
  "longitude": 90.4125,
  "timestamp": "2024-07-19T10:30:00Z",
  "source": "mobile_app"
}
```

## 🏗️ Architecture

### Database Schema
- **buses** - Bus information (B1-B5 with route names)
- **stops** - Bus stops with GPS coordinates
- **trips** - Scheduled trips with times and status
- **locations** - Real-time GPS pings with clustering
- **settings** - Global configuration
- **push_subscriptions** - Web push notification subscriptions

### Key Components
- **Clustering Algorithm** (`app/Support/Cluster.php`) - DBSCAN implementation
- **Location API** (`app/Http/Controllers/Api/LocationController.php`) - GPS ping handling
- **Livewire Components** - Real-time UI updates
- **PWA Service Worker** - Offline support and push notifications

## 🚌 Bus Routes (Based on BUBT Schedule)

- **B1 (Buriganga)**: Asad Gate → Shyamoli → Mirpur-1 → Rainkhola → BUBT
- **B2 (Brahmaputra)**: Hemayetpur → Amin Bazar → Gabtoli → Mazar Road → Mirpur-1 → Rainkhola → BUBT
- **B3 (Padma)**: Shyamoli → Agargaon → Kazipara → Mirpur-10 → Proshikha → BUBT
- **B4 (Meghna)**: Mirpur-14 → Mirpur-10 → Mirpur-11 → Proshikha → BUBT
- **B5 (Jamuna)**: ECB Chattar → Kalshi Bridge → Mirpur-12 → Duaripara → BUBT

### Schedule
- **Morning**: 7:00 AM departure, 4:10 PM return
- **Evening**: 5:00 PM departure, 9:25 PM return

## 🔧 Configuration

### Environment Variables
```env
# Database
DB_CONNECTION=sqlite  # or mysql
DB_DATABASE=database/database.sqlite

# Broadcasting (WebSocket)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=bubt-bus-tracker
REVERB_HOST=localhost
REVERB_PORT=8080

# App Settings
APP_NAME="BUBT Bus Tracker"
```

### Settings (Admin Panel)
- **Refresh Interval**: How often to update positions (default: 30s)
- **Max Location Age**: How old locations to show (default: 10 minutes)
- **Clustering Radius**: Distance for grouping buses (default: 60m)

## 🧪 Testing

```bash
# Run feature tests
php artisan test

# Test API endpoints
curl -X POST http://localhost:8000/api/ping \
  -H "Content-Type: application/json" \
  -d '{"bus_id":1,"latitude":23.8103,"longitude":90.4125}'

curl http://localhost:8000/api/positions
```

## 📱 PWA Features

- **Installable** on mobile devices
- **Offline support** with service worker caching
- **Push notifications** for bus arrivals
- **Background sync** for offline GPS data
- **Responsive design** for all screen sizes

## 🔒 Security

- **Rate limiting** on GPS ping endpoint (4/minute)
- **Input validation** for all API requests
- **CSRF protection** on web routes
- **SQL injection prevention** with Eloquent ORM

## 🚀 Deployment

### Production Setup
1. Use MySQL/PostgreSQL instead of SQLite
2. Configure proper WebSocket server (Laravel Reverb)
3. Set up HTTPS for PWA features
4. Configure push notification VAPID keys
5. Set up proper caching (Redis recommended)

### Docker (Optional)
```dockerfile
FROM php:8.2-fpm
# Add your Docker configuration
```

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 🏫 About BUBT

Bangladesh University of Business and Technology (BUBT) is a leading private university in Bangladesh, committed to providing quality education and modern transportation solutions for students and staff.

---

**Made with ❤️ for BUBT Community**

For support: transport@bubt.edu.bd | +880-2-9138234# realtime-bubt-bus-update
# realtime-bubt-bus-update
