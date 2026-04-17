<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { useDriverTripStore } from '@/stores/useDriverTripStore'

const router = useRouter()
const authStore = useAuthStore()
const driverTripStore = useDriverTripStore()

const loading = ref(false)
const statsLoading = ref(true)

const fetchAllHistory = async () => {
  const firstPage = await driverTripStore.fetchHistory(1)
  const lastPage = firstPage?.last_page || 1
  
  for (let page = 2; page <= lastPage; page++) {
    await driverTripStore.fetchHistory(page, { append: true })
  }
}

const refreshData = async () => {
  statsLoading.value = true
  try {
    driverTripStore.resetHistory()
    await fetchAllHistory()
  } finally {
    statsLoading.value = false
  }
}

onMounted(async () => {
  await Promise.all([
    checkActiveTrip(),
    fetchAllHistory()
  ])
  statsLoading.value = false
})

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

const todayStats = computed(() => {
  const today = new Date().toISOString().split('T')[0]
  const trips = driverTripStore.historyTrips || []
  
  const todayTrips = trips.filter(t => {
    return t.trip_date === today && t.status === 'completed'
  })
  
  const groupedByBus = {}
  todayTrips.forEach(trip => {
    const busKey = trip.bus?.code || trip.bus?.plate_number || 'Unknown'
    if (!groupedByBus[busKey]) {
      groupedByBus[busKey] = { count: 0, time: 0 }
    }
    groupedByBus[busKey].count += 1
    if (trip.started_at && trip.ended_at) {
      const start = new Date(trip.started_at)
      const end = new Date(trip.ended_at)
      groupedByBus[busKey].time += Math.round((end - start) / 60000)
    }
  })
  
  const busStats = Object.entries(groupedByBus).map(([bus, data]) => ({
    bus,
    count: data.count,
    time: formatTime(data.time)
  }))
  
  return {
    trips: todayTrips.length,
    busStats
  }
})

const totalStats = computed(() => {
  const trips = driverTripStore.historyTrips || []
  
  const completedTrips = trips.filter(t => t.status === 'completed')
  
  const groupedByBus = {}
  completedTrips.forEach(trip => {
    const busKey = trip.bus?.code || trip.bus?.plate_number || 'Unknown'
    if (!groupedByBus[busKey]) {
      groupedByBus[busKey] = { count: 0, time: 0 }
    }
    groupedByBus[busKey].count += 1
    if (trip.started_at && trip.ended_at) {
      const start = new Date(trip.started_at)
      const end = new Date(trip.ended_at)
      groupedByBus[busKey].time += Math.round((end - start) / 60000)
    }
  })
  
  const busStats = Object.entries(groupedByBus).map(([bus, data]) => ({
    bus,
    count: data.count,
    time: formatTime(data.time)
  }))
  
  return {
    trips: completedTrips.length,
    busStats
  }
})

const formatTime = (mins) => {
  if (!mins || mins <= 0) return '0m'
  const h = Math.floor(mins / 60)
  const m = mins % 60
  return h > 0 ? `${h}h ${m}m` : `${m}m`
}

const recentTrips = computed(() => {
  const trips = driverTripStore.historyTrips || []
  return trips.slice(0, 3).map(trip => ({
    ...trip,
    routeName: trip.route?.name || 'Unknown Route',
    busCode: trip.bus?.code || trip.bus?.plate_number || 'BUS',
    formattedTime: formatTripTime(trip),
    statusClass: trip.status === 'completed' ? 'completed' : 'cancelled'
  }))
})

const formatTripTime = (trip) => {
  if (!trip.started_at) return ''
  const time = new Date(trip.started_at).toLocaleTimeString([], { 
    hour: '2-digit', 
    minute: '2-digit',
    hour12: true
  })
  return time
}

const formatDuration = (trip) => {
  if (!trip.started_at || !trip.ended_at) return ''
  const start = new Date(trip.started_at)
  const end = new Date(trip.ended_at)
  const mins = Math.round((end - start) / 60000)
  if (mins < 60) return `${mins}m`
  const h = Math.floor(mins / 60)
  const m = mins % 60
  return `${h}h ${m}m`
}

onMounted(async () => {
  await Promise.all([
    checkActiveTrip(),
    driverTripStore.fetchHistory(1)
  ])
  statsLoading.value = false
})

