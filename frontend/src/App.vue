<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { resetTokenVerified } from '@/router'
import StudentLayout from '@/layouts/StudentLayout.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const appType = import.meta.env.VITE_APP_TYPE || 'driver'

const layout = computed(() => {
  // Driver layout is already applied via router nested routes — avoid double-wrapping
  if (appType === 'driver') return 'div'
  if (route.meta.layout === null) return 'div'
  return StudentLayout
})

// Handle 401s from the API interceptor without a full page reload
const handleUnauthorized = () => {
  authStore.token = null
  authStore.user = null
  localStorage.removeItem('auth_token')
  localStorage.removeItem('user')
  resetTokenVerified()
  if (route.name !== 'login') {
    router.push({ name: 'login' })
  }
}

onMounted(() => window.addEventListener('auth:unauthorized', handleUnauthorized))
onUnmounted(() => window.removeEventListener('auth:unauthorized', handleUnauthorized))
</script>

<template>
  <component :is="layout">
    <router-view />
  </component>
</template>
