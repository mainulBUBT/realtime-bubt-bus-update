import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.bustracker.driver',
  appName: 'Bus Tracker Driver',
  webDir: '../dist-driver',
  server: {
    androidScheme: 'https',
    cleartext: true
  },
  plugins: {
    Geolocation: {
      permissions: ['location', 'locationAlways']
    },
    LocalNotifications: {
      notifications: [
        {
          id: 1,
          title: 'Trip Tracking',
          body: 'Background location tracking is active'
        }
      ]
    }
  }
};

export default config;
