/**
 * Capacitor Configuration Template - Driver App
 *
 * This file is a template reference for the Driver app's Capacitor configuration.
 * When regenerating the Capacitor project, copy this to capacitor-driver/capacitor.config.js
 *
 * To regenerate the Capacitor project:
 * 1. npm run build:driver
 * 2. npx cap add driver (or npx cap add android --dir capacitor-driver)
 * 3. Copy this config to capacitor-driver/capacitor.config.js
 * 4. npx cap sync driver
 */

module.exports = {
  appId: 'com.bustracker.driver',
  appName: 'BUBT Bus Tracker - Driver',
  webDir: '../dist-driver',
  bundledWebRuntime: false,
  server: {
    androidScheme: 'https'
  },
  android: {
    buildOptions: {
      keystorePath: process.env.ANDROID_KEYSTORE_PATH || undefined,
      keystoreAlias: process.env.ANDROID_KEYSTORE_ALIAS || undefined
    }
  }
}
