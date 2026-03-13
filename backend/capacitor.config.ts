import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
    appId: 'com.bubt.bustracker',
    appName: 'BUBT Bus Tracker',
    webDir: 'public',
    server: {
        androidScheme: 'https',
        // For development, you can use your local IP
        // url: 'http://192.168.1.x:8000',
        // cleartext: true
    },
    plugins: {
        BackgroundGeolocation: {
            // Configuration for background geolocation plugin
            preventSuspend: true,
            heartbeatInterval: 10,
        }
    }
};

export default config;
