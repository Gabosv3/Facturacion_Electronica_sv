<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoInvalidacion extends Model
{
    use HasFactory;

    protected $table = 'eventos_invalidacion';

    protected $fillable = [
        'factura_id',
        'codigo_invalidacion',
        'version',
        'ambiente',
        'fecha_evento',
        'tipo_invalidacion',
        'motivo_invalidacion',
        'factura_reemplazo_id',
        'codigo_generacion_reemplazo',
        'responsable_nombre',
        'responsable_tipo_doc',
        'responsable_num_doc',
        'solicitante_nombre',
        'solicitante_tipo_doc',
        'solicitante_num_doc',
        'receptor_correo',
        'receptor_telefono',
        'evento_json',
        'evento_firmado',
        'estado',
        'respuesta_mh',
        'sello_recepcion',
        'fecha_procesamiento',
    ];

    protected $casts = [
        'fecha_evento' => 'datetime',
        'fecha_procesamiento' => 'datetime',
        'evento_json' => 'array',
        'respuesta_mh' => 'array',
    ];

    /**
     * Relación con la factura que se está invalidando
     */
    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    /**
     * Relación con la factura de reemplazo (si aplica)
     */
    public function facturaReemplazo(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'factura_reemplazo_id');
    }

    /**
     * Obtiene el nombre del tipo de invalidación según CAT-24
     */
    public function getTipoInvalidacionNombreAttribute(): string
    {
        $tipos = [
            '1' => 'Error de información del DTE a invalidar',
            '2' => 'Rescindir operación realizada',
            '3' => 'Otros motivos',
        ];

        return $tipos[$this->tipo_invalidacion] ?? 'Desconocido';
    }

    /**
     * Verifica si el evento puede ser enviado
     */
    public function puedeEnviarse(): bool
    {
        return in_array($this->estado, ['firmado', 'error']);
    }

    /**
     * Verifica si la invalidación fue exitosa
     */
    public function fueExitosa(): bool
    {
        return $this->estado === 'procesado' && !empty($this->sello_recepcion);
    }

    /**
     * Scope para eventos pendientes
     */
    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['pendiente', 'firmado', 'error']);
    }

    /**
     * Scope para eventos procesados
     */
    public function scopeProcesados($query)
    {
        return $query->where('estado', 'procesado');
    }
}
