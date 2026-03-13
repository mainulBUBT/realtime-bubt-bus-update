<script setup>
import { useAuthStore } from '@/stores/useAuthStore'

defineProps({
  show: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'logout'])
const authStore = useAuthStore()
</script>

<template>
  <!-- Backdrop -->
  <Transition name="fade">
    <div v-if="show" class="menu-drawer-backdrop" @click="emit('close')" />
  </Transition>

  <!-- Slide-up drawer -->
  <Transition name="slide-up">
    <div v-if="show" class="menu-drawer">
      <!-- Handle bar -->
      <div class="menu-drawer-handle">
        <span class="handle-bar" />
      </div>

      <!-- User info -->
      <div class="menu-drawer-profile">
        <div class="menu-drawer-avatar">
          <i class="bi bi-person-fill"></i>
        </div>
        <div class="menu-drawer-user-info">
          <div class="menu-drawer-name">{{ authStore.user?.name || 'Driver' }}</div>
          <div class="menu-drawer-role">{{ authStore.user?.email || '' }}</div>
        </div>
      </div>

      <!-- Menu items -->
      <div class="menu-drawer-items">
        <button class="menu-drawer-item danger" @click="emit('logout')">
          <i class="bi bi-box-arrow-right"></i>
          <span>Logout</span>
        </button>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.menu-drawer-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 1200;
}

.menu-drawer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #fff;
  border-radius: 20px 20px 0 0;
  z-index: 1300;
  padding-bottom: env(safe-area-inset-bottom, 0px);
}

.menu-drawer-handle {
  display: flex;
  justify-content: center;
  padding: 12px 0 4px;
}

.handle-bar {
  width: 40px;
  height: 4px;
  background: #d1d5db;
  border-radius: 999px;
  display: block;
}

.menu-drawer-profile {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 20px 12px;
  border-bottom: 1px solid #f3f4f6;
}

.menu-drawer-avatar {
  width: 52px;
  height: 52px;
  background: linear-gradient(135deg, #10B981 0%, #059669 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.menu-drawer-name {
  font-size: 1rem;
  font-weight: 700;
  color: #111827;
}

.menu-drawer-role {
  font-size: 0.8rem;
  color: #6b7280;
  margin-top: 2px;
}

.menu-drawer-items {
  padding: 8px 0 16px;
}

.menu-drawer-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 15px 20px;
  background: none;
  border: none;
  font-size: 0.95rem;
  font-weight: 500;
  color: #374151;
  cursor: pointer;
  transition: background 0.15s;
  text-align: left;
}

.menu-drawer-item:hover {
  background: #f9fafb;
}

.menu-drawer-item i {
  font-size: 1.2rem;
  width: 24px;
  text-align: center;
}

.menu-drawer-item.danger {
  color: #ef4444;
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}
.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
}
</style>
