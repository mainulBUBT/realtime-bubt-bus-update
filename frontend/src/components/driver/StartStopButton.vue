<script setup>
const props = defineProps({
  type: {
    type: String,
    default: 'stop',
    validator: (value) => ['start', 'stop'].includes(value)
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  label: {
    type: String,
    default: ''
  },
  sublabel: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['click'])

const handleClick = () => {
  if (!props.disabled && !props.loading) {
    emit('click')
  }
}

const getDefaultLabel = () => {
  if (props.label) return props.label
  return props.type === 'start' ? 'START TRIP' : 'END TRIP'
}

const getDefaultSublabel = () => {
  if (props.sublabel) return props.sublabel
  return props.type === 'start'
    ? 'Begin tracking your route'
    : 'Stop tracking & complete this trip'
}

const getIcon = () => {
  return props.type === 'start' ? 'bi-play-fill' : 'bi-stop-fill'
}
</script>

<template>
  <button
    class="start-stop-button"
    :class="type"
    :disabled="disabled || loading"
    @click="handleClick"
  >
    <i v-if="!loading" :class="getIcon()"></i>
    <i v-else class="bi bi-arrow-clockwise animate-spin"></i>

    <span v-if="!loading">{{ getDefaultLabel() }}</span>
    <span v-else>{{ type === 'start' ? 'Starting...' : 'Ending...' }}</span>

    <span v-if="!loading" class="text-xs opacity-75">{{ getDefaultSublabel() }}</span>
  </button>
</template>
