<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useDriverTripStore } from '@/stores/useDriverTripStore'
import { useDriverTrackingStore } from '@/stores/useDriverTrackingStore'
import StartStopButton from '@/components/driver/StartStopButton.vue'
import EndTripConfirmModal from '@/components/EndTripConfirmModal.vue'

const router = useRouter()
const driverTripStore = useDriverTripStore()
const driverTrackingStore = useDriverTrackingStore()

const endingTrip = ref(false)
const showEndTripModal = ref(false)
const nowTick = ref(Date.now())

const trip = computed(() => driverTripStore.currentTrip)

const location = computed(() => {
  const lastKnownLocation = driverTrackingStore.lastKnownLocation

  if (lastKnownLocation && (lastKnownLocation.timestamp || lastKnownLocation.lat !== 0 || lastKnownLocation.lng !== 0)) {
    return {
      lat: Number(lastKnownLocation.lat || 0),
      lng: Number(lastKnownLocation.lng || 0),
      speed: lastKnownLocation.speed
    }
  }

  return {
    lat: Number(trip.value?.current_lat || 0),
    lng: Number(trip.value?.current_lng || 0),
    speed: null
  }
})

const tripDuration = computed(() => {
  if (!trip.value?.started_at) return '00:00:00'

  const start = new Date(trip.value.started_at)
  const diff = Math.floor((nowTick.value - start.getTime()) / 1000)

  const hours = Math.floor(diff / 3600)
  const minutes = Math.floor((diff % 3600) / 60)
  const seconds = diff % 60

  return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
})

const trackingStatus = computed(() => {
  const labels = {
    idle: 'Stopped',
    starting: 'Starting',
    tracking: 'Active',
    syncing: 'Syncing',
    offline: 'Queued Offline',
    paused: 'Paused',
    error: 'Needs Attention'
  }

  return labels[driverTrackingStore.status] || 'Stopped'
})

const trackingStatusClass = computed(() => {
  if (['tracking', 'syncing'].includes(driverTrackingStore.status)) return 'text-green-600'
  if (driverTrackingStore.status === 'offline') return 'text-amber-600'
  if (driverTrackingStore.status === 'error') return 'text-red-600'
  return 'text-gray-500'
})

const trackingMessage = computed(() => {
  if (driverTrackingStore.lastError) {
    return driverTrackingStore.lastError
  }

  if (driverTrackingStore.providerError?.message) {
    return driverTrackingStore.providerError.message
  }

  if (driverTrackingStore.status === 'starting') {
    if (driverTrackingStore.provider === 'web-geolocation') {
      return 'Waiting for browser permission or your first location fix.'
    }

    return 'Starting location tracking...'
  }

  return ''
})

const trackingMessageClass = computed(() => (
  driverTrackingStore.lastError || driverTrackingStore.providerError
    ? 'text-sm text-red-600'
    : 'text-sm text-gray-500'
))

const formattedLastSentAt = computed(() => {
  if (!driverTrackingStore.lastSentAt) return 'Waiting for first sync'
  return new Date(driverTrackingStore.lastSentAt).toLocaleTimeString()
})

let durationInterval = null

onMounted(async () => {
  await fetchTrip()
  startDurationCounter()
})

onUnmounted(() => {
  if (durationInterval) {
    clearInterval(durationInterval)
  }
})

const fetchTrip = async () => {
  try {
    const activeTrip = driverTripStore.currentTrip || await driverTripStore.fetchCurrentTrip({ force: true })
    if (!activeTrip) {
      router.push({ name: 'trip-select-bus' })
    }
  } catch (error) {
    console.error('Failed to fetch trip:', error)
    router.push({ name: 'trip-select-bus' })
  }
}

const startDurationCounter = () => {
  durationInterval = setInterval(() => {
    nowTick.value = Date.now()
  }, 1000)
}

const handleEndTrip = () => {
  showEndTripModal.value = true
}

const confirmEndTrip = async () => {
  showEndTripModal.value = false
  endingTrip.value = true

  try {
    await driverTripStore.endTrip(trip.value.id)
    router.push({ name: 'dashboard' })
  } catch (error) {
    alert('Failed to end trip. Please try again.')
    endingTrip.value = false
  }
}

const cancelEndTrip = () => {
  showEndTripModal.value = false
}
</script>

<template>
  <div class="container mx-auto px-4 py-6 space-y-4">
    <div v-if="trip" class="trip-status-card">
      <div class="trip-status-header">
        <div class="trip-status-icon">
          <i class="bi bi-bus-front-fill"></i>
        </div>
        <div class="flex-1">
          <div class="trip-status-title">
            {{ trip.route?.name }} - {{ trip.route?.direction === 'down' ? 'Outbound' : 'Inbound' }}
          </div>
          <div class="text-sm text-gray-500">
            {{ trip.bus?.plate_number || trip.bus?.code }}
          </div>
        </div>
      </div>

      <div class="trip-status-info">
        <div class="trip-status-row">
          <span class="trip-status-label">Trip Status</span>
          <span class="trip-status-value text-green-600">{{ trip.status }}</span>
        </div>
        <div class="trip-status-row">
          <span class="trip-status-label">Started</span>
          <span class="trip-status-value">{{ new Date(trip.started_at).toLocaleTimeString() }}</span>
        </div>
        <div class="trip-status-row">
          <span class="trip-status-label">Duration</span>
          <span class="trip-status-value">{{ tripDuration }}</span>
        </div>
      </div>
    </div>

    <div class="trip-status-card">
      <div class="trip-status-header">
        <div class="trip-status-icon">
          <i class="bi bi-geo-alt-fill"></i>
        </div>
        <div class="trip-status-title">Location Tracking</div>
      </div>

      <div class="space-y-3">
        <div class="trip-status-row">
          <span class="trip-status-label">Status</span>
          <span class="trip-status-value" :class="trackingStatusClass">
            {{ trackingStatus }}
          </span>
        </div>
        <div class="trip-status-row">
          <span class="trip-status-label">Provider</span>
          <span class="trip-status-value">{{ driverTrackingStore.provider || 'inactive' }}</span>
        </div>
        <div class="trip-status-row">
          <span class="trip-status-label">Permission</span>
          <span class="trip-status-value">{{ driverTrackingStore.permissionState }}</span>
        </div>
        <div class="trip-status-row">
          <span class="trip-status-label">Queued Points</span>
          <span class="trip-status-value">{{ driverTrackingStore.queueSize }}</span>
        </div>
        <div class="trip-status-row">
          <span class="trip-status-label">Last Sync</span>
          <span class="trip-status-value">{{ formattedLastSentAt }}</span>
        </div>
        <div class="trip-status-row">
          <span class="trip-status-label">Latitude</span>
          <span class="trip-status-value">{{ location.lat.toFixed(7) }}</span>
        </div>
        <div class="trip-status-row">
          <span class="trip-status-label">Longitude</span>
          <span class="trip-status-value">{{ location.lng.toFixed(7) }}</span>
        </div>
        <div v-if="trackingMessage" :class="trackingMessageClass">
          {{ trackingMessage }}
        </div>
      </div>
    </div>

    <StartStopButton
      type="stop"
      :loading="endingTrip"
      @click="handleEndTrip"
    />

    <EndTripConfirmModal
      :show="showEndTripModal"
      @confirm="confirmEndTrip"
      @cancel="cancelEndTrip"
    />
  </div>
</template>

<style scoped>
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
