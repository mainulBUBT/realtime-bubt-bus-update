<script setup>
import { ref, onMounted } from 'vue'
import { useSettingsStore } from '@/stores/useSettingsStore'

const settingsStore = useSettingsStore()
const show = ref(true)

onMounted(() => {
  // Auto-hide splash screen after 2.5 seconds
  setTimeout(() => {
    show.value = false
  }, 2500)
})
</script>

<template>
  <Transition name="fade">
    <div v-if="show" class="splash-screen">
      <div class="splash-content">
        <div class="splash-logo">
          <i class="bi bi-bus-front-fill"></i>
        </div>
        <h1 class="app-name">{{ settingsStore.appSettings.appName || 'BUBT Bus Tracker' }}</h1>
        <div class="splash-tagline">{{ settingsStore.appSettings.appTagline || 'Your Campus Shuttle Companion' }}</div>
        <div class="splash-spacer"></div>
        <div class="splash-loading">
          <div class="loading-spinner"></div>
          <div class="loading-text">Loading...</div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
/* Fade transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
