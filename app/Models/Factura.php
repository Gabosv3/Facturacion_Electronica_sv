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
        // Campos de DTE-05 (Nota de Crédito)
        'documento_relacionado_id',
        'documento_relacionado_tipo_dte',
        'documento_relacionado_numero_control',
        'documento_relacionado_codigo_generacion',
        'documento_relacionado_fecha_emision',
        'tipo_documento',
        // Estado de nota de crédito
        'nota_credito_estado',
        'nota_credito_id',
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

    /**
     * Relación con el documento relacionado (para DTE-05)
     * Una nota de crédito pertenece a un documento original
     */
    public function documentoRelacionado()
    {
        return $this->belongsTo(Factura::class, 'documento_relacionado_id');
    }

    /**
     * Relación con la nota de crédito asociada
     * Un documento puede tener una nota de crédito
     */
    public function notaCredito()
    {
        return $this->belongsTo(Factura::class, 'nota_credito_id');
    }

    /**
     * Relación con las notas de crédito que referencian este documento
     * Un documento puede tener múltiples notas de crédito
     */
    public function notasCredito()
    {
        return $this->hasMany(Factura::class, 'documento_relacionado_id')
            ->where('tipo_dte', '05');
    }
}