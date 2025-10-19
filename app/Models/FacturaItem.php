<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacturaItem extends Model
{
    use HasFactory;

    protected $table = 'factura_items';

    protected $fillable = [
        'factura_id',
        'num_item',
        'codigo',
        'descripcion',
        'cantidad',
        'uni_medida',
        'precio_unitario',
        'monto_descuento',
        'venta_gravada',
        'venta_exenta',
        'venta_no_sujeta',
        'iva_item',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'monto_descuento' => 'decimal:2',
        'venta_gravada' => 'decimal:2',
        'venta_exenta' => 'decimal:2',
        'venta_no_sujeta' => 'decimal:2',
        'iva_item' => 'decimal:2',
    ];

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }
}