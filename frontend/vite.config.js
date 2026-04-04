import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const appType = env.VITE_APP_TYPE || 'driver'
  const apiUrl = env.VITE_API_URL || '/api'
  const devApiTarget = env.VITE_DEV_API_TARGET || 'http://localhost:8000'
  const shouldProxyApi = apiUrl.startsWith('/')

  return {
    plugins: [vue()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url))
      }
    },
    build: {
      outDir: `dist-${appType}`,
      emptyOutDir: true
    },
    server: {
      port: 5174,
      strictPort: false,
      proxy: shouldProxyApi ? {
        '/api': {
          target: devApiTarget,
          changeOrigin: true
        }
      } : undefined
    }
  }
})
