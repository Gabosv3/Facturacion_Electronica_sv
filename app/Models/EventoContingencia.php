<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventoContingencia extends Model
{
    use HasFactory;

    protected $table = 'eventos_contingencia';

    protected $fillable = [
        'codigo_evento',
        'nit_emisor',
        'tipo_contingencia',
        'motivo_contingencia',
        'evento_json',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'cantidad_dte',
        'estado',
        'respuesta_mh',
        'codigo_lote',
        'detalle_lote',
    ];

    protected $casts = [
        'fecha_hora_inicio' => 'datetime',
        'fecha_hora_fin' => 'datetime',
        'evento_json' => 'array',
        'detalle_lote' => 'array',
    ];

    /**
     * Tipos de contingencia según catálogo MH CAT-024
     */
    public const TIPOS_CONTINGENCIA = [
        1 => 'Falla en el suministro de energía eléctrica del emisor',
        2 => 'Falla del sistema de emisión del contribuyente',
        3 => 'Falla en el servicio de internet',
        4 => 'Falla en los servicios de la Administración Tributaria',
        5 => 'Otros eventos de fuerza mayor',
    ];

    /**
     * Estados del evento de contingencia
     */
    public const ESTADO_ACTIVO = 'activo';      // Contingencia activa, aún generando DTEs
    public const ESTADO_CERRADO = 'cerrado';    // Contingencia cerrada, lista para enviar
    public const ESTADO_ENVIADO = 'enviado';    // Evento enviado al MH
    public const ESTADO_PROCESADO = 'procesado'; // Lote procesado completamente

    /**
     * Relación con facturas
     */
    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'evento_contingencia_id');
    }

    /**
     * Verificar si el evento está activo
     */
    public function isActivo(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO;
    }

    /**
     * Verificar si el evento está cerrado y listo para enviar
     */
    public function isListo(): bool
    {
        return $this->estado === self::ESTADO_CERRADO;
    }

    /**
     * Cerrar el evento de contingencia
     */
    public function cerrar(): void
    {
        // Primero contar las facturas ANTES de actualizar
        $cantidadDte = $this->facturas()->count();

        $this->update([
            'fecha_hora_fin' => now(),
            'estado' => self::ESTADO_CERRADO,
            'cantidad_dte' => $cantidadDte,
        ]);
    }

    /**
     * Marcar como enviado al MH
     */
    public function marcarEnviado(array $respuestaMh): void
    {
        $this->update([
            'estado' => self::ESTADO_ENVIADO,
            'respuesta_mh' => json_encode($respuestaMh),
        ]);
    }

    /**
     * Actualizar con código de lote
     */
    public function actualizarCodigoLote(string $codigoLote): void
    {
        $this->update([
            'codigo_lote' => $codigoLote,
        ]);
    }

    /**
     * Marcar como procesado
     */
    public function marcarProcesado(array $detalleLote): void
    {
        $this->update([
            'estado' => self::ESTADO_PROCESADO,
            'detalle_lote' => $detalleLote,
        ]);
    }

    /**
     * Obtener descripción del tipo de contingencia
     */
    public function getTipoContingenciaDescripcionAttribute(): string
    {
        return self::TIPOS_CONTINGENCIA[$this->tipo_contingencia] ?? 'Desconocido';
    }
}
