import { defineStore } from 'pinia'
import api from '@/api/client'

export const useLocationStore = defineStore('location', {
  state: () => ({
    activeTrips: [],
    currentTrip: null,
    busLocations: {},
    loading: false
  }),

  actions: {
    async fetchActiveTrips() {
      this.loading = true
      try {
        const response = await api.get('/student/trips/active')
        this.activeTrips = response.data
      } catch (error) {
        console.error('Error fetching active trips:', error)
      } finally {
        this.loading = false
      }
    },

    async fetchTripLocations(tripId) {
      try {
        const response = await api.get(`/student/trips/${tripId}/locations`)
        return response.data
      } catch (error) {
        console.error('Error fetching trip locations:', error)
        throw error
      }
    },

    updateBusLocation(tripId, location) {
      this.busLocations = {
        ...this.busLocations,
        [tripId]: location
      }
    },

    setCurrentTrip(trip) {
      this.currentTrip = trip
    }
  }
})