const checkActiveTrip = async () => {
  const hasCache = driverTripStore.apiCache?.currentTrip &&
    (Date.now() - driverTripStore.apiCache.currentTrip) < 30000

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
    <div v-if="loading" class="dashboard-skeleton">
      <div class="dash-top dashboard-skeleton-top">
        <div class="dashboard-skeleton-copy">
          <div class="skeleton-shape" style="width: 88px; height: 14px;"></div>
          <div class="skeleton-shape" style="width: 148px; height: 28px; margin-top: 8px;"></div>
          <div class="skeleton-shape" style="width: 116px; height: 12px; margin-top: 8px;"></div>
        </div>
        <div class="dashboard-skeleton-avatar"></div>
      </div>

      <div class="stats-card dashboard-skeleton-card">
        <div class="stats-header">
          <div class="skeleton-shape" style="width: 118px; height: 16px;"></div>
        </div>
        <div class="stats-row-skeleton">
          <div class="skeleton-stat">
            <div class="skeleton-icon"></div>
            <div class="skeleton-text">
              <div class="skeleton-shape" style="width: 40px; height: 24px;"></div>
              <div class="skeleton-shape" style="width: 50px; height: 14px; margin-top: 6px;"></div>
            </div>
          </div>
          <div class="skeleton-divider"></div>
          <div class="skeleton-stat">
            <div class="skeleton-icon"></div>
            <div class="skeleton-text">
              <div class="skeleton-shape" style="width: 40px; height: 24px;"></div>
              <div class="skeleton-shape" style="width: 50px; height: 14px; margin-top: 6px;"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="hero-card dashboard-skeleton-card dashboard-skeleton-hero">
        <div class="skeleton-hero-icon"></div>
        <div class="skeleton-hero-body">
          <div class="skeleton-shape" style="width: 138px; height: 22px;"></div>
          <div class="skeleton-shape" style="width: 85%; height: 13px; margin-top: 10px;"></div>
          <div class="skeleton-shape" style="width: 72%; height: 13px; margin-top: 8px;"></div>
        </div>
        <div class="dashboard-skeleton-pill"></div>
      </div>

      <div class="activity-section dashboard-skeleton-card">
        <div class="activity-header">
          <div class="skeleton-shape" style="width: 132px; height: 16px;"></div>
        </div>
        <div class="activity-list">
          <div v-for="i in 3" :key="i" class="activity-skeleton">
            <div class="skeleton-icon"></div>
            <div class="skeleton-content">
              <div class="skeleton-shape" style="width: 60%; height: 14px;"></div>
              <div class="skeleton-shape" style="width: 40%; height: 12px; margin-top: 6px;"></div>
            </div>
            <div class="skeleton-shape" style="width: 40px; height: 14px;"></div>
          </div>
        </div>
      </div>
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

      <!-- Dashboard Stats -->
      <div class="stats-card">
        <div class="stats-header">
          <i class="bi bi-bar-chart-fill"></i>
          <span>Trip Stats</span>
        </div>
        
        <!-- Stats Skeleton -->
        <div v-if="statsLoading" class="stats-row-skeleton">
          <div class="skeleton-stat">
            <div class="skeleton-icon"></div>
            <div class="skeleton-text">
              <div class="skeleton-shape" style="width: 40px; height: 24px;"></div>
              <div class="skeleton-shape" style="width: 50px; height: 14px; margin-top: 6px;"></div>
            </div>
          </div>
          <div class="skeleton-divider"></div>
          <div class="skeleton-stat">
            <div class="skeleton-icon"></div>
            <div class="skeleton-text">
              <div class="skeleton-shape" style="width: 40px; height: 24px;"></div>
              <div class="skeleton-shape" style="width: 50px; height: 14px; margin-top: 6px;"></div>
            </div>
          </div>
        </div>

        <!-- Stats Content -->
        <Transition name="fade" mode="out-in">
          <div v-if="!statsLoading" class="stats-row" key="stats">
            <div class="stat-item">
              <div class="stat-icon">
                <i class="bi bi-calendar-check"></i>
              </div>
              <div class="stat-detail">
                <div class="stat-value">{{ todayStats.trips }}</div>
                <div class="stat-label">Today</div>
              </div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <div class="stat-icon">
                <i class="bi bi-graph-up-arrow"></i>
              </div>
              <div class="stat-detail">
                <div class="stat-value">{{ totalStats.trips }}</div>
                <div class="stat-label">Total</div>
              </div>
            </div>
          </div>
        </Transition>
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

      <!-- Recent Activity -->
      <Transition name="fade" mode="out-in">
        <div v-if="statsLoading" class="activity-section" key="loading">
          <div class="activity-header">
            <i class="bi bi-clock-history"></i>
            <span>Recent Activity</span>
          </div>
          <div class="activity-list">
            <div v-for="i in 3" :key="i" class="activity-skeleton">
              <div class="skeleton-icon"></div>
              <div class="skeleton-content">
                <div class="skeleton-shape" style="width: 60%; height: 14px;"></div>
                <div class="skeleton-shape" style="width: 40%; height: 12px; margin-top: 6px;"></div>
              </div>
              <div class="skeleton-shape" style="width: 40px; height: 14px;"></div>
            </div>
          </div>
        </div>

        <div v-else-if="recentTrips.length > 0" class="activity-section" key="content">
          <div class="activity-header">
            <i class="bi bi-clock-history"></i>
            <span>Recent Activity</span>
          </div>
          <div class="activity-list">
            <div v-for="trip in recentTrips" :key="trip.id" class="activity-item">
              <div class="activity-icon" :class="trip.statusClass">
                <i class="bi bi-bus-front-fill"></i>
              </div>
              <div class="activity-content">
                <div class="activity-route">{{ trip.routeName }}</div>
                <div class="activity-meta">
                  <span>{{ trip.busCode }}</span>
                  <span class="activity-dot">•</span>
                  <span>{{ formatDuration(trip) }}</span>
                </div>
              </div>
              <div class="activity-time">{{ trip.formattedTime }}</div>
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<style scoped>
.dashboard-page {
  padding: 24px 16px;
}

.dashboard-skeleton {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.dashboard-skeleton-top {
  align-items: flex-start;
}

.dashboard-skeleton-copy {
  flex: 1;
  min-width: 0;
}

.dashboard-skeleton-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--gray-100);
  animation: skeleton-loading 1.5s ease-in-out infinite;
  flex-shrink: 0;
  margin-top: 4px;
}

