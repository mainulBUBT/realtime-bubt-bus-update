<script setup>
const props = defineProps({
  title: {
    type: String,
    default: 'BUBT Driver'
  },
  showBack: {
    type: Boolean,
    default: false
  },
  showLogout: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['back', 'logout'])

const handleBack = () => {
  emit('back')
}

const handleLogout = () => {
  emit('logout')
}
</script>

<template>
  <header class="driver-mobile-header">
    <button
      v-if="showBack"
      class="driver-header-btn"
      @click="handleBack"
    >
      <i class="bi bi-arrow-left"></i>
    </button>

    <h1 class="driver-header-title">{{ title }}</h1>

    <button
      v-if="showLogout"
      class="driver-header-btn"
      @click="handleLogout"
    >
      <i class="bi bi-box-arrow-right"></i>
    </button>
  </header>
</template>
