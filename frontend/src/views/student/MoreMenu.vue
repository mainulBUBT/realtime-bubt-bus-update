<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { useNotificationStore } from '@/stores/useNotificationStore'
import { useSettingsStore } from '@/stores/useSettingsStore'
import LogoutConfirmModal from '@/components/LogoutConfirmModal.vue'
import { getDefaultAppName } from '@/utils/appBranding'

const router = useRouter()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()
const settingsStore = useSettingsStore()
const showLogoutModal = ref(false)

const userInitial = authStore.user?.name?.charAt(0)?.toUpperCase() || 'S'
const isVerified = computed(() => !!authStore.user?.email_verified_at)

const unreadCount = computed(() => notificationStore.unreadCount)
const appName = computed(() => settingsStore.appSettings.appName || getDefaultAppName('student'))

const menuSections = [
  {
    title: 'General',
    items: [
      { icon: 'bi-bell', label: 'Notifications', action: 'notifications', badge: unreadCount.value > 0 ? String(unreadCount.value) : null },
      { icon: 'bi-gear', label: 'Settings', action: 'settings' },
    ]
  },
  {
    title: 'Information',
    items: [
      { icon: 'bi-info-circle', label: 'About', action: 'about' },
      { icon: 'bi-question-circle', label: 'Help & Support', action: 'help' },
    ]
  },
  {
    title: 'Account',
    items: [
      { icon: 'bi-box-arrow-right', label: 'Logout', action: 'logout', danger: true },
    ]
  }
]

function handleAction(action) {
  if (action === 'logout') {
    showLogoutModal.value = true
  } else if (action === 'notifications') {
    router.push({ name: 'notifications' })
  } else if (action === 'settings') {
    router.push({ name: 'settings' })
  } else if (action === 'about') {
    router.push({ name: 'about' })
  } else if (action === 'help') {
    router.push({ name: 'help-support' })
  }
}

const confirmLogout = async () => {
  showLogoutModal.value = false
  await authStore.logout()
  router.push({ name: 'login' })
}

const cancelLogout = () => {
  showLogoutModal.value = false
}
</script>

<template>
  <div class="more-page">
    <!-- Profile Card -->
    <div class="profile-card">
      <div class="profile-avatar">
        {{ userInitial }}
      </div>
      <div class="profile-info">
        <h3 class="profile-name">{{ authStore.user?.name || 'Student' }}</h3>
        <span class="profile-role">
          <i class="bi bi-mortarboard-fill"></i>
          Student
        </span>
        <span v-if="isVerified" class="profile-role" style="color: #16a34a;">
          <i class="bi bi-patch-check-fill"></i>
          Verified
        </span>
      </div>
    </div>

    <!-- Menu Sections -->
    <div v-for="section in menuSections" :key="section.title" class="menu-section">
      <h4 class="menu-section-title">{{ section.title }}</h4>
      <div class="menu-group">
        <button
          v-for="item in section.items"
          :key="item.label"
          class="menu-item"
          :class="{ danger: item.danger, disabled: !item.action && item.badge }"
          @click="handleAction(item.action)"
        >
          <i :class="item.icon" class="menu-item-icon"></i>
          <span class="menu-item-label">{{ item.label }}</span>
          <span v-if="item.badge" class="menu-item-badge">{{ item.badge }}</span>
          <i v-else class="bi bi-chevron-right menu-item-arrow"></i>
        </button>
      </div>
    </div>

    <!-- Footer -->
    <div class="more-footer">
      <p>{{ appName }}</p>
      <span>Version 1.0.0</span>
    </div>

    <!-- Logout Modal -->
    <LogoutConfirmModal
      :show="showLogoutModal"
      @confirm="confirmLogout"
      @cancel="cancelLogout"
    />
  </div>
</template>
