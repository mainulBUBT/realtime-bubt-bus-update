import { defineStore } from 'pinia'
import api from '@/api/client'

export const useDriverTripStore = defineStore('driverTrip', {
  state: () => ({
    currentTrip: null,
    selectedBus: null,
    selectedDirection: null,
    availableBuses: [],
    availableRoutes: [],
    historyTrips: [],
    historyPagination: {
      currentPage: 1,
      lastPage: 1,
      total: 0,
      perPage: 15,
      from: null,
      to: null
    },
    historyLoading: false,
    historyError: null,
    loading: false,
    error: null,
    // Cache timestamps to prevent redundant API calls
    apiCache: {
      currentTrip: null,
      buses: null,
      routes: null
    }
  }),

  getters: {
    hasActiveTrip: (state) => !!state.currentTrip,
    canStartTrip: (state) => !state.currentTrip && state.selectedBus && state.selectedDirection,
    availableBusesCount: (state) => state.availableBuses.length,
    hasMoreHistory: (state) => state.historyPagination.currentPage < state.historyPagination.lastPage
  },

  actions: {
    async fetchCurrentTrip({ force = false } = {}) {
      // Cache for 30 seconds to prevent redundant API calls
      if (!force && this.apiCache.currentTrip && (Date.now() - this.apiCache.currentTrip) < 30000) {
        return this.currentTrip
      }

      this.loading = true
      this.error = null
      try {
        const response = await api.get('/driver/trips/current')
        this.currentTrip = response.data
        this.apiCache.currentTrip = Date.now()  // Update cache timestamp
        return response.data
      } catch (error) {
        // No active trip is not an error
        this.currentTrip = null
        this.apiCache.currentTrip = Date.now()  // Still cache the "null" result
        return null
      } finally {
        this.loading = false
      }
    },

    async fetchAvailableBuses() {
      // Cache for 60 seconds to prevent redundant API calls
      if (this.apiCache.buses && (Date.now() - this.apiCache.buses) < 60000) {
        return this.availableBuses
      }

      this.loading = true
      this.error = null
      try {
        const response = await api.get('/driver/buses')
        this.availableBuses = response.data
        this.apiCache.buses = Date.now()  // Update cache timestamp
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch buses'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchRoutes(busId = null) {
      // Cache for 60 seconds to prevent redundant API calls
      if (this.apiCache.routes && (Date.now() - this.apiCache.routes) < 60000) {
        return this.availableRoutes
      }

      this.loading = true
      this.error = null
      try {
        const params = busId ? { bus_id: busId } : {}
        const response = await api.get('/driver/routes', { params })
        this.availableRoutes = response.data
        this.apiCache.routes = Date.now()  // Update cache timestamp
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch routes'
        throw error
      } finally {
        this.loading = false
      }
    },

    async startTrip() {
      if (!this.selectedBus || !this.selectedDirection) {
        throw new Error('Bus and direction must be selected')
      }

      this.loading = true
      this.error = null
      try {
        const response = await api.post('/driver/trips/start', {
          bus_id: this.selectedBus.id,
          route_id: this.selectedDirection.routeId
        })
        this.currentTrip = response.data.trip ?? response.data
        this.apiCache.currentTrip = Date.now()  // Update cache after starting trip
        this.clearSelection()
        return this.currentTrip
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to start trip'
        throw error
      } finally {
        this.loading = false
      }
    },

    async endTrip(tripId) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post(`/driver/trips/${tripId}/end`)
        this.currentTrip = null
        this.apiCache.currentTrip = Date.now()  // Update cache after ending trip
        return response.data.trip ?? response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to end trip'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchHistory(page = 1, { append = false } = {}) {
      const shouldAppend = append || page > 1

      this.historyLoading = true
      this.historyError = null

      try {
        const response = await api.get('/driver/trips/history', {
          params: { page }
        })

        const payload = response.data || {}
        const items = payload.data || []

        this.historyTrips = shouldAppend
          ? [...this.historyTrips, ...items]
          : items

        this.historyPagination = {
          currentPage: payload.current_page ?? page,
          lastPage: payload.last_page ?? 1,
          total: payload.total ?? items.length,
          perPage: payload.per_page ?? items.length,
          from: payload.from ?? null,
          to: payload.to ?? null
        }

        return payload
      } catch (error) {
        if (!shouldAppend) {
          this.historyTrips = []
          this.historyPagination = {
            currentPage: 1,
            lastPage: 1,
            total: 0,
            perPage: 15,
            from: null,
            to: null
          }
        }

        this.historyError = error.response?.data?.message || 'Failed to fetch trip history'
        throw error
      } finally {
        this.historyLoading = false
      }
    },

    resetHistory() {
      this.historyTrips = []
      this.historyPagination = {
        currentPage: 1,
        lastPage: 1,
        total: 0,
        perPage: 15,
        from: null,
        to: null
      }
      this.historyError = null
    },

    setSelectedBus(bus) {
      this.selectedBus = bus
      this.selectedDirection = null
      this.apiCache.buses = null  // Invalidate buses cache when selection changes
      this.apiCache.routes = null  // Invalidate routes cache to force fetch with bus filter
    },

    setSelectedDirection(direction) {
      this.selectedDirection = direction
    },

    clearSelection() {
      this.selectedBus = null
      this.selectedDirection = null
    }
  }
})
