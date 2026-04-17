import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

const wsHost = import.meta.env.VITE_REVERB_HOST || '127.0.0.1'
const wsPort = import.meta.env.VITE_REVERB_PORT || 8080
const forceTLS = import.meta.env.VITE_REVERB_SCHEME === 'https'

let realtimeEnabled = true
let unavailableCount = 0

function disableRealtime(reason) {
  if (!realtimeEnabled) return

  realtimeEnabled = false
  echo.disconnect()
  console.warn('[Echo] Realtime disabled, falling back to HTTP polling:', reason)
}

const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost,
  wsPort,
  wssPort: wsPort,
  forceTLS,
  enabledTransports: ['ws', 'wss'],
  disableStats: true,
  client: new Pusher(import.meta.env.VITE_REVERB_APP_KEY, {
    wsHost,
    wsPort,
    wssPort: wsPort,
    forceTLS,
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    cluster: '',
  }),
})

// Connection diagnostics — helps debug WS issues on real devices
echo.connector.pusher.connection.bind('connected', () => {
  unavailableCount = 0
  console.log('[Echo] WebSocket CONNECTED')
})
echo.connector.pusher.connection.bind('disconnected', () => {
  console.warn('[Echo] WebSocket DISCONNECTED')
})
echo.connector.pusher.connection.bind('state_change', (states) => {
  if (!realtimeEnabled) return

  if (states?.current === 'unavailable') {
    unavailableCount += 1

    if (unavailableCount >= 2) {
      disableRealtime(`host ${wsHost} is unreachable`)
    }
  }
})
echo.connector.pusher.connection.bind('error', (err) => {
  console.error('[Echo] WebSocket ERROR:', err?.error || err)
})

export function canUseRealtime() {
  return realtimeEnabled
}

export default echo
