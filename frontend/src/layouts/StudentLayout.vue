<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { useMapStore } from '@/stores/useMapStore'
import { storeToRefs } from 'pinia'
import SplashScreen from '@/components/SplashScreen.vue'
import BottomNav from '@/components/BottomNav.vue'
import BusCard from '@/components/BusCard.vue'
import TimelinePanel from '@/components/TimelinePanel.vue'
import LogoutConfirmModal from '@/components/LogoutConfirmModal.vue'

const route = useRoute()
const isMapView = computed(() => route.name === 'map')

const sidebarOpen = ref(false)
const toggleSidebar = () => { sidebarOpen.value = !sidebarOpen.value }
const closeSidebar  = () => { sidebarOpen.value = false }

const router    = useRouter()
const authStore = useAuthStore()
const mapStore  = useMapStore()

const { buses, activeCount, delayedCount, inactiveCount, selectedTripId } = storeToRefs(mapStore)
const searchQuery = ref('')
const showLogoutModal = ref(false)

onMounted(async () => {
  authStore.loadUserFromStorage()
})

const handleLogoutClick = () => {
  showLogoutModal.value = true
  closeSidebar()
}

const confirmLogout = async () => {
  showLogoutModal.value = false
  await authStore.logout()
  router.push({ name: 'login' })
}

const cancelLogout = () => {
  showLogoutModal.value = false
}

const filteredBuses = computed(() => {
  if (!searchQuery.value) return buses.value
  const q = searchQuery.value.toLowerCase()
  return buses.value.filter(b =>
    b.code.toLowerCase().includes(q) ||
    b.name.toLowerCase().includes(q) ||
    b.route.toLowerCase().includes(q)
  )
})

function handleBusClick(bus) {
  mapStore.selectBus(bus.tripId)
  closeSidebar()
}
</script>

<template>
  <div class="main-screen active">
    <!-- Splash Screen -->
    <SplashScreen />

    <!-- Sidebar overlay backdrop (mobile only) -->
    <div
      class="sidebar-overlay"
      :class="{ active: sidebarOpen }"
      @click="closeSidebar"
    ></div>

    <!-- Sidebar -->
    <aside class="desktop-sidebar" :class="{ 'sidebar-open': sidebarOpen }">
      <div class="sidebar-header">
        <div class="sidebar-logo">
          <i class="bi bi-bus-front-fill"></i>
        </div>
        <div class="sidebar-brand">
          <h1>BUBT Bus Tracker</h1>
          <span>University Shuttle Service</span>
        </div>
        <!-- Close button (mobile only) -->
        <button class="sidebar-close-btn" @click="closeSidebar">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <!-- Search -->
      <div class="sidebar-search">
        <div class="search-input">
          <i class="bi bi-search"></i>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search buses..."
          >
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="sidebar-stats">
        <div class="stat-card active">
          <i class="bi bi-bus-front-fill"></i>
          <div class="stat-info">
            <span class="stat-number">{{ activeCount }}</span>
            <span class="stat-label">Active</span>
          </div>
        </div>
        <div class="stat-card delayed">
          <i class="bi bi-clock-fill"></i>
          <div class="stat-info">
            <span class="stat-number">{{ delayedCount }}</span>
            <span class="stat-label">Delayed</span>
          </div>
        </div>
        <div class="stat-card inactive">
          <i class="bi bi-pause-circle-fill"></i>
          <div class="stat-info">
            <span class="stat-number">{{ inactiveCount }}</span>
            <span class="stat-label">Inactive</span>
          </div>
        </div>
      </div>

      <!-- Bus List -->
      <div class="sidebar-buses">
        <h3 class="section-title">
          <span class="title-bar"></span>
          Available Buses
        </h3>
        <div class="bus-list">
          <BusCard
            v-for="bus in filteredBuses"
            :key="bus.id"
            :bus="bus"
            :class="{ 'bus-card-selected': bus.tripId === selectedTripId }"
            @click="handleBusClick(bus)"
          />
          <div v-if="filteredBuses.length === 0 && buses.length === 0" class="text-center py-4 text-muted">
            <small>No buses found</small>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="sidebar-footer">
        <a href="#" class="sidebar-link" @click.prevent>
          <i class="bi bi-gear-fill"></i>
          <span>Settings</span>
        </a>
        <a href="#" class="sidebar-link" @click.prevent>
          <i class="bi bi-question-circle-fill"></i>
          <span>Help</span>
        </a>
        <a href="#" class="sidebar-link" @click.prevent="handleLogoutClick">
          <i class="bi bi-box-arrow-right"></i>
          <span>Logout</span>
        </a>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content" :class="{ 'scrollable-content': !isMapView }">
      <!-- Mobile Header (< 768px) -->
      <header class="mobile-header">
        <button class="header-btn" @click="toggleSidebar">
          <i class="bi bi-list"></i>
        </button>
        <div class="header-center">
          <h1>BUBT Bus Tracker</h1>
        </div>
        <button class="header-btn">
          <i class="bi bi-bell-fill"></i>
          <span class="notification-badge">0</span>
        </button>
      </header>

      <slot />
    </main>

    <!-- Timeline panel — slides in from right when a bus is selected -->
    <TimelinePanel />

    <!-- Mobile Bottom Nav (< 768px) -->
    <BottomNav />

    <!-- Logout Confirmation Modal -->
    <LogoutConfirmModal
      :show="showLogoutModal"
      @confirm="confirmLogout"
      @cancel="cancelLogout"
    />
  </div>
</template>

<style scoped>
.main-content {
  position: relative;
  flex: 1;
  overflow: hidden;
}

.main-content.scrollable-content {
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}

/* Highlight selected bus card */
:deep(.bus-card-selected .bus-item) {
  border-left: 4px solid #10B981;
  background: rgba(16, 185, 129, 0.08);
}
</style>
