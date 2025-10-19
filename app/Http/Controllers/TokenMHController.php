<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TokenMH;
use App\Services\MHApiService;

class TokenMHController extends Controller
{
    protected $mhApiService;

    public function __construct(MHApiService $mhApiService)
    {
        $this->mhApiService = $mhApiService;
    }

    /**
     * Mostrar tokens del MH
     */
    public function index()
    {
        $tokens = TokenMH::orderBy('created_at', 'desc')->paginate(10);
        
        $tokenActivo = TokenMH::where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        return view('admin.tokens', compact('tokens', 'tokenActivo'));
    }

    /**
     * Renovar token manualmente
     */
    public function renovar()
    {
        if ($this->mhApiService->renovarToken()) {
            return redirect()->route('admin.tokens.index')
                ->with('success', 'Token renovado exitosamente');
        } else {
            return redirect()->route('admin.tokens.index')
                ->with('error', 'Error al renovar el token');
        }
    }
}