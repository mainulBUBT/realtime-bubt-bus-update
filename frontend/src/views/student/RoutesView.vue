<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import api from '@/api/client'

const routes = ref([])
const loadingInitial = ref(true)
const loadingMore = ref(false)
const hasMore = ref(false)
const currentPage = ref(1)
const totalCount = ref(0)
const searchQuery = ref('')
const debouncedSearchQuery = ref('')
const expandedIds = ref(new Set())
const routeStopsCache = ref(new Map())
const loadingStops = ref(new Set())

const PER_PAGE = 20
const SEARCH_DEBOUNCE_MS = 300
let searchDebounceTimer = null
let routeRequestController = null

const normalizedSearchQuery = computed(() => searchQuery.value.trim().toLowerCase())
const hasActiveSearch = computed(() => normalizedSearchQuery.value.length > 0)

onMounted(async () => {
  await fetchRoutesPage(1, { replace: true })
})

onUnmounted(() => {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
  }

  if (routeRequestController) {
    routeRequestController.abort()
    routeRequestController = null
  }
})

watch(searchQuery, (nextValue) => {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
  }

  searchDebounceTimer = setTimeout(() => {
    debouncedSearchQuery.value = nextValue.trim()
  }, SEARCH_DEBOUNCE_MS)
})

watch(debouncedSearchQuery, async () => {
  expandedIds.value = new Set()
  routeStopsCache.value = new Map()
  loadingStops.value = new Set()
  await fetchRoutesPage(1, { replace: true })
})

const fetchRoutesPage = async (page, { replace = false } = {}) => {
  if (routeRequestController) {
    routeRequestController.abort()
  }

  routeRequestController = new AbortController()

  if (page <= 1) {
    loadingInitial.value = true
  } else {
    loadingMore.value = true
  }

  try {
    const response = await api.get('/student/routes', {
      params: {
        q: debouncedSearchQuery.value || undefined,
        page,
        per_page: PER_PAGE
      },
      signal: routeRequestController.signal
    })

    const payload = response.data || {}
    const items = Array.isArray(payload.data) ? payload.data : []
    const meta = payload.meta || {}

    currentPage.value = Number(meta.current_page || page)
    totalCount.value = Number(meta.total || 0)
    hasMore.value = Boolean(meta.has_more)

    if (replace || page === 1) {
      routes.value = items
      return
    }

    const knownIds = new Set(routes.value.map(route => route.id))
    const nextItems = items.filter(route => !knownIds.has(route.id))
    routes.value = [...routes.value, ...nextItems]
  } catch (e) {
    if (e?.name === 'AbortError') {
      return
    }

    console.error('Failed to fetch routes:', e)
  } finally {
    loadingInitial.value = false
    loadingMore.value = false
  }
}

const searchSummary = computed(() => {
  if (!hasActiveSearch.value) return ''
  const loaded = routes.value.length
  return `Loaded ${loaded} of ${totalCount.value} routes for "${searchQuery.value.trim()}"`
})

async function toggleExpand(route) {
  const id = route.id
  const set = new Set(expandedIds.value)

  if (set.has(id)) {
    set.delete(id)
    expandedIds.value = set
    return
  }

  set.add(id)
  expandedIds.value = set

  await ensureRouteStops(route.id)
}

function directionLabel(dir) {
  return dir === 'outbound' ? 'To Campus' : 'From Campus'
}

function clearSearch() {
  searchQuery.value = ''
}

async function loadMoreRoutes() {
  if (!hasMore.value || loadingMore.value) return
  await fetchRoutesPage(currentPage.value + 1)
}

async function ensureRouteStops(routeId) {
  if (routeStopsCache.value.has(routeId) || loadingStops.value.has(routeId)) {
    return
  }

  loadingStops.value = new Set(loadingStops.value).add(routeId)

  try {
    const response = await api.get(`/student/routes/${routeId}`)
    const stops = Array.isArray(response.data?.stops) ? response.data.stops : []
    const cache = new Map(routeStopsCache.value)
    cache.set(routeId, stops)
    routeStopsCache.value = cache
  } catch (e) {
    console.error(`Failed to load route stops for route ${routeId}:`, e)
    const cache = new Map(routeStopsCache.value)
    cache.set(routeId, [])
    routeStopsCache.value = cache
  } finally {
    const next = new Set(loadingStops.value)
    next.delete(routeId)
    loadingStops.value = next
  }
}

