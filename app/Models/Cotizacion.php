<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cotizacion extends Model
{
    use HasFactory;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'numero_cotizacion',
        'fecha',
        'cliente_nombre',
        'cliente_documento',
        'cliente_telefono',
        'cliente_correo',
        'subtotal',
        'total_descuento',
        'total_iva',
        'total',
        'observaciones',
        'estado',
        'factura_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'subtotal' => 'decimal:2',
        'total_descuento' => 'decimal:2',
        'total_iva' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CotizacionItem::class);
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    public function getEstadoNombreAttribute(): string
    {
        return match ($this->estado) {
            'pendiente' => 'Pendiente',
            'aprobada' => 'Aprobada',
            'rechazada' => 'Rechazada',
            'convertida' => 'Convertida a Factura',
            default => ucfirst($this->estado),
        };
    }
}
