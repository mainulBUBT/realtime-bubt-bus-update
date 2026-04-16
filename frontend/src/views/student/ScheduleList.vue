<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import api from '@/api/client'
import { usePullToRefresh } from '@/composables/usePullToRefresh'

const schedules = ref([])
const loadingInitial = ref(true)
const loadingMore = ref(false)
const hasMore = ref(false)
const currentPage = ref(1)
const filter = ref('today') // 'today' | 'all'
const expandedIds = ref(new Set())
const routeStopsCache = ref(new Map())
const loadingStops = ref(new Set())

const PER_PAGE = 20
let scheduleRequestController = null

const refreshData = async () => {
  schedules.value = []
  await fetchSchedulesPage(1, { replace: true })
}

const { isRefreshing, pullDistance, canRelease, onMount: initPull } = usePullToRefresh(refreshData, {
  threshold: 60
})

const contentRef = ref(null)

onMounted(async () => {
  await fetchSchedulesPage(1, { replace: true })
  initPull(contentRef.value)
})

onUnmounted(() => {
  if (scheduleRequestController) {
    scheduleRequestController.abort()
    scheduleRequestController = null
  }
})

const fetchSchedulesPage = async (page, { replace = false } = {}) => {
  if (scheduleRequestController) {
    scheduleRequestController.abort()
  }

  scheduleRequestController = new AbortController()

  if (page <= 1) {
    loadingInitial.value = true
  } else {
    loadingMore.value = true
  }

  try {
    const response = await api.get('/student/schedules', {
      params: {
        filter: filter.value,
        page,
        per_page: PER_PAGE
      },
      signal: scheduleRequestController.signal
    })

    const payload = response.data || {}
    const items = Array.isArray(payload.data) ? payload.data : []
    const meta = payload.meta || {}

    currentPage.value = Number(meta.current_page || page)
    hasMore.value = Boolean(meta.has_more)

    if (replace || page === 1) {
      schedules.value = items
      return
    }

    const knownIds = new Set(schedules.value.map(schedule => schedule.id))
    const nextItems = items.filter(schedule => !knownIds.has(schedule.id))
    schedules.value = [...schedules.value, ...nextItems]
  } catch (e) {
    if (e?.name === 'AbortError') {
      return
    }

    console.error('Failed to fetch schedules:', e)
  } finally {
    loadingInitial.value = false
    loadingMore.value = false
  }
}

watch(filter, async () => {
  expandedIds.value = new Set()
  routeStopsCache.value = new Map()
  loadingStops.value = new Set()
  await fetchSchedulesPage(1, { replace: true })
})

async function toggleExpand(schedule) {
  const id = schedule.id
  const set = new Set(expandedIds.value)

  if (set.has(id)) {
    set.delete(id)
    expandedIds.value = set
    return
  }

  set.add(id)
  expandedIds.value = set

  const routeId = schedule.route?.id
  if (routeId) {
    await ensureRouteStops(routeId)
  }
}

