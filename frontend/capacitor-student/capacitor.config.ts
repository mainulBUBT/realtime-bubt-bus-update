import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.bustracker.student',
  appName: 'Bus Tracker Student',
  webDir: '../dist-student',
  server: {
    androidScheme: 'https',
    cleartext: true
  }
};

export default config;
