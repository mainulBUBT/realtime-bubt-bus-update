/**
 * BUBT Bus Tracker - Main Application JavaScript
 * Version 3.0 - Desktop + Mobile with Timeline
 */

let map = null;
let busMarkers = {};
let userMarker = null;

// BUBT University coordinates
const BUBT_LOCATION = { lat: 23.8759, lng: 90.3795 };

// Bus data with routes
const busData = {
    B1: {
        name: 'Buriganga',
        route: 'Mirpur → BUBT Campus',
        lat: 23.878, lng: 90.382,
        status: 'active',
        eta: '5 mins',
        stops: [
            { name: 'Mirpur-10', time: '7:30 AM', status: 'passed' },
            { name: 'Mirpur-11', time: '7:40 AM', status: 'passed' },
            { name: 'Pallabi', time: '7:50 AM', status: 'current' },
            { name: 'Rupnagar', time: '8:00 AM', status: 'upcoming' },
            { name: 'Gabtoli', time: '8:10 AM', status: 'upcoming' },
            { name: 'BUBT Campus', time: '8:25 AM', status: 'destination' }
        ]
    },
    B2: {
        name: 'Brahmaputra',
        route: 'Uttara → BUBT Campus',
        lat: 23.872, lng: 90.375,
        status: 'delayed',
        eta: 'Delayed 15 mins',
        stops: [
            { name: 'Uttara Sector-7', time: '7:15 AM', status: 'passed' },
            { name: 'Uttara Center', time: '7:25 AM', status: 'passed' },
            { name: 'Airport Road', time: '7:40 AM', status: 'current' },
            { name: 'Khilkhet', time: '8:00 AM', status: 'upcoming' },
            { name: 'BUBT Campus', time: '8:30 AM', status: 'destination' }
        ]
    },
    B3: {
        name: 'Padma',
        route: 'Mohammadpur → BUBT Campus',
        lat: 23.880, lng: 90.385,
        status: 'active',
        eta: '12 mins',
        stops: [
            { name: 'Mohammadpur', time: '7:20 AM', status: 'passed' },
            { name: 'Shyamoli', time: '7:35 AM', status: 'current' },
            { name: 'Technical', time: '7:50 AM', status: 'upcoming' },
            { name: 'Asad Gate', time: '8:05 AM', status: 'upcoming' },
            { name: 'BUBT Campus', time: '8:30 AM', status: 'destination' }
        ]
    },
    B4: {
        name: 'Meghna',
        route: 'Dhanmondi → BUBT Campus',
        lat: 23.874, lng: 90.378,
        status: 'inactive',
        eta: 'Not in service',
        stops: []
    },
    B5: {
        name: 'Jamuna',
        route: 'Banani → BUBT Campus',
        lat: 23.876, lng: 90.372,
        status: 'active',
        eta: '8 mins',
        stops: [
            { name: 'Banani', time: '7:25 AM', status: 'passed' },
            { name: 'Gulshan-2', time: '7:40 AM', status: 'passed' },
            { name: 'Mohakhali', time: '7:55 AM', status: 'current' },
            { name: 'Farmgate', time: '8:10 AM', status: 'upcoming' },
            { name: 'BUBT Campus', time: '8:25 AM', status: 'destination' }
        ]
    }
};

document.addEventListener('DOMContentLoaded', function () {
    console.log('BUBT Bus Tracker v3.0 initialized');
    initApp();
});

function initApp() {
    initPermissionFlow();
    initBusList();
    initMobileSheet();
    initTimelinePanel();
    initBottomNav();
    initMapControls();
    initDrawer();

    setTimeout(() => {
        if (document.getElementById('main-screen').classList.contains('active')) {
            initMap();
        }
    }, 100);

    setTimeout(() => updateUserLocation('BUBT, Dhaka'), 1500);
}

/**
 * Initialize OpenStreetMap
 */
