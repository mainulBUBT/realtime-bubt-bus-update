<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Admin Login - {{ config('app.name', 'Bus Tracker') }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Admin Authentication CSS -->
    @vite(['resources/css/admin-auth.css'])
</head>
<body class="admin-auth">
    <a href="{{ route('home') }}" class="back-to-site">
        <i class="bi bi-arrow-left me-2"></i>
        Back to Site
    </a>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-shield-lock display-4 mb-3"></i>
                <h1>Admin Login</h1>
                <p>Bus Tracker Administration</p>
            </div>
            
            <div class="login-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.submit') }}">
                    @csrf
                    
                    <div class="form-floating">
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               placeholder="name@example.com"
                               value="{{ old('email') }}" 
                               required 
                               autofocus>
                        <label for="email">
                            <i class="bi bi-envelope me-2"></i>
                            Email Address
                        </label>
                    </div>

                    <div class="form-floating">
                        <input type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               id="password" 
                               name="password" 
                               placeholder="Password"
                               required>
                        <label for="password">
                            <i class="bi bi-lock me-2"></i>
                            Password
                        </label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               name="remember" 
                               id="remember">
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>

                    <button type="submit" class="btn btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Sign In
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Login Form Enhancement -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.querySelector('form');
            const loginButton = document.querySelector('.btn-login');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            // Add loading state to login button
            if (loginForm && loginButton) {
                loginForm.addEventListener('submit', function() {
                    loginButton.classList.add('loading');
                    loginButton.disabled = true;
                });
            }

            // Auto-focus email field
            if (emailInput) {
                emailInput.focus();
            }

            // Enter key handling
            if (emailInput && passwordInput) {
                emailInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        passwordInput.focus();
                    }
                });
            }

            // Form validation feedback
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() === '') {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    }
                });

                input.addEventListener('input', function() {
                    this.classList.remove('is-invalid', 'is-valid');
                });
            });
        });
    </script>
</body>
</html>