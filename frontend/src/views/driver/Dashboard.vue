<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { useDriverTripStore } from '@/stores/useDriverTripStore'

const router = useRouter()
const authStore = useAuthStore()
const driverTripStore = useDriverTripStore()

const loading = ref(false)

const driverName = computed(() => authStore.user?.name || 'Driver')

const driverInitials = computed(() => {
  const name = authStore.user?.name
  if (!name) return 'D'
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
})

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good Morning'
  if (hour < 17) return 'Good Afternoon'
  return 'Good Evening'
})

const currentDate = computed(() => {
  return new Date().toLocaleDateString([], {
    weekday: 'long',
    month: 'long',
    day: 'numeric'
  })
})

onMounted(async () => {
  await checkActiveTrip()
})

const checkActiveTrip = async () => {
  // Skip loading state if we have recent cached data
  const hasCache = driverTripStore._cache.currentTrip &&
    (Date.now() - driverTripStore._cache.currentTrip) < 30000

  loading.value = !hasCache
  try {
    await driverTripStore.fetchCurrentTrip()

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
  <div class="dashboard-page">
    <!-- Loading State -->
    <div v-if="loading" class="space-y-4">
      <div class="skeleton-shape" style="width: 40%; height: 12px;"></div>
      <div class="skeleton-shape" style="width: 55%; height: 20px;"></div>
      <div class="skeleton-shape" style="width: 60%; height: 12px; margin-top: 4px;"></div>
      <div class="skeleton-shape" style="height: 200px; border-radius: var(--radius-lg, 16px); margin-top: 12px;"></div>
    </div>

    <!-- Dashboard Content -->
    <div v-else class="dashboard-content">
      <!-- Top Bar: Greeting left, Avatar right -->
      <div class="dash-top">
        <div>
          <p class="dash-greeting">{{ greeting }}</p>
          <h1 class="dash-name">{{ driverName }}</h1>
          <p class="dash-date">{{ currentDate }}</p>
        </div>
        <div class="dash-avatar">{{ driverInitials }}</div>
      </div>

      <!-- Start Trip Hero Card -->
      <button class="hero-card" @click="handleStartTrip">
        <div class="hero-icon-circle">
          <i class="bi bi-bus-front-fill"></i>
        </div>
        <div class="hero-body">
          <h2 class="hero-title">Start New Trip</h2>
          <p class="hero-desc">Select a bus and route direction to begin tracking your journey</p>
        </div>
        <div class="hero-btn">
          <span>Get Started</span>
          <i class="bi bi-arrow-right"></i>
        </div>
      </button>
    </div>
  </div>
</template>

<style scoped>
.dashboard-page {
  padding: 24px 16px;
}

.dashboard-content {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

/* ── Top Bar ── */
.dash-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.dash-greeting {
  font-size: 14px;
  font-weight: 500;
  color: var(--gray-500);
  margin: 0;
}

.dash-name {
  font-size: 22px;
  font-weight: 800;
  color: var(--gray-900);
  margin: 4px 0 0;
  line-height: 1.2;
}

.dash-date {
  font-size: 12px;
  color: var(--gray-400);
  margin: 6px 0 0;
}

.dash-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  color: var(--white);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  font-weight: 700;
  flex-shrink: 0;
  margin-top: 4px;
}

/* ── Hero Card ── */
.hero-card {
  width: 100%;
  background: var(--white);
  border: none;
  border-radius: var(--radius-xl);
  padding: 32px 24px 24px;
  box-shadow: var(--shadow-md);
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  cursor: pointer;
  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.hero-card:active {
  transform: scale(0.98);
  box-shadow: var(--shadow-sm);
}

.hero-icon-circle {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30px;
  color: var(--white);
  margin-bottom: 20px;
  box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
}

.hero-body {
  margin-bottom: 24px;
}

.hero-title {
  font-size: 18px;
  font-weight: 700;
  color: var(--gray-900);
  margin: 0 0 8px;
}

.hero-desc {
  font-size: 13px;
  color: var(--gray-500);
  margin: 0;
  line-height: 1.5;
}

.hero-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 28px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  color: var(--white);
  border-radius: 50px;
  font-size: 14px;
  font-weight: 700;
  box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
  transition: transform var(--transition-fast);
}

.hero-card:active .hero-btn {
  transform: scale(0.96);
}

.hero-btn i {
  font-size: 16px;
}
</style>
