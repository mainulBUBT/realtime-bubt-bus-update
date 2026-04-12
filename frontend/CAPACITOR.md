# Capacitor Setup Guide

The Capacitor native projects are gitignored to save repository space (~50MB per clone). Follow these steps to set them up locally.

## Initial Setup (One-time)

### Prerequisites
- Node.js and npm installed
- Android Studio installed (for Android development)
- Java JDK 17+ installed

### Steps

1. **Install dependencies**
   ```bash
   npm install
   ```

2. **Install Capacitor CLI**
   ```bash
   npm install -g @capacitor/cli
   ```

3. **Build the web app**
   ```bash
   npm run build:driver   # or build:student
   ```

4. **Add native platforms**
   ```bash
   npx cap add android --dir capacitor-driver   # Creates capacitor-driver/
   npx cap add android --dir capacitor-student  # Creates capacitor-student/
   ```

   Or for Android specifically:
   ```bash
   npx cap add android --dir capacitor-driver
   npx cap add android --dir capacitor-student
   ```

5. **Copy Capacitor config templates**
   ```bash
   cp scripts/capacitor-driver.config.js capacitor-driver/capacitor.config.js
   cp scripts/capacitor-student.config.js capacitor-student/capacitor.config.js
   ```

6. **Sync assets to native projects**
   ```bash
   npm run cap:sync:driver   # or cap:sync:student
   ```
   These sync scripts refresh the matching Android branding assets and local native resource/plugin wiring before copying the latest web build.

### Driver background tracking extras

The driver app now uses `@capacitor-community/background-geolocation` for Android-style tracking while the app is backgrounded.

- `frontend/scripts/capacitor-driver.config.js` already enables:
  - `android.useLegacyBridge = true`
  - `plugins.CapacitorHttp.enabled = true`
- After creating `capacitor-driver/`, make sure the Android project has the permissions required by the background geolocation plugin, including background location and foreground-service notification support.
- On Android 13+, allow notifications so the persistent foreground-service notification can be shown while an ongoing trip is being tracked.
- After any plugin/config change, run:
  ```bash
  npm run cap:sync:driver
  ```

## Daily Development

### Web Development
For fast development with hot reload:
```bash
npm run dev:driver   # or dev:student
```

### Native Development

1. **Build web assets + sync to native**
   ```bash
   npm run cap:sync:driver   # or cap:sync:student
   ```

2. **Open in Android Studio**
   ```bash
   npm run cap:open:driver   # or cap:open:student
   ```

3. **Or do both in one command**
   ```bash
   npm run cap:build:driver   # or cap:build:student
   ```

### Testing on Device/Emulator
After syncing, run via Android Studio or use:
```bash
cd capacitor-driver   # or capacitor-student
npx cap run android
```

## Project Structure

```
frontend/
├── src/                    # Vue source code (shared)
├── dist-driver/            # Build output for driver app (gitignored)
├── dist-student/           # Build output for student app (gitignored)
├── capacitor-driver/       # Native Android project for driver (gitignored)
├── capacitor-student/      # Native Android project for student (gitignored)
├── scripts/
│   ├── capacitor-driver.config.js    # Config template for driver
│   └── capacitor-student.config.js   # Config template for student
└── CAPACITOR.md          # This file
```

## Available Scripts

| Script | Description |
|--------|-------------|
| `npm run dev:driver` | Start dev server for driver app |
| `npm run dev:student` | Start dev server for student app |
| `npm run build:driver` | Build driver web app to `dist-driver/` |
| `npm run build:student` | Build student web app to `dist-student/` |
| `npm run cap:add:driver` | Add Android Capacitor project for driver app |
| `npm run cap:add:student` | Add Android Capacitor project for student app |
| `npm run cap:sync:driver` | Build + sync driver app to native Android |
| `npm run cap:sync:student` | Build + sync student app to native Android |
| `npm run cap:open:driver` | Open driver app in Android Studio |
| `npm run cap:open:student` | Open student app in Android Studio |
| `npm run cap:build:driver` | Sync + open driver app |
| `npm run cap:build:student` | Sync + open student app |

## Common Issues

### "capacitor-driver directory not found"
Run `npm run cap:add:driver` to create the Capacitor project.

### Sync fails after code changes
Always run `npm run cap:sync:driver` (or `cap:sync:student`) before rebuilding/installing the APK so the latest web bundle is copied into `capacitor-*/android/app/src/main/assets/public/`.

### Android Studio can't find the project
Make sure you've run `npm run cap:sync:driver` at least once.

### App crashes on launch
Check that `capacitor.config.js` has the correct `webDir` path:
- Driver: `webDir: '../dist-driver'`
- Student: `webDir: '../dist-student'`

### Driver app stops sending after going to background
- Confirm you copied the latest `scripts/capacitor-driver.config.js`
- Re-run `npm run cap:sync:driver`
- Verify Android location permission is granted as `Allow all the time`
- Verify notifications are allowed so Android can show the tracking notification

## Environment Variables

For release builds with signing, set these in your shell or `.env.local`:
```bash
export ANDROID_KEYSTORE_PATH=/path/to/keystore.jks
export ANDROID_KEYSTORE_ALIAS=your-alias
```

## App Branding

The frontend keeps the per-app branding source artwork in:

- `public/icons/app-driver.svg`
- `public/icons/app-student.svg`

The web favicon automatically switches based on `VITE_APP_TYPE`, so:

- `npm run dev:driver` and `npm run build:driver` use the driver icon
- `npm run dev:student` and `npm run build:student` use the student icon

Native Android branding can now be synced automatically for each app:

```bash
npm run icons:sync:driver
npm run icons:sync:student
```

This sync step:

- exports the matching SVG into `resources/generated/<app>/`
- updates Android launcher icons in `capacitor-<app>/app/src/main/res/mipmap-*`
- updates splash images in `capacitor-<app>/app/src/main/res/drawable*`
- refreshes the Android app label, launcher background color, and native status bar theme for that app

Because `capacitor-driver/` and `capacitor-student/` are gitignored and regenerated locally, native branding assets are not committed in this repo. The native sync and APK scripts now refresh branding automatically before copying web assets:

- `npm run cap:sync:driver`
- `npm run cap:sync:student`
- `npm run apk:driver`
- `npm run apk:student`

## Resources

- [Capacitor Documentation](https://capacitorjs.com/docs)
- [Android Guide](https://capacitorjs.com/docs/android)
- [Vue + Capacitor Guide](https://capacitorjs.com/docs/guides/vue)
