<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/useNotificationStore'
import { storeToRefs } from 'pinia'

const router = useRouter()
const notificationStore = useNotificationStore()
const { notifications, loadingMore, hasMore, total } = storeToRefs(notificationStore)

const initialLoading = ref(true)

function timeAgo(dateStr) {
  if (!dateStr) return ''
  const now = new Date()
  const date = new Date(dateStr)
  const seconds = Math.floor((now - date) / 1000)

  if (seconds < 60) return 'Just now'
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  if (days < 7) return `${days}d ago`
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

function typeIcon(type) {
  const icons = { info: 'bi-info-circle-fill', warning: 'bi-exclamation-triangle-fill', alert: 'bi-bell-fill' }
  return icons[type] || 'bi-bell-fill'
}

function displayTime(notification) {
  return notification.last_sent_at || notification.created_at || notification.sent_at
}

async function handleLoadMore() {
  await notificationStore.loadMore()
}

onMounted(async () => {
  try {
    await notificationStore.fetchNotifications()
    await notificationStore.markAllAsRead()
  } finally {
    initialLoading.value = false
  }
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
          <h1 class="header-title">Notifications</h1>
          <span v-if="!initialLoading && total > 0" class="header-subtitle">{{ total }} notification{{ total !== 1 ? 's' : '' }}</span>
        </div>
      </div>
    </div>

    <!-- Skeleton -->
    <div v-if="initialLoading" class="skeleton-list">
      <div v-for="i in 5" :key="i" class="skeleton-card">
        <div class="skeleton-icon"></div>
        <div class="skeleton-body">
          <div class="skeleton-line" style="width:75%;height:16px"></div>
          <div class="skeleton-line" style="width:100%;height:12px;margin-top:10px"></div>
          <div class="skeleton-line" style="width:40%;height:10px;margin-top:10px"></div>
        </div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else-if="notifications.length === 0" class="empty-state">
      <div class="empty-icon"><i class="bi bi-bell-slash"></i></div>
      <h3 class="empty-title">No notifications yet</h3>
      <p class="empty-text">Notifications from admins will appear here.</p>
    </div>

    <!-- Notification List -->
    <div v-else class="notification-list">
      <div v-for="notification in notifications" :key="notification.id" class="notification-card">
        <div class="notification-indicator">
          <i :class="[typeIcon(notification.type), 'type-icon']" :style="{ color: notification.type === 'warning' ? '#F59E0B' : notification.type === 'alert' ? '#EF4444' : 'var(--primary)' }"></i>
        </div>
        <div class="notification-content">
          <h4 class="notification-title">{{ notification.title }}</h4>
          <p class="notification-body">{{ notification.body }}</p>
          <span class="notification-time">{{ timeAgo(displayTime(notification)) }}</span>
        </div>
        <div v-if="notification.image_url" class="notification-media">
          <img :src="notification.image_url" alt="" class="notification-image">
        </div>
      </div>

      <div v-if="hasMore" class="load-more">
        <button class="load-more-btn" :disabled="loadingMore" @click="handleLoadMore">
          <span v-if="loadingMore" class="loading-spinner"></span>
          <span v-else>Load More</span>
        </button>
      </div>
    </div>
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

/* Skeleton */
.skeleton-list { padding: 12px 16px; }
.skeleton-card {
  display: flex; gap: 12px; padding: 14px 16px;
  background: white; border-radius: 14px; margin-bottom: 8px; border: 1px solid #f0f0f0;
}
.skeleton-icon {
  width: 24px; height: 24px; border-radius: 50%; flex-shrink: 0;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%; animation: shimmer 1.5s infinite;
}
.skeleton-body { flex: 1; }
.skeleton-line {
  border-radius: 4px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%; animation: shimmer 1.5s infinite;
}
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

/* Empty */
.empty-state { text-align: center; padding: 60px 20px; }
.empty-icon { font-size: 48px; color: #d1d5db; margin-bottom: 16px; }
.empty-title { font-size: 16px; font-weight: 600; color: #374151; margin: 0 0 8px 0; }
.empty-text { font-size: 14px; color: #9ca3af; margin: 0; }

/* Notifications */
.notification-list { padding: 8px 16px; }
.notification-card {
  display: flex; gap: 12px; padding: 14px 16px;
  background: white; border-radius: 14px; margin-bottom: 8px; border: 1px solid #f0f0f0;
}
.notification-indicator { display: flex; align-items: center; padding-top: 2px; min-width: 24px; }
.type-icon { font-size: 18px; }
.notification-content { flex: 1; min-width: 0; }
.notification-title { font-size: 14px; font-weight: 600; color: #111827; margin: 0 0 4px 0; line-height: 1.3; }
.notification-body { font-size: 13px; color: #6b7280; margin: 0 0 6px 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.notification-time { font-size: 11px; color: #9ca3af; }

.notification-media { width: 64px; flex: 0 0 64px; display: flex; align-items: center; justify-content: flex-end; }
.notification-image { width: 64px; height: 64px; border-radius: 12px; object-fit: cover; border: 1px solid #f0f0f0; }

.load-more { text-align: center; padding: 16px 0 24px; }
.load-more-btn { padding: 10px 24px; border-radius: 12px; border: 1.5px solid #e5e7eb; background: white; font-size: 14px; font-weight: 500; color: #374151; cursor: pointer; }
.load-more-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.load-more-btn:active:not(:disabled) { transform: scale(0.98); }

.loading-spinner {
  display: inline-block; width: 16px; height: 16px;
  border: 2px solid #e5e7eb; border-top-color: var(--primary);
  border-radius: 50%; animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
