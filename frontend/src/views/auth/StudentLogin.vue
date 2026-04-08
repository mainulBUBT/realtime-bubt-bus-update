<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { useSettingsStore } from '@/stores/useSettingsStore'
import BusFrontIcon from '@/components/BusFrontIcon.vue'
import { getDefaultAppName } from '@/utils/appBranding'

const router = useRouter()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()
const appName = computed(() => settingsStore.appSettings.appName || getDefaultAppName('student'))

const form = ref({
  email: '',
  password: '',
  role: 'student'
})

const loading = ref(false)
const error = ref('')

const login = async () => {
  loading.value = true
  error.value = ''

  try {
    await authStore.login(form.value)
    router.push({ name: 'map' })
  } catch (err) {
    error.value = err.message || 'Login failed'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-screen">
    <div class="login-card">
      <div class="login-logo">
        <BusFrontIcon />
      </div>
      <h1 class="login-title">{{ appName }}</h1>
      <p class="login-subtitle">Sign in to track your campus shuttle in real-time</p>

      <div class="login-features">
        <div class="login-feature">
          <i class="bi bi-geo-alt-fill"></i>
          <span>Real-time tracking</span>
        </div>
        <div class="login-feature">
          <i class="bi bi-bell-fill"></i>
          <span>Notifications</span>
        </div>
        <div class="login-feature">
          <i class="bi bi-clock-fill"></i>
          <span>Schedules</span>
        </div>
      </div>

      <form @submit.prevent="login">
        <div class="mb-3">
          <input
            v-model="form.email"
            type="email"
            class="form-control"
            placeholder="Email address"
            required
          />
        </div>

        <div class="mb-3">
          <input
            v-model="form.password"
            type="password"
            class="form-control"
            placeholder="Password"
            required
          />
        </div>

        <div v-if="error" class="alert alert-danger py-2 mb-3" role="alert">
          <small>{{ error }}</small>
        </div>

        <button type="submit" class="btn-login w-100" :disabled="loading">
          <span v-if="loading">
            <span class="spinner-border spinner-border-sm me-2"></span>
            Signing in...
          </span>
          <span v-else>Sign In</span>
        </button>
      </form>

      <div class="login-privacy">
        <i class="bi bi-shield-check"></i>
        <span>For BUBT Students & Staff</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* No additional styles needed - all from main.css */
</style>
