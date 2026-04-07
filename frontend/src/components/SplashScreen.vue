<script setup>
import { useSettingsStore } from '@/stores/useSettingsStore'
import BusFrontIcon from '@/components/BusFrontIcon.vue'

const settingsStore = useSettingsStore()
</script>

<template>
  <div class="splash-screen">
    <div class="splash-content">
      <div class="splash-logo">
        <BusFrontIcon />
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
</template>

<style scoped></style>
