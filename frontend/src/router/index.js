import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import api from '@/api/client'

// Driver routes
import DriverDashboard from '@/views/driver/Dashboard.vue'
import TripStart from '@/views/driver/TripStart.vue'
import SelectBus from '@/views/driver/SelectBus.vue'
import SelectDirection from '@/views/driver/SelectDirection.vue'
import ActiveTrip from '@/views/driver/ActiveTrip.vue'
import DriverHistory from '@/views/driver/History.vue'

// Student routes
import StudentMap from '@/views/student/MapView.vue'
import ScheduleList from '@/views/student/ScheduleList.vue'
import RoutesView from '@/views/student/RoutesView.vue'
import MoreMenu from '@/views/student/MoreMenu.vue'
import StudentSettings from '@/views/student/StudentSettings.vue'
import StudentAbout from '@/views/student/StudentAbout.vue'
import StudentHelpSupport from '@/views/student/StudentHelpSupport.vue'
import Notifications from '@/views/student/Notifications.vue'

// Auth routes
import DriverLogin from '@/views/auth/DriverLogin.vue'
import DriverSignUp from '@/views/auth/DriverSignUp.vue'
import StudentLogin from '@/views/auth/StudentLogin.vue'
import StudentSignUp from '@/views/auth/StudentSignUp.vue'

// Layouts
import DriverLayout from '@/layouts/DriverLayout.vue'
import StudentLayout from '@/layouts/StudentLayout.vue'

const appType = import.meta.env.VITE_APP_TYPE || 'driver'

