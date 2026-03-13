<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useDriverTripStore } from '@/stores/useDriverTripStore'

const router = useRouter()
const route = useRoute()
const driverTripStore = useDriverTripStore()

const emit = defineEmits(['open-menu'])

const navItems = computed(() => [
  {
    name: 'dashboard',
    icon: 'bi-house-door-fill',
    label: 'Home',
    disabled: false
  },
  {
    name: 'trip-active',
    icon: 'bi-bus-front-fill',
    label: 'Active Trip',
    disabled: !driverTripStore.hasActiveTrip
  },
  {
    name: 'menu',
    icon: 'bi-grid',
    label: 'More',
    disabled: false
  }
])

const navigate = (item) => {
  if (item.disabled) return

  if (item.name === 'menu') {
    emit('open-menu')
  } else {
    router.push({ name: item.name })
  }
}
</script>

<template>
  <nav class="driver-bottom-nav">
    <button
      v-for="item in navItems"
      :key="item.name"
      class="driver-nav-item"
      :class="{ active: route.name === item.name || (item.name === 'trip-active' && route.name === 'trip-active'), disabled: item.disabled }"
      @click="navigate(item)"
    >
      <i :class="item.icon"></i>
      <span>{{ item.label }}</span>
      <div
        v-if="item.name === 'trip-active' && driverTripStore.hasActiveTrip"
        class="driver-nav-badge"
      ></div>
    </button>
  </nav>
</template>
