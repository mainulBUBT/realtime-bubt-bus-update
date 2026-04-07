<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSettingsStore } from '@/stores/useSettingsStore'

const router = useRouter()
const settingsStore = useSettingsStore()

const loading = ref(true)

const appName = computed(() => settingsStore.appSettings.appName || 'BUBT Bus Tracker')
const appTagline = computed(() => settingsStore.appSettings.appTagline || 'University Shuttle Service')
const aboutText = computed(() => (settingsStore.appSettings.aboutText || '').trim())
const hasAbout = computed(() => aboutText.value.length > 0)

onMounted(async () => {
  if (!settingsStore.isReady) {
    const appType = import.meta.env.VITE_APP_TYPE || 'student'
    await settingsStore.fetchSettings(appType)
  }
  loading.value = false
})
</script>

<template>
  <div class="sub-page">
    <div class="sub-page-header">
      <div class="header-left">
        <button class="back-btn" @click="router.back()">
          <i class="bi bi-arrow-left"></i>
        </button>
        <div>
          <h1 class="header-title">About</h1>
          <span class="header-subtitle">{{ appName }}</span>
        </div>
      </div>
    </div>

    <!-- Skeleton -->
    <template v-if="loading">
      <div class="content-card" style="text-align: center; padding: 24px 16px;">
        <div class="skel-circle" style="margin:0 auto 14px"></div>
        <div class="skel-line" style="width:50%;height:18px;margin:0 auto 8px"></div>
        <div class="skel-line" style="width:70%;height:12px;margin:0 auto"></div>
      </div>
      <div class="content-card">
        <div class="skel-line" style="width:30%;height:14px;margin-bottom:14px"></div>
        <div class="skel-line" style="width:100%;height:12px;margin-bottom:8px"></div>
        <div class="skel-line" style="width:90%;height:12px;margin-bottom:8px"></div>
        <div class="skel-line" style="width:60%;height:12px"></div>
      </div>
    </template>

    <template v-else>
    <!-- App Info Card -->
    <div class="content-card" style="text-align: center; padding: 24px 16px;">
      <div class="app-logo"><i class="bi bi-bus-front-fill"></i></div>
      <h2 class="app-name">{{ appName }}</h2>
      <p class="app-tagline">{{ appTagline }}</p>
      <span class="app-version">Version 1.0.0</span>
    </div>

    <!-- About Content -->
    <div class="content-card">
      <h4 class="card-title"><i class="bi bi-info-circle-fill"></i> About</h4>
      <div v-if="hasAbout" class="about-text">{{ aboutText }}</div>
      <div v-else class="empty-hint">About info not set yet. Please contact admin.</div>
    </div>
    </template>
  </div>
</template>

<style scoped>
.sub-page { padding: 0 0 100px 0; }

.sub-page-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px; background: white; border-bottom: 1px solid #f0f0f0;
  position: sticky; top: var(--mobile-header-height, 60px); z-index: 10;
}
.header-left { display: flex; align-items: center; gap: 12px; }

.back-btn {
  width: 36px; height: 36px; border-radius: 10px; border: none;
  background: #f3f4f6; display: flex; align-items: center; justify-content: center;
  font-size: 16px; color: #374151; cursor: pointer; transition: all 0.2s;
}
.back-btn:active { transform: scale(0.95); background: #e5e7eb; }

.header-title { font-size: 18px; font-weight: 700; color: #111827; margin: 0; line-height: 1.2; }
.header-subtitle { font-size: 12px; color: #9ca3af; }

.content-card { margin: 12px 16px; padding: 16px; background: white; border-radius: 14px; border: 1px solid #f0f0f0; }
.card-title { font-size: 14px; font-weight: 600; color: #374151; margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px; }
.card-title i { color: var(--primary); }

.app-logo {
  width: 64px; height: 64px; border-radius: 18px;
  background: var(--primary);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 14px; font-size: 28px; color: white;
}
.app-name { text-align: center; font-size: 18px; font-weight: 700; color: #111827; margin: 0 0 4px 0; }
.app-tagline { text-align: center; font-size: 13px; color: #6b7280; margin: 0 0 10px 0; }
.app-version { font-size: 12px; color: #9ca3af; }

.about-text { font-size: 14px; line-height: 1.6; color: #374151; white-space: pre-wrap; word-break: break-word; }
.empty-hint { font-size: 14px; color: #9ca3af; text-align: center; padding: 8px 0; }

.skel-circle { width: 64px; height: 64px; border-radius: 18px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
.skel-line { border-radius: 6px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
</style>
