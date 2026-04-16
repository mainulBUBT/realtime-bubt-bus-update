<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { App as CapacitorApp } from '@capacitor/app'
import { useAuthStore } from '@/stores/useAuthStore'
import { useDriverTripStore } from '@/stores/useDriverTripStore'
import { useDriverTrackingStore } from '@/stores/useDriverTrackingStore'
import { useFirebaseMessaging } from '@/composables/useFirebaseMessaging'
import { useNotificationStore } from '@/stores/useNotificationStore'
import api from '@/api/client'
import { resetTokenVerified } from '@/router'
import StudentLayout from '@/layouts/StudentLayout.vue'
import SplashScreen from '@/components/SplashScreen.vue'
import { isDriverTaskGuardAvailable, setDriverTaskGuardEnabled } from '@/utils/driverTaskGuard'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const appType = import.meta.env.VITE_APP_TYPE || 'driver'
const driverTripStore = appType === 'driver' ? useDriverTripStore() : null
const driverTrackingStore = appType === 'driver' ? useDriverTrackingStore() : null
const hasStartupSplash = appType === 'student' || appType === 'driver'
const MIN_SPLASH_DURATION_MS = 2500
const showStartupSplash = ref(hasStartupSplash)
const toasts = ref([])
const tripProtectionEnabled = ref(null)
let isFirstAuthCheck = true
let protectedBackButtonListener = null

const toastIcons = {
  success: 'bi-check-circle-fill',
  error: 'bi-x-circle-fill',
  warning: 'bi-exclamation-triangle-fill'
}

const dismissToast = (id) => {
  toasts.value = toasts.value.filter(toast => toast.id !== id)
}

