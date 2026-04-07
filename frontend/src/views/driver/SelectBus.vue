<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useDriverTripStore } from '@/stores/useDriverTripStore'
import BusSelectCard from '@/components/driver/BusSelectCard.vue'

const router = useRouter()
const driverTripStore = useDriverTripStore()

const loading = ref(false)
const error = ref(null)

onMounted(async () => {
  await fetchBuses()
})

const fetchBuses = async () => {
  loading.value = true
  error.value = null
  try {
    await driverTripStore.fetchAvailableBuses()
  } catch (err) {
    error.value = 'Failed to load available buses. Please try again.'
  } finally {
    loading.value = false
  }
}

const handleSelectBus = (bus) => {
  driverTripStore.setSelectedBus(bus)
  router.push({ name: 'trip-select-direction' })
}

const handleRetry = () => {
  fetchBuses()
}
</script>

<template>
  <div class="container mx-auto px-4 py-6">
    <!-- Section Title -->
    <h2 class="text-lg font-semibold text-gray-800 mb-4">
      Available Buses
    </h2>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="skeleton-bus-card">
        <div class="skeleton-icon"></div>
        <div class="skeleton-lines">
          <div class="skeleton-shape" style="width: 70%; height: 16px;"></div>
          <div class="skeleton-shape" style="width: 50%; height: 13px;"></div>
        </div>
        <div class="skeleton-badge"></div>
        <div class="skeleton-chevron"></div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="empty-state">
      <i class="bi bi-exclamation-triangle empty-state-icon"></i>
      <h3 class="empty-state-title">Unable to Load Buses</h3>
      <p class="empty-state-text">{{ error }}</p>
      <button
        @click="handleRetry"
        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
      >
        Retry
      </button>
    </div>

    <!-- Empty State -->
    <div v-else-if="driverTripStore.availableBuses.length === 0" class="empty-state">
      <i class="bi bi-bus empty-state-icon"></i>
      <h3 class="empty-state-title">No Buses Available</h3>
      <p class="empty-state-text">
        All buses are currently on trips. Check back later.
      </p>
    </div>

    <!-- Bus List -->
    <div v-else class="space-y-3">
      <BusSelectCard
        v-for="bus in driverTripStore.availableBuses"
        :key="bus.id"
        :bus="bus"
        @select="handleSelectBus"
      />
    </div>
  </div>
</template>
