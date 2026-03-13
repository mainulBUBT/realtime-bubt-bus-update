<script setup>
import { computed } from 'vue'

const props = defineProps({
  bus: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['click'])

const statusClass = computed(() => {
  return props.bus.status || 'inactive'
})

const badgeClass = computed(() => {
  const status = props.bus.status || 'inactive'
  if (status === 'delayed') return 'delayed'
  if (status === 'inactive') return 'inactive'
  return ''
})

const statusIndicatorClass = computed(() => {
  return props.bus.status || 'inactive'
})

const statusText = computed(() => {
  const status = props.bus.status || 'inactive'
  return status.charAt(0).toUpperCase() + status.slice(1)
})
</script>

<template>
  <div class="bus-item" @click="emit('click')">
    <div class="bus-item-left">
      <div class="bus-badge" :class="badgeClass">
        <i class="bi bi-bus-front-fill"></i>
      </div>
      <div class="status-indicator" :class="statusIndicatorClass"></div>
    </div>
    <div class="bus-item-center">
      <h4 class="bus-name">{{ bus.name }}</h4>
      <p class="bus-route">{{ bus.route }}</p>
      <span class="bus-eta" :class="statusClass">
        <i class="bi bi-geo-alt-fill"></i>
        {{ bus.eta }}
      </span>
    </div>
    <div class="bus-item-right">
      <i class="bi bi-chevron-right"></i>
    </div>
  </div>
</template>

<style scoped>
.bus-item {
  cursor: pointer;
}
</style>
