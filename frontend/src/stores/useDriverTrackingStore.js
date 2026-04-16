import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { App } from '@capacitor/app'
import { Capacitor, CapacitorHttp } from '@capacitor/core'
import api, { buildApiHeaders, dispatchUnauthorized, resolveApiUrl } from '@/api/client'
import { useBackgroundLocation } from '@/composables/useBackgroundLocation'
import { useAuthStore } from '@/stores/useAuthStore'
import { useDriverTripStore } from '@/stores/useDriverTripStore'

const STORAGE_KEY = 'driver_tracking_state'
const SEND_INTERVAL_MS = 5000
const MAX_BATCH_SIZE = 25
const MAX_QUEUE_SIZE = 200

// Location filtering (reduce GPS drift while stopped)
const MAX_ACCEPTABLE_ACCURACY_M = 50
const MIN_DISTANCE_M = 15
const MIN_TIME_BETWEEN_POINTS_MS = 5000
const MAX_JUMP_M = 250
const MAX_IMPLIED_SPEED_MPS = 40 // ~144 km/h
const GOOD_ACCURACY_FOR_JUMP_M = 20

function toRadians(deg) {
  return (deg * Math.PI) / 180
}

function haversineDistanceMeters(a, b) {
  if (!a || !b) return 0
  const R = 6371000
  const dLat = toRadians(b.lat - a.lat)
  const dLng = toRadians(b.lng - a.lng)
  const lat1 = toRadians(a.lat)
  const lat2 = toRadians(b.lat)

  const sinDLat = Math.sin(dLat / 2)
  const sinDLng = Math.sin(dLng / 2)
  const h = (sinDLat * sinDLat) + (Math.cos(lat1) * Math.cos(lat2) * sinDLng * sinDLng)
  return 2 * R * Math.asin(Math.min(1, Math.sqrt(h)))
}

function parseTimestampMs(value) {
  if (value == null) return null
  if (typeof value === 'number' && Number.isFinite(value)) return value
  if (typeof value === 'string') {
    const ms = Date.parse(value)
    return Number.isFinite(ms) ? ms : null
  }
  return null
}

function createEmptyLocation() {
  return {
    lat: 0,
    lng: 0,
    speed: null,
    accuracy: null,
    timestamp: null
  }
}

function normalizeQueuedLocation(location) {
  if (!location) return null

  const lat = Number(location.lat)
  const lng = Number(location.lng)

  if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
    return null
  }

  return {
    lat,
    lng,
    speed: location.speed == null ? null : Number(location.speed),
    accuracy: location.accuracy == null ? null : Number(location.accuracy),
    recorded_at: location.recorded_at || new Date(location.timestamp || Date.now()).toISOString()
  }
}

function toDedupeKey(tripId, location) {
  return [
    tripId,
    Number(location.lat).toFixed(6),
    Number(location.lng).toFixed(6),
    location.recorded_at
  ].join(':')
}

function createHttpError(status, data) {
  const error = new Error(data?.message || `HTTP ${status}`)
  error.response = { status, data }
  return error
}

