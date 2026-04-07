<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { useDriverTripStore } from '@/stores/useDriverTripStore'
import { useDriverTrackingStore } from '@/stores/useDriverTrackingStore'
import { resetTokenVerified } from '@/router'
import StudentLayout from '@/layouts/StudentLayout.vue'
import SplashScreen from '@/components/SplashScreen.vue'
import { useFirebaseMessaging } from '@/composables/useFirebaseMessaging'
import api from '@/api/client'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const appType = import.meta.env.VITE_APP_TYPE || 'driver'
const driverTripStore = appType === 'driver' ? useDriverTripStore() : null
const driverTrackingStore = appType === 'driver' ? useDriverTrackingStore() : null
const hasStartupSplash = appType === 'student' || appType === 'driver'
const fcm = appType === 'student' ? useFirebaseMessaging() : null
const MIN_SPLASH_DURATION_MS = 2500
const showStartupSplash = ref(hasStartupSplash)
let isFirstAuthCheck = true

const layout = computed(() => {
  // Driver layout is already applied via router nested routes — avoid double-wrapping
  if (appType === 'driver') return 'div'
  if (route.meta.layout === null) return 'div'
  return StudentLayout
})

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
  () => [authStore.isAuthenticated, authStore.user?.role],
  () => {
    if (appType === 'driver') {
      void syncDriverTracking()
    } else if (appType === 'student' && authStore.isAuthenticated && authStore.isStudent) {
      void initStudentNotifications()
    }
  }
)

watch(
  () => driverTripStore?.currentTrip?.id ?? null,
  (tripId, previousTripId) => {
    if (!driverTrackingStore || appType !== 'driver') return

    if (tripId && driverTripStore?.currentTrip) {
      void safelyStartDriverTracking(driverTripStore.currentTrip)
      return
    }

    if (previousTripId) {
      void driverTrackingStore.stop({ clearQueue: true, resetTrip: true })
    }
  }
)

const initStudentNotifications = async () => {
  if (!fcm || !authStore.isAuthenticated || !authStore.isStudent) return

  try {
    const result = await fcm.initialize({
      topic: 'all_students',
      onMessage: (notification) => {
        console.log('Push notification received:', notification)
      },
      onNotificationClick: (notification) => {
        console.log('Notification clicked:', notification)
      }
    })

    if (result.success && result.token) {
      await api.post('/student/fcm-token', { fcm_token: result.token })
      console.log('FCM token registered')
    }
  } catch (err) {
    console.warn('FCM initialization failed:', err?.message || err)
  }
}

onMounted(async () => {
  window.addEventListener('auth:unauthorized', handleUnauthorized)

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
  }

  if (appType === 'student') {
    await initStudentNotifications()
  }
})

onUnmounted(() => {
  window.removeEventListener('auth:unauthorized', handleUnauthorized)
})
</script>

<template>
  <component :is="layout">
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
  </component>
</template>

<style scoped>
.startup-splash-fade-enter-active,
.startup-splash-fade-leave-active {
  transition: opacity 0.35s ease;
}

.startup-splash-fade-enter-from,
.startup-splash-fade-leave-to {
  opacity: 0;
}
</style>
