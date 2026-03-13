<script setup>
const props = defineProps({
  bus: {
    type: Object,
    required: true
  },
  selected: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['select'])

const handleClick = () => {
  emit('select', props.bus)
}

const getBusBadge = (bus) => {
  return bus.code || 'B'
}

const getBusDisplay = (bus) => {
  if (bus.code && bus.display_name) {
    return `${bus.code} - ${bus.display_name}`
  }
  return bus.plate_number || bus.code || 'BUS'
}
</script>

<template>
  <div
    class="bus-select-card"
    :class="{ selected }"
    @click="handleClick"
  >
    <div class="bus-select-badge">
      <i class="bi bi-bus-front-fill"></i>
    </div>

    <div class="bus-select-info">
      <div class="bus-select-plate">
        {{ getBusDisplay(bus) }}
      </div>
      <div class="bus-select-capacity">
        <span class="text-gray-400 text-xs">{{ bus.plate_number }}</span>
        <span class="mx-1">•</span>
        <span>Capacity: {{ bus.capacity }} seats</span>
      </div>
    </div>

    <div class="bus-select-status">
      Available
    </div>

    <i class="bi bi-chevron-right bus-select-chevron"></i>
  </div>
</template>
