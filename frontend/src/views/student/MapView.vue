<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import L from 'leaflet'
import { useMapStore } from '@/stores/useMapStore'
import { storeToRefs } from 'pinia'

// Fix Leaflet default marker icon paths broken by Vite bundling
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
  iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
  shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
})

const BUBT = { lat: 23.8759, lng: 90.3795 }

const mapStore = useMapStore()
const { trips, selectedTripId, lastLocationUpdate } = storeToRefs(mapStore)

const mapContainer = ref(null)
const mapLoading = ref(true)

let mapInstance = null
let busMarkers = {}
let markerAnimations = {}  // token map — keyed by tripId, each { cancelled: false }
let busLastPositions = {}  // last WS-received { lat, lng } per tripId — source of truth for "did the bus actually move?"
let userMarker = null
let refreshInterval = null

// ── icon factory ─────────────────────────────────────────
function createBusIcon(code, status, hasGps = true, selected = false) {
  const cls = [
    !hasGps ? 'no-gps' : status === 'delayed' ? 'delayed' : status === 'inactive' ? 'inactive' : '',
    selected ? 'selected' : ''
  ].filter(Boolean).join(' ')

  return L.divIcon({
    className: 'custom-bus-marker',
    html: `
      <div class="bus-marker-icon ${cls}">
        <div class="bus-icon-wrapper">
          <i class="bi bi-bus-front-fill bus-bi-icon"></i>
        </div>
      </div>`,
    iconSize: [52, 52],
    iconAnchor: [26, 52],
    popupAnchor: [0, -54],
  })
}

function buildPopup(code, route, status, eta, hasGps = true) {
  const color = status === 'delayed' ? '#F59E0B' : status === 'inactive' ? '#94A3B8' : '#10B981'
  const gpsNote = hasGps ? '' : '<div style="font-size:0.7rem;color:#EF4444;margin-top:4px">⚠ No GPS — approximate position</div>'
  return `
    <div style="min-width:160px;font-family:inherit">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
        <div style="width:32px;height:32px;background:${color};border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.75rem">${code}</div>
        <div>
          <div style="font-weight:600;font-size:0.875rem">${code}</div>
          <div style="font-size:0.75rem;color:#64748B">${route}</div>
        </div>
      </div>
      <div style="font-size:0.75rem;color:#64748B;display:flex;align-items:center;gap:4px">
        <i class="bi bi-clock"></i> ${eta}
      </div>
      ${gpsNote}
    </div>`
}

// ── map init ──────────────────────────────────────────────
function initMap() {
  if (!mapContainer.value || mapInstance) return

  mapInstance = L.map(mapContainer.value, {
    center: [BUBT.lat, BUBT.lng],
    zoom: 13,
    zoomControl: false,
    attributionControl: false,
  })

  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(mapInstance)

  setTimeout(() => mapInstance.invalidateSize(), 200)
  mapLoading.value = false
}

// ── user location ─────────────────────────────────────────
function placeUserMarker(lat, lng) {
  const icon = L.divIcon({
    className: 'custom-user-marker',
    html: '<div class="user-location-marker"></div>',
    iconSize: [18, 18],
    iconAnchor: [9, 9],
  })
  if (userMarker) {
    userMarker.setLatLng([lat, lng])
  } else {
    userMarker = L.marker([lat, lng], { icon }).addTo(mapInstance)
  }
}

function locateOnStart() {
  if (!navigator.geolocation) {
    placeUserMarker(BUBT.lat, BUBT.lng)
    return
  }
  navigator.geolocation.getCurrentPosition(
    pos => {
      mapInstance?.flyTo([pos.coords.latitude, pos.coords.longitude], 15, { duration: 1.2 })
      placeUserMarker(pos.coords.latitude, pos.coords.longitude)
    },
    () => placeUserMarker(BUBT.lat, BUBT.lng),
    { enableHighAccuracy: true, timeout: 10000 }
  )
}

