<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api/client'

const routes = ref([])
const loading = ref(true)
const searchQuery = ref('')
const expandedIds = ref(new Set())

onMounted(async () => {
  await fetchRoutes()
})

const fetchRoutes = async () => {
  loading.value = true
  try {
    const response = await api.get('/student/routes')
    routes.value = response.data
  } catch (e) {
    console.error('Failed to fetch routes:', e)
  } finally {
    loading.value = false
  }
}

const filteredRoutes = computed(() => {
  if (!searchQuery.value) return routes.value
  const q = searchQuery.value.toLowerCase()
  return routes.value.filter(r =>
    r.name?.toLowerCase().includes(q) ||
    r.code?.toLowerCase().includes(q) ||
    r.origin_name?.toLowerCase().includes(q) ||
    r.destination_name?.toLowerCase().includes(q)
  )
})

function toggleExpand(id) {
  const set = new Set(expandedIds.value)
  if (set.has(id)) set.delete(id)
  else set.add(id)
  expandedIds.value = set
}

function directionLabel(dir) {
  return dir === 'inbound' ? 'To Campus' : 'From Campus'
}
</script>

<template>
  <div class="routes-page">
    <!-- Header -->
    <div class="routes-header">
      <h2 class="routes-title">
        <i class="bi bi-signpost-split-fill"></i>
        Bus Routes
      </h2>
      <span class="routes-count" v-if="!loading">{{ routes.length }} Routes</span>
    </div>

    <!-- Search -->
    <div class="routes-search">
      <i class="bi bi-search"></i>
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search routes, stops..."
      >
    </div>

    <!-- Loading -->
    <div v-if="loading" class="routes-list">
      <div v-for="n in 3" :key="n" class="skeleton-route-card">
        <div class="skeleton-line w-50"></div>
        <div class="skeleton-line w-80"></div>
        <div class="skeleton-line w-30"></div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else-if="filteredRoutes.length === 0" class="routes-empty">
      <div class="empty-icon">
        <i class="bi bi-signpost-split"></i>
      </div>
      <h3>No Routes Found</h3>
      <p v-if="searchQuery">No routes match "{{ searchQuery }}"</p>
      <p v-else>No bus routes are currently available.</p>
    </div>

    <!-- Route Cards -->
    <div v-else class="routes-list">
      <div
        v-for="route in filteredRoutes"
        :key="route.id"
        class="route-card"
        :class="{ expanded: expandedIds.has(route.id) }"
      >
        <div class="route-card-main" @click="toggleExpand(route.id)">
          <!-- Route Code Badge -->
          <div class="route-code-badge" :class="route.direction">
            {{ route.code || '#' }}
          </div>

          <!-- Route Info -->
          <div class="route-info">
            <div class="route-name">{{ route.name }}</div>
            <div class="route-endpoints">
              <span>{{ route.origin_name }}</span>
              <i class="bi bi-arrow-right-short"></i>
              <span>{{ route.destination_name }}</span>
            </div>
            <div class="route-meta">
              <span class="direction-badge" :class="route.direction">
                <i :class="route.direction === 'inbound' ? 'bi bi-arrow-down-left-circle' : 'bi bi-arrow-up-right-circle'"></i>
                {{ directionLabel(route.direction) }}
              </span>
              <span class="stops-count">
                <i class="bi bi-geo-alt"></i>
                {{ route.stops?.length || 0 }} stops
              </span>
            </div>
          </div>

          <!-- Expand -->
          <div class="route-expand">
            <i class="bi" :class="expandedIds.has(route.id) ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
          </div>
        </div>

        <!-- Expanded Stops -->
        <transition name="slide">
          <div v-if="expandedIds.has(route.id) && route.stops?.length" class="route-stops">
            <div class="stops-header" @click="toggleExpand(route.id)">
              <span>{{ route.stops.length }} Stops</span>
              <i class="bi bi-chevron-down"></i>
            </div>
            <div class="stops-timeline">
              <div
                v-for="(stop, idx) in route.stops"
                :key="stop.id"
                class="stop-item"
                :class="{
                  'is-first': idx === 0,
                  'is-last': idx === route.stops.length - 1
                }"
              >
                <div class="stop-dot">
                  <div class="dot-inner"></div>
                </div>
                <div class="stop-line" v-if="idx < route.stops.length - 1"></div>
                <div class="stop-details">
                  <span class="stop-name">{{ stop.name }}</span>
                  <span class="stop-seq">Stop {{ stop.sequence }}</span>
                </div>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </div>
  </div>
</template>
