import { ref } from 'vue'
import { FirebaseMessaging } from '@capacitor-firebase/messaging'
import { LocalNotifications } from '@capacitor/local-notifications'
import { Capacitor } from '@capacitor/core'

export function useFirebaseMessaging() {
  const token = ref(null)
  const error = ref(null)
  const isPermissionGranted = ref(false)

  /**
   * Request notification permission and get FCM token
   */
  const requestPermission = async () => {
    try {
      const permissionResult = await FirebaseMessaging.requestPermission()

      if (permissionResult.receive === 'granted') {
        isPermissionGranted.value = true
        return true
      }

      return false
    } catch (err) {
      error.value = err
      console.error('Failed to request notification permission:', err)
      return false
    }
  }

  /**
   * Get the FCM token
   */
  const getToken = async () => {
    try {
      const result = await FirebaseMessaging.getToken()
      token.value = result.token
      return result.token
    } catch (err) {
      error.value = err
      console.error('Failed to get FCM token:', err)
      throw err
    }
  }

  /**
   * Subscribe to a topic
   */
  const subscribeToTopic = async (topic) => {
    try {
      await FirebaseMessaging.subscribeToTopic({ topic })
      console.log(`Subscribed to topic: ${topic}`)
    } catch (err) {
      error.value = err
      console.error(`Failed to subscribe to topic ${topic}:`, err)
    }
  }

  /**
   * Unsubscribe from a topic
   */
  const unsubscribeFromTopic = async (topic) => {
    try {
      await FirebaseMessaging.unsubscribeFromTopic({ topic })
      console.log(`Unsubscribed from topic: ${topic}`)
    } catch (err) {
      error.value = err
      console.error(`Failed to unsubscribe from topic ${topic}:`, err)
    }
  }

  /**
   * Create notification channel for Android 8+
   */
  const createNotificationChannel = async () => {
    if (!Capacitor.isNativePlatform() || Capacitor.getPlatform() !== 'android') return

    try {
      await LocalNotifications.createChannel({
        id: 'bus_tracker_notifications',
        name: 'Bus Tracker Notifications',
        description: 'Notifications from BUBT Bus Tracker',
        importance: 5,
        visibility: 1,
        lights: true,
        lightColor: '#10B981',
        vibration: true,
      })
    } catch (err) {
      console.warn('Failed to create notification channel:', err)
    }
  }

  /**
   * Listen for incoming FCM messages (foreground)
   */
  const onMessage = (onMessage) => {
    FirebaseMessaging.addListener('notificationReceived', (event) => {
      console.log('FCM message received:', event.notification)

      // Show local notification for foreground messages
      LocalNotifications.schedule({
        notifications: [
          {
            id: Date.now(),
            title: event.notification.title,
            body: event.notification.body,
            channelId: 'bus_tracker_notifications',
            extra: event.notification
          }
        ]
      })

      if (onMessage && typeof onMessage === 'function') {
        onMessage(event.notification)
      }
    })
  }

  /**
   * Listen for when user taps on a notification
   */
  const onNotificationClick = (onClick) => {
    FirebaseMessaging.addListener('notificationActionPerformed', (event) => {
      console.log('Notification clicked:', event.notification)

      if (onClick && typeof onClick === 'function') {
        onClick(event.notification)
      }
    })
  }

  /**
   * Delete the FCM token
   */
  const deleteToken = async () => {
    try {
      await FirebaseMessaging.deleteToken()
      token.value = null
    } catch (err) {
      error.value = err
      console.error('Failed to delete FCM token:', err)
    }
  }

  /**
   * Initialize Firebase messaging with all listeners
   */
  const initialize = async (options = {}) => {
    try {
      // Create notification channel (Android)
      await createNotificationChannel()

      // Request permission
      const granted = await requestPermission()
      if (!granted) {
        throw new Error('Notification permission not granted')
      }

      // Get token
      const fcmToken = await getToken()

      // Subscribe to topic if provided
      if (options.topic) {
        await subscribeToTopic(options.topic)
      }

      // Set up message listeners
      if (options.onMessage) {
        onMessage(options.onMessage)
      }

      if (options.onNotificationClick) {
        onNotificationClick(options.onNotificationClick)

        // Also handle local notification clicks (foreground path)
        LocalNotifications.addListener('localNotificationActionPerformed', (event) => {
          if (options.onNotificationClick && typeof options.onNotificationClick === 'function') {
            options.onNotificationClick(event.notification?.extra || event.notification)
          }
        })
      }

      return { token: fcmToken, success: true }
    } catch (err) {
      error.value = err
      console.error('Failed to initialize Firebase messaging:', err)
      return { token: null, success: false, error: err }
    }
  }

  return {
    token,
    error,
    isPermissionGranted,
    requestPermission,
    getToken,
    subscribeToTopic,
    unsubscribeFromTopic,
    onMessage,
    onNotificationClick,
    deleteToken,
    initialize
  }
}
