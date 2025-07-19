<x-app-layout>
    <div class="welcome-container">
        <!-- Navigation -->
        <nav class="main-nav">
            <div class="nav-brand">
                <h1>🚌 BUBT Bus Tracker</h1>
                <p>Know Where Is My University Bus</p>
            </div>
            <div class="nav-links">
                <a href="/" class="nav-link active">Live Tracking</a>
                <a href="/admin" class="nav-link">Admin</a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <livewire:today-trips />
        </main>

        <!-- Footer -->
        <footer class="main-footer">
            <div class="footer-content">
                <p>&copy; 2024 Bangladesh University of Business and Technology</p>
                <p>Real-time bus tracking system - 100% free, no hardware required</p>
                <div class="footer-links">
                    <a href="#" onclick="installPWA()">📱 Install App</a>
                    <a href="#" onclick="enableNotifications()">🔔 Enable Notifications</a>
                </div>
            </div>
        </footer>
    </div>

    <style>
        .welcome-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-nav {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .main-nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand h1 {
            margin: 0;
            font-size: 1.5rem;
            color: #667eea;
        }

        .nav-brand p {
            margin: 0;
            font-size: 0.9rem;
            color: #666;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-link {
            padding: 0.5rem 1rem;
            text-decoration: none;
            color: #666;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #667eea;
            color: white;
        }

        .main-content {
            flex: 1;
            padding: 2rem 0;
        }

        .main-footer {
            background: #333;
            color: white;
            padding: 2rem 0;
            text-align: center;
        }

        .footer-content p {
            margin: 0.5rem 0;
        }

        .footer-links {
            margin-top: 1rem;
            display: flex;
            justify-content: center;
            gap: 2rem;
        }

        .footer-links a {
            color: #667eea;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border: 1px solid #667eea;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .footer-links a:hover {
            background: #667eea;
            color: white;
        }

        @media (max-width: 768px) {
            .main-nav .container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .footer-links {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>

    <script>
        // PWA Installation
        let deferredPrompt;
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
        });

        function installPWA() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    }
                    deferredPrompt = null;
                });
            } else {
                alert('App is already installed or installation is not available');
            }
        }

        // Push Notifications
        function enableNotifications() {
            if ('Notification' in window && 'serviceWorker' in navigator) {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        console.log('Notification permission granted');
                        // Subscribe to push notifications
                        subscribeUserToPush();
                    } else {
                        alert('Notification permission denied');
                    }
                });
            } else {
                alert('Notifications not supported');
            }
        }

        function subscribeUserToPush() {
            navigator.serviceWorker.ready.then(registration => {
                const vapidPublicKey = 'YOUR_VAPID_PUBLIC_KEY'; // You'll need to generate this
                
                registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
                }).then(subscription => {
                    console.log('User is subscribed:', subscription);
                    
                    // Send subscription to server
                    fetch('/api/push-subscribe', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(subscription)
                    });
                }).catch(err => {
                    console.log('Failed to subscribe user: ', err);
                });
            });
        }

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/-/g, '+')
                .replace(/_/g, '/');

            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);

            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }
    </script>
</x-app-layout>