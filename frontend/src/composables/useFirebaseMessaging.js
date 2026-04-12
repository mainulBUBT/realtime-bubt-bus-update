import { ref } from 'vue'
import { FirebaseMessaging } from '@capacitor-firebase/messaging'
import { LocalNotifications } from '@capacitor/local-notifications'
import { Capacitor } from '@capacitor/core'

let messageListenerRegistered = false
let notificationClickListenerRegistered = false
let localNotificationClickListenerRegistered = false
let nextLocalNotificationId = 1

function getSafeNotificationId() {
  // Capacitor LocalNotifications on Android requires a Java int.
  nextLocalNotificationId = (nextLocalNotificationId % 2147483647) + 1
  return nextLocalNotificationId
}

function isNative() {
  return Capacitor.isNativePlatform()
}

function extractNotificationPayload(notification = {}) {
  const data = notification.data || notification.extra || {}

  return {
    title: notification.title,
    body: notification.body,
    image: notification.image || data.image || data.imageUrl || data.image_url || null,
    data
  }
}

export function useFirebaseMessaging() {
  const token = ref(null)
  const error = ref(null)
  const isPermissionGranted = ref(false)

  /**
   * Request notification permission and get FCM token
   */
  const requestPermission = async () => {
    if (!isNative()) {
      // Web: use browser Notification API
      if (typeof Notification === 'undefined') return false
      const result = await Notification.requestPermission()
      isPermissionGranted.value = result === 'granted'
      return result === 'granted'
    }

    try {
      const permissionState = await FirebaseMessaging.checkPermissions()
      console.info('FCM permission state before request:', permissionState.receive)

      const permissionResult = await FirebaseMessaging.requestPermissions()

      if (permissionResult.receive === 'granted') {
        isPermissionGranted.value = true
        return true
      }

      console.warn('FCM permission not granted:', permissionResult.receive)
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
    if (!isNative()) return null

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
    if (!isNative()) return

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
    if (!isNative()) return

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
    if (!isNative() || Capacitor.getPlatform() !== 'android') return

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
    if (!isNative() || messageListenerRegistered) return

    FirebaseMessaging.addListener('notificationReceived', (event) => {
      console.log('FCM message received:', event.notification)
      const payload = extractNotificationPayload(event.notification)

      // Show local notification for foreground messages
      LocalNotifications.schedule({
        notifications: [
          {
            id: getSafeNotificationId(),
            title: payload.title,
            body: payload.body,
            channelId: 'bus_tracker_notifications',
            largeBody: payload.body,
            summaryText: payload.data?.target_route === 'map' ? 'Tap to open home' : undefined,
            extra: {
              ...event.notification,
              ...payload.data,
              image: payload.image
            }
          }
        ]
      })

      if (onMessage && typeof onMessage === 'function') {
        onMessage(event.notification)
      }
    })

    messageListenerRegistered = true
  }

  /**
   * Listen for when user taps on a notification
   */
  const onNotificationClick = (onClick) => {
    if (!isNative() || notificationClickListenerRegistered) return

    FirebaseMessaging.addListener('notificationActionPerformed', (event) => {
      console.log('Notification clicked:', event.notification)

      if (onClick && typeof onClick === 'function') {
        onClick(event.notification?.data || event.notification)
      }
    })

    notificationClickListenerRegistered = true
  }

  /**
   * Delete the FCM token
   */
  const deleteToken = async () => {
    if (!isNative()) return

    try {
      await FirebaseMessaging.deleteToken()
      token.value = null
      isPermissionGranted.value = false
    } catch (err) {
      error.value = err
      console.error('Failed to delete FCM token:', err)
    }
  }

  /**
   * Initialize Firebase messaging with all listeners
   */
  const initialize = async (options = {}) => {
    if (!isNative()) {
      console.info('FCM: Skipping initialization on non-native platform')
      return { token: null, success: false }
    }

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
      if (!fcmToken) {
        throw new Error('FCM token generation failed')
      }
      console.info('FCM token generated successfully')

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
        if (!localNotificationClickListenerRegistered) {
          LocalNotifications.addListener('localNotificationActionPerformed', (event) => {
            if (options.onNotificationClick && typeof options.onNotificationClick === 'function') {
              options.onNotificationClick(event.notification?.extra || event.notification)
            }
          })
          localNotificationClickListenerRegistered = true
        }
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
