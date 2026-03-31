<script setup>
import { computed, onMounted } from 'vue'
import { useDriverTripStore } from '@/stores/useDriverTripStore'

const driverTripStore = useDriverTripStore()

const historyEntries = computed(() => {
  const grouped = new Map()

  for (const trip of driverTripStore.historyTrips) {
    const busKey = trip.bus?.id ?? trip.bus_id ?? trip.bus?.code ?? trip.id
    const tripDate = trip.trip_date || 'unknown-date'
    const key = `${busKey}-${tripDate}`
    const routeName = trip.route?.name
    const startedAt = trip.started_at ? toLocalDate(trip.started_at) : null
    const endedAt = trip.ended_at ? toLocalDate(trip.ended_at) : null
    const sortTime = endedAt?.getTime() ?? startedAt?.getTime() ?? 0

    if (!grouped.has(key)) {
      grouped.set(key, {
        ...trip,
        historyKey: key,
        routeNames: routeName ? [routeName] : [],
        tripCount: 1,
        _sortTime: sortTime,
      })
      continue
    }

    const existing = grouped.get(key)

    existing.tripCount += 1

    if (routeName && !existing.routeNames.includes(routeName)) {
      existing.routeNames.push(routeName)
    }

    if (trip.started_at && (!existing.started_at || startedAt < toLocalDate(existing.started_at))) {
      existing.started_at = trip.started_at
    }

    if (trip.ended_at && (!existing.ended_at || endedAt > toLocalDate(existing.ended_at))) {
      existing.ended_at = trip.ended_at
    }

    if (sortTime >= existing._sortTime) {
      existing.id = trip.id
      existing.bus = trip.bus
      existing.route = trip.route
      existing.status = trip.status
      existing._sortTime = sortTime
    }
  }

  return Array.from(grouped.values()).sort((a, b) => {
    const dateDiff = toLocalDate(b.trip_date) - toLocalDate(a.trip_date)
    if (dateDiff !== 0) return dateDiff
    return (b._sortTime || 0) - (a._sortTime || 0)
  })
})

const isInitialLoading = computed(() => driverTripStore.historyLoading && historyEntries.value.length === 0)
const hasLoadedTrips = computed(() => historyEntries.value.length > 0)
const hasLoadMoreError = computed(() => !!driverTripStore.historyError && hasLoadedTrips.value)
const paginationLabel = computed(() => {
  const totalLoaded = historyEntries.value.length

  if (!totalLoaded) return ''

  const label = `${totalLoaded} bus-day record${totalLoaded === 1 ? '' : 's'} loaded`

  return driverTripStore.hasMoreHistory ? `${label} so far` : label
})

onMounted(async () => {
  await loadHistory()
})

const loadHistory = async () => {
  try {
    driverTripStore.resetHistory()
    await driverTripStore.fetchHistory(1)
  } catch (error) {
    console.error('Failed to load driver history:', error)
  }
}

const loadMore = async () => {
  if (!driverTripStore.hasMoreHistory || driverTripStore.historyLoading) return

  try {
    await driverTripStore.fetchHistory(driverTripStore.historyPagination.currentPage + 1, {
      append: true
    })
  } catch (error) {
    console.error('Failed to load more history:', error)
  }
}

const getBusDisplay = (trip) => {
  const code = trip.bus?.code
  const displayName = trip.bus?.display_name

  if (code && displayName) return `${code} - ${displayName}`
  return displayName || trip.bus?.plate_number || trip.bus?.code || 'Unknown Bus'
}

const getStatusText = (status) => {
  if (!status) return 'Unknown'
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const getRouteSummary = (entry) => {
  if (!entry.routeNames?.length) return 'Unknown Route'
  if (entry.routeNames.length === 1) return entry.routeNames[0]
  return `${entry.routeNames.length} routes that day`
}

const toLocalDate = (value) => {
  if (!value) return null

  if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    return new Date(`${value}T12:00:00`)
  }

  return new Date(value)
}

