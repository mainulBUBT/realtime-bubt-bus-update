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
   npx cap add driver   # Creates capacitor-driver/
   npx cap add student  # Creates capacitor-student/
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
| `npm run cap:add:driver` | Add Capacitor platform for driver app |
| `npm run cap:add:student` | Add Capacitor platform for student app |
| `npm run cap:sync:driver` | Build + sync driver app to native |
| `npm run cap:sync:student` | Build + sync student app to native |
| `npm run cap:open:driver` | Open driver app in Android Studio |
| `npm run cap:open:student` | Open student app in Android Studio |
| `npm run cap:build:driver` | Sync + open driver app |
| `npm run cap:build:student` | Sync + open student app |

## Common Issues

### "capacitor-driver directory not found"
Run `npm run cap:add:driver` to create the Capacitor project.

### Sync fails after code changes
Always run `npm run build:driver` (or `build:student`) before syncing.

### Android Studio can't find the project
Make sure you've run `npm run cap:sync:driver` at least once.

### App crashes on launch
Check that `capacitor.config.js` has the correct `webDir` path:
- Driver: `webDir: '../dist-driver'`
- Student: `webDir: '../dist-student'`

## Environment Variables

For release builds with signing, set these in your shell or `.env.local`:
```bash
export ANDROID_KEYSTORE_PATH=/path/to/keystore.jks
export ANDROID_KEYSTORE_ALIAS=your-alias
```

## Resources

- [Capacitor Documentation](https://capacitorjs.com/docs)
- [Android Guide](https://capacitorjs.com/docs/android)
- [Vue + Capacitor Guide](https://capacitorjs.com/docs/guides/vue)
