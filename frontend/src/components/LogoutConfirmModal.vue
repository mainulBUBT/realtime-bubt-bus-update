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
  <!-- Modal -->
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="modal-backdrop" @click.self="emit('cancel')">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">Confirm Logout</h3>
            <button class="modal-close" @click="emit('cancel')">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="modal-body">
            <p>Are you sure you want to logout?</p>
          </div>

          <div class="modal-footer">
            <button class="btn btn-secondary" @click="emit('cancel')">
              Cancel
            </button>
            <button class="btn btn-danger" @click="emit('confirm')">
              <i class="bi bi-box-arrow-right"></i>
              Logout
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
