<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Quiz App') }} - Login</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
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
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 40px 20px;
            border: none;
        }
        
        .card-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 28px;
            letter-spacing: 0.5px;
        }
        
        .card-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .card-body {
            padding: 40px 30px;
            background: white;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 8px;
            display: block;
            font-size: 14px;
        }
        
        .input-group {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }
        
        .input-group:focus-within {
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }
        
        .input-group-text {
            background: white;
            border: 1px solid #e0e0e0;
            border-right: none;
            color: #667eea;
            font-size: 1.2rem;
            padding: 12px 15px;
        }
        
        .form-control {
            border: 1px solid #e0e0e0;
            border-left: none;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: none;
            outline: none;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 12px;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .social-login {
            text-align: center;
            margin-top: 30px;
        }
        
        .social-login p {
            color: #999;
            font-size: 14px;
            position: relative;
            margin-bottom: 20px;
        }
        
        .social-login p::before,
        .social-login p::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 30%;
            height: 1px;
            background: #e0e0e0;
        }
        
        .social-login p::before {
            left: 0;
        }
        
        .social-login p::after {
            right: 0;
        }
        
        .social-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn-social {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            background: white;
            color: #555;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-social:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-google {
            color: #DB4437;
        }
        
        .btn-facebook {
            color: #4267B2;
        }
        
        .form-check {
            margin: 20px 0;
        }
        
        .form-check-input {
            border-radius: 4px;
            border: 2px solid #e0e0e0;
        }
        
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .form-check-label {
            color: #666;
            font-size: 14px;
        }
        
        .links {
            text-align: center;
            margin-top: 25px;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .links a:hover {
            color: #764ba2;
            transform: translateX(5px);
        }
        
        .links .register-link {
            background: #f8f9fa;
            padding: 12px 20px;
            border-radius: 10px;
            margin-top: 15px;
            display: block;
            border: 1px solid #e0e0e0;
        }
        
        .links .register-link:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
            border: none;
            font-size: 14px;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .alert-success {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            color: #155724;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #feb692 0%, #ea5455 100%);
            color: #721c24;
        }
        
        .invalid-feedback {
            color: #dc3545;
            font-size: 13px;
            margin-top: 5px;
            display: block;
        }
        
        .floating-icon {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: #667eea;
            cursor: pointer;
            z-index: 10;
        }
        
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: #667eea;
            cursor: pointer;
            z-index: 10;
            background: white;
            padding: 0 5px;
        }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 8px;
            transition: all 0.3s;
        }
        
        .strength-weak {
            background: linear-gradient(90deg, #dc3545 33%, #e0e0e0 33%);
        }
        
        .strength-medium {
            background: linear-gradient(90deg, #ffc107 66%, #e0e0e0 66%);
        }
        
        .strength-strong {
            background: #28a745;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-trophy-fill" style="font-size: 3rem; margin-bottom: 15px;"></i>
                <h3>Welcome Back!</h3>
                <p>Login to continue your quiz journey</p>
            </div>
            
            <div class="card-body">
                <!-- Session Status -->
                @if (session('status'))
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf

                    <!-- Email Address -->
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope-fill me-1"></i> Email Address
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   placeholder="Enter your email"
                                   required 
                                   autofocus>
                        </div>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock-fill me-1"></i> Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password"
                                   required>
                            <span class="password-toggle" onclick="togglePassword()">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </span>
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-decoration-none" style="color: #667eea;">
                                <i class="bi bi-question-circle me-1"></i>Forgot Password?
                            </a>
                        @endif
                    </div>

                    <!-- Login Button -->
                    <button type="submit" class="btn-login" id="loginButton">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="display: none;" id="loginSpinner"></span>
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        <span id="loginText">Log In</span>
                    </button>

                    <!-- Social Login -->
                    <div class="social-login">
                        <p>Or continue with</p>
                        <div class="social-buttons">
                            <a href="{{ route('social.login', 'google') }}" class="btn-social btn-google">
                                <i class="bi bi-google"></i>
                                Google
                            </a>
                            <a href="{{ route('social.login', 'facebook') }}" class="btn-social btn-facebook">
                                <i class="bi bi-facebook"></i>
                                Facebook
                            </a>
                        </div>
                    </div>

                    <!-- Register Link -->
                    <div class="links">
                        <p class="mb-2">Don't have an account?</p>
                        <a href="{{ route('register') }}" class="register-link">
                            <i class="bi bi-person-plus-fill me-2"></i>
                            Create New Account
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-4">
            <p class="text-white opacity-75 small">
                <i class="bi bi-c-circle me-1"></i>
                {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword() {
            const password = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function() {
            const button = document.getElementById('loginButton');
            const spinner = document.getElementById('loginSpinner');
            const text = document.getElementById('loginText');
            
            button.style.opacity = '0.8';
            button.style.pointerEvents = 'none';
            spinner.style.display = 'inline-block';
            text.textContent = 'Logging in...';
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Email validation
        document.getElementById('email').addEventListener('input', function() {
            const email = this.value;
            const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !pattern.test(email)) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });

        // Add floating animation to icons
        document.querySelectorAll('.input-group-text i').forEach(icon => {
            icon.addEventListener('mouseover', function() {
                this.style.transform = 'scale(1.1)';
                this.style.transition = 'transform 0.3s';
            });
            
            icon.addEventListener('mouseout', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Remember me checkbox animation
        document.getElementById('remember').addEventListener('change', function() {
            const label = document.querySelector('label[for="remember"]');
            if (this.checked) {
                label.style.color = '#667eea';
                label.style.transition = 'color 0.3s';
            } else {
                label.style.color = '';
            }
        });

        // Password strength indicator (optional - can be removed if not needed)
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.querySelector('.password-strength') || createStrengthDiv();
            
            if (password.length === 0) {
                strengthDiv.style.display = 'none';
            } else {
                strengthDiv.style.display = 'block';
                if (password.length < 6) {
                    strengthDiv.className = 'password-strength strength-weak';
                } else if (password.length < 10) {
                    strengthDiv.className = 'password-strength strength-medium';
                } else {
                    strengthDiv.className = 'password-strength strength-strong';
                }
            }
        });

        function createStrengthDiv() {
            const div = document.createElement('div');
            div.className = 'password-strength';
            document.querySelector('.form-group:last-child').appendChild(div);
            return div;
        }
    </script>
</body>
</html>