/**
 * Map initialization and bus tracking functionality
 */

// Initialize the map
function initMap() {
    // Create map and set initial view to Dhaka, Bangladesh
    map = L.map('map', {
        zoomControl: false // Disable default zoom controls since we're adding custom ones
    }).setView([23.7937, 90.3629], 14);

    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    // Add custom bus marker icon
    const busIcon = L.divIcon({
        className: 'bus-marker-icon',
        html: '<div class="marker-icon">B1</div>',
        iconSize: [40, 40],
        iconAnchor: [20, 20]
    });

    // Add bus marker to map
    busMarker = L.marker([busPosition.lat, busPosition.lng], {
        icon: busIcon,
        zIndexOffset: 1000 // Make sure bus is on top of other markers
    }).addTo(map);

    // Add a popup to the bus marker
    busMarker.bindPopup('<b>Bus B1: Buriganga</b><br>On route to Mirpur-1');

    // Simulate bus movement for demo purposes
    simulateBusMovement();
}

// Center map on bus location
function centerMap() {
    if (map && busMarker) {
        map.setView(busMarker.getLatLng(), map.getZoom());
    }
}

// Simulate bus movement for demonstration
function simulateBusMovement() {
    // Define route coordinates (simplified for demo)
    const route = [
        { lat: 23.7651, lng: 90.3668 }, // Asad Gate
        { lat: 23.7696, lng: 90.3662 }, // In between
        { lat: 23.7746, lng: 90.3657 }, // Shyamoli
        { lat: 23.7842, lng: 90.3643 }, // In between
        { lat: 23.7937, lng: 90.3629 }, // Mirpur-1
        { lat: 23.8069, lng: 90.3554 }, // Rainkhola
        { lat: 23.8213, lng: 90.3541 }  // BUBT
    ];

    // Start from current position in the route
    let currentStopIndex = 2; // Starting from Shyamoli in this demo

    // Update bus position every few seconds
    setInterval(() => {
        // Move to next point if not at the end
        if (currentStopIndex < route.length - 1) {
            currentStopIndex += 0.1; // Move gradually between points

            // Interpolate position between points
            const currentIndex = Math.floor(currentStopIndex);
            const nextIndex = Math.min(currentIndex + 1, route.length - 1);
            const fraction = currentStopIndex - currentIndex;

            // Calculate interpolated position
            const lat = route[currentIndex].lat + fraction * (route[nextIndex].lat - route[currentIndex].lat);
            const lng = route[currentIndex].lng + fraction * (route[nextIndex].lng - route[currentIndex].lng);

            // Update bus position
            busPosition = { lat, lng };

            // Update marker position
            if (busMarker) {
                busMarker.setLatLng([lat, lng]);
            }

            // Update the UI with new information
            updateBusInfo(currentStopIndex, route);
        } else {
            // Reached the end, reset to beginning for demo purposes
            currentStopIndex = 0;
        }
    }, 3000); // Update every 3 seconds
}

// Update bus information based on current position
function updateBusInfo(currentStopIndex, route) {
    // Calculate which stop we're approaching
    const currentIndex = Math.floor(currentStopIndex);
    const nextIndex = Math.min(currentIndex + 1, route.length - 1);

    // Update ETA based on position between stops
    const fraction = currentStopIndex - currentIndex;
    const etaMinutes = Math.round((1 - fraction) * 10);

    // Map route indices to stop names
    const stopNames = ['Asad Gate', 'In between', 'Shyamoli', 'In between', 'Mirpur-1', 'Rainkhola', 'BUBT'];

    // Update ETA card
    const etaTime = document.querySelector('.eta-time');
    const etaDestination = document.querySelector('.eta-destination');
    if (etaTime && nextIndex < stopNames.length) {
        etaTime.textContent = etaMinutes > 0 ? `${etaMinutes} min` : 'Arriving';
    }
    if (etaDestination && nextIndex < stopNames.length) {
        etaDestination.textContent = `To ${stopNames[nextIndex]}`;
    }

    // Update bus status text
    const busStatus = document.querySelector('.bus-status span:last-child');
    if (busStatus) {
        busStatus.textContent = `On Route • Arriving in ${etaMinutes} min`;
    }

    // Update current and next stop in info card
    const currentStopValue = document.querySelector('.info-row:nth-child(1) .info-item:nth-child(1) .info-value');
    const nextStopValue = document.querySelector('.info-row:nth-child(1) .info-item:nth-child(2) .info-value');

    if (currentStopValue && currentIndex < stopNames.length) {
        currentStopValue.textContent = stopNames[currentIndex];
    }
    if (nextStopValue && nextIndex < stopNames.length) {
        nextStopValue.textContent = stopNames[nextIndex];
    }
}
