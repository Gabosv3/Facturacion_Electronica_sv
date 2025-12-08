<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Factura Electrónica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/default.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
        }

        body {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a8c 50%, #3a6fa3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background */
        body::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            top: -100px;
            left: -100px;
            animation: float 8s ease-in-out infinite;
        }

        body::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
            bottom: -50px;
            right: -50px;
            animation: float 10s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(20px); }
        }

        .login-container {
            width: 100%;
            max-width: 620px;
            padding: 20px;
            z-index: 10;
            position: relative;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25), 0 0 1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.3);
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

        .login-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a8c 100%);
            color: white;
            padding: 50px 30px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .logo-icon {
            font-size: 3.5rem;
            margin-bottom: 15px;
            display: inline-block;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .login-header p {
            font-size: 13px;
            opacity: 0.95;
            font-weight: 500;
            margin: 0;
        }

        .login-body {
            padding: 48px 45px;
        }

        .form-group {
            margin-bottom: 28px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
        
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            color: #3a6fa3;
            font-size: 16px;
            z-index: 1;
        }

        .form-group input {
            width: 100%;
            padding: 13px 15px 13px 44px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f9fafb;
            color: #2c3e50;
        }

        .form-group input::placeholder {
            color: #a0aec0;
            font-size: 13px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3a6fa3;
            background: white;
            box-shadow: 0 0 0 4px rgba(58, 111, 163, 0.1);
        }

        .form-group input:hover {
            border-color: #cbd5e1;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a8c 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(30, 58, 95, 0.2);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
            z-index: 0;
        }

        .btn-login span {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(30, 58, 95, 0.3);
        }

        .btn-login:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
            margin-right: 10px;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .info-box {
            background: linear-gradient(135deg, rgba(58, 111, 163, 0.08) 0%, rgba(45, 90, 140, 0.08) 100%);
            border-left: 4px solid #3a6fa3;
            padding: 20px 24px;
            border-radius: 10px;
            margin-top: 28px;
            font-size: 13px;
            color: #2c3e50;
            line-height: 1.6;
        }

        .info-box strong {
            font-weight: 700;
            color: #1e3a5f;
            display: block;
            margin-bottom: 6px;
        }

        .info-box i {
            color: #3a6fa3;
            margin-right: 8px;
        }

        .divider {
            text-align: center;
            margin: 28px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e5e7eb;
        }

        .divider span {
            background: white;
            padding: 0 10px;
            color: #a0aec0;
            font-size: 12px;
            position: relative;
            display: inline-block;
            font-weight: 500;
        }

        /* Alertify customization */
        .ajs-dimmer {
            background: rgba(0, 0, 0, 0.3);
        }

        .ajs-dialog {
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .ajs-title {
            font-weight: 700;
            color: #1e3a5f;
            font-size: 16px;
        }

        .ajs-content {
            color: #2c3e50;
            font-size: 14px;
        }

        .ajs-button {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a8c 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 24px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .ajs-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 58, 95, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .login-container {
                padding: 15px;
            }

            .login-card {
                border-radius: 12px;
            }

            .login-header {
                padding: 40px 25px 30px;
            }

            .logo-icon {
                font-size: 2.8rem;
                margin-bottom: 12px;
            }

            .login-header h1 {
                font-size: 24px;
            }

            .login-header p {
                font-size: 12px;
            }

            .login-body {
                padding: 30px 25px;
            }

            .form-group {
                margin-bottom: 18px;
            }

            .btn-login {
                padding: 12px;
                font-size: 14px;
            }

            .form-group input {
                padding: 12px 12px 12px 40px;
                font-size: 16px;
            }

            .input-wrapper i {
                left: 12px;
                font-size: 15px;
            }

            .info-box {
                font-size: 12px;
                padding: 14px 14px;
                margin-top: 22px;
            }

            .login-header::before,
            .login-header::after {
                display: none;
            }
        }

        @media (max-width: 380px) {
            .login-body {
                padding: 25px 18px;
            }

            .login-header {
                padding: 35px 20px 25px;
            }

            .logo-icon {
                font-size: 2.2rem;
                margin-bottom: 10px;
            }

            .login-header h1 {
                font-size: 20px;
                margin-bottom: 6px;
            }

            .login-header p {
                font-size: 11px;
            }

            .form-group label {
                font-size: 12px;
            }

            .form-group input {
                padding: 11px 11px 11px 38px;
                font-size: 14px;
            }

            .btn-login {
                padding: 11px;
                font-size: 13px;
                margin-top: 12px;
            }

            .info-box {
                font-size: 11px;
                padding: 12px 12px;
                margin-top: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="header-content">
                    <div class="logo-icon">
                        📄
                    </div>
                    <h1>Factura Electrónica</h1>
                    <p>Sistema de Emisión de DTE El Salvador</p>
                </div>
            </div>

            <!-- Body -->
            <div class="login-body">
                <form id="loginForm" method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    <!-- Email Input -->
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope" style="margin-right: 6px;"></i>
                            Correo Electrónico
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope" style="margin-right: 6px;"></i>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="tu@email.com"
                                value="{{ old('email') }}"
                                required
                                autocomplete="email"
                            >
                        </div>
                        @error('email')
                            <div style="color: #dc3545; font-size: 12px; margin-top: 6px;">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Password Input -->
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock" style="margin-right: 6px;"></i>
                            Contraseña
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock" style="margin-right: 6px;"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                            >
                        </div>
                        @error('password')
                            <div style="color: #dc3545; font-size: 12px; margin-top: 6px;">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-login" id="submitBtn">
                        <span>
                            <span class="loading-spinner" style="display: none;"></span>
                            <span id="btnText">Iniciar Sesión</span>
                        </span>
                    </button>
                </form>

                <!-- Divider -->
                <div class="divider">
                    <span>Información</span>
                </div>

                <!-- Info Box -->
                <div class="info-box">
                    <strong><i class="fas fa-info-circle"></i>¿Cómo acceder?</strong>
                    Utiliza las credenciales proporcionadas por <strong>Motors González</strong> para acceder al sistema de facturación electrónica. Tu cuenta está vinculada con tu perfil corporativo.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/alertify.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Configure Alertify
        alertify.set('notifier', 'position', 'top-right');
        
        // Show alerts if there are errors or success messages
        @if($errors->any())
            @foreach($errors->all() as $error)
                alertify.error('{{ $error }}');
            @endforeach
        @endif

        @if(session('success'))
            alertify.success('{{ session('success') }}');
        @endif

        // Form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const spinner = submitBtn.querySelector('.loading-spinner');
            const btnText = document.getElementById('btnText');
            
            spinner.style.display = 'inline-block';
            btnText.textContent = 'Iniciando sesión...';
            submitBtn.disabled = true;
        });

        // Input validation and styling
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

        [emailInput, passwordInput].forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.style.borderColor = '#dc3545';
                } else if (input.id === 'email') {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    this.style.borderColor = emailRegex.test(this.value) ? '#28a745' : '#dc3545';
                } else {
                    this.style.borderColor = this.value.length >= 6 ? '#28a745' : '#dc3545';
                }
            });

            input.addEventListener('focus', function() {
                if (this.value.trim() !== '') {
                    this.style.borderColor = '#3a6fa3';
                }
            });

            input.addEventListener('input', function() {
                this.style.borderColor = '#e5e7eb';
            });
        });
    </script>
</body>
</html>
