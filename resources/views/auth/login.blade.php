<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Factura Electrónica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .login-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .login-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .login-header p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .login-body {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
            padding: 12px 15px;
            font-size: 14px;
        }

        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .error-text {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .error-text.show {
            display: block;
        }

        .loading-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #667eea;
            padding: 12px 15px;
            border-radius: 4px;
            margin-top: 20px;
            font-size: 13px;
            color: #0066cc;
        }

        @media (max-width: 576px) {
            .login-header {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 24px;
            }

            .login-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Factura Electrónica</h1>
                <p>Inicia sesión para continuar</p>
            </div>

            <div class="login-body">
                <!-- Mostrar mensaje de éxito si existe -->
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Mostrar errores si existen -->
                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form id="loginForm" method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="tu@email.com"
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                        >
                        @error('email')
                            <span class="error-text show">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                        @error('password')
                            <span class="error-text show">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn-login" id="submitBtn">
                        <span class="loading-spinner" style="display: none;"></span>
                        Iniciar Sesión
                    </button>
                </form>

                <div class="info-box">
                    <strong>ℹ️ Información:</strong><br>
                    Usa tus credenciales de Motors González para acceder al sistema de facturación electrónica.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const spinner = submitBtn.querySelector('.loading-spinner');
            
            // Mostrar spinner
            spinner.style.display = 'inline-block';
            submitBtn.disabled = true;
            submitBtn.textContent = '';
            submitBtn.appendChild(spinner);
            submitBtn.appendChild(document.createTextNode('Iniciando sesión...'));
        });

        // Validación en cliente
        const form = document.getElementById('loginForm');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

        [emailInput, passwordInput].forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.style.borderColor = '#dc3545';
                } else {
                    this.style.borderColor = '#e0e0e0';
                }
            });

            input.addEventListener('focus', function() {
                this.style.borderColor = '#667eea';
            });
        });
    </script>
</body>
</html>
