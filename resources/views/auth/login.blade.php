<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Factura Electrónica</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/default.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body { height: 100%; width: 100%; }

        body {
            background: radial-gradient(circle at 15% 15%, rgba(59,130,246,0.25), transparent 45%),
                        radial-gradient(circle at 85% 85%, rgba(37,99,235,0.2), transparent 45%),
                        linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #1e3a8a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            position: relative;
            overflow-x: hidden;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            z-index: 10;
            position: relative;
        }

        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 60px -12px rgba(15, 23, 42, 0.45);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            padding: 40px 32px 28px;
            text-align: center;
        }

        .logo-badge {
            width: 56px;
            height: 56px;
            margin: 0 auto 16px;
            border-radius: 16px;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            box-shadow: 0 8px 20px -6px rgba(37, 99, 235, 0.5);
        }

        .login-header h1 {
            font-size: 22px;
            font-weight: 800;
            color: #1e293b;
            letter-spacing: -0.02em;
        }

        .login-header .badge-v2 {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            color: #1d4ed8;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            padding: 2px 7px;
            border-radius: 6px;
            margin-left: 6px;
            vertical-align: middle;
        }

        .login-header p {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            margin-top: 6px;
        }

        .login-body {
            padding: 8px 32px 36px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #475569;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            color: #94a3b8;
            font-size: 14px;
            z-index: 1;
        }

        .form-group input {
            width: 100%;
            padding: 11px 14px 11px 40px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            background: #f8fafc;
            color: #1e293b;
        }

        .form-group input::placeholder { color: #94a3b8; font-size: 13px; }

        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.15s ease, transform 0.1s ease;
            margin-top: 6px;
            box-shadow: 0 4px 12px -4px rgba(37, 99, 235, 0.5);
        }

        .btn-login:hover { background: #1d4ed8; }
        .btn-login:active { transform: scale(0.99); }
        .btn-login:disabled { opacity: 0.6; cursor: not-allowed; }

        .btn-login span { display: flex; align-items: center; justify-content: center; }

        .loading-spinner {
            display: inline-block;
            width: 15px;
            height: 15px;
            border: 2px solid rgba(255, 255, 255, 0.35);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
            margin-right: 9px;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .info-box {
            background: #eff6ff;
            border: 1px solid #dbeafe;
            padding: 14px 16px;
            border-radius: 10px;
            margin-top: 22px;
            font-size: 12.5px;
            color: #334155;
            line-height: 1.55;
        }

        .info-box strong {
            font-weight: 700;
            color: #1e3a8a;
            display: block;
            margin-bottom: 4px;
            font-size: 12.5px;
        }

        .info-box i { color: #2563eb; margin-right: 6px; }

        .field-error {
            color: #dc2626;
            font-size: 11.5px;
            margin-top: 5px;
        }

        .ajs-dimmer { background: rgba(15, 23, 42, 0.4); }
        .ajs-dialog { border-radius: 12px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); }
        .ajs-title { font-weight: 700; color: #1e3a8a; font-size: 16px; }
        .ajs-content { color: #334155; font-size: 14px; }
        .ajs-button {
            background: #2563eb;
            border: none;
            border-radius: 8px;
            padding: 9px 22px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }
        .ajs-button:hover { background: #1d4ed8; }

        @media (max-width: 420px) {
            .login-header { padding: 32px 24px 22px; }
            .login-body { padding: 8px 24px 28px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-badge">📄</div>
                <h1>Factura Electrónica <span class="badge-v2">v2.0</span></h1>
                <p>Sistema de Emisión de DTE El Salvador</p>
            </div>

            <div class="login-body">
                <form id="loginForm" method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
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
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
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
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn-login" id="submitBtn">
                        <span>
                            <span class="loading-spinner" style="display: none;"></span>
                            <span id="btnText">Iniciar Sesión</span>
                        </span>
                    </button>
                </form>

                <div class="info-box">
                    <strong><i class="fas fa-info-circle"></i>¿Cómo acceder?</strong>
                    Utiliza las credenciales proporcionadas por <strong>Motors González</strong>. Tu cuenta está vinculada con tu perfil corporativo.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/alertify.min.js"></script>
    <script>
        alertify.set('notifier', 'position', 'top-right');

        @if($errors->any())
            @foreach($errors->all() as $error)
                alertify.error('{{ $error }}');
            @endforeach
        @endif

        @if(session('success'))
            alertify.success('{{ session('success') }}');
        @endif

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const spinner = submitBtn.querySelector('.loading-spinner');
            const btnText = document.getElementById('btnText');

            spinner.style.display = 'inline-block';
            btnText.textContent = 'Iniciando sesión...';
            submitBtn.disabled = true;
        });

        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

        [emailInput, passwordInput].forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.style.borderColor = '#e2e8f0';
                } else if (input.id === 'email') {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    this.style.borderColor = emailRegex.test(this.value) ? '#22c55e' : '#e2e8f0';
                }
            });

            input.addEventListener('focus', function() {
                this.style.borderColor = '#3b82f6';
            });
        });
    </script>
</body>
</html>