export const useDriverTrackingStore = defineStore('driverTracking', () => {
  const {
    isTracking,
    currentLocation,
    error: providerError,
    permissionState,
    provider,
    startTracking,
    stopTracking
  } = useBackgroundLocation()

  const initialized = ref(false)
  const bootstrapping = ref(false)
  const activeTripId = ref(null)
  const queue = ref([])
  const lastSentAt = ref(null)
  const lastError = ref(null)
  const persistedLastLocation = ref(createEmptyLocation())
  const syncInProgress = ref(false)
  const status = ref('idle')
  const isOnline = ref(typeof navigator === 'undefined' ? true : navigator.onLine)
  const appState = ref(document.visibilityState === 'visible' ? 'active' : 'background')
  const lastFlushAttemptAt = ref(0)
  const ignoredCounts = ref({
    total: 0,
    accuracy: 0,
    distance: 0,
    jump: 0
  })
  const recentAccuracyRejects = ref(0)

  let flushTimerId = null
  let appStateListener = null
  let onlineHandler = null
  let offlineHandler = null
  let visibilityHandler = null

  const queueSize = computed(() => queue.value.length)
  const hasQueuedLocations = computed(() => queueSize.value > 0)
  const lastAcceptedLocation = computed(() => {
    const latestQueued = queue.value[queue.value.length - 1]
    if (!latestQueued) return persistedLastLocation.value

    return {
      lat: latestQueued.lat,
      lng: latestQueued.lng,
      speed: latestQueued.speed,
      accuracy: latestQueued.accuracy,
      timestamp: latestQueued.recorded_at
    }
  })
  const lastKnownLocation = computed(() => {
    if (lastAcceptedLocation.value.timestamp ||
      lastAcceptedLocation.value.lat !== 0 ||
      lastAcceptedLocation.value.lng !== 0) {
      return lastAcceptedLocation.value
    }

    return createEmptyLocation()
  })
  const isAndroidNative = computed(() => Capacitor.isNativePlatform() && Capacitor.getPlatform() === 'android')

  function persistState() {
    if (!activeTripId.value && queue.value.length === 0 && !lastSentAt.value) {
      localStorage.removeItem(STORAGE_KEY)
      return
    }

    localStorage.setItem(STORAGE_KEY, JSON.stringify({
      activeTripId: activeTripId.value,
      queue: queue.value,
      lastSentAt: lastSentAt.value
    }))
  }

  function hydrateState() {
    try {
      const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null')
      if (!saved || typeof saved !== 'object') return

      activeTripId.value = typeof saved.activeTripId === 'number' ? saved.activeTripId : null
      queue.value = Array.isArray(saved.queue)
        ? saved.queue.map(normalizeQueuedLocation).filter(Boolean)
        : []
      lastSentAt.value = saved.lastSentAt || null
    } catch (err) {
      console.warn('Failed to restore driver tracking state:', err)
      localStorage.removeItem(STORAGE_KEY)
    }
  }

  function clearPersistedState() {
    activeTripId.value = null
    queue.value = []
    lastSentAt.value = null
    lastError.value = null
    status.value = 'idle'
    persistedLastLocation.value = createEmptyLocation()
    persistState()
  }

  function setStatus(nextStatus) {
    status.value = nextStatus
  }

  function startFlushLoop() {
    if (flushTimerId !== null) return

    flushTimerId = window.setInterval(() => {
      flushQueue()
    }, SEND_INTERVAL_MS)
  }

  function stopFlushLoop() {
    if (flushTimerId === null) return
    window.clearInterval(flushTimerId)
    flushTimerId = null
  }

  function handleLocationUpdate(location) {
    if (!activeTripId.value) return

    const normalized = normalizeQueuedLocation(location)
    if (!normalized) return

    const candidateTimeMs = parseTimestampMs(location?.timestamp) ?? Date.now()
    const candidateAccuracy = normalized.accuracy == null ? null : Number(normalized.accuracy)

    const isBootstrapMode = bootstrapping.value && queue.value.length === 0
    const canAcceptPoorAccuracy = isBootstrapMode && candidateAccuracy != null && candidateAccuracy <= 200

    if (candidateAccuracy != null && Number.isFinite(candidateAccuracy) && candidateAccuracy > MAX_ACCEPTABLE_ACCURACY_M && !canAcceptPoorAccuracy) {
      if (isBootstrapMode) {
        recentAccuracyRejects.value += 1
      }
      ignoredCounts.value.total += 1
      ignoredCounts.value.accuracy += 1
      return
    }

    if (isBootstrapMode) {
      recentAccuracyRejects.value = 0
      bootstrapping.value = false
    }

    const previousQueued = queue.value[queue.value.length - 1]
    if (previousQueued) {
      const prevTimeMs = parseTimestampMs(previousQueued.recorded_at)
      const timeDeltaMs = prevTimeMs == null ? null : Math.max(0, candidateTimeMs - prevTimeMs)
      const distanceM = haversineDistanceMeters(previousQueued, normalized)

      if (timeDeltaMs != null && distanceM < MIN_DISTANCE_M && timeDeltaMs < MIN_TIME_BETWEEN_POINTS_MS) {
        ignoredCounts.value.total += 1
        ignoredCounts.value.distance += 1
        return
      }

      if (timeDeltaMs != null && timeDeltaMs > 0 && timeDeltaMs < 30000 && distanceM > MAX_JUMP_M) {
        const impliedSpeed = distanceM / (timeDeltaMs / 1000)
        const isVeryAccurate = candidateAccuracy != null && Number.isFinite(candidateAccuracy) && candidateAccuracy <= GOOD_ACCURACY_FOR_JUMP_M

        if (!isVeryAccurate && impliedSpeed > MAX_IMPLIED_SPEED_MPS) {
          ignoredCounts.value.total += 1
          ignoredCounts.value.jump += 1
          return
        }
      }
    }

    const previous = queue.value[queue.value.length - 1]
    if (previous && toDedupeKey(activeTripId.value, previous) === toDedupeKey(activeTripId.value, normalized)) {
      return
    }

    queue.value.push(normalized)

    persistedLastLocation.value = {
      lat: normalized.lat,
      lng: normalized.lng,
      speed: normalized.speed,
      accuracy: normalized.accuracy,
      timestamp: normalized.recorded_at
    }

    if (queue.value.length > MAX_QUEUE_SIZE) {
      queue.value = queue.value.slice(queue.value.length - MAX_QUEUE_SIZE)
    }

    lastError.value = null
    setStatus('tracking')
    persistState()

    const now = Date.now()
    if ((now - lastFlushAttemptAt.value) >= SEND_INTERVAL_MS) {
      void flushQueue()
    }
  }

  async function sendRequest(path, payload) {
    if (Capacitor.isNativePlatform()) {
      const response = await CapacitorHttp.post({
        url: resolveApiUrl(path),
        headers: buildApiHeaders(),
        data: payload,
        connectTimeout: 15000,
        readTimeout: 15000
      })

      if (response.status >= 400) {
        if (response.status === 401) {
          dispatchUnauthorized()
        }

        throw createHttpError(response.status, response.data)
      }

      return response.data
    }

    const response = await api.post(path, payload)
    return response.data
  }

  async function flushQueue() {
    if (!activeTripId.value || queue.value.length === 0 || syncInProgress.value) {
      return
    }

    if (!isOnline.value) {
      setStatus(isTracking.value ? 'offline' : status.value)
      return
    }

    syncInProgress.value = true
    lastFlushAttemptAt.value = Date.now()
    setStatus('syncing')

    try {
      while (queue.value.length > 0) {
        const batch = queue.value.slice(0, MAX_BATCH_SIZE)

        if (batch.length === 1) {
          await sendRequest('/driver/location', {
            trip_id: activeTripId.value,
            ...batch[0]
          })
        } else {
          await sendRequest('/driver/location/batch', {
            trip_id: activeTripId.value,
            locations: batch
          })
        }

        queue.value = queue.value.slice(batch.length)
        lastSentAt.value = new Date().toISOString()
        persistState()
      }

      setStatus(isTracking.value ? 'tracking' : 'idle')
    } catch (err) {
      lastError.value = err?.response?.data?.message || err.message || 'Failed to sync locations'

      if (err?.response?.status === 401) {
        await stop({ clearQueue: true, resetTrip: true })
        dispatchUnauthorized()
      } else if (err?.response?.status === 400 || err?.response?.status === 403) {
        await stop({ clearQueue: true, resetTrip: true })
      } else {
        setStatus(isTracking.value ? 'offline' : 'error')
      }
    } finally {
      syncInProgress.value = false
      persistState()
    }
  }

  async function initialize() {
    if (initialized.value) return

    hydrateState()

    onlineHandler = () => {
      isOnline.value = true
      void flushQueue()
    }

    offlineHandler = () => {
      isOnline.value = false
      if (isTracking.value) {
        setStatus('offline')
      }
    }

    visibilityHandler = () => {
      appState.value = document.visibilityState === 'visible' ? 'active' : 'background'
      if (document.visibilityState === 'visible') {
        void flushQueue()
      }
    }

    window.addEventListener('online', onlineHandler)
    window.addEventListener('offline', offlineHandler)
    document.addEventListener('visibilitychange', visibilityHandler)

    if (Capacitor.isNativePlatform()) {
      appStateListener = await App.addListener('appStateChange', ({ isActive }) => {
        appState.value = isActive ? 'active' : 'background'
        if (isActive) {
          void flushQueue()
        }
      })
    }

    startFlushLoop()
    initialized.value = true
  }

  async function startForTrip(trip) {
    await initialize()

    const tripId = typeof trip === 'number' ? trip : trip?.id
    if (!tripId) return

    if (activeTripId.value === tripId) {
      if (isTracking.value) {
        await flushQueue()
      }

      if (status.value === 'starting') {
        return
      }

      if (isTracking.value) {
        return
      }

      lastError.value = null
    }

    if (activeTripId.value && activeTripId.value !== tripId) {
      queue.value = []
      lastSentAt.value = null
    }

    activeTripId.value = tripId
    bootstrapping.value = true
    recentAccuracyRejects.value = 0

    if (status.value !== 'starting') {
      lastError.value = null
      setStatus('starting')
    }

    try {
      await startTracking(handleLocationUpdate)
      persistState()
      if (queue.value.length > 0) {
        await flushQueue()
      }
      return
    } catch (err) {
      lastError.value = err.message || 'Failed to start driver tracking'
      setStatus('error')
      persistState()
      throw err
    }
  }

  async function resumeIfTripActive() {
    await initialize()

    if (bootstrapping.value) return
    bootstrapping.value = true

    const authStore = useAuthStore()
    const driverTripStore = useDriverTripStore()

    try {
      if (!authStore.isAuthenticated || !authStore.isDriver) {
        await stop({ clearQueue: true, resetTrip: true })
        return
      }

      const trip = driverTripStore.currentTrip || await driverTripStore.fetchCurrentTrip({ force: true })

      if (!trip?.id) {
        await stop({ clearQueue: true, resetTrip: true })
        return
      }

      await startForTrip(trip)
    } finally {
      bootstrapping.value = false
    }
  }

  async function stop({ clearQueue = true, resetTrip = true } = {}) {
    await stopTracking()

    if (clearQueue) {
      queue.value = []
      lastSentAt.value = null
      persistedLastLocation.value = createEmptyLocation()
    }

    if (resetTrip) {
      activeTripId.value = null
    }

    lastError.value = null
    setStatus(clearQueue ? 'idle' : 'paused')
    persistState()
  }

  return {
    initialized,
    bootstrapping,
    activeTripId,
    queue,
    queueSize,
    hasQueuedLocations,
    lastAcceptedLocation,
    lastKnownLocation,
    lastSentAt,
    lastError,
    syncInProgress,
    status,
    isOnline,
    appState,
    isAndroidNative,
    ignoredCounts,
    isTracking,
    currentLocation,
    providerError,
    permissionState,
    provider,
    initialize,
    resumeIfTripActive,
    startForTrip,
    stop,
    flushQueue,
    getStatus: () => ({
      activeTripId: activeTripId.value,
      tracking: isTracking.value,
      provider: provider.value,
      permissionState: permissionState.value,
      queueSize: queue.value.length,
      lastSentAt: lastSentAt.value,
      status: status.value,
      ignoredCounts: ignoredCounts.value
    }),
    clearPersistedState
  }
})
