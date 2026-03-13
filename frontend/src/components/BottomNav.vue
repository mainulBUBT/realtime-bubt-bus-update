<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const currentRoute = computed(() => router.currentRoute.value.name)

const navItems = [
  { name: 'map', icon: 'bi-house-door-fill', label: 'Home' },
  { name: 'schedules', icon: 'bi-calendar3', label: 'Schedule' },
  { name: 'routes', icon: 'bi-signpost-split', label: 'Routes' },
  { name: 'menu', icon: 'bi-grid', label: 'More' }
]

function navigateTo(item) {
  router.push({ name: item.name })
}
</script>

<template>
  <nav class="mobile-bottom-nav">
    <button
      v-for="item in navItems"
      :key="item.name"
      class="nav-item"
      :class="{ active: currentRoute === item.name }"
      @click="navigateTo(item)"
    >
      <i :class="item.icon"></i>
      <span>{{ item.label }}</span>
    </button>
  </nav>
</template>