function routeStopsFor(routeId) {
  if (!routeId) return []
  return routeStopsCache.value.get(routeId) || []
}

function isRouteStopsLoading(routeId) {
  if (!routeId) return false
  return loadingStops.value.has(routeId)
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
      <span class="routes-count" v-if="!loadingInitial">{{ totalCount }} Routes</span>
    </div>

    <!-- Search -->
    <div class="routes-search">
      <i class="bi bi-search"></i>
      <input
        :value="searchQuery"
        @input="searchQuery = $event.target.value"
        @keyup="searchQuery = $event.target.value"
        type="text"
        placeholder="Search routes, stops..."
        enterkeyhint="search"
      >
      <button
        v-if="hasActiveSearch"
        type="button"
        class="routes-search-clear"
        @click="clearSearch"
      >
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <div v-if="hasActiveSearch && !loadingInitial" class="routes-search-feedback">
      <span class="routes-search-feedback-text">{{ searchSummary }}</span>
      <span class="routes-search-feedback-badge">
        <i class="bi bi-funnel-fill"></i>
        Filter active
      </span>
    </div>

    <!-- Loading -->
    <div v-if="loadingInitial" class="routes-list">
      <div v-for="n in 3" :key="n" class="skeleton-route-card">
        <div class="skeleton-line w-50"></div>
        <div class="skeleton-line w-80"></div>
        <div class="skeleton-line w-30"></div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else-if="routes.length === 0" class="routes-empty">
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
        v-for="route in routes"
        :key="route.id"
        class="route-card"
        :class="{ expanded: expandedIds.has(route.id) }"
      >
        <div class="route-card-main" @click="toggleExpand(route)">
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
                <i :class="route.direction === 'outbound' ? 'bi bi-arrow-up-right-circle' : 'bi bi-arrow-down-left-circle'"></i>
                {{ directionLabel(route.direction) }}
              </span>
              <span class="stops-count">
                <i class="bi bi-geo-alt"></i>
                {{ route.stops_count || 0 }} stops
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
          <div v-if="expandedIds.has(route.id)" class="route-stops">
            <div class="stops-header" @click="toggleExpand(route)">
              <span>{{ route.stops_count || 0 }} Stops</span>
              <i class="bi bi-chevron-down"></i>
            </div>

            <div v-if="isRouteStopsLoading(route.id)" class="stops-loading">
              <i class="bi bi-arrow-repeat spin"></i>
              Loading stops...
            </div>

            <div v-else-if="routeStopsFor(route.id).length > 0" class="stops-timeline">
              <div
                v-for="(stop, idx) in routeStopsFor(route.id)"
                :key="stop.id"
                class="stop-item"
                :class="{
                  'is-first': idx === 0,
                  'is-last': idx === routeStopsFor(route.id).length - 1
                }"
              >
                <div class="stop-dot">
                  <div class="dot-inner"></div>
                </div>
                <div class="stop-line" v-if="idx < routeStopsFor(route.id).length - 1"></div>
                <div class="stop-details">
                  <span class="stop-name">{{ stop.name }}</span>
                  <span class="stop-seq">Stop {{ stop.sequence }}</span>
                </div>
              </div>
            </div>

            <div v-else class="stops-empty">
              No stops available for this route.
            </div>
          </div>
        </transition>
      </div>

      <div v-if="hasMore" class="list-load-more">
        <button
          type="button"
          class="load-more-btn"
          :disabled="loadingMore"
          @click="loadMoreRoutes"
        >
          <i v-if="loadingMore" class="bi bi-arrow-repeat spin"></i>
          {{ loadingMore ? 'Loading more...' : 'Load More' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>
