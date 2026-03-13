<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useBackgroundLocation } from '@/composables/useBackgroundLocation'
import { useDriverTripStore } from '@/stores/useDriverTripStore'
import api from '@/api/client'
import StartStopButton from '@/components/driver/StartStopButton.vue'
import EndTripConfirmModal from '@/components/EndTripConfirmModal.vue'

const router = useRouter()
const { isTracking, currentLocation, startTracking, stopTracking } = useBackgroundLocation()
const driverTripStore = useDriverTripStore()

let locationInterval = null

const trip = ref(null)
const location = ref({ lat: 0, lng: 0 })
const endingTrip = ref(false)
const showEndTripModal = ref(false)

const tripDuration = computed(() => {
  if (!trip.value?.started_at) return '00:00:00'

  const start = new Date(trip.value.started_at)
  const now = new Date()
  const diff = Math.floor((now - start) / 1000)

  const hours = Math.floor(diff / 3600)
  const minutes = Math.floor((diff % 3600) / 60)
  const seconds = diff % 60

  return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
})

// Update duration every second
let durationInterval = null

onMounted(async () => {
  await fetchTrip()
  await startLocationTracking()
  // Send location on a fixed 3-second interval (independent of GPS fix rate)
  locationInterval = setInterval(() => {
    if (currentLocation.value && currentLocation.value.lat !== 0) {
      sendLocationToServer(currentLocation.value)
    }
  }, 5000)
  startDurationCounter()
})

onUnmounted(async () => {
  clearInterval(locationInterval)
  await stopLocationTracking()
  if (durationInterval) {
    clearInterval(durationInterval)
  }
})

const fetchTrip = async () => {
  try {
    const response = await api.get('/driver/trips/current')
    if (!response.data) {
      router.push({ name: 'trip-select-bus' })
      return
    }
    trip.value = response.data
  } catch (error) {
    console.error('Failed to fetch trip:', error)
    router.push({ name: 'trip-select-bus' })
  }
}

const sendLocationToServer = async (loc) => {
  location.value = loc

  if (trip.value) {
    try {
      await api.post('/driver/location', {
        trip_id: trip.value.id,
        lat: loc.lat,
        lng: loc.lng,
        speed: loc.speed
      })
    } catch (error) {
      console.error('Failed to update location:', error)
    }
  }
}

const startLocationTracking = async () => {
  try {
    // No callback — GPS watch just keeps currentLocation.value fresh.
    // The 3s interval in onMounted handles POSTing to the server.
    await startTracking()
  } catch (error) {
    console.error('Failed to start tracking:', error)
  }
}

const startDurationCounter = () => {
  durationInterval = setInterval(() => {
    // Trigger computed update
    tripDuration.value
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
    <!-- Trip Details Card -->
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
          <span class="trip-status-label">Status</span>
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

    <!-- Location Tracking Card -->
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
          <span class="trip-status-value" :class="isTracking ? 'text-green-600' : 'text-red-600'">
            {{ isTracking ? '● Active' : '● Stopped' }}
          </span>
        </div>
        <div class="trip-status-row">
          <span class="trip-status-label">Latitude</span>
          <span class="trip-status-value">{{ location.lat.toFixed(7) }}</span>
        </div>
        <div class="trip-status-row">
          <span class="trip-status-label">Longitude</span>
          <span class="trip-status-value">{{ location.lng.toFixed(7) }}</span>
        </div>
      </div>
    </div>

    <!-- End Trip Button -->
    <StartStopButton
      type="stop"
      :loading="endingTrip"
      @click="handleEndTrip"
    />

    <!-- Confirmation Modal -->
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