function initMap() {
    const mapContainer = document.getElementById('map');
    if (!mapContainer || map) return;

    map = L.map('map', {
        center: [BUBT_LOCATION.lat, BUBT_LOCATION.lng],
        zoom: 14,
        zoomControl: false,
        attributionControl: true
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    Object.keys(busData).forEach(busId => {
        addBusMarker(busId, busData[busId]);
    });

    addUserLocationMarker();
    console.log('Map initialized');
}

function createBusIcon(busId, status) {
    const colorClass = status === 'delayed' ? 'delayed' : (status === 'inactive' ? 'inactive' : '');
    return L.divIcon({
        className: 'custom-bus-marker',
        html: `<div class="bus-marker-icon ${colorClass}">
                   <div class="bus-icon-wrapper">
                       <i class="bi bi-bus-front-fill"></i>
                   </div>
               </div>`,
        iconSize: [48, 48],
        iconAnchor: [24, 40]
    });
}

function addBusMarker(busId, bus) {
    if (!map) return;

    const marker = L.marker([bus.lat, bus.lng], {
        icon: createBusIcon(busId, bus.status)
    }).addTo(map);

    marker.on('click', () => showTimelinePanel(busId));
    busMarkers[busId] = marker;
}

function addUserLocationMarker() {
    if (!map) return;

    const userIcon = L.divIcon({
        className: 'custom-user-marker',
        html: '<div class="user-location-marker"></div>',
        iconSize: [16, 16],
        iconAnchor: [8, 8]
    });

    userMarker = L.marker([BUBT_LOCATION.lat, BUBT_LOCATION.lng], { icon: userIcon }).addTo(map);
}

/**
 * Permission Flow
 */
function initPermissionFlow() {
    const permissionScreen = document.getElementById('permission-screen');
    const mainScreen = document.getElementById('main-screen');
    const allowWhileUsing = document.getElementById('allow-while-using');
    const allowOnce = document.getElementById('allow-once');
    const dontAllow = document.getElementById('dont-allow');

    const hasPermission = localStorage.getItem('locationPermission');

    if (hasPermission) {
        if (permissionScreen) permissionScreen.classList.remove('active');
        if (mainScreen) {
            mainScreen.classList.add('active');
            setTimeout(initMap, 100);
        }
    }

    function handlePermission(type) {
        localStorage.setItem('locationPermission', type);
        if (permissionScreen) permissionScreen.classList.remove('active');
        if (mainScreen) {
            mainScreen.classList.add('active');
            setTimeout(initMap, 100);
        }
    }

    if (allowWhileUsing) allowWhileUsing.addEventListener('click', () => handlePermission('while-using'));
    if (allowOnce) allowOnce.addEventListener('click', () => handlePermission('once'));
    if (dontAllow) dontAllow.addEventListener('click', () => {
        handlePermission('denied');
        updateUserLocation('Location disabled');
    });
}

function updateUserLocation(location) {
    const el = document.getElementById('user-location');
    if (el) el.textContent = location;
}

/**
 * Bus List Click Handlers
 */
function initBusList() {
    // Desktop sidebar bus items
    const busItems = document.querySelectorAll('.bus-item');
    busItems.forEach(item => {
        const busId = item.dataset.busId;
        const iconArea = item.querySelector('.bus-item-left');
        const infoArea = item.querySelector('.bus-item-center');

        // Click on ICON → Show Timeline
        iconArea?.addEventListener('click', (e) => {
            e.stopPropagation();
            showTimelinePanel(busId);

            // Highlight selected
            busItems.forEach(i => i.classList.remove('selected'));
            item.classList.add('selected');

            // Center map on bus
            centerMapOnBus(busId);
        });

        // Click on NAME/INFO → Just Relocate Map (no timeline)
        infoArea?.addEventListener('click', (e) => {
            e.stopPropagation();

            // Close timeline on desktop when clicking different bus
            if (window.innerWidth >= 768) {
                hideTimelinePanel();
            }

            // Highlight selected
            busItems.forEach(i => i.classList.remove('selected'));
            item.classList.add('selected');

            // Center map on bus
            centerMapOnBus(busId);
            showToast(`Showing ${busData[busId]?.name || busId} on map`);
        });

        // Click on chevron → Show Timeline
        item.querySelector('.bus-item-right')?.addEventListener('click', (e) => {
            e.stopPropagation();
            showTimelinePanel(busId);
            busItems.forEach(i => i.classList.remove('selected'));
            item.classList.add('selected');
            centerMapOnBus(busId);
        });
    });

    // Clone bus list for mobile
    cloneBusListForMobile();

    // Search
    const searchInput = document.getElementById('bus-search');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            busItems.forEach(item => {
                const name = item.querySelector('.bus-name').textContent.toLowerCase();
                const route = item.querySelector('.bus-route').textContent.toLowerCase();
                item.style.display = (name.includes(query) || route.includes(query)) ? 'flex' : 'none';
            });
        });
    }
}

