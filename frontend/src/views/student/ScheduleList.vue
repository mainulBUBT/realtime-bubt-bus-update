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
            <div class="stops-header">
              <i class="bi bi-signpost-split"></i>
              <span>{{ schedule.route.stops.length }} Stops</span>
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

<style scoped>
.schedule-page {
  padding: 16px;
  padding-bottom: calc(var(--mobile-nav-height, 56px) + 24px);
  min-height: 100%;
  background: var(--gray-50, #F8FAFC);
}

/* Header */
.schedule-header {
  margin-bottom: 20px;
}

.schedule-header-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.schedule-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--gray-900, #0F172A);
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0;
}

.schedule-title i {
  color: var(--primary, #10B981);
}

.schedule-day-badge {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--primary-dark, #059669);
  background: var(--primary-light, #D1FAE5);
  padding: 6px 12px;
  border-radius: var(--radius-full, 9999px);
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Filter Tabs */
.filter-tabs {
  display: flex;
  gap: 8px;
  background: var(--white, #fff);
  padding: 4px;
  border-radius: var(--radius-md, 12px);
  box-shadow: var(--shadow-sm);
}

.filter-tab {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px 16px;
  border: none;
  background: transparent;
  border-radius: var(--radius-sm, 8px);
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--gray-500, #64748B);
  cursor: pointer;
  transition: all 200ms ease;
}

.filter-tab.active {
  background: var(--primary, #10B981);
  color: white;
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.filter-count {
  font-size: 0.7rem;
  background: rgba(255,255,255,0.25);
  padding: 1px 6px;
  border-radius: 10px;
  font-weight: 700;
}

.filter-tab:not(.active) .filter-count {
  background: var(--gray-100, #F1F5F9);
}

/* Schedule List */
.schedule-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Schedule Card */
.schedule-card {
  background: var(--white, #fff);
  border-radius: var(--radius-md, 12px);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--gray-100, #F1F5F9);
  overflow: hidden;
  transition: all 200ms ease;
}

.schedule-card.is-today {
  border-left: 3px solid var(--primary, #10B981);
}

.schedule-card.expanded {
  box-shadow: var(--shadow-md);
}

.schedule-card-main {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 16px;
  cursor: pointer;
}

/* Time Column */
.schedule-time-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 72px;
  flex-shrink: 0;
}

.schedule-time {
  font-size: 1rem;
  font-weight: 700;
  color: var(--primary, #10B981);
  line-height: 1.2;
}

.schedule-time-label {
  font-size: 0.65rem;
  color: var(--gray-400, #94A3B8);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
}

/* Info Column */
.schedule-info-col {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.schedule-bus-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--gray-700, #334155);
  background: var(--gray-50, #F8FAFC);
  padding: 4px 10px;
  border-radius: var(--radius-sm, 8px);
  width: fit-content;
}

.schedule-bus-badge i {
  color: var(--primary, #10B981);
}

.bus-capacity {
  font-size: 0.7rem;
  color: var(--gray-400, #94A3B8);
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 2px;
  margin-left: 4px;
}

.schedule-route {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.route-name {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--gray-800, #1E293B);
}

.route-direction {
  font-size: 0.75rem;
  color: var(--gray-500, #64748B);
  display: flex;
  align-items: center;
  gap: 4px;
  flex-wrap: wrap;
}

.route-direction i {
  font-size: 0.65rem;
  color: var(--primary, #10B981);
}

/* Weekday Pills */
.weekday-pills {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}

.weekday-pill {
  font-size: 0.65rem;
  font-weight: 600;
  text-transform: uppercase;
  padding: 2px 8px;
  border-radius: var(--radius-full, 9999px);
  background: var(--gray-100, #F1F5F9);
  color: var(--gray-400, #94A3B8);
}

.weekday-pill.active {
  background: var(--primary, #10B981);
  color: white;
}

/* Expand Arrow */
.schedule-expand {
  color: var(--gray-400, #94A3B8);
  font-size: 1rem;
  flex-shrink: 0;
  padding-top: 4px;
  transition: transform 200ms ease;
}

/* Stops Section */
.schedule-stops {
  border-top: 1px solid var(--gray-100, #F1F5F9);
  padding: 16px;
  background: var(--gray-50, #F8FAFC);
}

.stops-header {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--gray-600, #475569);
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 16px;
}

.stops-header i {
  color: var(--primary, #10B981);
}

/* Timeline */
.stops-timeline {
  padding-left: 8px;
}

.stop-item {
  position: relative;
  padding-left: 28px;
  padding-bottom: 20px;
  min-height: 32px;
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
  background: transparent;
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

.stop-name {
  font-size: 0.825rem;
  color: var(--gray-700, #334155);
  line-height: 1.4;
}

.stop-item.is-first .stop-name,
.stop-item.is-last .stop-name {
  font-weight: 600;
  color: var(--gray-900, #0F172A);
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
  padding-top: 0;
  padding-bottom: 0;
}

/* Empty State */
.schedule-empty {
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

.schedule-empty h3 {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--gray-700, #334155);
  margin-bottom: 8px;
}

.schedule-empty p {
  font-size: 0.875rem;
  color: var(--gray-500, #64748B);
  margin: 0;
}

.link-btn {
  background: none;
  border: none;
  color: var(--primary, #10B981);
  font-weight: 600;
  cursor: pointer;
  text-decoration: underline;
  padding: 0;
  font-size: inherit;
}

/* Skeleton Loading */
.skeleton-schedule-card {
  background: var(--white, #fff);
  border-radius: var(--radius-md, 12px);
  padding: 16px;
  display: flex;
  gap: 16px;
}

.skeleton-time {
  width: 72px;
  height: 40px;
  background: linear-gradient(90deg, var(--gray-200, #E2E8F0) 25%, var(--gray-100, #F1F5F9) 50%, var(--gray-200, #E2E8F0) 75%);
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.5s ease-in-out infinite;
  border-radius: 8px;
  flex-shrink: 0;
}

.skeleton-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.skeleton-line {
  height: 14px;
  background: linear-gradient(90deg, var(--gray-200, #E2E8F0) 25%, var(--gray-100, #F1F5F9) 50%, var(--gray-200, #E2E8F0) 75%);
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.5s ease-in-out infinite;
  border-radius: 4px;
}

.w-60 { width: 60%; }
.w-80 { width: 80%; }
.w-40 { width: 40%; }

@keyframes skeleton-shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@media (min-width: 768px) {
  .schedule-page {
    padding: 24px;
    padding-bottom: 24px;
  }
}
</style>