const handleToast = (event) => {
  const message = event.detail?.message
  if (!message) return

  const id = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`
  const type = event.detail?.type || 'warning'
  const duration = Number(event.detail?.duration) || 3000

  toasts.value.push({ id, message, type })
  window.setTimeout(() => dismissToast(id), duration)
}

const layout = computed(() => {
  // Driver layout is already applied via router nested routes — avoid double-wrapping
  if (appType === 'driver') return 'div'
  if (route.meta.layout === null) return 'div'
  return StudentLayout
})

const canUseProtectedBackNavigation = appType === 'driver' && isDriverTaskGuardAvailable()

const shouldProtectDriverTrip = () => (
  appType === 'driver'
  && !!driverTripStore
  && authStore.isAuthenticated
  && authStore.user?.role === 'driver'
  && !!driverTripStore.currentTrip?.id
)

const releaseProtectedBackButtonListener = async () => {
  if (!protectedBackButtonListener) return

  await protectedBackButtonListener.remove()
  protectedBackButtonListener = null
}

const ensureProtectedBackButtonListener = async () => {
  if (!canUseProtectedBackNavigation || protectedBackButtonListener) return

  protectedBackButtonListener = await CapacitorApp.addListener('backButton', async () => {
    if (!shouldProtectDriverTrip()) {
      await releaseProtectedBackButtonListener()

      const currentRouteName = router.currentRoute.value.name
      if (currentRouteName && currentRouteName !== 'dashboard' && currentRouteName !== 'login') {
        router.back()
        return
      }

      await CapacitorApp.exitApp()
      return
    }

    if (router.currentRoute.value.name === 'trip-active') {
      return
    }

    await router.push({ name: 'trip-active' })
  })
}

const syncTripProtection = async () => {
  if (appType !== 'driver') return

  const shouldEnable = shouldProtectDriverTrip()

  if (tripProtectionEnabled.value !== shouldEnable) {
    tripProtectionEnabled.value = shouldEnable
    await setDriverTaskGuardEnabled({ enabled: shouldEnable })
  }

  if (shouldEnable) {
    await ensureProtectedBackButtonListener()
    return
  }

  await releaseProtectedBackButtonListener()
}

// Show splash on logout transition (not on first app load)
watch(
  () => authStore.isAuthenticated,
  (isAuthenticated) => {
    if (isFirstAuthCheck) {
      isFirstAuthCheck = false
      return
    }
    // User has logged out — re-enable splash for smooth transition
    if (!isAuthenticated && hasStartupSplash) {
      showStartupSplash.value = true
      const minDurationPromise = new Promise((resolve) => {
        setTimeout(resolve, MIN_SPLASH_DURATION_MS)
      })
      minDurationPromise.then(() => {
        showStartupSplash.value = false
      })
    }
  }
)

// Handle 401s from the API interceptor without a full page reload
const handleUnauthorized = async () => {
  await setDriverTaskGuardEnabled({ enabled: false })
  tripProtectionEnabled.value = false
  await releaseProtectedBackButtonListener()

  if (driverTrackingStore) {
    await driverTrackingStore.stop({ clearQueue: true, resetTrip: true })
  }

  authStore.token = null
  authStore.user = null
  localStorage.removeItem('auth_token')
  localStorage.removeItem('user')
  resetTokenVerified()
  if (route.name !== 'login') {
    router.push({ name: 'login' })
  }
}

const safelyStartDriverTracking = async (trip = null) => {
  if (!driverTrackingStore) return

  try {
    if (trip) {
      await driverTrackingStore.startForTrip(trip)
    } else {
      await driverTrackingStore.resumeIfTripActive()
    }
  } catch (error) {
    if (import.meta.env.DEV) {
      console.warn('Driver tracking is unavailable:', error?.message || error)
    }
  }
}

const syncDriverTracking = async () => {
  if (!driverTrackingStore || !driverTripStore) return

  if (!authStore.isAuthenticated || !authStore.isDriver) {
    await driverTrackingStore.stop({ clearQueue: true, resetTrip: true })
    return
  }

  await safelyStartDriverTracking()
}

watch(
  () => driverTripStore?.currentTrip?.id ?? null,
  (tripId, previousTripId) => {
    if (!driverTrackingStore || appType !== 'driver') return

    if (tripId && driverTripStore?.currentTrip) {
      void safelyStartDriverTracking(driverTripStore.currentTrip)
    } else if (previousTripId) {
      void driverTrackingStore.stop({ clearQueue: true, resetTrip: true })
    }

    void syncTripProtection()
  }
)

const { initialize: initFirebaseMessaging, deleteToken: deleteFcmToken } = useFirebaseMessaging()

const syncStudentNotifications = async () => {
  if (appType !== 'student') return

  if (!authStore.isAuthenticated) {
    try {
      await deleteFcmToken()
    } catch (e) {
      console.warn('Failed to delete FCM token on logout', e)
    }
    return
  }

  // Capacitor FCM init
  const result = await initFirebaseMessaging({
    topic: 'all_students',
    onMessage: () => {
      const notificationStore = useNotificationStore()
      notificationStore.incrementUnread()
    },
    onNotificationClick: () => {
      router.push({ name: 'map' })
    }
  })

  if (!result.success) {
    console.error('Student FCM initialization failed:', result.error || 'Unknown initialization error')
    return
  }

  if (!result.token) {
    console.error('Student FCM token generation failed: no token returned')
    return
  }

  try {
    await api.post('/student/fcm-token', { fcm_token: result.token })
    console.info('Student FCM token saved to backend successfully')
  } catch (e) {
    console.error('Failed to save student FCM token to backend', e)
  }
}

watch(
  () => [authStore.isAuthenticated, authStore.user?.role],
  async () => {
    if (appType === 'driver') {
      await syncDriverTracking()
      await syncTripProtection()
    } else if (appType === 'student') {
      void syncStudentNotifications()
    }
  }
)

onMounted(async () => {
  window.addEventListener('auth:unauthorized', handleUnauthorized)
  window.addEventListener('app:toast', handleToast)

  if (hasStartupSplash) {
    const minDurationPromise = new Promise((resolve) => {
      setTimeout(resolve, MIN_SPLASH_DURATION_MS)
    })

    await Promise.all([router.isReady(), minDurationPromise])
    showStartupSplash.value = false
  }

  if (appType === 'driver' && driverTrackingStore) {
    await driverTrackingStore.initialize()
    await syncDriverTracking()
    await syncTripProtection()
  } else if (appType === 'student' && authStore.isAuthenticated) {
    await syncStudentNotifications()
  }
})

onUnmounted(() => {
  window.removeEventListener('auth:unauthorized', handleUnauthorized)
  window.removeEventListener('app:toast', handleToast)
  void releaseProtectedBackButtonListener()
})
</script>

<template>
  <component :is="layout" :class="appType === 'student' && route.meta.layout === null ? 'auth-transition-bg' : undefined">
    <router-view v-slot="{ Component, route }">
      <template v-if="appType === 'driver'">
        <component :is="Component" />
      </template>
      <Transition v-else name="app-level" mode="out-in">
        <component :is="Component" :key="route.path" />
      </Transition>
    </router-view>

    <Transition name="startup-splash-fade">
      <SplashScreen
        v-if="hasStartupSplash && showStartupSplash"
      />
    </Transition>

    <div class="toast-container">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="toast-notification"
          :class="toast.type"
          @click="dismissToast(toast.id)"
        >
          <i :class="toastIcons[toast.type] || toastIcons.warning"></i>
          <span>{{ toast.message }}</span>
        </div>
      </TransitionGroup>
    </div>
  </component>
</template>

<style scoped>
.auth-transition-bg {
  position: fixed;
  inset: 0;
  background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%);
  z-index: 1;
}

.startup-splash-fade-enter-active,
.startup-splash-fade-leave-active {
  transition: opacity 0.35s ease;
}

.startup-splash-fade-enter-from,
.startup-splash-fade-leave-to {
  opacity: 0;
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s cubic-bezier(0.21, 1.02, 0.73, 1);
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(16px);
}
</style>