const routes = appType === 'driver' ? [
  {
    path: '/login',
    name: 'login',
    component: DriverLogin,
    meta: { layout: null, transition: 'push', depth: 0 }
  },
  {
    path: '/signup',
    name: 'signup',
    component: DriverSignUp,
    meta: { layout: null, transition: 'push', depth: 1 }
  },
  {
    path: '/',
    component: DriverLayout,
    children: [
      {
        path: '',
        name: 'dashboard',
        component: DriverDashboard,
        meta: { transition: 'tab', tabIndex: 0 }
      },
      {
        path: 'trip/select-bus',
        name: 'trip-select-bus',
        component: SelectBus,
        meta: { requiresNoActiveTrip: true, transition: 'push', depth: 1 }
      },
      {
        path: 'trip/select-direction',
        name: 'trip-select-direction',
        component: SelectDirection,
        meta: { requiresNoActiveTrip: true, transition: 'push', depth: 2 }
      },
      {
        path: 'trip/start',
        name: 'trip-start',
        redirect: { name: 'trip-select-bus' }
      },
      {
        path: 'trip/active',
        name: 'trip-active',
        component: ActiveTrip,
        meta: { transition: 'tab', tabIndex: 2 }
      },
      {
        path: 'history',
        name: 'trip-history',
        component: DriverHistory,
        meta: { transition: 'tab', tabIndex: 1 }
      }
    ]
  }
] : [
  {
    path: '/login',
    name: 'login',
    component: StudentLogin,
    meta: { layout: null, transition: 'push', depth: 0 }
  },
  {
    path: '/signup',
    name: 'signup',
    component: StudentSignUp,
    meta: { layout: null, transition: 'push', depth: 1 }
  },
  {
    path: '/',
    name: 'map',
    component: StudentMap,
    meta: { transition: 'tab', tabIndex: 0 }
  },
  {
    path: '/schedules',
    name: 'schedules',
    component: ScheduleList,
    meta: { transition: 'tab', tabIndex: 1 }
  },
  {
    path: '/routes',
    name: 'routes',
    component: RoutesView,
    meta: { transition: 'tab', tabIndex: 2 }
  },
  {
    path: '/more',
    name: 'menu',
    component: MoreMenu,
    meta: { transition: 'tab', tabIndex: 3 }
  },
  {
    path: '/notifications',
    name: 'notifications',
    component: Notifications,
    meta: { transition: 'push', depth: 1 }
  },
  {
    path: '/settings',
    name: 'settings',
    component: StudentSettings,
    meta: { transition: 'push', depth: 1 }
  },
  {
    path: '/about',
    name: 'about',
    component: StudentAbout,
    meta: { transition: 'push', depth: 1 }
  },
  {
    path: '/help',
    name: 'help-support',
    component: StudentHelpSupport,
    meta: { transition: 'push', depth: 1 }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Track whether we've verified the token with the server this session
let tokenVerified = false
export function resetTokenVerified() { tokenVerified = false }

// Navigation guard
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  const publicRoutes = ['login', 'signup']
  const isDev = import.meta.env.DEV

  try {
    // Ensure user data is loaded from localStorage
    if (authStore.isAuthenticated && !authStore.user) {
      authStore.loadUserFromStorage()
    }

    // Verify token with server once per session on first protected route access
    if (authStore.isAuthenticated && !tokenVerified && !publicRoutes.includes(to.name)) {
      try {
        await authStore.fetchMe()
        tokenVerified = true
        if (isDev) console.log('✅ Token verified with server')
      } catch (error) {
        // Token is invalid/expired — clear and redirect to login
        if (isDev) console.error('❌ Token verification failed:', error)
        await authStore.logout()
        return next({ name: 'login' })
      }
    }

    // Check authentication — redirect to login if not authenticated
    if (!publicRoutes.includes(to.name) && !authStore.isAuthenticated) {
      if (isDev) console.log('🔒 Protected route, redirecting to login')
      return next({ name: 'login' })
    }

    if (publicRoutes.includes(to.name) && authStore.isAuthenticated) {
      // Redirect based on app type
      const defaultRoute = appType === 'driver' ? 'dashboard' : 'map'
      if (isDev) console.log('✅ Already authenticated, redirecting to', defaultRoute)
      return next({ name: defaultRoute })
    }

    // Driver-specific route guards - ONLY for trip-related routes
    if (authStore.isDriver) {
      const tripRoutes = ['trip-select-bus', 'trip-select-direction', 'trip-start', 'trip-active']

      // Only check trip status if navigating to trip-related routes
      if (tripRoutes.includes(to.name)) {
        let hasActiveTrip = false

        try {
          const response = await api.get('/driver/trips/current')
          hasActiveTrip = !!(response.data && response.data.id)
        } catch (error) {
          if (error?.response?.status === 404) {
            hasActiveTrip = false
          } else {
            if (isDev) console.error('❌ Error checking trip status:', error)

            // If error checking trip status and trying to access active trip, redirect to select bus
            if (to.name === 'trip-active') {
              return next({ name: 'trip-select-bus' })
            }
          }
        }

        if (isDev) console.log('🚌 Trip status check:', hasActiveTrip ? 'Active' : 'None')

        // If has active trip and trying to access start flow pages
        if (hasActiveTrip && (to.name === 'trip-select-bus' || to.name === 'trip-select-direction' || to.name === 'trip-start')) {
          return next({ name: 'trip-active' })
        }

        // If no active trip and trying to access active trip page
        if (!hasActiveTrip && to.name === 'trip-active') {
          return next({ name: 'trip-select-bus' })
        }
      }
    }

    next()
  } catch (error) {
    console.error('❌ Router error:', error)
    // In case of any error, try to proceed to login if possible
    if (to.name !== 'login') {
      return next({ name: 'login' })
    }
    next()
  }
})

// Add error handler for router navigation failures
router.onError((error) => {
  console.error('❌ Router navigation error:', error)

  // Show user-friendly error in development
  if (import.meta.env.DEV) {
    const errorDiv = document.createElement('div')
    errorDiv.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fee;padding:20px;border-radius:8px;border:2px solid #f88;font-family:sans-serif;z-index:99999;max-width:400px;'
    errorDiv.innerHTML = `
      <h3 style="color:#c33;margin:0 0 10px 0;">Route Error</h3>
      <p style="margin:0 0 10px 0;">Failed to load route: ${error.message}</p>
      <button onclick="location.href='/login'" style="padding:8px 16px;background:#10B981;color:white;border:none;border-radius:4px;cursor:pointer;">Go to Login</button>
    `
    document.body.appendChild(errorDiv)
  }
})

export default router
