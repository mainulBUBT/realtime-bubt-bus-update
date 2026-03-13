<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useDriverTripStore } from '@/stores/useDriverTripStore'

const router = useRouter()
const driverTripStore = useDriverTripStore()

const loading = ref(false)

onMounted(async () => {
  await checkActiveTrip()
})

const checkActiveTrip = async () => {
  loading.value = true
  try {
    await driverTripStore.fetchCurrentTrip()

    // If there's an active trip, redirect to active trip page
    if (driverTripStore.hasActiveTrip) {
      router.push({ name: 'trip-active' })
    }
  } catch (error) {
    console.error('Failed to check trip status:', error)
  } finally {
    loading.value = false
  }
}

const handleStartTrip = () => {
  router.push({ name: 'trip-select-bus' })
}
</script>

<template>
  <div class="container mx-auto px-4 py-6">
    <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-12">
      <div class="animate-spin w-12 h-12 border-4 border-green-500 border-t-transparent rounded-full mb-4"></div>
      <p class="text-gray-600">Loading...</p>
    </div>

    <!-- No Active Trip State -->
    <div v-else class="space-y-6">
      <!-- Welcome Card -->
      <div class="trip-status-card">
        <div class="trip-status-header">
          <div class="trip-status-icon">
            <i class="bi bi-person-fill"></i>
          </div>
          <div class="trip-status-title">Welcome, Driver</div>
        </div>
        <p class="text-gray-600 mt-2">
          Ready to start your shift? Select a bus to begin tracking your trip.
        </p>
      </div>

      <!-- Start Trip CTA -->
      <div class="empty-state" style="padding: 60px 20px;">
        <i class="bi bi-bus empty-state-icon" style="font-size: 64px;"></i>
        <h3 class="empty-state-title" style="font-size: 18px;">No Active Trip</h3>
        <p class="empty-state-text">
          You're not on a route right now. Start a new trip to begin tracking.
        </p>
        <button
          @click="handleStartTrip"
          class="start-stop-button start"
        >
          <i class="bi bi-play-fill"></i>
          <span>START NEW TRIP</span>
          <span class="text-xs opacity-75">Begin tracking your route</span>
        </button>
      </div>
    </div>
  </div>
</template>
