<script setup>
const props = defineProps({
  show: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="modal-backdrop" @click.self="emit('cancel')">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">End Trip</h3>
            <button class="modal-close" @click="emit('cancel')">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="modal-body">
            <p>Are you sure you want to end this trip?</p>
            <p class="text-sm text-gray-500 mt-2">This will stop location tracking for the current trip.</p>
          </div>

          <div class="modal-footer">
            <button class="btn btn-secondary" @click="emit('cancel')">
              Cancel
            </button>
            <button class="btn btn-danger" @click="emit('confirm')">
              <i class="bi bi-stop-circle"></i>
              End Trip
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
