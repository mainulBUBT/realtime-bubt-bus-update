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

<style scoped>
.routes-page {
  padding: 16px;
  padding-bottom: calc(var(--mobile-nav-height, 56px) + 24px);
  min-height: 100%;
  background: var(--gray-50, #F8FAFC);
}

.routes-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.routes-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--gray-900, #0F172A);
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0;
}

.routes-title i {
  color: var(--primary, #10B981);
}

.routes-count {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--gray-500, #64748B);
  background: var(--gray-100, #F1F5F9);
  padding: 4px 12px;
  border-radius: var(--radius-full, 9999px);
}

/* Search */
.routes-search {
  display: flex;
  align-items: center;
  gap: 10px;
  background: var(--white, #fff);
  border-radius: var(--radius-md, 12px);
  padding: 12px 16px;
  margin-bottom: 20px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--gray-100, #F1F5F9);
}

.routes-search i {
  color: var(--gray-400, #94A3B8);
  font-size: 1rem;
}

.routes-search input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 0.9rem;
  color: var(--gray-900, #0F172A);
  background: transparent;
}

.routes-search input::placeholder {
  color: var(--gray-400, #94A3B8);
}

/* Route List */
.routes-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Route Card */
.route-card {
  background: var(--white, #fff);
  border-radius: var(--radius-md, 12px);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--gray-100, #F1F5F9);
  overflow: hidden;
  transition: all 200ms ease;
}

.route-card.expanded {
  box-shadow: var(--shadow-md);
}

.route-card-main {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 16px;
  cursor: pointer;
}

/* Route Code Badge */
.route-code-badge {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-md, 12px);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
  color: white;
  flex-shrink: 0;
}

.route-code-badge.inbound {
  background: linear-gradient(135deg, var(--primary, #10B981), var(--primary-dark, #059669));
}

.route-code-badge.outbound {
  background: linear-gradient(135deg, #3B82F6, #2563EB);
}

/* Route Info */
.route-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.route-name {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--gray-800, #1E293B);
}

.route-endpoints {
  font-size: 0.8rem;
  color: var(--gray-500, #64748B);
  display: flex;
  align-items: center;
  gap: 4px;
  flex-wrap: wrap;
}

.route-endpoints i {
  color: var(--primary, #10B981);
  font-size: 1rem;
}

.route-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.direction-badge {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: var(--radius-full, 9999px);
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.direction-badge.inbound {
  background: rgba(16, 185, 129, 0.1);
  color: var(--primary-dark, #059669);
}

.direction-badge.outbound {
  background: rgba(59, 130, 246, 0.1);
  color: #2563EB;
}

.stops-count {
  font-size: 0.75rem;
  color: var(--gray-400, #94A3B8);
  display: flex;
  align-items: center;
  gap: 4px;
}

.route-expand {
  color: var(--gray-400, #94A3B8);
  font-size: 1rem;
  flex-shrink: 0;
  padding-top: 8px;
}

/* Stops Section */
.route-stops {
  border-top: 1px solid var(--gray-100, #F1F5F9);
  padding: 16px;
  background: var(--gray-50, #F8FAFC);
}

.stops-timeline {
  padding-left: 8px;
}

.stop-item {
  position: relative;
  padding-left: 28px;
  padding-bottom: 18px;
  display: flex;
  align-items: flex-start;
}

.stop-item.is-last {
  padding-bottom: 0;
}

.stop-dot {
  position: absolute;
  left: 0;
  top: 2px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: white;
  border: 2px solid var(--gray-300, #CBD5E1);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
}

.stop-item.is-first .stop-dot,
.stop-item.is-last .stop-dot {
  border-color: var(--primary, #10B981);
  background: var(--primary-light, #D1FAE5);
}

.dot-inner {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.stop-item.is-first .dot-inner,
.stop-item.is-last .dot-inner {
  background: var(--primary, #10B981);
}

.stop-line {
  position: absolute;
  left: 7px;
  top: 18px;
  width: 2px;
  bottom: 0;
  background: var(--gray-200, #E2E8F0);
}

.stop-details {
  display: flex;
  flex-direction: column;
}

.stop-name {
  font-size: 0.825rem;
  color: var(--gray-700, #334155);
}

.stop-item.is-first .stop-name,
.stop-item.is-last .stop-name {
  font-weight: 600;
  color: var(--gray-900, #0F172A);
}

.stop-seq {
  font-size: 0.65rem;
  color: var(--gray-400, #94A3B8);
}

/* Slide Transition */
.slide-enter-active, .slide-leave-active {
  transition: all 250ms ease;
  max-height: 500px;
  overflow: hidden;
}

.slide-enter-from, .slide-leave-to {
  max-height: 0;
  opacity: 0;
}

/* Empty State */
.routes-empty {
  text-align: center;
  padding: 60px 24px;
}

.empty-icon {
  width: 80px;
  height: 80px;
  background: var(--gray-100, #F1F5F9);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}

.empty-icon i {
  font-size: 2rem;
  color: var(--gray-400, #94A3B8);
}

.routes-empty h3 {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--gray-700, #334155);
  margin-bottom: 8px;
}

.routes-empty p {
  font-size: 0.875rem;
  color: var(--gray-500, #64748B);
  margin: 0;
}

/* Skeleton */
.skeleton-route-card {
  background: var(--white, #fff);
  border-radius: var(--radius-md, 12px);
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.skeleton-line {
  height: 14px;
  background: linear-gradient(90deg, var(--gray-200, #E2E8F0) 25%, var(--gray-100, #F1F5F9) 50%, var(--gray-200, #E2E8F0) 75%);
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.5s ease-in-out infinite;
  border-radius: 4px;
}

.w-50 { width: 50%; }
.w-80 { width: 80%; }
.w-30 { width: 30%; }

@keyframes skeleton-shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@media (min-width: 768px) {
  .routes-page {
    padding: 24px;
    padding-bottom: 24px;
  }
}
</style>
