import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/client'
import echo from '@/plugins/echo'

export const useMapStore = defineStore('map', () => {
  // ── state ─────────────────────────────────────────────
  const trips = ref([])          // raw API trip objects (with latestLocation)
  const loading = ref(false)
  const lastFetchFailed = ref(false)
  const lastSuccessfulFetchAt = ref(null)
  const consecutiveFetchFailures = ref(0)
  const selectedTripId = ref(null)  // null = no selection, number = follow this trip
  const showTimeline = ref(false)   // true only when map marker icon is clicked

  // Last WS location update — MapView watches this to follow the selected bus
  const lastLocationUpdate = ref(null)  // { tripId, busId, lat, lng }

  // Track active channel subscriptions so we can leave stale ones
  const subscribedBusIds = new Set()

  // ── derived bus list for sidebar ─────────────────────
  const buses = computed(() =>
    trips.value.map(trip => {
      // Normalize API status: 'ongoing' → 'active'
      const rawStatus = trip.status || 'active'
      const status = rawStatus === 'ongoing' ? 'active' : rawStatus

      const plateOrCode = trip.bus?.plate_number || trip.bus?.code || `Bus${trip.id}`
      return {
        id: trip.id,
        tripId: trip.id,
        code: plateOrCode,
        shortCode: shortCode(plateOrCode),
        name: trip.bus?.display_name || trip.bus?.name || trip.route?.name || 'Unknown',
        route: trip.route?.name || 'Unknown Route',
        status,
        eta: trip.latestLocation?.recorded_at
          ? `Updated ${timeAgo(trip.latestLocation.recorded_at)}`
          : (trip.latest_location?.recorded_at
            ? `Updated ${timeAgo(trip.latest_location.recorded_at)}`
            : 'No recent updates'),
        lat: trip.latestLocation?.lat ?? trip.latest_location?.lat ?? null,
        lng: trip.latestLocation?.lng ?? trip.latest_location?.lng ?? null,
      }
    })
  )

  const activeCount   = computed(() => buses.value.filter(b => b.status === 'active').length)
  const delayedCount  = computed(() => buses.value.filter(b => b.status === 'delayed').length)
  const inactiveCount = computed(() => buses.value.filter(b => b.status === 'inactive').length)

  // Full raw trip object for the selected bus (used by TimelinePanel)
  const selectedTrip  = computed(() =>
    selectedTripId.value ? trips.value.find(t => t.id === selectedTripId.value) ?? null : null
  )

  // ── actions ───────────────────────────────────────────
  async function fetchTrips() {
    loading.value = true
    try {
      const res = await api.get('/student/trips/active')
      trips.value = res.data || []
      lastFetchFailed.value = false
      consecutiveFetchFailures.value = 0
      lastSuccessfulFetchAt.value = new Date().toISOString()
      console.log('[MapStore] fetched', trips.value.length, 'trips')
      subscribeToTrips(trips.value)
    } catch (e) {
      console.error('[MapStore] fetch failed:', e?.response?.status, e?.message)
      lastFetchFailed.value = true
      consecutiveFetchFailures.value += 1

      // Keep the last known good trip list on transient failures so
      // the student map does not flicker buses off and back on.
      if (e?.response?.status === 401) {
        trips.value = []
        selectedTripId.value = null
        showTimeline.value = false
        lastSuccessfulFetchAt.value = null
      }
    } finally {
      loading.value = false
    }
  }

  /**
   * Subscribe to Reverb public channels for each active trip's bus.
   * Leaves channels for buses that are no longer active.
   */
  function subscribeToTrips(tripList) {
    const activeBusIds = new Set(tripList.map(t => t.bus_id).filter(Boolean))

    // Leave channels for buses no longer active
    for (const busId of subscribedBusIds) {
      if (!activeBusIds.has(busId)) {
        echo.leave(`bus.${busId}`)
        subscribedBusIds.delete(busId)
        console.log('[Echo] left channel bus.' + busId)
      }
    }

    // Subscribe to channels for new active buses
    for (const trip of tripList) {
      const busId = trip.bus_id
      if (!busId || subscribedBusIds.has(busId)) continue

      echo.channel(`bus.${busId}`)
        .listen('.BusLocationUpdated', (payload) => {
          console.log('[Echo] BusLocationUpdated bus', busId, payload)
          handleLocationUpdate(busId, payload)
        })
        .listen('.BusTripEnded', (payload) => {
          console.log('[Echo] BusTripEnded bus', busId, payload)
          // Remove the trip from the list — student app updates instantly
          trips.value = trips.value.filter(t => t.id !== payload.trip_id)
          // Unsubscribe — this bus has no more activity
          echo.leave(`bus.${busId}`)
          subscribedBusIds.delete(busId)
          console.log('[Echo] left channel bus.' + busId + ' (trip ended)')
        })

      subscribedBusIds.add(busId)
      console.log('[Echo] subscribed to channel bus.' + busId)
    }
  }

  /**
   * Patch the in-memory trip's latestLocation when a WebSocket event arrives.
   * This triggers the computed `buses` to recompute → map markers update live.
   */
  function handleLocationUpdate(busId, payload) {
    const idx = trips.value.findIndex(t => t.bus_id === busId)
    if (idx === -1) return

    // Patch in place so Vue reactivity picks it up
    trips.value[idx] = {
      ...trips.value[idx],
      latestLocation: {
        lat:         payload.lat,
        lng:         payload.lng,
        speed:       payload.speed,
        recorded_at: payload.updated_at,
      },
    }

    // Signal MapView to follow if this bus is currently selected
    const tripId = trips.value[idx].id
    lastLocationUpdate.value = {
      tripId,
      busId,
      lat: payload.lat,
      lng: payload.lng,
    }
  }

  function unsubscribeAll() {
    for (const busId of subscribedBusIds) {
      echo.leave(`bus.${busId}`)
    }
    subscribedBusIds.clear()
  }

  function selectBus(tripId, openTimeline = false) {
    if (tripId === selectedTripId.value && !openTimeline) {
      selectedTripId.value = null
      showTimeline.value = false
    } else {
      selectedTripId.value = tripId
      showTimeline.value = openTimeline
    }
  }

  function clearSelection() {
    selectedTripId.value = null
    showTimeline.value = false
  }

  // ── helpers ───────────────────────────────────────────
  function shortCode(plate) {
    if (!plate) return '?'
    const parts = plate.split('-')
    if (parts.length <= 2) return plate.slice(0, 8)
    return parts.slice(-2).join('-').slice(0, 9)
  }

  function timeAgo(dateString) {
    if (!dateString) return 'No updates'
    const diffMins = Math.floor((Date.now() - new Date(dateString)) / 60000)
    if (diffMins < 1) return 'just now'
    if (diffMins === 1) return '1 min ago'
    if (diffMins < 60) return `${diffMins} mins ago`
    return 'over 1 hr ago'
  }

  return {
    trips, buses, loading, lastFetchFailed, lastSuccessfulFetchAt, consecutiveFetchFailures, selectedTripId, selectedTrip, showTimeline, lastLocationUpdate,
    activeCount, delayedCount, inactiveCount,
    fetchTrips, selectBus, clearSelection, unsubscribeAll,
  }
})
