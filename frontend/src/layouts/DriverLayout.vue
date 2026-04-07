<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { useSettingsStore } from '@/stores/useSettingsStore'
import { RouterView } from 'vue-router'
import LogoutConfirmModal from '@/components/LogoutConfirmModal.vue'
import DriverMobileHeader from '@/components/driver/DriverMobileHeader.vue'
import DriverBottomNav from '@/components/driver/DriverBottomNav.vue'
import DriverMenuDrawer from '@/components/driver/DriverMenuDrawer.vue'
import { useNavigationDirection } from '@/composables/useNavigationDirection'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()
const showLogoutModal = ref(false)
const showMenuDrawer = ref(false)
const { transitionName } = useNavigationDirection()

const pageTitle = computed(() => {
  const titles = {
    'dashboard': 'Dashboard',
    'trip-history': 'History',
    'trip-select-bus': 'Select Bus',
    'trip-select-direction': 'Select Direction',
    'trip-active': 'Active Trip'
  }
  return titles[route.name] || (settingsStore.appSettings.appName || 'BUBT Driver')
})

const showBackButton = computed(() => {
  return route.name !== 'dashboard' && route.name !== 'trip-active' && route.name !== 'trip-history'
})

const handleLogoutClick = () => {
  showLogoutModal.value = true
}

const confirmLogout = async () => {
  showLogoutModal.value = false
  await authStore.logout()
  router.push({ name: 'login' })
}

const cancelLogout = () => {
  showLogoutModal.value = false
}

const handleBack = () => {
  router.back()
}
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Mobile Header (Visible on all screens) -->
    <DriverMobileHeader
      :title="pageTitle"
      :show-back="showBackButton"
      @back="handleBack"
      @logout="handleLogoutClick"
    />

    <!-- Main Content -->
    <main class="driver-main-content">
      <RouterView v-slot="{ Component, route }">
        <Transition :name="transitionName" mode="out-in">
          <component :is="Component" :key="route.path" />
        </Transition>
      </RouterView>
    </main>

    <!-- Driver Bottom Nav (Visible on all screens) -->
    <DriverBottomNav @open-menu="showMenuDrawer = true" />

    <!-- Logout Confirmation Modal -->
    <LogoutConfirmModal
      :show="showLogoutModal"
      @confirm="confirmLogout"
      @cancel="cancelLogout"
    />

    <!-- More Menu Drawer -->
    <DriverMenuDrawer
      :show="showMenuDrawer"
      @close="showMenuDrawer = false"
      @logout="() => { showMenuDrawer = false; handleLogoutClick() }"
    />
  </div>
</template>
