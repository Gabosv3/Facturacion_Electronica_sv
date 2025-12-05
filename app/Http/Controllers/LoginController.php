<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Mostrar el formulario de login
     */
    public function showLoginForm()
    {
        if (Session::has('auth_token')) {
            return redirect()->route('facturas.index');
        }
        return view('auth.login');
    }

    /**
     * Procesar el login
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ], [
            'email.required' => 'El email es requerido',
            'email.email' => 'El email debe ser válido',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
        ]);

        try {
            // Consumir el endpoint de autenticación externa
            $response = $this->authService->authenticate($validated['email'], $validated['password']);

            if ($response['success']) {
                // Guardar el token en sesión
                Session::put('auth_token', $response['token']);
                Session::put('user_data', $response['user']);
                Session::put('authenticated', true);

                return redirect()->route('facturas.index')->with('success', 'Sesión iniciada correctamente');
            } else {
                return back()->withErrors(['email' => $response['message'] ?? 'Credenciales inválidas'])->withInput();
            }
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Error de conexión: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout()
    {
        Session::flush();
        return redirect()->route('login')->with('success', 'Sesión cerrada correctamente');
    }

    /**
     * Mostrar el perfil del usuario
     */
    public function showProfile()
    {
        if (!Session::has('auth_token')) {
            return redirect()->route('login');
        }

        $userData = Session::get('user_data', []);
        return view('auth.profile.show', ['user' => $userData]);
    }
}
