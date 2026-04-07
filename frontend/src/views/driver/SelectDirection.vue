<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useDriverTripStore } from '@/stores/useDriverTripStore'
import DirectionSelector from '@/components/driver/DirectionSelector.vue'

const router = useRouter()
const driverTripStore = useDriverTripStore()

const loading = ref(false)
const starting = ref(false)
const error = ref(null)
const routes = ref([])

onMounted(async () => {
  // Check if bus is selected
  if (!driverTripStore.selectedBus) {
    router.push({ name: 'trip-select-bus' })
    return
  }

  await fetchRoutes()
})

const fetchRoutes = async () => {
  loading.value = true
  error.value = null
  try {
    // Pass the selected bus ID to filter routes for this bus only
    const busId = driverTripStore.selectedBus?.id
    const data = await driverTripStore.fetchRoutes(busId)
    routes.value = data
  } catch (err) {
    error.value = 'Failed to load routes. Please try again.'
  } finally {
    loading.value = false
  }
}

const handleSelectDirection = async (route) => {
  starting.value = true
  error.value = null

  try {
    // Set the selected direction with route info
    driverTripStore.setSelectedDirection({
      ...route,
      routeId: route.id
    })

    // Start the trip
    await driverTripStore.startTrip()

    // Navigate to active trip
    router.push({ name: 'trip-active' })
  } catch (err) {
    error.value = err.message || 'Failed to start trip. Please try again.'
    starting.value = false
  }
}

const handleCancel = () => {
  driverTripStore.clearSelection()
  router.push({ name: 'trip-select-bus' })
}

const handleRetry = () => {
  fetchRoutes()
}

const getBusDisplay = (bus) => {
  if (bus.code && bus.display_name) {
    return `${bus.code} - ${bus.display_name}`
  }
  return bus.plate_number || bus.code || 'BUS'
}
</script>

<template>
  <div class="container mx-auto px-4 py-6">
    <!-- Selected Bus Summary -->
    <div v-if="driverTripStore.selectedBus" class="trip-status-card mb-4">
      <div class="trip-status-header">
        <div class="trip-status-icon">
          <i class="bi bi-bus-front-fill"></i>
        </div>
        <div>
          <div class="trip-status-title">Selected Bus</div>
          <div class="text-sm text-gray-800 font-medium">
            {{ getBusDisplay(driverTripStore.selectedBus) }}
          </div>
          <div class="text-xs text-gray-500">
            {{ driverTripStore.selectedBus.plate_number }}
          </div>
        </div>
      </div>
    </div>

    <!-- Section Title -->
    <h2 class="text-lg font-semibold text-gray-800 mb-4">
      Choose Route Direction
    </h2>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 2" :key="i" class="skeleton-direction-card">
        <div class="skeleton-icon"></div>
        <div class="skeleton-lines">
          <div class="skeleton-shape" style="width: 65%; height: 16px;"></div>
          <div class="skeleton-shape" style="width: 45%; height: 13px;"></div>
        </div>
        <div class="skeleton-chevron"></div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="empty-state">
      <i class="bi bi-exclamation-triangle empty-state-icon"></i>
      <h3 class="empty-state-title">Unable to Load Routes</h3>
      <p class="empty-state-text">{{ error }}</p>
      <button
        @click="handleRetry"
        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
      >
        Retry
      </button>
    </div>

    <!-- Direction List -->
    <div v-else class="space-y-3">
      <DirectionSelector
        v-for="route in routes"
        :key="route.id"
        :route="route"
        @select="handleSelectDirection"
      />
    </div>

    <!-- Cancel Button -->
    <div class="cancel-action-wrap">
      <button
        v-if="!loading && !starting"
        @click="handleCancel"
        class="cancel-selection-button"
      >
        <i class="bi bi-arrow-left-circle"></i>
        <span>Choose Another Bus</span>
      </button>
    </div>

    <!-- Starting Overlay -->
    <div
      v-if="starting"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-lg p-6 text-center">
        <div class="animate-spin w-12 h-12 border-4 border-green-500 border-t-transparent rounded-full mx-auto mb-4"></div>
        <p class="text-gray-800 font-semibold">Starting Trip...</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.cancel-action-wrap {
  display: flex;
  justify-content: center;
  margin-top: 18px;
  padding-bottom: 10px;
}

.cancel-selection-button {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 14px 18px;
  border: 1px solid var(--gray-200);
  border-radius: 14px;
  background: var(--white);
  color: var(--gray-700);
  font-size: 0.95rem;
  font-weight: 600;
  box-shadow: var(--shadow-sm);
  transition: transform var(--transition-fast), border-color var(--transition-fast), background var(--transition-fast);
}

.cancel-selection-button:active {
  transform: translateY(1px);
  background: var(--gray-50);
  border-color: var(--gray-300);
}

.cancel-selection-button i {
  font-size: 1.1rem;
  color: var(--gray-500);
}
</style>