// ── markers ───────────────────────────────────────────────
function updateMapMarkers(tripList) {
  if (!mapInstance) return

  const seen = new Set()

  tripList.forEach(trip => {
    const loc = trip.latestLocation || trip.latest_location
    const id = trip.id
    const code = trip.bus?.plate_number || trip.bus?.code || `Bus${id}`
    const status = trip.status || 'active'
    const routeName = trip.route?.name || 'Unknown Route'
    const eta = loc?.recorded_at ? timeAgo(loc.recorded_at) : 'No GPS data'

    const lat = loc?.lat ? parseFloat(loc.lat) : BUBT.lat
    const lng = loc?.lng ? parseFloat(loc.lng) : BUBT.lng
    const hasGps = !!(loc?.lat && loc?.lng)
    const selected = id === selectedTripId.value

    seen.add(id)

    if (busMarkers[id]) {
      busMarkers[id].setIcon(createBusIcon(code, status, hasGps, selected))
      busMarkers[id].setPopupContent(buildPopup(code, routeName, status, eta, hasGps))

      // Polling safety net: update marker position when no WS animation is active.
      // This ensures markers move even when WebSocket is disconnected.
      if (hasGps && !markerAnimations[id]) {
        const currentPos = busMarkers[id].getLatLng()
        const newPos = L.latLng(lat, lng)
        if (currentPos.distanceTo(newPos) > 1) {
          busMarkers[id].setLatLng(newPos)
        }
      }
    } else {
      const marker = L.marker([lat, lng], { icon: createBusIcon(code, status, hasGps, selected) })
        .addTo(mapInstance)
        .bindPopup(buildPopup(code, routeName, status, eta, hasGps))
      // Clicking the map marker selects that bus and opens the timeline
      marker.on('click', () => mapStore.selectBus(id, true))
      busMarkers[id] = marker
    }
  })

  // Remove stale markers (trip ended / bus went offline)
  Object.keys(busMarkers).forEach(id => {
    if (!seen.has(Number(id))) {
      busMarkers[id].remove()
      delete busMarkers[id]
      // Cancel any in-progress animation and clear position history
      if (markerAnimations[id]) {
        markerAnimations[id].cancelled = true
        delete markerAnimations[id]
      }
      delete busLastPositions[id]
    }
  })
}

// ── distance helper ─────────────────────────────────────
function haversineMeters(lat1, lng1, lat2, lng2) {
  const R = 6371000  // Earth radius in metres
  const dLat = (lat2 - lat1) * Math.PI / 180
  const dLng = (lng2 - lng1) * Math.PI / 180
  const a = Math.sin(dLat / 2) ** 2 +
    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
}

// ── smooth marker animation ──────────────────────────────
/**
 * Animate a Leaflet marker from (fromLat, fromLng) to (toLat, toLng) over `duration` ms.
 * Cancels any in-progress animation for that marker to avoid position stutter.
 */
function animateMarker(tripId, marker, fromLat, fromLng, toLat, toLng, duration = 3000) {
  // Cancel any prior animation for this marker
  if (markerAnimations[tripId]) {
    markerAnimations[tripId].cancelled = true
  }
  const token = { cancelled: false }
  markerAnimations[tripId] = token

  const startTime = performance.now()
  function step(now) {
    if (token.cancelled) return
    const t = Math.min((now - startTime) / duration, 1)
    // linear easing
    const lat = fromLat + (toLat - fromLat) * t
    const lng = fromLng + (toLng - fromLng) * t
    marker.setLatLng([lat, lng])
    if (t < 1) {
      requestAnimationFrame(step)
    } else {
      delete markerAnimations[tripId]
    }
  }
  requestAnimationFrame(step)
}

// ── follow selected bus ───────────────────────────────────
function followSelectedBus(tripId) {
  if (!tripId || !mapInstance) return
  const trip = trips.value.find(t => t.id === tripId)
  if (!trip) return

  const loc = trip.latestLocation || trip.latest_location
  const lat = loc?.lat ? parseFloat(loc.lat) : BUBT.lat
  const lng = loc?.lng ? parseFloat(loc.lng) : BUBT.lng

  mapInstance.flyTo([lat, lng], 16, { duration: 0.8 })

  // Open this bus's popup after fly animation
  setTimeout(() => {
    if (busMarkers[tripId]) busMarkers[tripId].openPopup()
  }, 900)

  // Refresh marker icons to show selected state
  updateMapMarkers(trips.value)
}

// ── helpers ───────────────────────────────────────────────
function timeAgo(dateString) {
  if (!dateString) return 'No updates'
  const diffMins = Math.floor((Date.now() - new Date(dateString)) / 60000)
  if (diffMins < 1) return 'just now'
  if (diffMins === 1) return '1 min ago'
  if (diffMins < 60) return `${diffMins} mins ago`
  return 'over 1 hr ago'
}

// ── controls ─────────────────────────────────────────────
function locateMe() {
  if (!mapInstance || !navigator.geolocation) return
  navigator.geolocation.getCurrentPosition(
    pos => {
      const latlng = [pos.coords.latitude, pos.coords.longitude]
      mapInstance.flyTo(latlng, 16, { duration: 1 })
      placeUserMarker(pos.coords.latitude, pos.coords.longitude)
    },
    err => console.warn('[Map] locateMe failed:', err.message),
    { enableHighAccuracy: true, timeout: 8000 }
  )
}

function zoomIn()  { mapInstance?.zoomIn() }
function zoomOut() { mapInstance?.zoomOut() }

