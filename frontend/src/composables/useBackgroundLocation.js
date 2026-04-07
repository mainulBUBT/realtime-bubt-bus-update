import { ref } from 'vue'
import { Capacitor, registerPlugin } from '@capacitor/core'
import { Geolocation } from '@capacitor/geolocation'
import { LocalNotifications } from '@capacitor/local-notifications'

const BackgroundGeolocation = registerPlugin('BackgroundGeolocation')

const isTracking = ref(false)
const currentLocation = ref(createEmptyLocation())
const error = ref(null)
const permissionState = ref('prompt')
const provider = ref('inactive')

let geolocationWatchId = null
let backgroundWatcherId = null
const WEB_GEOLOCATION_OPTIONS = {
  enableHighAccuracy: true,
  timeout: 10000,
  maximumAge: 0
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

function normalizeLocation(payload) {
  if (!payload) return null

  if (payload.coords) {
    return {
      lat: payload.coords.latitude,
      lng: payload.coords.longitude,
      speed: payload.coords.speed,
      accuracy: payload.coords.accuracy,
      timestamp: payload.timestamp ?? Date.now()
    }
  }

  if (typeof payload.latitude === 'number' && typeof payload.longitude === 'number') {
    return {
      lat: payload.latitude,
      lng: payload.longitude,
      speed: payload.speed ?? null,
      accuracy: payload.accuracy ?? null,
      timestamp: payload.time ?? payload.timestamp ?? Date.now()
    }
  }

  return null
}

function isAndroidNative() {
  return Capacitor.isNativePlatform() && Capacitor.getPlatform() === 'android'
}

function isWebPlatform() {
  return !Capacitor.isNativePlatform()
}

function canUseBackgroundPlugin() {
  return isAndroidNative() && Capacitor.isPluginAvailable('BackgroundGeolocation')
}

function createLocationError(message, code = 'LOCATION_ERROR', cause = null) {
  const err = new Error(message)
  err.code = code
  err.cause = cause
  return err
}

function createBrowserLocationError(err) {
  if (err?.code === 1) {
    permissionState.value = 'denied'
    return createLocationError('Location permission denied. Please allow location access and try again.', 'PERMISSION_DENIED', err)
  }

  if (err?.code === 2) {
    return createLocationError('Location information is unavailable right now. Please try again.', 'POSITION_UNAVAILABLE', err)
  }

  if (err?.code === 3) {
    return createLocationError('Location request timed out. Please try again.', 'TIMEOUT', err)
  }

  return createLocationError('Unable to get your browser location. Please try again.', 'LOCATION_ERROR', err)
}

function createNativePluginLocationError(err) {
  const code = String(err?.code || '').toUpperCase()

  if (code === 'NOT_AUTHORIZED' || code === 'NOT_AUTHORIZED_ALWAYS') {
    permissionState.value = 'denied'
    return createLocationError(
      'Location permission is not granted. Enable Location (Allow all the time) and allow Notifications so the tracking notification can run in background.',
      'PERMISSION_DENIED',
      err
    )
  }

  if (code === 'NOT_ENABLED') {
    return createLocationError(
      'Location is turned off. Please enable GPS/Location services and try again.',
      'POSITION_UNAVAILABLE',
      err
    )
  }

  return createLocationError(err?.message || 'Location tracking error. Please try again.', 'LOCATION_ERROR', err)
}

async function readWebPermissionState() {
  if (typeof navigator === 'undefined' || !navigator.geolocation) {
    return 'unsupported'
  }

  if (!navigator.permissions?.query) {
    return permissionState.value === 'denied' ? 'denied' : 'prompt'
  }

  try {
    const status = await navigator.permissions.query({ name: 'geolocation' })
    return ['granted', 'denied', 'prompt'].includes(status.state) ? status.state : 'prompt'
  } catch {
    return permissionState.value === 'denied' ? 'denied' : 'prompt'
  }
}

function getBrowserPosition() {
  return new Promise((resolve, reject) => {
    if (typeof navigator === 'undefined' || !navigator.geolocation) {
      reject(createLocationError('Browser geolocation is not supported in this browser.', 'UNSUPPORTED'))
      return
    }

    navigator.geolocation.getCurrentPosition(resolve, reject, WEB_GEOLOCATION_OPTIONS)
  })
}

async function requestNotificationPermission() {
  if (!isAndroidNative()) return

  try {
    await LocalNotifications.requestPermissions()
  } catch (err) {
    console.warn('Notification permission request failed:', err)
  }
}

async function ensureLocationPermission({ request = false, onLocation = null } = {}) {
  if (isWebPlatform()) {
    const browserPermission = await readWebPermissionState()

    if (browserPermission === 'unsupported') {
      const unsupportedError = createLocationError('Browser geolocation is not supported in this browser.', 'UNSUPPORTED')
      error.value = unsupportedError
      throw unsupportedError
    }

    permissionState.value = browserPermission

    if (browserPermission === 'granted') {
      return browserPermission
    }

    if (browserPermission === 'denied') {
      const deniedError = createLocationError('Location permission denied. Please allow location access and try again.', 'PERMISSION_DENIED')
      error.value = deniedError
      throw deniedError
    }

    if (!request) {
      return browserPermission
    }

    try {
      const position = await getBrowserPosition()
      const normalized = normalizeLocation(position)

      permissionState.value = 'granted'

      if (normalized) {
        currentLocation.value = normalized

        if (onLocation && typeof onLocation === 'function') {
          onLocation(normalized)
        }
      }

      return 'granted'
    } catch (err) {
      const browserError = err?.code ? createBrowserLocationError(err) : err
      error.value = browserError
      throw browserError
    }
  }

  try {
    const permissions = await Geolocation.checkPermissions()
    permissionState.value = permissions.location ?? 'prompt'

    if (permissionState.value === 'granted' || permissionState.value === 'limited') {
      await requestNotificationPermission()
      return permissionState.value
    }

    const requested = await Geolocation.requestPermissions()
    permissionState.value = requested.location ?? permissionState.value
    await requestNotificationPermission()
    return permissionState.value
  } catch (err) {
    error.value = err
    permissionState.value = 'denied'
    throw err
  }
}

function clearWebWatch() {
  if (typeof navigator === 'undefined' || !navigator.geolocation || geolocationWatchId === null) {
    return
  }

  navigator.geolocation.clearWatch(geolocationWatchId)
  geolocationWatchId = null
}

async function startWebTracking(onLocation = null) {
  if (typeof navigator === 'undefined' || !navigator.geolocation) {
    throw createLocationError('Browser geolocation is not supported in this browser.', 'UNSUPPORTED')
  }

  geolocationWatchId = navigator.geolocation.watchPosition(
    (position) => {
      error.value = null
      permissionState.value = 'granted'

      const normalized = normalizeLocation(position)
      if (!normalized) return

      currentLocation.value = normalized

      if (onLocation && typeof onLocation === 'function') {
        onLocation(normalized)
      }
    },
    (watchError) => {
      const browserError = createBrowserLocationError(watchError)
      error.value = browserError

      if (browserError.code === 'PERMISSION_DENIED') {
        clearWebWatch()
        isTracking.value = false
        provider.value = 'inactive'
      }
    },
    WEB_GEOLOCATION_OPTIONS
  )

  provider.value = 'web-geolocation'
  isTracking.value = true
}

async function startNativeForegroundTracking(onLocation = null) {
  geolocationWatchId = await Geolocation.watchPosition(
    WEB_GEOLOCATION_OPTIONS,
    (position, err) => {
      if (err) {
        error.value = err
        console.error('Geolocation error:', err)
        return
      }

      const normalized = normalizeLocation(position)
      if (!normalized) return

      currentLocation.value = normalized

      if (onLocation && typeof onLocation === 'function') {
        onLocation(normalized)
      }
    }
  )

  provider.value = 'capacitor-geolocation'
  isTracking.value = true
}

async function startNativeBackgroundTracking(onLocation = null) {
  backgroundWatcherId = await BackgroundGeolocation.addWatcher(
    {
      backgroundTitle: 'BUBT Driver Tracking Active',
      backgroundMessage: 'Location tracking continues during your ongoing trip.',
      requestPermissions: false,
      stale: false,
      distanceFilter: 15
    },
    (location, err) => {
      if (err) {
        const normalizedError = createNativePluginLocationError(err)
        error.value = normalizedError
        console.error('Background geolocation error:', err)

        if (normalizedError.code === 'PERMISSION_DENIED') {
          const watcherId = backgroundWatcherId
          backgroundWatcherId = null
          isTracking.value = false
          provider.value = 'inactive'

          if (watcherId !== null) {
            void BackgroundGeolocation.removeWatcher({ id: watcherId }).catch((removeErr) => {
              console.warn('Failed to remove background watcher after permission error:', removeErr)
            })
          }
        }
        return
      }

      const normalized = normalizeLocation(location)
      if (!normalized) return

      currentLocation.value = normalized

      if (onLocation && typeof onLocation === 'function') {
        onLocation(normalized)
      }
    }
  )

  provider.value = 'android-background'
  isTracking.value = true
}

export function useBackgroundLocation() {
  const startTracking = async (onLocation = null) => {
    try {
      error.value = null
      await stopTracking()

      const permission = await ensureLocationPermission({ request: true, onLocation })
      if (permission !== 'granted' && permission !== 'limited') {
        throw new Error('Location permission not granted')
      }

      if (canUseBackgroundPlugin()) {
        try {
          await startNativeBackgroundTracking(onLocation)
          return provider.value
        } catch (err) {
          console.warn('Falling back to foreground geolocation watch:', err)
        }
      }

      if (isWebPlatform()) {
        await startWebTracking(onLocation)
      } else {
        await startNativeForegroundTracking(onLocation)
      }

      return provider.value
    } catch (err) {
      error.value = err
      isTracking.value = false
      provider.value = 'inactive'
      throw err
    }
  }

  const stopTracking = async () => {
    try {
      if (backgroundWatcherId !== null) {
        await BackgroundGeolocation.removeWatcher({ id: backgroundWatcherId })
        backgroundWatcherId = null
      }

      if (geolocationWatchId !== null) {
        if (isWebPlatform()) {
          clearWebWatch()
        } else {
          await Geolocation.clearWatch({ id: geolocationWatchId })
          geolocationWatchId = null
        }
      }

      isTracking.value = false
      provider.value = 'inactive'
    } catch (err) {
      error.value = err
      console.error('Failed to stop tracking:', err)
    }
  }

  const getCurrentPosition = async () => {
    try {
      const permission = await ensureLocationPermission({ request: true })
      if (permission !== 'granted' && permission !== 'limited') {
        throw new Error('Location permission not granted')
      }

      const position = isWebPlatform()
        ? await getBrowserPosition()
        : await Geolocation.getCurrentPosition(WEB_GEOLOCATION_OPTIONS)

      const normalized = normalizeLocation(position) ?? createEmptyLocation()
      currentLocation.value = normalized
      return normalized
    } catch (err) {
      error.value = err
      console.error('Failed to get current position:', err)
      throw err
    }
  }

  return {
    isTracking,
    currentLocation,
    error,
    permissionState,
    provider,
    startTracking,
    stopTracking,
    getCurrentPosition,
    ensureLocationPermission
  }
}
