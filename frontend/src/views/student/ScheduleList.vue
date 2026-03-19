<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api/client'

const schedules = ref([])
const loading = ref(true)
const filter = ref('today') // 'today' | 'all'
const expandedIds = ref(new Set())

onMounted(async () => {
  await fetchSchedules()
})

const fetchSchedules = async () => {
  loading.value = true
  try {
    const response = await api.get('/student/schedules')
    schedules.value = response.data
  } catch (e) {
    console.error('Failed to fetch schedules:', e)
  } finally {
    loading.value = false
  }
}

const filteredSchedules = computed(() => {
  if (filter.value === 'today') {
    return schedules.value.filter(s => s.is_today)
  }
  return schedules.value
})

function toggleExpand(id) {
  const set = new Set(expandedIds.value)
  if (set.has(id)) set.delete(id)
  else set.add(id)
  expandedIds.value = set
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
  <div class="schedule-page">
    <!-- Page Header -->
    <div class="schedule-header">
      <div class="schedule-header-top">
        <h2 class="schedule-title">
          <i class="bi bi-calendar3"></i>
          Schedules
        </h2>
        <span class="schedule-day-badge">
          <i class="bi bi-calendar-check"></i>
          {{ todayName }}
        </span>
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
          <span v-if="!loading" class="filter-count">{{ schedules.filter(s => s.is_today).length }}</span>
        </button>
        <button
          class="filter-tab"
          :class="{ active: filter === 'all' }"
          @click="filter = 'all'"
        >
          <i class="bi bi-calendar-week"></i>
          All Days
          <span v-if="!loading" class="filter-count">{{ schedules.length }}</span>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="schedule-list">
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
    <div v-else-if="filteredSchedules.length === 0" class="schedule-empty">
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
        v-for="schedule in filteredSchedules"
        :key="schedule.id"
        class="schedule-card"
        :class="{ expanded: expandedIds.has(schedule.id), 'is-today': schedule.is_today }"
      >
        <!-- Main Content -->
        <div class="schedule-card-main" @click="toggleExpand(schedule.id)">
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
          <div v-if="expandedIds.has(schedule.id) && schedule.route?.stops?.length" class="schedule-stops">
            <div class="stops-header" @click="toggleExpand(schedule.id)">
              <span>{{ schedule.route.stops.length }} Stops</span>
              <i class="bi bi-chevron-down"></i>
            </div>
            <div class="stops-timeline">
              <div
                v-for="(stop, idx) in schedule.route.stops"
                :key="stop.id"
                class="stop-item"
                :class="{
                  'is-first': idx === 0,
                  'is-last': idx === schedule.route.stops.length - 1
                }"
              >
                <div class="stop-dot">
                  <div class="dot-inner"></div>
                </div>
                <div class="stop-line" v-if="idx < schedule.route.stops.length - 1"></div>
                <span class="stop-name">{{ stop.name }}</span>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </div>
  </div>
</template>
