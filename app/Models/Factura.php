<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_control',
        'codigo_generacion',
        'tipo_dte',
        'fecha_emision',
        'hora_emision',
        'receptor_tipo_documento',
        'receptor_num_documento',
        'receptor_nombre',
        'receptor_nrc',
        'receptor_actividad',
        'receptor_direccion',
        'receptor_telefono',
        'receptor_correo',
        'total_gravada',
        'total_exenta',
        'total_no_sujeto',
        'total_descuento',
        'sub_total',
        'total_iva',
        'total_pagar',
        'total_letras',
        'condicion_operacion',
        'dte_json',
        'estado',
        'intentos_envio',
        'respuesta_mh',
        // Campos de contingencia
        'en_contingencia',
        'evento_contingencia_id',
        'estado_lote',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'dte_json' => 'array',
        'total_gravada' => 'decimal:2',
        'total_exenta' => 'decimal:2',
        'total_no_sujeto' => 'decimal:2',
        'total_descuento' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'total_iva' => 'decimal:2',
        'total_pagar' => 'decimal:2',
        'en_contingencia' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(FacturaItem::class);
    }

    /**
     * Relación con evento de contingencia
     */
    public function eventoContingencia()
    {
        return $this->belongsTo(EventoContingencia::class, 'evento_contingencia_id');
    }
}