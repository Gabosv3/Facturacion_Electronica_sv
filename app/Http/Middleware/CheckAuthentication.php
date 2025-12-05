<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthentication
{
    /**
     * Manejar una solicitud entrante.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si el usuario tiene token de autenticación en sesión
        if (!Session::has('auth_token') || !Session::has('authenticated')) {
            return redirect()->route('login')
                ->with('warning', 'Debes iniciar sesión para acceder');
        }

        // Puedes validar el token aquí si lo deseas
        // $token = Session::get('auth_token');
        // if (!$this->validateToken($token)) {
        //     Session::flush();
        //     return redirect()->route('login')->with('error', 'Tu sesión ha expirado');
        // }

        return $next($request);
    }
}
