import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/useAuthStore'
import { useSettingsStore } from './stores/useSettingsStore'
import { getDefaultAppPrimaryColor } from './utils/appBranding'
import { applySystemBarTheme } from './utils/systemTheme'
import './assets/main.css'

const appType = import.meta.env.VITE_APP_TYPE || 'driver'
const isDev = import.meta.env.DEV

document.body.dataset.appType = appType
void applySystemBarTheme(getDefaultAppPrimaryColor(appType))

// Development mode logging
if (isDev) {
  console.log('🚀 Initializing Vue app...')
  console.log('📱 App Type:', appType)
  console.log('🔧 Development Mode: ON')
}

const app = createApp(App)
const pinia = createPinia()

// Add global error handler
app.config.errorHandler = (err, instance, info) => {
  console.error('❌ Vue Error:', err)
  console.error('Component:', instance?.$options?.name || 'Unknown')
  console.error('Error Info:', info)

  // Show user-friendly error message in development
  if (isDev) {
    const errorDiv = document.createElement('div')
    errorDiv.style.cssText = 'position:fixed;top:10px;left:10px;right:10px;background:#fee;padding:15px;border-radius:8px;border:2px solid #f88;font-family:sans-serif;z-index:99999;'
    errorDiv.innerHTML = `
      <strong style="color:#c33;">Vue Error:</strong><br>
      <pre style="margin:5px 0;white-space:pre-wrap;word-break:break-word;font-size:12px;">${err.message}</pre>
      ${info ? `<small style="color:#666;">${info}</small>` : ''}
    `
    document.body.appendChild(errorDiv)
  }
}

app.use(pinia)
app.use(router)

// Load user from storage before mounting
const authStore = useAuthStore()
authStore.loadUserFromStorage()

const settingsStore = useSettingsStore()
const usedCachedBranding = settingsStore.applyCachedBranding(appType)

if (!usedCachedBranding) {
  settingsStore.applyDefaultBranding(appType)
}

// Fetch settings before mounting
settingsStore.fetchSettings(appType).then(() => {
  if (isDev) {
    console.log('⚙️ Settings loaded:', settingsStore.appSettings)
    console.log('👤 Auth State:', {
      isAuthenticated: authStore.isAuthenticated,
      user: authStore.user,
      hasToken: !!authStore.token
    })
  }
}).catch((err) => {
  console.error('❌ Failed to load settings:', err)
  // Continue mounting even if settings fail
}).finally(() => {
  // Mount app with error handling
  try {
    app.mount('#app')
    if (isDev) {
      console.log('✅ Vue app mounted successfully!')
      console.log('📍 Current Route:', window.location.pathname)
    }
  } catch (err) {
    console.error('❌ Failed to mount Vue app:', err)
    document.getElementById('app').innerHTML = `
      <div style="display:flex;align-items:center;justify-content:center;height:100vh;font-family:sans-serif;text-align:center;padding:20px;">
        <div>
          <h2 style="color:#c33;">Failed to load application</h2>
          <p style="color:#666;">Please check the console for details.</p>
          <p style="font-size:12px;color:#999;">${err.message}</p>
        </div>
      </div>
    `
  }
})

// Log route changes in development
if (isDev) {
  router.afterEach((to) => {
    console.log('🔄 Route changed:', to.name, to.path)
  })
}
