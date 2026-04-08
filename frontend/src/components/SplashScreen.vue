<script setup>
import { useSettingsStore } from '@/stores/useSettingsStore'
import BusFrontIcon from '@/components/BusFrontIcon.vue'
import CarFrontIcon from '@/components/CarFrontIcon.vue'
import { getDefaultAppName, getDefaultAppTagline } from '@/utils/appBranding'

const settingsStore = useSettingsStore()
const appType = import.meta.env.VITE_APP_TYPE || 'student'
const defaultAppName = getDefaultAppName(appType)
const defaultAppTagline = getDefaultAppTagline(appType)
const splashIcon = appType === 'driver' ? CarFrontIcon : BusFrontIcon
</script>

<template>
  <div class="splash-screen">
    <div class="splash-content">
      <div class="splash-logo">
        <component :is="splashIcon" />
      </div>
      <h1 class="app-name">{{ settingsStore.appSettings.appName || defaultAppName }}</h1>
      <div class="splash-tagline">{{ settingsStore.appSettings.appTagline || defaultAppTagline }}</div>
      <div class="splash-spacer"></div>
      <div class="splash-loading">
        <div class="loading-spinner"></div>
        <div class="loading-text">Loading...</div>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
