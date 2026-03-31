<script setup>
import { computed } from 'vue'
import { useMapStore } from '@/stores/useMapStore'
import { storeToRefs } from 'pinia'

const mapStore = useMapStore()
const { selectedTrip, selectedTripId, showTimeline } = storeToRefs(mapStore)

// ── helpers ────────────────────────────────────────────────
const STOP_PROXIMITY_METERS = 160
const TERMINAL_PROXIMITY_METERS = 260
const SEGMENT_END_SNAP_RATIO = 0.92

function haversineMeters(lat1, lng1, lat2, lng2) {
  const R = 6371000
  const dLat = (lat2 - lat1) * Math.PI / 180
  const dLng = (lng2 - lng1) * Math.PI / 180
  const a = Math.sin(dLat / 2) ** 2 +
    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
}

function projectToMeters(lat, lng, originLat) {
  const latMeters = 110540
  const lngMeters = 111320 * Math.cos(originLat * Math.PI / 180)

  return {
    x: lng * lngMeters,
    y: lat * latMeters,
  }
}

function distanceToSegmentMeters(lat, lng, startLat, startLng, endLat, endLng) {
  const originLat = (startLat + endLat + lat) / 3
  const point = projectToMeters(lat, lng, originLat)
  const start = projectToMeters(startLat, startLng, originLat)
  const end = projectToMeters(endLat, endLng, originLat)

  const segX = end.x - start.x
  const segY = end.y - start.y
  const segLenSq = segX ** 2 + segY ** 2

  if (segLenSq === 0) {
    return {
      distance: Math.hypot(point.x - start.x, point.y - start.y),
      t: 0,
    }
  }

  const rawT = ((point.x - start.x) * segX + (point.y - start.y) * segY) / segLenSq
  const t = Math.max(0, Math.min(1, rawT))
  const projectionX = start.x + segX * t
  const projectionY = start.y + segY * t

  return {
    distance: Math.hypot(point.x - projectionX, point.y - projectionY),
    t,
  }
}

function resolveCurrentStopIndex(stops, busLat, busLng) {
  if (busLat === null || busLng === null) return 0
  if (stops.length <= 1) return 0

  const distances = stops.map(stop =>
    haversineMeters(busLat, busLng, parseFloat(stop.lat), parseFloat(stop.lng))
  )

  const lastIdx = stops.length - 1
  const firstTerminalDistance = distances[0]
  const lastTerminalDistance = distances[lastIdx]

  if (firstTerminalDistance <= TERMINAL_PROXIMITY_METERS || lastTerminalDistance <= TERMINAL_PROXIMITY_METERS) {
    return firstTerminalDistance <= lastTerminalDistance ? 0 : lastIdx
  }

  const nearbyStopIndexes = distances
    .map((distance, index) => distance <= STOP_PROXIMITY_METERS ? index : -1)
    .filter(index => index >= 0)

  if (nearbyStopIndexes.length > 0) {
    return Math.max(...nearbyStopIndexes)
  }

  let bestSegment = { distance: Infinity, startIdx: 0, t: 0 }

  for (let i = 0; i < lastIdx; i++) {
    const segment = distanceToSegmentMeters(
      busLat,
      busLng,
      parseFloat(stops[i].lat),
      parseFloat(stops[i].lng),
      parseFloat(stops[i + 1].lat),
      parseFloat(stops[i + 1].lng),
    )

    if (segment.distance < bestSegment.distance) {
      bestSegment = {
        distance: segment.distance,
        startIdx: i,
        t: segment.t,
      }
    }
  }

  if (bestSegment.t >= SEGMENT_END_SNAP_RATIO) {
    return Math.min(bestSegment.startIdx + 1, lastIdx)
  }

  return bestSegment.startIdx
}

function timeAgo(dateString) {
  if (!dateString) return 'No GPS data'
  const diffMins = Math.floor((Date.now() - new Date(dateString)) / 60000)
  if (diffMins < 1) return 'just now'
  if (diffMins === 1) return '1 min ago'
  if (diffMins < 60) return `${diffMins} mins ago`
  return 'over 1 hr ago'
}

