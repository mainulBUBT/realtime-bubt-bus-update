<script setup>
import { computed } from 'vue'
import { useMapStore } from '@/stores/useMapStore'
import { storeToRefs } from 'pinia'

const mapStore = useMapStore()
const { selectedTrip, selectedTripId, showTimeline } = storeToRefs(mapStore)

function formatDistance(distanceMeters) {
  if (!Number.isFinite(distanceMeters)) return ''
  if (distanceMeters < 20) return `${Math.round(distanceMeters)} m away`
  if (distanceMeters < 1000) return `${Math.round(distanceMeters / 10) * 10} m away`
  return `${(distanceMeters / 1000).toFixed(1)} km away`
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

const trackingStatus = computed(() => trip.value?.tracking_status || 'no_gps')

const tripStatus = computed(() => {
  const status = trackingStatus.value
  if (status === 'at_stop') return 'active'
  if (status === 'backward') return 'delayed'
  if (status === 'off_route' || status === 'no_gps') return 'inactive'
  return 'active'
})

const statusLabel = computed(() => ({
  on_route: 'On Route',
  at_stop: 'At Stop',
  off_route: 'Off Route',
  backward: 'Returning',
  no_gps: 'No GPS',
}[trackingStatus.value] ?? 'On Route'))

const lastUpdate = computed(() => {
  const loc = trip.value?.latestLocation || trip.value?.latest_location
  return loc?.recorded_at ? `Updated ${timeAgo(loc.recorded_at)}` : 'No GPS data'
})

// ── stops with state ──────────────────────────────────────
const stopsWithState = computed(() => {
  const raw = trip.value?.route?.stops
  if (!raw || raw.length === 0) return []

  const stops = [...raw].sort((a, b) => a.sequence - b.sequence)
  const stateByStopId = new Map((trip.value?.stop_states || []).map((entry) => [entry.stop_id, entry]))
  const nextStopId = trip.value?.next_stop_id
  const nextDistance = Number(trip.value?.distance_to_next_stop_m)

  return stops.map((stop, i) => {
    const backendState = stateByStopId.get(stop.id)
    const state = backendState?.state || (i === stops.length - 1 ? 'destination' : 'upcoming')
    let statusText = backendState?.status_text || 'Upcoming'

    if (stop.id === nextStopId && Number.isFinite(nextDistance) && trackingStatus.value === 'on_route') {
      statusText = `Approaching - ${formatDistance(nextDistance)}`
    }

    return { ...stop, state, statusText }
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
                    'bi bi-signpost-fill':     stop.state === 'approaching',
                    'bi bi-circle':            stop.state === 'upcoming',
                    'bi bi-geo-alt-fill':      stop.state === 'destination',
                  }"
                ></i>
              </div>
              <div class="stop-info">
                <span class="stop-name">{{ stop.name }}</span>
                <span class="stop-status">{{ stop.statusText }}</span>
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
