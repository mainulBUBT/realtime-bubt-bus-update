import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST || '127.0.0.1',
  wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
  wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
  forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
  enabledTransports: ['ws', 'wss'],
  disableStats: true,
  client: new Pusher(import.meta.env.VITE_REVERB_APP_KEY, {
    wsHost: import.meta.env.VITE_REVERB_HOST || '127.0.0.1',
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    cluster: '',
  }),
})

// Connection diagnostics — helps debug WS issues on real devices
echo.connector.pusher.connection.bind('connected', () => {
  console.log('[Echo] WebSocket CONNECTED')
})
echo.connector.pusher.connection.bind('disconnected', () => {
  console.warn('[Echo] WebSocket DISCONNECTED')
})
echo.connector.pusher.connection.bind('error', (err) => {
  console.error('[Echo] WebSocket ERROR:', err?.error || err)
})

export default echo
