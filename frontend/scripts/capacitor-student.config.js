/**
 * Capacitor Configuration Template - Student App
 *
 * This file is a template reference for the Student app's Capacitor configuration.
 * When regenerating the Capacitor project, copy this to capacitor-student/capacitor.config.js
 *
 * To regenerate the Capacitor project:
 * 1. npm run build:student
 * 2. npx cap add student (or npx cap add android --dir capacitor-student)
 * 3. Copy this config to capacitor-student/capacitor.config.js
 * 4. npx cap sync student
 */

module.exports = {
  appId: 'com.bustracker.student',
  appName: 'BUBT Bus Tracker - Student',
  webDir: '../dist-student',
  bundledWebRuntime: false,
  server: {
    androidScheme: 'https'
  }
}
