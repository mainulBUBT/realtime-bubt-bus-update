# BUBT Bus Tracker API - cURL Examples
# Base URL: http://localhost:8000/api
# Replace {{token}} with actual bearer token

# =============================================================================
# PUBLIC ROUTES
# =============================================================================

# Login as Driver
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"driver@bubt.edu","password":"password123","role":"driver"}'

# Login as Student
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"student@bubt.edu","password":"password123","role":"student"}'

# Login as Admin
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@bubt.edu","password":"password123","role":"admin"}'

# Register New User
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Rafiq Ahmed","email":"rafiq.ahmed@student.bubt.edu","phone":"+8801812345678","password":"SecurePass123","password_confirmation":"SecurePass123","role":"student"}'

# Get App Settings
curl -X GET http://localhost:8000/api/settings \
  -H "Accept: application/json"

# =============================================================================
# AUTHENTICATION (Protected Routes)
# =============================================================================

# Get Current User
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer {{driverToken}}" \
  -H "Accept: application/json"

# Update Profile
curl -X PATCH http://localhost:8000/api/auth/profile \
  -H "Authorization: Bearer {{driverToken}}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Updated Name","phone":"+8801712345679"}'

# Update Password
curl -X PATCH http://localhost:8000/api/auth/password \
  -H "Authorization: Bearer {{driverToken}}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"current_password":"password123","password":"newpassword123","password_confirmation":"newpassword123"}'

# Logout
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer {{driverToken}}" \
  -H "Accept: application/json"

# =============================================================================
# DRIVER ENDPOINTS
# =============================================================================

# Get Available Buses
curl -X GET http://localhost:8000/api/driver/buses \
  -H "Authorization: Bearer {{driverToken}}" \
  -H "Accept: application/json"

# Get Available Routes
curl -X GET http://localhost:8000/api/driver/routes \
  -H "Authorization: Bearer {{driverToken}}" \
  -H "Accept: application/json"

# Start Trip
curl -X POST http://localhost:8000/api/driver/trips/start \
  -H "Authorization: Bearer {{driverToken}}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"bus_id":1,"route_id":1,"schedule_id":null}'

# Get Current Trip
curl -X GET http://localhost:8000/api/driver/trips/current \
  -H "Authorization: Bearer {{driverToken}}" \
  -H "Accept: application/json"

# Get Trip History
curl -X GET http://localhost:8000/api/driver/trips/history \
  -H "Authorization: Bearer {{driverToken}}" \
  -H "Accept: application/json"

# Submit Single Location
curl -X POST http://localhost:8000/api/driver/location \
  -H "Authorization: Bearer {{driverToken}}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"trip_id":1,"lat":23.7942,"lng":90.3635,"speed":28.5,"recorded_at":"'"$(date -u +%Y-%m-%dT%H:%M:%SZ)"'"}'