function centerMapOnBus(busId) {
    if (map && busMarkers[busId]) {
        map.setView(busMarkers[busId].getLatLng(), 16, { animate: true });
    }
}

function cloneBusListForMobile() {
    const mobileList = document.getElementById('mobile-bus-list');
    const desktopList = document.getElementById('bus-list');

    if (mobileList && desktopList) {
        mobileList.innerHTML = desktopList.innerHTML;

        // Add click handlers to mobile items
        mobileList.querySelectorAll('.bus-item').forEach(item => {
            const busId = item.dataset.busId;
            const iconArea = item.querySelector('.bus-item-left');
            const infoArea = item.querySelector('.bus-item-center');

            // Click on ICON → Show Timeline
            iconArea?.addEventListener('click', (e) => {
                e.stopPropagation();
                closeMobileSheet();
                showTimelinePanel(busId);
                centerMapOnBus(busId);
            });

            // Click on NAME/INFO → Just Relocate Map
            infoArea?.addEventListener('click', (e) => {
                e.stopPropagation();
                closeMobileSheet();
                centerMapOnBus(busId);
                showToast(`Showing ${busData[busId]?.name || busId} on map`);
            });

            // Click on chevron → Show Timeline
            item.querySelector('.bus-item-right')?.addEventListener('click', (e) => {
                e.stopPropagation();
                closeMobileSheet();
                showTimelinePanel(busId);
                centerMapOnBus(busId);
            });
        });
    }
}

/**
 * Mobile Bottom Sheet
 */
function initMobileSheet() {
    const sheet = document.getElementById('mobile-bottom-sheet');
    const toggleBtn = document.getElementById('mobile-bus-toggle');
    const handle = sheet?.querySelector('.sheet-handle');

    if (!sheet || !toggleBtn) return;

    toggleBtn.addEventListener('click', () => {
        sheet.classList.add('active');
        toggleBtn.classList.add('hidden');
    });

    handle?.addEventListener('click', closeMobileSheet);
}

function closeMobileSheet() {
    const sheet = document.getElementById('mobile-bottom-sheet');
    const toggleBtn = document.getElementById('mobile-bus-toggle');

    sheet?.classList.remove('active');
    toggleBtn?.classList.remove('hidden');
}

/**
 * Timeline Panel (Google Maps style)
 */
function initTimelinePanel() {
    const panel = document.getElementById('timeline-panel');
    const closeBtn = document.getElementById('timeline-close');
    const onBusBtn = document.getElementById('btn-on-this-bus');

    if (closeBtn) {
        closeBtn.addEventListener('click', hideTimelinePanel);
    }

    if (onBusBtn) {
        onBusBtn.addEventListener('click', () => {
            const busId = document.getElementById('timeline-badge').textContent;
            confirmOnBus(busId);
        });
    }
}

function showTimelinePanel(busId) {
    const panel = document.getElementById('timeline-panel');
    const bus = busData[busId];

    if (!panel || !bus) return;

    // Update header
    document.getElementById('timeline-badge').textContent = busId;
    document.getElementById('timeline-bus-name').textContent = bus.name;
    document.getElementById('timeline-route').textContent = bus.route;

    // Update status
    const statusEl = document.getElementById('timeline-status');
    let statusBadge = '<span class="status-badge active">On Route</span>';
    if (bus.status === 'delayed') statusBadge = '<span class="status-badge delayed">Delayed</span>';
    if (bus.status === 'inactive') statusBadge = '<span class="status-badge">Not Active</span>';
    statusEl.innerHTML = `${statusBadge}<span class="arrival-time">ETA: ${bus.eta}</span>`;

    // Update timeline stops
    updateTimelineStops(bus.stops);

    // Show panel
    panel.classList.add('active');

    // Close mobile sheet if open
    closeMobileSheet();
}

