<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Quiz App') }} - Register</title>
    
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
        
        .register-container {
            max-width: 500px;
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
            padding: 30px 20px;
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
            padding: 30px;
            background: white;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 6px;
            display: block;
            font-size: 13px;
        }
        
        .input-group {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            transition: all 0.3s;
        }
        
        .input-group:focus-within {
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }
        
        .input-group-text {
            background: white;
            border: 1px solid #e0e0e0;
            border-right: none;
            color: #667eea;
            font-size: 1rem;
            padding: 10px 12px;
        }
        
        .form-control {
            border: 1px solid #e0e0e0;
            border-left: none;
            padding: 10px 12px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: none;
            outline: none;
        }
        
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 10px;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            margin-top: 10px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-register:active {
            transform: translateY(0);
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
            background: linear-gradient(90deg, #dc3545 25%, #e0e0e0 25%);
        }
        
        .strength-fair {
            background: linear-gradient(90deg, #ffc107 50%, #e0e0e0 50%);
        }
        
        .strength-good {
            background: linear-gradient(90deg, #17a2b8 75%, #e0e0e0 75%);
        }
        
        .strength-strong {
            background: #28a745;
        }
        
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
            padding-left: 5px;
        }
        
        .requirement-item {
            margin-bottom: 3px;
        }
        
        .requirement-item i {
            font-size: 12px;
            margin-right: 5px;
        }
        
        .requirement-met {
            color: #28a745;
        }
        
        .requirement-unmet {
            color: #dc3545;
        }
        
        .social-register {
            text-align: center;
            margin-top: 25px;
        }
        
        .social-register p {
            color: #999;
            font-size: 13px;
            position: relative;
            margin-bottom: 15px;
        }
        
        .social-register p::before,
        .social-register p::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 30%;
            height: 1px;
            background: #e0e0e0;
        }
        
        .social-register p::before {
            left: 0;
        }
        
        .social-register p::after {
            right: 0;
        }
        
        .social-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        
        .btn-social {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            background: white;
            color: #555;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        .btn-social:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(0,0,0,0.08);
        }
        
        .btn-google {
            color: #DB4437;
        }
        
        .btn-facebook {
            color: #4267B2;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .links a:hover {
            color: #764ba2;
        }
        
        .login-link {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 12px;
            display: block;
            border: 1px solid #e0e0e0;
        }
        
        .login-link:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .alert {
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: none;
            font-size: 13px;
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
            font-size: 12px;
            margin-top: 4px;
            display: block;
        }
        
        .terms-check {
            margin: 15px 0;
            font-size: 13px;
        }
        
        .terms-check a {
            color: #667eea;
            text-decoration: none;
        }
        
        .terms-check a:hover {
            text-decoration: underline;
        }
        
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
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
    </style>
</head>
<body>
    <div class="register-container">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-trophy-fill" style="font-size: 2.5rem; margin-bottom: 10px;"></i>
                <h3>Create Account</h3>
                <p>Join our quiz community and start learning</p>
            </div>
            
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Please fix the errors below.
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" id="registerForm">
                    @csrf

                    <!-- Name -->
                    <div class="form-group">
                        <label for="name" class="form-label">
                            <i class="bi bi-person-fill me-1"></i> Full Name
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person"></i>
                            </span>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   placeholder="Enter your full name"
                                   required 
                                   autofocus>
                        </div>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

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
                                   required>
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
                                   placeholder="Create a password"
                                   required>
                            <span class="password-toggle" onclick="togglePassword('password', 'toggleIcon')">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </span>
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                        <div class="password-requirements" id="passwordRequirements">
                            <div class="requirement-item" id="lengthReq">
                                <i class="bi bi-x-circle-fill requirement-unmet"></i> At least 8 characters
                            </div>
                            <div class="requirement-item" id="uppercaseReq">
                                <i class="bi bi-x-circle-fill requirement-unmet"></i> At least 1 uppercase letter
                            </div>
                            <div class="requirement-item" id="numberReq">
                                <i class="bi bi-x-circle-fill requirement-unmet"></i> At least 1 number
                            </div>
                            <div class="requirement-item" id="specialReq">
                                <i class="bi bi-x-circle-fill requirement-unmet"></i> At least 1 special character
                            </div>
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">
                            <i class="bi bi-lock-fill me-1"></i> Confirm Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" 
                                   class="form-control @error('password_confirmation') is-invalid @enderror" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   placeholder="Confirm your password"
                                   required>
                            <span class="password-toggle" onclick="togglePassword('password_confirmation', 'toggleConfirmIcon')">
                                <i class="bi bi-eye" id="toggleConfirmIcon"></i>
                            </span>
                        </div>
                        <div id="passwordMatch" class="small mt-1"></div>
                        @error('password_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="terms-check">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a>
                            </label>
                        </div>
                    </div>

                    <!-- Register Button -->
                    <button type="submit" class="btn-register" id="registerButton">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="display: none;" id="registerSpinner"></span>
                        <i class="bi bi-person-plus-fill me-2"></i>
                        <span id="registerText">Create Account</span>
                    </button>

                    <!-- Social Registration -->
                    @if(Route::has('social.login'))
                    <div class="social-register">
                        <p>Or sign up with</p>
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
                    @endif

                    <!-- Login Link -->
                    <div class="links">
                        <p class="mb-2">Already have an account?</p>
                        <a href="{{ route('login') }}" class="login-link">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Sign In Instead
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-3">
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
        function togglePassword(fieldId, iconId) {
            const password = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(iconId);
            
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

        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            // Check requirements
            const lengthReq = password.length >= 8;
            const uppercaseReq = /[A-Z]/.test(password);
            const numberReq = /[0-9]/.test(password);
            const specialReq = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            
            // Update requirement icons
            updateRequirement('lengthReq', lengthReq);
            updateRequirement('uppercaseReq', uppercaseReq);
            updateRequirement('numberReq', numberReq);
            updateRequirement('specialReq', specialReq);
            
            // Calculate strength
            const requirements = [lengthReq, uppercaseReq, numberReq, specialReq];
            const metCount = requirements.filter(Boolean).length;
            
            if (password.length === 0) {
                strengthDiv.style.display = 'none';
                strengthDiv.className = 'password-strength';
            } else {
                strengthDiv.style.display = 'block';
                if (metCount <= 1) {
                    strengthDiv.className = 'password-strength strength-weak';
                } else if (metCount === 2) {
                    strengthDiv.className = 'password-strength strength-fair';
                } else if (metCount === 3) {
                    strengthDiv.className = 'password-strength strength-good';
                } else {
                    strengthDiv.className = 'password-strength strength-strong';
                }
            }
            
            checkPasswordMatch();
        });

        function updateRequirement(elementId, met) {
            const element = document.getElementById(elementId);
            const icon = element.querySelector('i');
            
            if (met) {
                icon.className = 'bi bi-check-circle-fill requirement-met';
            } else {
                icon.className = 'bi bi-x-circle-fill requirement-unmet';
            }
        }

        // Password match checker
        document.getElementById('password_confirmation').addEventListener('input', checkPasswordMatch);
        document.getElementById('password').addEventListener('input', checkPasswordMatch);

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirmation').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirm.length > 0) {
                if (password === confirm) {
                    matchDiv.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i> <span class="text-success">Passwords match</span>';
                } else {
                    matchDiv.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-1"></i> <span class="text-danger">Passwords do not match</span>';
                }
            } else {
                matchDiv.innerHTML = '';
            }
        }

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

        // Form submission with loading state
        document.getElementById('registerForm').addEventListener('submit', function() {
            const button = document.getElementById('registerButton');
            const spinner = document.getElementById('registerSpinner');
            const text = document.getElementById('registerText');
            
            button.style.opacity = '0.8';
            button.style.pointerEvents = 'none';
            spinner.style.display = 'inline-block';
            text.textContent = 'Creating Account...';
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

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
    </script>
</body>
</html>