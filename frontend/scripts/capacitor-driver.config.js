/**
 * Capacitor Configuration Template - Driver App
 *
 * This file is a template reference for the Driver app's Capacitor configuration.
 * When regenerating the Capacitor project, copy this to capacitor-driver/capacitor.config.js
 *
 * To regenerate the Capacitor project:
 * 1. npm run build:driver
 * 2. npx cap add android --dir capacitor-driver
 * 3. Copy this config to capacitor-driver/capacitor.config.js
 * 4. npx cap sync android --dir capacitor-driver
 */

export default {
  appId: 'com.bustracker.driver',
  appName: 'BUBT Driver',
  webDir: '../dist-driver',
  bundledWebRuntime: false,
  plugins: {
    CapacitorHttp: {
      enabled: true
    },
    StatusBar: {
      overlaysWebView: false
    }
  },
  server: {
    androidScheme: 'https'
  },
  android: {
    useLegacyBridge: true,
    buildOptions: {
      keystorePath: process.env.ANDROID_KEYSTORE_PATH || undefined,
      keystoreAlias: process.env.ANDROID_KEYSTORE_ALIAS || undefined
    }
  }
}
