<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSettingsStore } from '@/stores/useSettingsStore'

const router = useRouter()
const settingsStore = useSettingsStore()

const loading = ref(true)

const supportEmail = computed(() => (settingsStore.appSettings.supportEmail || '').trim())
const supportPhone = computed(() => (settingsStore.appSettings.supportPhone || '').trim())
const supportUrl = computed(() => (settingsStore.appSettings.supportUrl || '').trim())

const hasAny = computed(() => !!(supportEmail.value || supportPhone.value || supportUrl.value))

function openUrl(url) {
  if (!url) return
  window.open(url, '_blank', 'noopener,noreferrer')
}

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
          <h1 class="header-title">Help & Support</h1>
          <span class="header-subtitle">Get in touch</span>
        </div>
      </div>
    </div>

    <!-- Skeleton -->
    <template v-if="loading">
      <div class="content-card" style="text-align: center; padding: 24px 16px;">
        <div class="skel-circle" style="margin:0 auto 14px"></div>
        <div class="skel-line" style="width:50%;height:16px;margin:0 auto 8px"></div>
        <div class="skel-line" style="width:70%;height:12px;margin:0 auto"></div>
      </div>
      <div class="content-card">
        <div class="skel-row" v-for="i in 3" :key="i" style="margin-bottom:12px">
          <div class="skel-icon"></div>
          <div class="skel-lines">
            <div class="skel-line" style="width:40%;height:12px;margin-bottom:6px"></div>
            <div class="skel-line" style="width:70%;height:10px"></div>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
    <!-- Support Header -->
    <div class="content-card" style="text-align: center; padding: 24px 16px;">
      <div class="support-icon"><i class="bi bi-headset"></i></div>
      <h3 class="support-title">How can we help?</h3>
      <p class="support-text">Choose a method below to reach our support team.</p>
    </div>

    <!-- Contact Options -->
    <div v-if="hasAny" class="content-card">
      <a v-if="supportEmail" class="contact-item" :href="`mailto:${supportEmail}`">
        <div class="contact-icon email"><i class="bi bi-envelope-fill"></i></div>
        <div class="contact-info">
          <span class="contact-label">Email</span>
          <span class="contact-value">{{ supportEmail }}</span>
        </div>
        <i class="bi bi-chevron-right contact-arrow"></i>
      </a>

      <div v-if="supportEmail && supportPhone" class="contact-divider"></div>

      <a v-if="supportPhone" class="contact-item" :href="`tel:${supportPhone}`">
        <div class="contact-icon phone"><i class="bi bi-telephone-fill"></i></div>
        <div class="contact-info">
          <span class="contact-label">Phone</span>
          <span class="contact-value">{{ supportPhone }}</span>
        </div>
        <i class="bi bi-chevron-right contact-arrow"></i>
      </a>

      <div v-if="(supportEmail || supportPhone) && supportUrl" class="contact-divider"></div>

      <button v-if="supportUrl" class="contact-item" @click="openUrl(supportUrl)">
        <div class="contact-icon web"><i class="bi bi-globe2"></i></div>
        <div class="contact-info">
          <span class="contact-label">Website</span>
          <span class="contact-value">Open support page</span>
        </div>
        <i class="bi bi-chevron-right contact-arrow"></i>
      </button>
    </div>

    <div v-else class="content-card">
      <div class="empty-hint">
        <i class="bi bi-info-circle" style="margin-right: 4px;"></i>
        Contact info not configured yet. Please contact admin.
      </div>
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

.support-icon {
  width: 56px; height: 56px; border-radius: 16px;
  background: var(--primary);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 14px; font-size: 24px; color: white;
}
.support-title { font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 4px 0; }
.support-text { font-size: 13px; color: #6b7280; margin: 0; }

.contact-item {
  display: flex; align-items: center; gap: 14px;
  padding: 12px 0; text-decoration: none; width: 100%;
  background: none; border: none; cursor: pointer; text-align: left; color: inherit;
}
.contact-icon {
  width: 40px; height: 40px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; flex-shrink: 0;
}
.contact-icon.email { background: #EFF6FF; color: #3B82F6; }
.contact-icon.phone { background: var(--primary-50); color: var(--primary); }
.contact-icon.web { background: #FEF3C7; color: #F59E0B; }

.contact-info { flex: 1; min-width: 0; }
.contact-label { display: block; font-size: 13px; font-weight: 600; color: #111827; }
.contact-value { display: block; font-size: 12px; color: #6b7280; }
.contact-arrow { font-size: 14px; color: #d1d5db; }
.contact-divider { height: 1px; background: #f0f0f0; margin: 4px 0; }

.empty-hint { font-size: 14px; color: #9ca3af; text-align: center; padding: 8px 0; }

.skel-circle { width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
.skel-row { display: flex; align-items: center; gap: 14px; }
.skel-icon { width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
.skel-lines { flex: 1; }
.skel-line { border-radius: 6px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
</style>
