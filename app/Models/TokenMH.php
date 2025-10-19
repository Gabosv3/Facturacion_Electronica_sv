<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TokenMH extends Model
{
    use HasFactory;

    protected $table = 'tokens_mh';

    protected $fillable = [
        'token',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Verificar si el token ha expirado
     */
    public function isExpired(): bool
    {
        return Carbon::now()->greaterThanOrEqualTo($this->expires_at);
    }

    /**
     * Verificar si el token es válido
     */
    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }
}