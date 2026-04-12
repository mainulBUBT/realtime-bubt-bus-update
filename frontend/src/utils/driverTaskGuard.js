import { Capacitor, registerPlugin } from '@capacitor/core'

const DriverTaskGuard = registerPlugin('DriverTaskGuard')
const appType = import.meta.env.VITE_APP_TYPE || 'driver'

export function isDriverTaskGuardAvailable() {
  return appType === 'driver' && Capacitor.isNativePlatform() && Capacitor.getPlatform() === 'android'
}

export async function setDriverTaskGuardEnabled({ enabled }) {
  if (!isDriverTaskGuardAvailable()) {
    return {
      enabled: false,
      supported: false,
      applied: false
    }
  }

  try {
    return await DriverTaskGuard.setEnabled({ enabled })
  } catch (error) {
    if (import.meta.env.DEV) {
      console.warn('Driver task guard is unavailable:', error?.message || error)
    }

    return {
      enabled: false,
      supported: false,
      applied: false,
      error: error?.message || 'Driver task guard unavailable'
    }
  }
}

export async function getDriverTaskGuardStatus() {
  if (!isDriverTaskGuardAvailable()) {
    return {
      enabled: false,
      supported: false,
      applied: false
    }
  }

  try {
    return await DriverTaskGuard.getStatus()
  } catch (error) {
    if (import.meta.env.DEV) {
      console.warn('Failed to read driver task guard status:', error?.message || error)
    }

    return {
      enabled: false,
      supported: false,
      applied: false,
      error: error?.message || 'Unable to read driver task guard status'
    }
  }
}
