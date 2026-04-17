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
        class="retry-button"
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

    <!-- Starting Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="starting" class="modal-backdrop">
          <div class="modal-content">
            <div class="modal-body" style="padding: 32px 24px; text-align: center;">
              <div class="modal-spinner"></div>
              <p class="modal-text">Starting Trip...</p>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.retry-button {
  padding: 10px 24px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  color: var(--white);
  font-weight: 700;
  box-shadow: 0 8px 20px rgba(var(--primary-rgb), 0.24);
  transition: transform var(--transition-fast), box-shadow var(--transition-fast), filter var(--transition-fast);
}

.retry-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 24px rgba(var(--primary-rgb), 0.3);
  filter: brightness(1.04);
}

.retry-button:active {
  transform: scale(0.98);
}

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
  transition: transform var(--transition-fast), border-color var(--transition-fast), background var(--transition-fast), box-shadow var(--transition-fast);
}

.cancel-selection-button:hover {
  transform: translateY(-1px);
  background: var(--gray-50);
  border-color: rgba(var(--primary-rgb), 0.2);
  box-shadow: var(--shadow-md);
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

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 16px;
}

.modal-content {
  background: var(--white);
  border-radius: var(--radius-lg);
  width: 100%;
  max-width: 360px;
  box-shadow: var(--shadow-xl);
  overflow: hidden;
}

.modal-body {
  padding: 24px;
}

.modal-spinner {
  width: 48px;
  height: 48px;
  border: 4px solid rgba(var(--primary-rgb), 0.22);
  border-top-color: var(--primary);
  border-radius: 50%;
  margin: 0 auto 16px;
  animation: spin 1s linear infinite;
}

.modal-text {
  color: var(--gray-800);
  font-weight: 700;
  margin: 0;
  font-size: 16px;
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
