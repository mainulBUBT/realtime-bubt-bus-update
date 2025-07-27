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
    
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-accent: #3498db;
        }

        body {
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-secondary) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }

        .login-header {
            background: linear-gradient(135deg, var(--admin-accent) 0%, #2980b9 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .login-header p {
            opacity: 0.9;
            margin: 0;
        }

        .login-body {
            padding: 40px 30px;
        }

        .form-floating {
            margin-bottom: 20px;
        }

        .form-floating .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 16px;
            height: auto;
        }

        .form-floating .form-control:focus {
            border-color: var(--admin-accent);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .form-floating label {
            padding: 12px 16px;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--admin-accent) 0%, #2980b9 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            width: 100%;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
            color: white;
        }

        .form-check {
            margin: 20px 0;
        }

        .form-check-input:checked {
            background-color: var(--admin-accent);
            border-color: var(--admin-accent);
        }

        .alert {
            border-radius: 12px;
            border: none;
        }

        .back-to-site {
            position: absolute;
            top: 20px;
            left: 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-to-site:hover {
            color: white;
        }

        @media (max-width: 576px) {
            .login-card {
                margin: 20px;
                border-radius: 15px;
            }
            
            .login-header {
                padding: 30px 20px;
            }
            
            .login-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
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
</body>
</html>