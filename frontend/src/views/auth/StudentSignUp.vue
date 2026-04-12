<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { useSettingsStore } from '@/stores/useSettingsStore'
import { showToast } from '@/composables/useToast'
import BusFrontIcon from '@/components/BusFrontIcon.vue'
import { getDefaultAppName } from '@/utils/appBranding'

const router = useRouter()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()
const appName = computed(() => settingsStore.appSettings.appName || getDefaultAppName('student'))

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  role: 'student'
})

const loading = ref(false)
const error = ref('')

const signup = async () => {
  loading.value = true
  error.value = ''

  try {
    await authStore.register(form.value)
    showToast('Account created! Please wait for admin approval.', { type: 'success', duration: 4000 })
    router.push({ name: 'login' })
  } catch (err) {
    const message = err.message || 'Registration failed'
    const errors = err.errors
    if (errors) {
      error.value = Object.values(errors).flat().join(' ')
    } else {
      error.value = message
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-screen login-screen--student">
    <button class="signup-back-btn" @click="router.back()">
      <i class="bi bi-arrow-left"></i>
    </button>
    <div class="login-card">
      <div class="login-logo">
        <BusFrontIcon />
      </div>
      <h1 class="login-title">{{ appName }}</h1>
      <p class="login-subtitle">Create an account to track your campus shuttle</p>

      <form @submit.prevent="signup">
        <div class="mb-3">
          <input
            v-model="form.name"
            type="text"
            class="form-control"
            placeholder="Full name"
            required
          />
        </div>

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
            v-model="form.phone"
            type="tel"
            class="form-control"
            placeholder="Phone number (optional)"
          />
        </div>

        <div class="mb-3">
          <input
            v-model="form.password"
            type="password"
            class="form-control"
            placeholder="Password (min 8 characters)"
            required
          />
        </div>

        <div class="mb-3">
          <input
            v-model="form.password_confirmation"
            type="password"
            class="form-control"
            placeholder="Confirm password"
            required
          />
        </div>

        <div v-if="error" class="alert alert-danger py-2 mb-3" role="alert">
          <small>{{ error }}</small>
        </div>

        <button type="submit" class="btn-login w-100" :disabled="loading">
          <span v-if="loading">
            <span class="spinner-border spinner-border-sm me-2"></span>
            Creating account...
          </span>
          <span v-else>Create Account</span>
        </button>
      </form>

      <p class="login-privacy">
        Already have an account? <router-link :to="{ name: 'login' }">Sign In</router-link>
      </p>
    </div>
  </div>
</template>

<style scoped>
.signup-back-btn {
  position: absolute;
  top: 16px;
  left: 16px;
  z-index: 10;
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.8);
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  backdrop-filter: blur(10px);
  transition: background 0.2s ease, color 0.2s ease;
}

.signup-back-btn:hover {
  background: rgba(255, 255, 255, 0.15);
  color: #fff;
}

.signup-back-btn:active {
  background: rgba(255, 255, 255, 0.2);
}
</style>