const formatDate = (value) => {
  if (!value) return 'Unknown date'

  return toLocalDate(value).toLocaleDateString([], {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const formatTime = (value) => {
  if (!value) return 'N/A'

  return toLocalDate(value).toLocaleTimeString([], {
    hour: 'numeric',
    minute: '2-digit'
  })
}

const formatDuration = (trip) => {
  if (!trip.started_at || !trip.ended_at) return 'Duration unavailable'

  const started = toLocalDate(trip.started_at)
  const ended = toLocalDate(trip.ended_at)
  const diffMs = ended - started

  if (Number.isNaN(diffMs) || diffMs <= 0) return 'Duration unavailable'

  const totalMinutes = Math.round(diffMs / 60000)
  const hours = Math.floor(totalMinutes / 60)
  const minutes = totalMinutes % 60

  if (hours > 0) {
    return `${hours}h ${minutes}m`
  }

  return `${minutes}m`
}

const formatTripCount = (count) => {
  return `${count} trip${count === 1 ? '' : 's'}`
}
</script>

<template>
  <div class="container mx-auto px-4 py-6">
    <div class="trip-status-card history-summary-card">
      <div class="trip-status-header">
        <div class="trip-status-icon">
          <i class="bi bi-clock-history"></i>
        </div>
        <div>
          <div class="trip-status-title">Trip History</div>
          <div class="history-summary-text">
            Your completed and cancelled driving sessions.
          </div>
        </div>
      </div>

      <div v-if="paginationLabel" class="history-summary-meta">
        {{ paginationLabel }}
      </div>
    </div>

    <div v-if="isInitialLoading" class="space-y-3">
      <div class="skeleton-card" style="height: 140px;"></div>
      <div class="skeleton-card" style="height: 140px;"></div>
      <div class="skeleton-card" style="height: 140px;"></div>
    </div>

    <div v-else-if="driverTripStore.historyError && !hasLoadedTrips" class="empty-state">
      <i class="bi bi-exclamation-triangle empty-state-icon"></i>
      <h3 class="empty-state-title">Unable to Load History</h3>
      <p class="empty-state-text">{{ driverTripStore.historyError }}</p>
      <button
        @click="loadHistory"
        class="history-action-button"
      >
        Retry
      </button>
    </div>

    <div v-else-if="!hasLoadedTrips" class="empty-state">
      <i class="bi bi-clock-history empty-state-icon"></i>
      <h3 class="empty-state-title">No Trip History Yet</h3>
      <p class="empty-state-text">
        Finished trips will appear here once you complete or cancel a route.
      </p>
    </div>

    <div v-else class="history-list">
      <article
        v-for="trip in historyEntries"
        :key="trip.historyKey"
        class="history-card"
      >
        <div class="history-card-top">
          <div class="history-card-icon">
            <i class="bi bi-bus-front-fill"></i>
          </div>

          <div class="history-card-copy">
            <div class="history-card-bus">{{ getBusDisplay(trip) }}</div>
            <div class="history-card-day">{{ formatDate(trip.trip_date) }}</div>
            <div class="history-card-route">{{ getRouteSummary(trip) }}</div>
          </div>

          <span class="history-status-badge" :class="trip.status">
            {{ getStatusText(trip.status) }}
          </span>
        </div>

        <div class="history-meta-grid">
          <div class="history-meta-item">
            <span class="history-meta-label">Started</span>
            <span class="history-meta-value">{{ formatTime(trip.started_at) }}</span>
          </div>
          <div class="history-meta-item">
            <span class="history-meta-label">Ended</span>
            <span class="history-meta-value">{{ formatTime(trip.ended_at) }}</span>
          </div>
          <div class="history-meta-item">
            <span class="history-meta-label">Duration</span>
            <span class="history-meta-value">{{ formatDuration(trip) }}</span>
          </div>
          <div class="history-meta-item">
            <span class="history-meta-label">Recorded</span>
            <span class="history-meta-value">{{ formatTripCount(trip.tripCount) }}</span>
          </div>
        </div>
      </article>

      <p v-if="hasLoadMoreError" class="history-inline-error">
        {{ driverTripStore.historyError }}
      </p>

      <button
        v-if="driverTripStore.hasMoreHistory"
        @click="loadMore"
        class="history-action-button history-load-more"
        :disabled="driverTripStore.historyLoading"
      >
        <i v-if="driverTripStore.historyLoading" class="bi bi-arrow-repeat animate-spin"></i>
        <i v-else class="bi bi-clock-history"></i>
        <span>{{ driverTripStore.historyLoading ? 'Loading...' : 'Load More' }}</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.history-summary-card {
  margin-bottom: 18px;
}

.history-summary-text {
  margin-top: 4px;
  font-size: 0.9rem;
  color: var(--gray-500);
}

.history-summary-meta {
  font-size: 0.85rem;
  color: var(--gray-500);
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding-bottom: 18px;
}

.history-card {
  background: var(--white);
  border-radius: var(--radius-md);
  padding: 18px;
  box-shadow: var(--shadow-sm);
}

.history-card-top {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.history-card-icon {
  width: 46px;
  height: 46px;
  border-radius: 14px;
  background: var(--primary-50);
  color: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  flex-shrink: 0;
}

.history-card-copy {
  flex: 1;
  min-width: 0;
}

.history-card-bus {
  font-size: 1rem;
  font-weight: 700;
  color: var(--gray-900);
}

.history-card-route {
  margin-top: 4px;
  font-size: 0.85rem;
  color: var(--gray-500);
  line-height: 1.4;
}

.history-card-day {
  margin-top: 3px;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  color: var(--primary-dark);
}

.history-status-badge {
  flex-shrink: 0;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  background: var(--gray-100);
  color: var(--gray-600);
}

.history-status-badge.completed {
  background: rgba(16, 185, 129, 0.12);
  color: var(--primary-dark);
}

.history-status-badge.cancelled {
  background: rgba(239, 68, 68, 0.12);
  color: #dc2626;
}

.history-meta-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.history-meta-item {
  padding: 12px;
  border-radius: 12px;
  background: var(--gray-50);
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.history-meta-label {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--gray-500);
}

.history-meta-value {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--gray-900);
}

.history-inline-error {
  margin: 0;
  text-align: center;
  font-size: 0.9rem;
  color: var(--danger);
}

.history-action-button {
  width: 100%;
  border: none;
  border-radius: 14px;
  padding: 14px 18px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  color: var(--white);
  font-size: 0.95rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  box-shadow: var(--shadow-sm);
}

.history-action-button:disabled {
  opacity: 0.7;
}

.history-load-more {
  margin-top: 4px;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}
</style>
