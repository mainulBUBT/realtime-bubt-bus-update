import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api/client'

export const useNotificationStore = defineStore('notifications', () => {
  const notifications = ref([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const loadingMore = ref(false)
  const currentPage = ref(1)
  const hasMore = ref(false)
  const total = ref(0)
  const PER_PAGE = 20

  async function fetchNotifications({ page = 1, replace = true } = {}) {
    if (loading.value) return
    loading.value = true

    try {
      const response = await api.get('/student/notifications', {
        params: { page, per_page: PER_PAGE }
      })

      const { data: items, meta } = response.data

      if (replace || page === 1) {
        notifications.value = items
      } else {
        notifications.value.push(...items)
      }

      currentPage.value = meta.current_page
      hasMore.value = meta.has_more
      total.value = meta.total
      unreadCount.value = meta.unread_count
    } catch (error) {
      console.error('Failed to fetch notifications:', error)
    } finally {
      loading.value = false
    }
  }

  async function fetchUnreadCount() {
    try {
      const response = await api.get('/student/notifications/unread-count')
      unreadCount.value = response.data.unread_count
    } catch (error) {
      console.error('Failed to fetch unread count:', error)
    }
  }

  async function markAsRead(id) {
    try {
      const response = await api.post(`/student/notifications/${id}/read`)
      const notification = notifications.value.find(n => n.id === id)
      if (notification && !notification.read_at) {
        notification.read_at = new Date().toISOString()
      }
      unreadCount.value = response.data.unread_count ?? Math.max(0, unreadCount.value - 1)
    } catch (error) {
      console.error('Failed to mark as read:', error)
    }
  }

  async function markAllAsRead() {
    try {
      await api.post('/student/notifications/read-all')
      notifications.value.forEach(n => {
        if (!n.read_at) n.read_at = new Date().toISOString()
      })
      unreadCount.value = 0
    } catch (error) {
      console.error('Failed to mark all as read:', error)
    }
  }

  async function loadMore() {
    if (loadingMore.value || !hasMore.value) return
    loadingMore.value = true

    try {
      const nextPage = currentPage.value + 1
      const response = await api.get('/student/notifications', {
        params: { page: nextPage, per_page: PER_PAGE }
      })

      const { data: items, meta } = response.data
      notifications.value.push(...items)
      currentPage.value = meta.current_page
      hasMore.value = meta.has_more
      total.value = meta.total
      unreadCount.value = meta.unread_count
    } catch (error) {
      console.error('Failed to load more notifications:', error)
    } finally {
      loadingMore.value = false
    }
  }

  function incrementUnread() {
    unreadCount.value++
  }

  return {
    notifications,
    unreadCount,
    loading,
    loadingMore,
    currentPage,
    hasMore,
    total,
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    loadMore,
    incrementUnread
  }
})