async function loadMoreSchedules() {
  if (!hasMore.value || loadingMore.value) return
  await fetchSchedulesPage(currentPage.value + 1)
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

function formatTime(timeStr) {
  if (!timeStr) return ''
  const [h, m] = timeStr.split(':')
  const hour = parseInt(h)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const h12 = hour % 12 || 12
  return `${h12}:${m} ${ampm}`
}

function isToday(day) {
  const today = new Date().toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase()
  return day === today
}

const todayName = new Date().toLocaleDateString('en-US', { weekday: 'long' })
</script>

<template>
  <div ref="contentRef" class="schedule-page">
    <!-- Pull Indicator -->
    <div 
      v-if="isPulling" 
      class="pull-indicator"
      :class="{ visible: pullDistance > 0, releasing: canRelease, refreshing: isRefreshing }"
    >
      <i v-if="isRefreshing" class="bi bi-arrow-repeat spinning"></i>
      <i v-else-if="canRelease" class="bi bi-arrow-up-circle-fill"></i>
      <i v-else class="bi bi-arrow-down"></i>
    </div>
    
    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <button
        class="filter-tab"
        :class="{ active: filter === 'today' }"
        @click="filter = 'today'"
      >
        <i class="bi bi-clock"></i>
        Today
      </button>
      <button
        class="filter-tab"
        :class="{ active: filter === 'all' }"
        @click="filter = 'all'"
      >
        <i class="bi bi-calendar-week"></i>
        All Days
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loadingInitial" class="schedule-list">
      <div v-for="n in 4" :key="n" class="skeleton-schedule-card">
        <div class="skeleton-time"></div>
        <div class="skeleton-body">
          <div class="skeleton-line w-60"></div>
          <div class="skeleton-line w-80"></div>
          <div class="skeleton-line w-40"></div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="schedules.length === 0" class="schedule-empty">
      <div class="empty-icon">
        <i class="bi bi-calendar-x"></i>
      </div>
      <h3>No Schedules {{ filter === 'today' ? 'Today' : 'Found' }}</h3>
      <p v-if="filter === 'today'">
        There are no bus services scheduled for today.
        <button class="link-btn" @click="filter = 'all'">View all schedules</button>
      </p>
      <p v-else>No schedules have been added yet.</p>
    </div>

    <!-- Schedule Cards -->
    <div v-else class="schedule-list">
      <div
        v-for="schedule in schedules"
        :key="schedule.id"
        class="schedule-card"
        :class="{ expanded: expandedIds.has(schedule.id), 'is-today': schedule.is_today }"
      >
        <!-- Main Content -->
        <div class="schedule-card-main" @click="toggleExpand(schedule)">
          <!-- Time Column -->
          <div class="schedule-time-col">
            <span class="schedule-time">{{ formatTime(schedule.departure_time) }}</span>
            <span class="schedule-time-label">Departure</span>
          </div>

          <!-- Info Column -->
          <div class="schedule-info-col">
            <!-- Bus Badge -->
            <div class="schedule-bus-badge" v-if="schedule.bus">
              <i class="bi bi-bus-front-fill"></i>
              {{ schedule.bus.plate_number }}
              <span class="bus-capacity" v-if="schedule.bus.capacity">
                <i class="bi bi-people-fill"></i> {{ schedule.bus.capacity }}
              </span>
            </div>

            <!-- Route -->
            <div class="schedule-route" v-if="schedule.route">
              <span class="route-name">{{ schedule.route.name }}</span>
              <div class="route-direction">
                <span>{{ schedule.route.origin_name }}</span>
                <i class="bi bi-arrow-right"></i>
                <span>{{ schedule.route.destination_name }}</span>
                <span class="stops-count">
                  <i class="bi bi-geo-alt"></i>
                  {{ schedule.route.stops_count || 0 }} stops
                </span>
              </div>
            </div>

            <!-- Weekday Pills -->
            <div class="weekday-pills">
              <span
                v-for="day in (schedule.weekdays || [])"
                :key="day"
                class="weekday-pill"
                :class="{ active: isToday(day) }"
              >
                {{ day.substring(0, 3) }}
              </span>
            </div>
          </div>

          <!-- Expand Arrow -->
          <div class="schedule-expand">
            <i class="bi" :class="expandedIds.has(schedule.id) ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
          </div>
        </div>

        <!-- Expandable Stops -->
        <transition name="slide">
          <div v-if="expandedIds.has(schedule.id)" class="schedule-stops">
            <div class="stops-header" @click="toggleExpand(schedule)">
              <span>{{ schedule.route?.stops_count || 0 }} Stops</span>
              <i class="bi bi-chevron-down"></i>
            </div>

            <div v-if="isRouteStopsLoading(schedule.route?.id)" class="stops-loading">
              <i class="bi bi-arrow-repeat spin"></i>
              Loading stops...
            </div>

            <div v-else-if="routeStopsFor(schedule.route?.id).length > 0" class="stops-timeline">
              <div
                v-for="(stop, idx) in routeStopsFor(schedule.route?.id)"
                :key="stop.id"
                class="stop-item"
                :class="{
                  'is-first': idx === 0,
                  'is-last': idx === routeStopsFor(schedule.route?.id).length - 1
                }"
              >
                <div class="stop-dot">
                  <div class="dot-inner"></div>
                </div>
                <div class="stop-line" v-if="idx < routeStopsFor(schedule.route?.id).length - 1"></div>
                <span class="stop-name">{{ stop.name }}</span>
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
          @click="loadMoreSchedules"
        >
          <i v-if="loadingMore" class="bi bi-arrow-repeat spin"></i>
          {{ loadingMore ? 'Loading more...' : 'Load More' }}
        </button>
      </div>
    </div>
  </div>
</template>
