import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api/client'

export const useSettingsStore = defineStore('settings', () => {
  const appSettings = ref({
    appName: '',
    appTagline: '',
    splashPrimaryColor: '',
    supportEmail: '',
    supportPhone: '',
    supportUrl: '',
    aboutText: ''
  })

  // Color generation utilities
  function hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
    return result ? {
      r: parseInt(result[1], 16),
      g: parseInt(result[2], 16),
      b: parseInt(result[3], 16)
    } : null
  }

  function rgbToHex(r, g, b) {
    return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)
  }

  function adjustBrightness(hex, percent) {
    const rgb = hexToRgb(hex)
    if (!rgb) return hex

    const adjust = (value) => {
      const adjusted = Math.round(value + (percent * 255))
      return Math.max(0, Math.min(255, adjusted))
    }

    return rgbToHex(
      adjust(rgb.r),
      adjust(rgb.g),
      adjust(rgb.b)
    )
  }

  function generateColorVariants(primaryColor) {
    return {
      primary: primaryColor,
      primaryDark: adjustBrightness(primaryColor, -0.2),
      primaryLight: adjustBrightness(primaryColor, 0.8),
      primary50: hexToRgb(primaryColor)
        ? `rgba(${hexToRgb(primaryColor).r}, ${hexToRgb(primaryColor).g}, ${hexToRgb(primaryColor).b}, 0.1)`
        : 'rgba(16, 185, 129, 0.1)'
    }
  }

  const loading = ref(false)
  const error = ref(null)
  const isReady = ref(false)

  async function fetchSettings(appType) {
    loading.value = true
    error.value = null

    try {
      const response = await api.get('/settings', {
        params: { app_type: appType }
      })

      // Map API response keys to store keys
      const data = response.data
      const prefix = appType === 'student' ? 'student' : 'driver'

      appSettings.value = {
        appName: data[`${prefix}_app_name`] || (appType === 'student' ? 'BUBT Bus Tracker' : 'BUBT Bus Tracker - Driver'),
        appTagline: data[`${prefix}_app_tagline`] || '',
        splashPrimaryColor: data[`${prefix}_splash_primary_color`] || (appType === 'student' ? '#4F46E5' : '#059669'),
        supportEmail: appType === 'student' ? (data.student_support_email || '') : '',
        supportPhone: appType === 'student' ? (data.student_support_phone || '') : '',
        supportUrl: appType === 'student' ? (data.student_support_url || '') : '',
        aboutText: appType === 'student' ? (data.student_about_text || '') : ''
      }

      // Apply CSS variables for splash colors
      applySplashColors()

      isReady.value = true
    } catch (err) {
      console.error('Failed to fetch settings:', err)
      error.value = err.message
      // Use default values on error
      appSettings.value = {
        appName: appType === 'student' ? 'BUBT Bus Tracker' : 'BUBT Bus Tracker - Driver',
        appTagline: appType === 'student' ? 'Your Campus Shuttle Companion' : 'Campus Shuttle Driver App',
        splashPrimaryColor: appType === 'student' ? '#4F46E5' : '#059669',
        supportEmail: '',
        supportPhone: '',
        supportUrl: '',
        aboutText: ''
      }
      // Apply default colors
      applySplashColors()
      isReady.value = true
    } finally {
      loading.value = false
    }
  }

  function applyAppColors() {
    if (typeof document !== 'undefined') {
      const variants = generateColorVariants(appSettings.value.splashPrimaryColor)
      const rgb = hexToRgb(appSettings.value.splashPrimaryColor)

      // Splash screen colors - uses primary and auto-generated dark variant
      document.documentElement.style.setProperty('--splash-primary', appSettings.value.splashPrimaryColor)
      document.documentElement.style.setProperty('--splash-secondary', variants.primaryDark)

      // Main app colors - applies to ALL components
      document.documentElement.style.setProperty('--primary', variants.primary)
      document.documentElement.style.setProperty('--primary-dark', variants.primaryDark)
      document.documentElement.style.setProperty('--primary-light', variants.primaryLight)
      document.documentElement.style.setProperty('--primary-50', variants.primary50)

      // RGB components for opacity variants in CSS: rgba(var(--primary-rgb), 0.2)
      if (rgb) {
        document.documentElement.style.setProperty('--primary-rgb', `${rgb.r}, ${rgb.g}, ${rgb.b}`)
      }
    }
  }

  // Backward compatibility wrapper
  function applySplashColors() {
    applyAppColors()
  }

  return {
    appSettings,
    loading,
    error,
    isReady,
    fetchSettings,
    applySplashColors,  // Keep for backward compatibility
    applyAppColors      // New function for clarity
  }
})
