<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useDriverTripStore } from '@/stores/useDriverTripStore'
import { showToast } from '@/composables/useToast'

const router = useRouter()
const route = useRoute()
const driverTripStore = useDriverTripStore()

const emit = defineEmits(['open-menu'])

const navItems = computed(() => [
  {
    name: 'dashboard',
    icon: 'bi-house-door-fill',
    label: 'Home',
    disabled: driverTripStore.hasActiveTrip
  },
  {
    name: 'trip-history',
    icon: 'bi-clock-history',
    label: 'History',
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
  if (item.disabled) {
    if (item.name === 'dashboard') {
      showToast('Home is unavailable while a trip is active. Please end the current trip first.', {
        type: 'warning'
      })
    } else if (item.name === 'trip-active') {
      showToast('Active Trip is unavailable because no trip is running right now. Please start a trip first.', {
        type: 'warning'
      })
    }
    return
  }

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
      type="button"
      class="driver-nav-item"
      :class="{ active: route.name === item.name, disabled: item.disabled }"
      :aria-disabled="item.disabled"
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
