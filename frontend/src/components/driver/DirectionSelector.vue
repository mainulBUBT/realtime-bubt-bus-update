<script setup>
const props = defineProps({
  route: {
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
  emit('select', props.route)
}

const getDirectionIcon = (direction) => {
  // outbound = TO campus, inbound = FROM campus
  return direction === 'outbound' ? 'bi-arrow-up-circle' : 'bi-arrow-down-circle'
}

const getDirectionLabel = (direction) => {
  // Show user-friendly text
  return direction === 'outbound' ? 'To Campus' : 'From Campus'
}

const getDirectionEmoji = (direction) => {
  return direction === 'outbound' ? '⬆️' : '⬇️'
}
</script>

<template>
  <div
    class="direction-selector"
    :class="{ selected }"
    :data-direction="route.direction"
    @click="handleClick"
  >
    <div class="direction-icon">
      <i :class="getDirectionIcon(route.direction)"></i>
    </div>

    <div class="direction-info">
      <div class="direction-name">
        {{ route.name }}
      </div>
      <div class="direction-label">
        <i class="bi bi-geo-alt-fill"></i>
        {{ getDirectionLabel(route.direction) }}
      </div>
    </div>

    <i class="bi bi-chevron-right direction-chevron"></i>
  </div>
</template>
