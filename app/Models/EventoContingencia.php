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
     * Marcar como procesado y actualizar facturas con sellos de recepción
     */
    public function marcarProcesado(array $detalleLote): void
    {
        $this->update([
            'estado' => self::ESTADO_PROCESADO,
            'detalle_lote' => $detalleLote,
        ]);

        // Actualizar facturas con los sellos de recepción del MH
        $this->actualizarFacturasConSellos($detalleLote);
    }

    /**
     * Actualizar facturas con sellos de recepción del MH
     */
    private function actualizarFacturasConSellos(array $detalleLote): void
    {
        // El detalle del lote contiene la información de cada DTE procesado
        // Estructura esperada: $detalleLote['respuestaDTE'] = array de DTEs
        if (!isset($detalleLote['respuestaDTE']) || !is_array($detalleLote['respuestaDTE'])) {
            \Log::warning('⚠️ No se encontró respuestaDTE en el detalle del lote', [
                'evento_id' => $this->id,
                'detalle_keys' => array_keys($detalleLote),
            ]);
            return;
        }

        $respuestaDTE = $detalleLote['respuestaDTE'];

        \Log::info('🔄 Actualizando facturas con sellos de recepción', [
            'evento_id' => $this->id,
            'cantidad_respuestas' => count($respuestaDTE),
        ]);

        $facturasActualizadas = 0;
        $facturasSinSello = 0;

        foreach ($respuestaDTE as $dteRespuesta) {
            // Buscar la factura por código de generación
            $codigoGeneracion = $dteRespuesta['codigoGeneracion'] ?? null;

            if (!$codigoGeneracion) {
                \Log::warning('⚠️ DTE sin código de generación en respuesta');
                continue;
            }

            $factura = Factura::where('codigo_generacion', $codigoGeneracion)
                ->where('evento_contingencia_id', $this->id)
                ->first();

            if (!$factura) {
                \Log::warning('⚠️ No se encontró factura con código de generación', [
                    'codigo_generacion' => $codigoGeneracion,
                ]);
                continue;
            }

            // Extraer sello de recepción
            $selloRecibido = $dteRespuesta['selloRecibido'] ?? null;
            $estado = $dteRespuesta['estado'] ?? null;

            if (!$selloRecibido) {
                \Log::warning('⚠️ DTE sin sello de recepción', [
                    'codigo_generacion' => $codigoGeneracion,
                    'estado' => $estado,
                ]);
                $facturasSinSello++;
                continue;
            }

            // Actualizar factura con sello y estado
            $respuestaMhActual = $factura->respuesta_mh;

            // Si respuesta_mh es string, decodificar
            if (is_string($respuestaMhActual)) {
                $respuestaMhActual = json_decode($respuestaMhActual, true) ?? [];
            }

            // Agregar información del envío al MH desde contingencia
            $respuestaMhActual['envio_mh'] = [
                'selloRecibido' => $selloRecibido,
                'estado' => $estado,
                'fhProcesamiento' => $dteRespuesta['fhProcesamiento'] ?? now()->format('d/m/Y H:i:s'),
                'clasificaMsg' => $dteRespuesta['clasificaMsg'] ?? null,
                'codigoMsg' => $dteRespuesta['codigoMsg'] ?? null,
                'descripcionMsg' => $dteRespuesta['descripcionMsg'] ?? null,
                'observaciones' => $dteRespuesta['observaciones'] ?? [],
                'via_contingencia' => true,
                'codigo_lote' => $this->codigo_lote,
            ];

            $factura->update([
                'estado' => 'enviado_mh',
                'estado_lote' => $estado,
                'respuesta_mh' => $respuestaMhActual,
            ]);

            \Log::info('✅ Factura actualizada con sello de recepción', [
                'factura_id' => $factura->id,
                'numero_control' => $factura->numero_control,
                'sello_recibido' => substr($selloRecibido, 0, 20) . '...',
                'estado' => $estado,
            ]);

            $facturasActualizadas++;
        }

        \Log::info('📊 Resumen de actualización de facturas', [
            'evento_id' => $this->id,
            'facturas_actualizadas' => $facturasActualizadas,
            'facturas_sin_sello' => $facturasSinSello,
            'total_procesadas' => $facturasActualizadas + $facturasSinSello,
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
