<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import LogoutConfirmModal from '@/components/LogoutConfirmModal.vue'

const router = useRouter()
const authStore = useAuthStore()
const showLogoutModal = ref(false)

const userInitial = authStore.user?.name?.charAt(0)?.toUpperCase() || 'S'

const menuSections = [
  {
    title: 'General',
    items: [
      { icon: 'bi-bell', label: 'Notifications', action: null, badge: 'Soon' },
      { icon: 'bi-gear', label: 'Settings', action: null, badge: 'Soon' },
    ]
  },
  {
    title: 'Information',
    items: [
      { icon: 'bi-info-circle', label: 'About', action: null },
      { icon: 'bi-question-circle', label: 'Help & Support', action: null, badge: 'Soon' },
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
      <p>BUBT Bus Tracker</p>
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

<style scoped>
.more-page {
  padding: 16px;
  padding-bottom: calc(var(--mobile-nav-height, 56px) + 24px);
  min-height: 100%;
  background: var(--gray-50, #F8FAFC);
}

/* Profile Card */
.profile-card {
  display: flex;
  align-items: center;
  gap: 16px;
  background: linear-gradient(135deg, var(--primary, #10B981), var(--primary-dark, #059669));
  border-radius: var(--radius-lg, 16px);
  padding: 24px 20px;
  margin-bottom: 24px;
  color: white;
}

.profile-avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: 700;
  flex-shrink: 0;
}

.profile-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.profile-name {
  font-size: 1.15rem;
  font-weight: 600;
  margin: 0;
}

.profile-role {
  font-size: 0.8rem;
  opacity: 0.85;
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Menu Sections */
.menu-section {
  margin-bottom: 24px;
}

.menu-section-title {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--gray-400, #94A3B8);
  margin-bottom: 8px;
  padding-left: 4px;
}

.menu-group {
  background: var(--white, #fff);
  border-radius: var(--radius-md, 12px);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  border: 1px solid var(--gray-100, #F1F5F9);
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  width: 100%;
  border: none;
  background: transparent;
  cursor: pointer;
  transition: background 150ms ease;
  text-align: left;
  font-size: 0.9rem;
  color: var(--gray-800, #1E293B);
}

.menu-item:not(:last-child) {
  border-bottom: 1px solid var(--gray-100, #F1F5F9);
}

.menu-item:active {
  background: var(--gray-50, #F8FAFC);
}

.menu-item.disabled {
  cursor: default;
  opacity: 0.6;
}

.menu-item.danger {
  color: #EF4444;
}

.menu-item-icon {
  font-size: 1.15rem;
  width: 24px;
  text-align: center;
  color: var(--gray-500, #64748B);
}

.menu-item.danger .menu-item-icon {
  color: #EF4444;
}

.menu-item-label {
  flex: 1;
  font-weight: 500;
}

.menu-item-badge {
  font-size: 0.65rem;
  font-weight: 600;
  background: var(--gray-100, #F1F5F9);
  color: var(--gray-400, #94A3B8);
  padding: 2px 8px;
  border-radius: var(--radius-full, 9999px);
  text-transform: uppercase;
}

.menu-item-arrow {
  color: var(--gray-300, #CBD5E1);
  font-size: 0.8rem;
}

/* Footer */
.more-footer {
  text-align: center;
  padding: 24px 0 8px;
}

.more-footer p {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--gray-400, #94A3B8);
  margin-bottom: 2px;
}

.more-footer span {
  font-size: 0.7rem;
  color: var(--gray-300, #CBD5E1);
}

@media (min-width: 768px) {
  .more-page {
    padding: 24px;
    padding-bottom: 24px;
  }
}
</style>