// ── lifecycle ─────────────────────────────────────────────
onMounted(async () => {
  initMap()
  locateOnStart()
  await mapStore.fetchTrips()
  updateMapMarkers(trips.value)
  // Poll every 30s as a fallback — Reverb WebSocket handles live updates
  refreshInterval = setInterval(async () => {
    await mapStore.fetchTrips()
    updateMapMarkers(trips.value)
  }, 30000)
})

onUnmounted(() => {
  clearInterval(refreshInterval)
  mapStore.unsubscribeAll()
  // Cancel all pending animations
  Object.values(markerAnimations).forEach(t => { t.cancelled = true })
  markerAnimations = {}
  busLastPositions = {}
  mapInstance?.remove()
  mapInstance = null
  busMarkers = {}
})

// ── watch store changes ───────────────────────────────────
// Re-render markers when trips update
watch(trips, (newTrips) => {
  updateMapMarkers(newTrips)
})

// Fly to bus when sidebar selection changes
watch(selectedTripId, (newId) => {
  followSelectedBus(newId)
})

// Animate only the specific marker that received a live WS update.
// Also pan the map if this is the currently selected bus.
watch(lastLocationUpdate, (update) => {
  if (!update || !mapInstance) return
  if (!busMarkers[update.tripId]) {
    updateMapMarkers(trips.value)
  }

  const marker = busMarkers[update.tripId]
  if (!marker) return

  const toLat = parseFloat(update.lat)
  const toLng = parseFloat(update.lng)

  // Compare against the LAST WS-received server position, not the marker's current
  // rendered position (which could be mid-animation). This is the only reliable way
  // to know whether the bus actually moved between server updates.
  const last = busLastPositions[update.tripId]
  const dist = last
    ? Math.abs(last.lat - toLat) + Math.abs(last.lng - toLng)
    : Infinity  // first update ever → always animate/place

  // Always record the new server position as the reference for next comparison
  busLastPositions[update.tripId] = { lat: toLat, lng: toLng }

  if (dist < 0.000001) return  // bus hasn't moved — do nothing, no animation

  // Calculate real-world distance between last known position and new position.
  // A city bus at 80 km/h travels ~111 m in 5 seconds. Anything beyond ~250 m
  // between updates is a GPS bounce / manual test / data glitch — snap it.
  const distMeters = last ? haversineMeters(last.lat, last.lng, toLat, toLng) : 0
  const isGlitch = distMeters > 250

  // Cancel any in-progress animation before we decide what to do
  if (markerAnimations[update.tripId]) {
    markerAnimations[update.tripId].cancelled = true
    delete markerAnimations[update.tripId]
  }

  if (isGlitch) {
    // GPS bounce or large data correction: snap instantly, no animation
    marker.setLatLng([toLat, toLng])
  } else {
    // Normal movement: animate from current rendered position (smooth continuation
    // if a prior animation was mid-flight) toward the new server position.
    // Scale duration so short moves don't feel slow (min 800ms, max 4500ms).
    const from = marker.getLatLng()
    const animDuration = Math.max(800, Math.min(4500, distMeters * 18))
    animateMarker(update.tripId, marker, from.lat, from.lng, toLat, toLng, animDuration)
  }

  // Gently re-center map on the selected bus without changing zoom
  if (update.tripId === selectedTripId.value) {
    mapInstance.panTo([toLat, toLng], { animate: true, duration: 0.5 })
  }
})
</script>

<template>
  <div class="map-wrapper">
    <!-- Loading placeholder -->
    <div v-if="mapLoading" class="map-loading-overlay">
      <div class="spinner-border spinner-border-sm text-success" role="status"></div>
      <span>Loading map…</span>
    </div>

    <!-- Leaflet map -->
    <div ref="mapContainer" id="map"></div>

    <!-- Map Controls -->
    <div class="map-controls">
      <button class="map-control-btn" title="My Location" @click="locateMe">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="12" cy="12" r="4" />
          <circle cx="12" cy="12" r="8" stroke-width="1.5" opacity="0.4" />
          <line x1="12" y1="2" x2="12" y2="6" />
          <line x1="12" y1="18" x2="12" y2="22" />
          <line x1="2" y1="12" x2="6" y2="12" />
          <line x1="18" y1="12" x2="22" y2="12" />
        </svg>
      </button>
      <button class="map-control-btn" title="Zoom In" @click="zoomIn">
        <i class="bi bi-plus-lg"></i>
      </button>
      <button class="map-control-btn" title="Zoom Out" @click="zoomOut">
        <i class="bi bi-dash-lg"></i>
      </button>
    </div>
  </div>
</template>

<style scoped>
</style>
