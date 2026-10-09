<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotizacionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cotizacion_id',
        'num_item',
        'codigo',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'monto_descuento',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'monto_descuento' => 'decimal:2',
    ];

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function getSubtotalAttribute(): float
    {
        return ($this->cantidad * $this->precio_unitario) - $this->monto_descuento;
    }
}
