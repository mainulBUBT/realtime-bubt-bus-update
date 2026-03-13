<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/client'

const router = useRouter()

const form = ref({
  bus_id: '',
  route_id: '',
  schedule_id: ''
})

const buses = ref([])
const routes = ref([])
const schedules = ref([])
const loading = ref(false)
const submitting = ref(false)

onMounted(async () => {
  await fetchResources()
})

const fetchResources = async () => {
  loading.value = true
  try {
    const [busesRes, routesRes] = await Promise.all([
      api.get('/driver/buses'),
      api.get('/driver/routes')
    ])
    buses.value = busesRes.data
    routes.value = routesRes.data
  } catch (error) {
    console.error('Failed to fetch resources:', error)
    alert('Failed to load buses and routes. Please try again.')
  } finally {
    loading.value = false
  }
}

const startTrip = async () => {
  submitting.value = true
  try {
    await api.post('/driver/trips/start', form.value)
    router.push({ name: 'trip-active' })
  } catch (error) {
    alert('Failed to start trip: ' + (error.response?.data?.message || 'Unknown error'))
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Start New Trip</h2>

    <div class="bg-white rounded-lg shadow p-6">
      <form @submit.prevent="startTrip" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Bus</label>
          <select
            v-model="form.bus_id"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Select a bus</option>
            <option v-for="bus in buses" :key="bus.id" :value="bus.id">
              {{ bus.plate_number }} (Capacity: {{ bus.capacity }})
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Route</label>
          <select
            v-model="form.route_id"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Select a route</option>
            <option v-for="route in routes" :key="route.id" :value="route.id">
              {{ route.name }} - {{ route.direction }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Schedule (Optional)</label>
          <select
            v-model="form.schedule_id"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">No schedule (ad-hoc trip)</option>
            <option v-for="schedule in schedules" :key="schedule.id" :value="schedule.id">
              {{ schedule.departure_time }}
            </option>
          </select>
        </div>

        <button
          type="submit"
          :disabled="submitting"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition disabled:opacity-50"
        >
          {{ submitting ? 'Starting...' : 'Start Trip' }}
        </button>
      </form>
    </div>
  </div>
</template>