# Submit Batch Locations
curl -X POST http://localhost:8000/api/driver/location/batch \
  -H "Authorization: Bearer {{driverToken}}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "trip_id": 1,
    "locations": [
      {"lat": 23.7937, "lng": 90.3629, "speed": 25.5, "recorded_at": "'"$(date -u +%Y-%m-%dT%H:%M:%SZ"'"},
      {"lat": 23.7940, "lng": 90.3630, "speed": 26.0, "recorded_at": "'"$(date -u +%Y-%m-%dT%H:%M:%SZ"'"},
      {"lat": 23.7943, "lng": 90.3631, "speed": 24.8, "recorded_at": "'"$(date -u +%Y-%m-%dT%H:%M:%SZ"'"}
    ]
  }'

# End Trip
curl -X POST http://localhost:8000/api/driver/trips/1/end \
  -H "Authorization: Bearer {{driverToken}}" \
  -H "Accept: application/json"

# =============================================================================
# STUDENT ENDPOINTS
# =============================================================================

# Get Routes
curl -X GET http://localhost:8000/api/student/routes \
  -H "Authorization: Bearer {{studentToken}}" \
  -H "Accept: application/json"

# Search Routes
curl -X GET "http://localhost:8000/api/student/routes?q=mirpur" \
  -H "Authorization: Bearer {{studentToken}}" \
  -H "Accept: application/json"

# Get Route Details
curl -X GET http://localhost:8000/api/student/routes/1 \
  -H "Authorization: Bearer {{studentToken}}" \
  -H "Accept: application/json"

# Get Active Trips
curl -X GET http://localhost:8000/api/student/trips/active \
  -H "Authorization: Bearer {{studentToken}}" \
  -H "Accept: application/json"

# Get Trip Locations
curl -X GET http://localhost:8000/api/student/trips/1/locations \
  -H "Authorization: Bearer {{studentToken}}" \
  -H "Accept: application/json"

# Get Latest Location
curl -X GET http://localhost:8000/api/student/trips/1/latest-location \
  -H "Authorization: Bearer {{studentToken}}" \
  -H "Accept: application/json"

# Get Today's Schedules
curl -X GET http://localhost:8000/api/student/schedules \
  -H "Authorization: Bearer {{studentToken}}" \
  -H "Accept: application/json"

# Get All Schedules (in current period)
curl -X GET "http://localhost:8000/api/student/schedules?filter=all" \
  -H "Authorization: Bearer {{studentToken}}" \
  -H "Accept: application/json"

# Update FCM Token
curl -X POST http://localhost:8000/api/student/fcm-token \
  -H "Authorization: Bearer {{studentToken}}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"fcm_token":"dQw4w9WgXcQ1234567890abcdefghijklmnopqrstuvwxyz"}'

# Get Notifications
curl -X GET http://localhost:8000/api/student/notifications \
  -H "Authorization: Bearer {{studentToken}}" \
  -H "Accept: application/json"

# Get Unread Count
curl -X GET http://localhost:8000/api/student/notifications/unread-count \
  -H "Authorization: Bearer {{studentToken}}" \
  -H "Accept: application/json"

# Mark Notification Read
curl -X POST http://localhost:8000/api/student/notifications/1/read \
  -H "Authorization: Bearer {{studentToken}}" \
  -H "Accept: application/json"

# Mark All Read
curl -X POST http://localhost:8000/api/student/notifications/read-all \
  -H "Authorization: Bearer {{studentToken}}" \
  -H "Accept: application/json"

# =============================================================================
# ADMIN ENDPOINTS - BUSES
# =============================================================================

# List Buses
curl -X GET http://localhost:8000/api/admin/buses \
  -H "Authorization: Bearer {{adminToken}}" \
  -H "Accept: application/json"

# Create Bus
curl -X POST http://localhost:8000/api/admin/buses \
  -H "Authorization: Bearer {{adminToken}}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"plate_number":"DHAKA METRO-GA-12-3456","device_id":"driver-phone-001","capacity":40,"status":"active"}'

# Get Bus
curl -X GET http://localhost:8000/api/admin/buses/1 \
  -H "Authorization: Bearer {{adminToken}}" \
  -H "Accept: application/json"

# Update Bus
curl -X PUT http://localhost:8000/api/admin/buses/1 \
  -H "Authorization: Bearer {{adminToken}}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"plate_number":"DHAKA METRO-GA-12-3456","device_id":"driver-phone-001","capacity":45,"status":"active"}'

# Delete Bus
curl -X DELETE http://localhost:8000/api/admin/buses/1 \
  -H "Authorization: Bearer {{adminToken}}" \
  -H "Accept: application/json"

# =============================================================================
# ADMIN ENDPOINTS - ROUTES
# =============================================================================

# List Routes
curl -X GET http://localhost:8000/api/admin/routes \
  -H "Authorization: Bearer {{adminToken}}" \
  -H "Accept: application/json"

# Create Route
curl -X POST http://localhost:8000/api/admin/routes \
  -H "Authorization: Bearer {{adminToken}}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Mirpur - BUBT Express",
    "schedule_period_id": 1,
    "direction": "up",
    "origin_name": "Mirpur 10",
    "destination_name": "BUBT Campus",
    "polyline": [[23.7937, 90.3629], [23.7945, 90.3645], [23.7955, 90.3680], [23.7966, 90.3745]],
    "stops": [
      {"name": "Mirpur 10", "lat": 23.7937, "lng": 90.3629, "sequence": 1},
      {"name": "Sony Hall", "lat": 23.7945, "lng": 90.3645, "sequence": 2},
      {"name": "Kohinoor College", "lat": 23.7955, "lng": 90.3680, "sequence": 3},
      {"name": "BUBT Main Gate", "lat": 23.7966, "lng": 90.3745, "sequence": 4}
    ]
  }'

# Get Route
curl -X GET http://localhost:8000/api/admin/routes/1 \
  -H "Authorization: Bearer {{adminToken}}" \
  -H "Accept: application/json"

# Update Route
curl -X PUT http://localhost:8000/api/admin/routes/1 \
  -H "Authorization: Bearer {{adminToken}}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Mirpur - BUBT Express Updated",
    "schedule_period_id": 1,
    "direction": "up",
    "origin_name": "Mirpur 10",
    "destination_name": "BUBT Campus",
    "is_active": true
  }'

# Delete Route
curl -X DELETE http://localhost:8000/api/admin/routes/1 \
  -H "Authorization: Bearer {{adminToken}}" \
  -H "Accept: application/json"

# =============================================================================
# ADMIN ENDPOINTS - SCHEDULES
# =============================================================================

# List Schedules
curl -X GET http://localhost:8000/api/admin/schedules \
  -H "Authorization: Bearer {{adminToken}}" \
  -H "Accept: application/json"

# Create Schedule
curl -X POST http://localhost:8000/api/admin/schedules \
  -H "Authorization: Bearer {{adminToken}}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "route_id": 1,
    "bus_id": 1,
    "schedule_period_id": 1,
    "departure_time": "07:30",
    "weekdays": ["saturday", "sunday", "monday", "tuesday", "wednesday", "thursday"],
    "is_active": true
  }'

# Get Schedule
curl -X GET http://localhost:8000/api/admin/schedules/1 \
  -H "Authorization: Bearer {{adminToken}}" \
  -H "Accept: application/json"

# Update Schedule
curl -X PUT http://localhost:8000/api/admin/schedules/1 \
  -H "Authorization: Bearer {{adminToken}}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "route_id": 1,
    "bus_id": 1,
    "schedule_period_id": 1,
    "departure_time": "08:00",
    "weekdays": ["sunday", "monday", "tuesday", "wednesday", "thursday"],
    "is_active": true
  }'

# Delete Schedule
curl -X DELETE http://localhost:8000/api/admin/schedules/1 \
  -H "Authorization: Bearer {{adminToken}}" \
  -H "Accept: application/json"
