<div class="auth-container">
    <!-- Toast Notifications -->
    <div id="toast-container" class="toast-container"></div>
    
    <!-- Logo Section -->
    <div class="logo-section">
        <div class="logo">
            <img src="/images/bubt-logo.png" alt="BUBT Logo" onerror="this.style.display='none'">
            <div class="logo-text">
                <h1>🚌 BUBT</h1>
                <p>Bus Tracker</p>
            </div>
        </div>
        <h2>Welcome Back, Student!</h2>
        <p>Track your university buses in real-time</p>
    </div>

    @if(!$showRegister)
        <!-- Login Form -->
        <div class="auth-form">
            <form wire:submit.prevent="login">
                <div class="form-group">
                    <label for="email">📧 Email Address</label>
                    <input type="email" id="email" wire:model="email" placeholder="your.email@bubt.edu.bd" required>
                    @error('email') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="password">🔒 Password</label>
                    <input type="password" id="password" wire:model="password" placeholder="Enter your password" required>
                    @error('password') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" wire:model="remember">
                        <span class="checkmark"></span>
                        Remember me
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span wire:loading.remove>Sign In</span>
                    <span wire:loading>Signing in...</span>
                </button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account?</p>
                <button wire:click="toggleRegister" class="btn btn-link">Create Account</button>
            </div>
        </div>
    @else
        <!-- Register Form -->
        <livewire:auth.register />
        
        <div class="auth-footer">
            <p>Already have an account?</p>
            <button wire:click="toggleRegister" class="btn btn-link">Sign In</button>
        </div>
    @endif

    <style>
        .auth-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .logo-section {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            padding-top: 60px;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .logo img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 15px;
            background: white;
            padding: 10px;
        }

        .logo-text h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: bold;
        }

        .logo-text p {
            margin: 0;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .logo-section h2 {
            margin: 0 0 10px 0;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .logo-section p {
            margin: 0;
            opacity: 0.8;
            font-size: 1rem;
        }

        .auth-form {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 0.9rem;
            color: #666;
        }

        .checkbox-label input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
            transform: scale(1.2);
        }

        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-link {
            background: none;
            color: #667eea;
            text-decoration: underline;
            padding: 10px;
        }

        .auth-footer {
            text-align: center;
            color: white;
        }

        .auth-footer p {
            margin: 0 0 10px 0;
            opacity: 0.8;
        }

        .error {
            color: #e74c3c;
            font-size: 0.8rem;
            margin-top: 5px;
            display: block;
        }

        /* Mobile Optimizations */
        @media (max-width: 768px) {
            .auth-container {
                padding: 15px;
            }
            
            .logo-section {
                padding-top: 40px;
            }
            
            .logo-text h1 {
                font-size: 2rem;
            }
            
            .auth-form {
                padding: 25px;
            }
        }

        /* PWA Optimizations */
        @media (display-mode: standalone) {
            .logo-section {
                padding-top: 80px;
            }
        }

        /* Loading States */
        [wire\\:loading] {
            opacity: 0.6;
        }

        /* Animations */
        .auth-form {
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
        }

        .toast {
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-left: 4px solid;
            display: flex;
            align-items: center;
            animation: slideInRight 0.3s ease-out;
            position: relative;
            overflow: hidden;
        }

        .toast.success {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        }

        .toast.error {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        }

        .toast.info {
            border-left-color: #17a2b8;
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
        }

        .toast-icon {
            font-size: 1.2rem;
            margin-right: 12px;
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            margin-bottom: 2px;
            color: #333;
        }

        .toast-message {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
        }

        .toast-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #999;
            margin-left: 10px;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }

        /* Mobile toast adjustments */
        @media (max-width: 768px) {
            .toast-container {
                top: 10px;
                right: 10px;
                left: 10px;
                max-width: none;
            }
        }
    </style>

    <script>
        // Toast notification system
        function showToast(message, type = 'info', title = '') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            const icons = {
                success: '✅',
                error: '❌',
                info: 'ℹ️',
                warning: '⚠️'
            };

            const titles = {
                success: title || 'Success!',
                error: title || 'Error!',
                info: title || 'Info',
                warning: title || 'Warning!'
            };

            toast.innerHTML = `
                <div class="toast-icon">${icons[type]}</div>
                <div class="toast-content">
                    <div class="toast-title">${titles[type]}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" onclick="removeToast(this.parentElement)">×</button>
            `;

            container.appendChild(toast);

            // Auto remove after 5 seconds
            setTimeout(() => {
                removeToast(toast);
            }, 5000);
        }

        function removeToast(toast) {
            if (toast && toast.parentElement) {
                toast.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => {
                    if (toast.parentElement) {
                        toast.parentElement.removeChild(toast);
                    }
                }, 300);
            }
        }

        // Listen for Laravel flash messages
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                showToast('{{ session('success') }}', 'success');
            @endif

            @if(session('error'))
                showToast('{{ session('error') }}', 'error');
            @endif

            @if(session('info'))
                showToast('{{ session('info') }}', 'info');
            @endif

            @if(session('warning'))
                showToast('{{ session('warning') }}', 'warning');
            @endif
        });

        // Listen for Livewire events
        document.addEventListener('livewire:init', () => {
            Livewire.on('toast', (event) => {
                showToast(event.message, event.type || 'info', event.title || '');
            });
        });

        // PWA Install prompt
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // Show install hint after successful login
            setTimeout(() => {
                if (deferredPrompt) {
                    showToast('Install BUBT Bus Tracker as an app for the best experience!', 'info', 'Install App');
                }
            }, 2000);
        });

        // Service Worker registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('SW registered: ', registration);
                    })
                    .catch(function(registrationError) {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
    </script>
</div></span>