function formatTime(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

// ── derived data ──────────────────────────────────────────
const trip = computed(() => selectedTrip.value)

const busCode = computed(() =>
  trip.value?.bus?.plate_number || trip.value?.bus?.code || `Bus${trip.value?.id}`
)

// Short badge label: last 2 dash-segments, max 9 chars
const badgeCode = computed(() => {
  const p = busCode.value
  if (!p) return '?'
  const parts = p.split('-')
  if (parts.length <= 2) return p.slice(0, 9)
  return parts.slice(-2).join('-').slice(0, 9)
})

const busName = computed(() =>
  trip.value?.bus?.display_name || trip.value?.bus?.name || trip.value?.route?.name || 'Unknown Bus'
)

const routeName = computed(() =>
  trip.value?.route?.name || 'Unknown Route'
)

const tripStatus = computed(() => {
  const s = trip.value?.status || 'active'
  return s === 'ongoing' ? 'active' : s
})

const statusLabel = computed(() => ({
  active: 'On Route',
  delayed: 'Delayed',
  inactive: 'Inactive',
}[tripStatus.value] ?? 'On Route'))

const lastUpdate = computed(() => {
  const loc = trip.value?.latestLocation || trip.value?.latest_location
  return loc?.recorded_at ? `Updated ${timeAgo(loc.recorded_at)}` : 'No GPS data'
})

// ── stops with state ──────────────────────────────────────
const stopsWithState = computed(() => {
  const raw = trip.value?.route?.stops
  if (!raw || raw.length === 0) return []

  // Sort by sequence
  const stops = [...raw].sort((a, b) => a.sequence - b.sequence)

  // Find bus current position
  const loc = trip.value?.latestLocation || trip.value?.latest_location
  const busLat = loc?.lat ? parseFloat(loc.lat) : null
  const busLng = loc?.lng ? parseFloat(loc.lng) : null

  const lastIdx = stops.length - 1
  const currentIdx = resolveCurrentStopIndex(stops, busLat, busLng)

  return stops.map((stop, i) => {
    let state
    if (i < currentIdx) state = 'passed'
    else if (i === currentIdx) state = 'current'
    else if (i === lastIdx) state = 'destination'
    else state = 'upcoming'
    return { ...stop, state }
  })
})

// ── schedule trips (from route.schedulePeriod if present) ─
const schedulePeriod = computed(() =>
  trip.value?.route?.schedule_period || trip.value?.route?.schedulePeriod || null
)

// ── actions ───────────────────────────────────────────────
function close() {
  mapStore.clearSelection()
}
</script>

<template>
  <Transition name="timeline-slide">
    <div v-if="showTimeline && selectedTripId && trip" class="timeline-panel active">
      <!-- Header -->
      <div class="timeline-header">
        <button class="timeline-close" @click="close">
          <i class="bi bi-x-lg"></i>
        </button>

        <div class="timeline-bus-info">
          <div class="timeline-badge"><i class="bi bi-bus-front-fill"></i></div>
          <div class="timeline-details">
            <h2>{{ busName }}</h2>
            <span class="timeline-route">{{ routeName }}</span>
          </div>
        </div>

        <div class="timeline-status">
          <span class="status-badge" :class="tripStatus">{{ statusLabel }}</span>
          <span class="arrival-time">{{ lastUpdate }}</span>
        </div>
      </div>

      <!-- Scrollable content -->
      <div class="timeline-content">

        <!-- Route Timeline -->
        <div class="timeline-section">
          <h3><i class="bi bi-signpost-split"></i> Route Stops</h3>

          <div v-if="stopsWithState.length > 0" class="route-timeline">
            <div
              v-for="stop in stopsWithState"
              :key="stop.id"
              class="timeline-stop"
              :class="stop.state"
            >
              <div class="stop-marker">
                <div v-if="stop.state === 'current'" class="current-pulse"></div>
                <i
                  :class="{
                    'bi bi-check-circle-fill': stop.state === 'passed',
                    'bi bi-bus-front-fill':    stop.state === 'current',
                    'bi bi-circle':            stop.state === 'upcoming',
                    'bi bi-geo-alt-fill':      stop.state === 'destination',
                  }"
                ></i>
              </div>
              <div class="stop-info">
                <span class="stop-name">{{ stop.name }}</span>
                <span class="stop-status">
                  {{ stop.state === 'passed' ? 'Passed' :
                     stop.state === 'current' ? 'Currently Here' :
                     stop.state === 'destination' ? 'Final Stop' :
                     'Upcoming' }}
                </span>
              </div>
            </div>
          </div>

          <div v-else class="no-stops-msg">
            <i class="bi bi-exclamation-circle"></i> No stop data available
          </div>
        </div>

        <!-- Schedule Period -->
        <div v-if="schedulePeriod" class="timeline-section">
          <h3><i class="bi bi-calendar3"></i> Service Period</h3>
          <div class="schedule-list">
            <div class="schedule-item active">
              <span class="schedule-time">{{ formatTime(schedulePeriod.start_time) }}</span>
              <span class="schedule-status">Start</span>
            </div>
            <div class="schedule-item">
              <span class="schedule-time">{{ formatTime(schedulePeriod.end_time) }}</span>
              <span class="schedule-status">End</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Action buttons (coming soon) -->
      <!-- <div class="timeline-actions">
        <button class="btn-action primary">
          <i class="bi bi-person-check-fill"></i>
          I'm on this bus
        </button>
        <button class="btn-action secondary">
          <i class="bi bi-bell-fill"></i>
          Set Alert
        </button>
      </div> -->
    </div>
  </Transition>
</template>

<style scoped>
/* Vue transition fallback — CSS handles the real animation via .active class */
.timeline-slide-enter-active,
.timeline-slide-leave-active {
  transition: transform 0.3s ease;
}
.timeline-slide-enter-from,
.timeline-slide-leave-to {
  transform: translateX(100%);
}

.no-stops-msg {
  color: var(--gray-500, #64748b);
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 12px 0;
}
</style>
