import { ref } from 'vue'
import { Geolocation } from '@capacitor/geolocation'

export function useBackgroundLocation() {
  const isTracking = ref(false)
  const currentLocation = ref({ lat: 0, lng: 0 })
  const error = ref(null)
  let watchId = null

  /**
   * Start location tracking
   * On mobile: continues while app is minimized (OS restrictions apply)
   * On web: continues while tab is active
   * @param {Function} onLocation - Callback when location is updated
   */
  const startTracking = async (onLocation = null) => {
    try {
      // Clear any existing watch
      if (watchId !== null) {
        await Geolocation.clearWatch({ id: watchId })
      }

      watchId = await Geolocation.watchPosition(
        {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 0
        },
        (position, err) => {
          if (err) {
            error.value = err
            console.error('Geolocation error:', err)
            return
          }

          if (position) {
            currentLocation.value = {
              lat: position.coords.latitude,
              lng: position.coords.longitude,
              speed: position.coords.speed,
              accuracy: position.coords.accuracy,
              timestamp: position.timestamp
            }

            // Call callback if provided
            if (onLocation && typeof onLocation === 'function') {
              onLocation(currentLocation.value)
            }
          }
        }
      )

      isTracking.value = true
    } catch (err) {
      error.value = err
      console.error('Failed to start tracking:', err)
      throw err
    }
  }

  /**
   * Stop location tracking
   */
  const stopTracking = async () => {
    try {
      if (watchId !== null) {
        await Geolocation.clearWatch({ id: watchId })
        watchId = null
      }
      isTracking.value = false
    } catch (err) {
      error.value = err
      console.error('Failed to stop tracking:', err)
    }
  }

  /**
   * Get current position once
   */
  const getCurrentPosition = async () => {
    try {
      const position = await Geolocation.getCurrentPosition({
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
      })

      return {
        lat: position.coords.latitude,
        lng: position.coords.longitude,
        speed: position.coords.speed,
        accuracy: position.coords.accuracy,
        timestamp: position.timestamp
      }
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
    startTracking,
    stopTracking,
    getCurrentPosition
  }
}
