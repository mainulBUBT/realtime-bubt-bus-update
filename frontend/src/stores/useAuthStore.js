import { defineStore } from 'pinia'
import api from '@/api/client'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('auth_token') || null
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    userRole: (state) => state.user?.role || null,
    isDriver: (state) => state.user?.role === 'driver',
    isStudent: (state) => state.user?.role === 'student',
    isAdmin: (state) => state.user?.role === 'admin'
  },

  actions: {
    async login(credentials) {
      try {
        const response = await api.post('/auth/login', credentials)
        this.user = response.data.user
        this.token = response.data.token
        localStorage.setItem('auth_token', this.token)
        localStorage.setItem('user', JSON.stringify(this.user))
        return response.data
      } catch (error) {
        if (import.meta.env.DEV) {
          console.error('Login failed:', error.response?.status)
        }
        throw error.response?.data || { message: 'Login failed' }
      }
    },

    async register(data) {
      try {
        const response = await api.post('/auth/register', data)

        if (response.data.token) {
          this.user = response.data.user
          this.token = response.data.token
          localStorage.setItem('auth_token', this.token)
          localStorage.setItem('user', JSON.stringify(this.user))
        } else {
          this.user = null
          this.token = null
          localStorage.removeItem('auth_token')
          localStorage.removeItem('user')
        }

        return response.data
      } catch (error) {
        if (import.meta.env.DEV) {
          console.error('Registration failed:', error.response?.status)
        }
        throw error.response?.data || { message: 'Registration failed' }
      }
    },

    async logout() {
      try {
        await api.post('/auth/logout')
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.user = null
        this.token = null
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user')
      }
    },

    async fetchMe() {
      try {
        const response = await api.get('/auth/me')
        this.user = response.data
        localStorage.setItem('user', JSON.stringify(this.user))
      } catch (error) {
        this.logout()
      }
    },

    loadUserFromStorage() {
      const savedUser = localStorage.getItem('user')
      if (savedUser) {
        this.user = JSON.parse(savedUser)
      }
    }
  }
})