function updateTimelineStops(stops) {
    const timeline = document.querySelector('.route-timeline');
    if (!timeline || !stops.length) return;

    timeline.innerHTML = stops.map(stop => {
        let iconHtml = '';
        let markerClass = '';

        switch (stop.status) {
            case 'passed':
                iconHtml = '<i class="bi bi-check-circle-fill"></i>';
                markerClass = 'passed';
                break;
            case 'current':
                iconHtml = '<div class="current-pulse"></div><i class="bi bi-bus-front-fill"></i>';
                markerClass = 'current';
                break;
            case 'destination':
                iconHtml = '<i class="bi bi-geo-alt-fill"></i>';
                markerClass = 'destination';
                break;
            default:
                iconHtml = '<i class="bi bi-circle"></i>';
                markerClass = 'upcoming';
        }

        let statusText = '';
        if (stop.status === 'passed') statusText = 'Departed';
        else if (stop.status === 'current') statusText = 'Currently Here';
        else if (stop.status === 'destination') statusText = 'Final Stop';
        else statusText = 'Upcoming';

        return `
            <div class="timeline-stop ${markerClass}">
                <div class="stop-marker">${iconHtml}</div>
                <div class="stop-info">
                    <span class="stop-time">${stop.time}</span>
                    <span class="stop-name">${stop.name}</span>
                    <span class="stop-status">${statusText}</span>
                </div>
            </div>
        `;
    }).join('');
}

function hideTimelinePanel() {
    const panel = document.getElementById('timeline-panel');
    panel?.classList.remove('active');

    // Remove selection from bus items
    document.querySelectorAll('.bus-item').forEach(item => {
        item.classList.remove('selected');
    });
}

function confirmOnBus(busId) {
    hideTimelinePanel();
    showToast(`You're now sharing location for Bus ${busId}`);
}

/**
 * Map Controls
 */
function initMapControls() {
    document.getElementById('locate-me')?.addEventListener('click', () => {
        if (map && userMarker) {
            map.setView(userMarker.getLatLng(), 16);
            showToast('Centered on your location');
        }
    });

    document.getElementById('zoom-in')?.addEventListener('click', () => {
        if (map) map.zoomIn();
    });

    document.getElementById('zoom-out')?.addEventListener('click', () => {
        if (map) map.zoomOut();
    });
}

/**
 * Bottom Nav
 */
function initBottomNav() {
    const navItems = document.querySelectorAll('.mobile-bottom-nav .nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function (e) {
            if (this.id === 'nav-menu-btn') return;
            e.preventDefault();
            navItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

/**
 * Toast
 */
function showToast(message) {
    const toast = document.getElementById('success-toast');
    if (toast) {
        toast.innerHTML = `<i class="bi bi-check-circle-fill"></i><span>${message}</span>`;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }
}

/**
 * Side Drawer (Hamburger Menu)
 */
function initDrawer() {
    const drawer = document.getElementById('side-drawer');
    const overlay = document.getElementById('drawer-overlay');
    const menuBtn = document.getElementById('menu-btn');
    const navMenuBtn = document.getElementById('nav-menu-btn');
    const closeBtn = document.getElementById('drawer-close');
    const drawerItems = document.querySelectorAll('.drawer-item');

    function openDrawer() {
        if (drawer) drawer.classList.add('active');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        if (drawer) drawer.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Menu button in header
    if (menuBtn) {
        menuBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openDrawer();
        });
    }

    // More button in bottom nav
    if (navMenuBtn) {
        navMenuBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openDrawer();
        });
    }

    // Close button
    if (closeBtn) {
        closeBtn.addEventListener('click', closeDrawer);
    }

    // Overlay click to close
    if (overlay) {
        overlay.addEventListener('click', closeDrawer);
    }

    // Drawer items
    drawerItems.forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            drawerItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            closeDrawer();
            showToast('Feature coming soon!');
        });
    });
}