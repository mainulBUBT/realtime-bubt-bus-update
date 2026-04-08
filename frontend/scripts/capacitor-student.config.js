/**
 * Capacitor Configuration Template - Student App
 *
 * This file is a template reference for the Student app's Capacitor configuration.
 * When regenerating the Capacitor project, copy this to capacitor-student/capacitor.config.js
 *
 * To regenerate the Capacitor project:
 * 1. npm run build:student
 * 2. npx cap add android --dir capacitor-student
 * 3. Copy this config to capacitor-student/capacitor.config.js
 * 4. npx cap sync android --dir capacitor-student
 */

export default {
  appId: 'com.bustracker.student',
  appName: 'BUBT Tracker',
  webDir: '../dist-student',
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