.dashboard-skeleton-card {
  overflow: hidden;
}

.dashboard-skeleton-hero {
  cursor: default;
  pointer-events: none;
}

.skeleton-hero-icon {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: var(--gray-100);
  margin-bottom: 20px;
}

.skeleton-hero-body {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  width: 100%;
}

.dashboard-skeleton-pill {
  width: 144px;
  height: 44px;
  border-radius: 999px;
  background: var(--gray-100);
  animation: skeleton-loading 1.5s ease-in-out infinite;
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

.hero-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
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
  box-shadow: 0 8px 24px rgba(var(--primary-rgb), 0.3);
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
  box-shadow: 0 4px 16px rgba(var(--primary-rgb), 0.3);
  transition: transform var(--transition-fast), box-shadow var(--transition-fast), filter var(--transition-fast);
}

.hero-card:hover .hero-btn {
  transform: translateY(-1px);
  box-shadow: 0 8px 24px rgba(var(--primary-rgb), 0.35);
  filter: brightness(1.05);
}

.hero-card:active .hero-btn {
  transform: scale(0.96);
}

.hero-btn i {
  font-size: 16px;
}

/* ── Stats Card ── */
.stats-card {
  background: var(--white);
  border-radius: var(--radius-xl);
  padding: 20px 24px;
  box-shadow: var(--shadow-md);
}

.stats-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--gray-500);
  margin-bottom: 16px;
}

.stats-header i {
  font-size: 16px;
  color: var(--primary);
}

.stats-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 24px;
}

.stat-item {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 12px;
}

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: var(--primary-50);
  color: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

.stat-detail {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 24px;
  font-weight: 800;
  color: var(--gray-900);
  line-height: 1.1;
}

.stat-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--gray-500);
  margin-top: 2px;
}

.stat-divider {
  width: 1px;
  height: 44px;
  background: var(--gray-200);
}

/* ── Skeleton ── */
.stats-row-skeleton {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 24px;
}

.skeleton-stat {
  display: flex;
  align-items: center;
  gap: 12px;
}

.skeleton-icon {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: var(--gray-100);
}

.skeleton-text {
  display: flex;
  flex-direction: column;
}

.skeleton-divider {
  width: 1px;
  height: 44px;
  background: var(--gray-200);
  flex-shrink: 0;
}

/* ── Transitions ── */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* ── Recent Activity ── */
.activity-section {
  background: var(--white);
  border-radius: var(--radius-xl);
  padding: 20px 24px;
  box-shadow: var(--shadow-md);
}

.activity-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--gray-500);
  margin-bottom: 16px;
}

.activity-header i {
  font-size: 16px;
  color: var(--primary);
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: var(--radius-md);
  background: var(--gray-50);
  transition: background var(--transition-fast);
}

.activity-item:hover {
  background: var(--gray-100);
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.activity-icon.completed {
  background: var(--primary-50);
  color: var(--primary);
}

.activity-icon.cancelled {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.activity-content {
  flex: 1;
  min-width: 0;
}

.activity-route {
  font-size: 14px;
  font-weight: 600;
  color: var(--gray-900);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.activity-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--gray-500);
  margin-top: 2px;
}

.activity-dot {
  font-size: 8px;
  opacity: 0.5;
}

.activity-time {
  font-size: 12px;
  font-weight: 600;
  color: var(--gray-400);
  flex-shrink: 0;
}

/* ── Activity Skeleton ── */
.activity-skeleton {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: var(--radius-md);
}

.activity-skeleton .skeleton-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
}

.activity-skeleton .skeleton-content {
  flex: 1;
  display: flex;
flex-direction: column;
}
</style>
