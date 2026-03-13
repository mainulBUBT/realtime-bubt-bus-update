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

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-xl);
  max-width: 400px;
  width: 100%;
  overflow: hidden;
  animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: scale(0.9) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
  transform: scale(0.9) translateY(-20px);
}
</style>
