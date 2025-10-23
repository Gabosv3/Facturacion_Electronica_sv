<?php

namespace App\Services;

use App\Models\EventoContingencia;
use App\Models\Factura;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ContingenciaService
{
    /**
     * Crear un nuevo evento de contingencia
     */
    public function crearEvento(int $tipoContingencia, string $motivoContingencia): EventoContingencia
    {
        Log::info('🚨 Creando evento de contingencia', [
            'tipo' => $tipoContingencia,
            'motivo' => $motivoContingencia,
        ]);

        $evento = EventoContingencia::create([
            'codigo_evento' => strtoupper(Str::uuid()->toString()),
            'nit_emisor' => config('dte.emisor.nit'),
            'tipo_contingencia' => $tipoContingencia,
            'motivo_contingencia' => $motivoContingencia,
            'fecha_hora_inicio' => now(),
            'estado' => EventoContingencia::ESTADO_ACTIVO,
        ]);

        Log::info('✅ Evento de contingencia creado', [
            'id' => $evento->id,
            'codigo' => $evento->codigo_evento,
        ]);

        return $evento;
    }

    /**
     * Obtener el evento de contingencia activo actual
     */
    public function getEventoActivo(): ?EventoContingencia
    {
        return EventoContingencia::where('estado', EventoContingencia::ESTADO_ACTIVO)
            ->where('nit_emisor', config('dte.emisor.nit'))
            ->orderBy('fecha_hora_inicio', 'desc')
            ->first();
    }

    /**
     * Verificar si hay un evento de contingencia activo
     */
    public function hayEventoActivo(): bool
    {
        return $this->getEventoActivo() !== null;
    }

    /**
     * Cerrar el evento de contingencia activo
     */
    public function cerrarEventoActivo(): ?EventoContingencia
    {
        $evento = $this->getEventoActivo();

        if (!$evento) {
            Log::warning('⚠️ No hay evento de contingencia activo para cerrar');
            return null;
        }

        Log::info('🔒 Cerrando evento de contingencia', [
            'id' => $evento->id,
            'codigo' => $evento->codigo_evento,
            'cantidad_dte' => $evento->facturas()->count(),
        ]);

        $evento->cerrar();

        return $evento;
    }

    /**
     * Construir JSON del evento de contingencia para enviar al MH
     */
    public function construirJsonEvento(EventoContingencia $evento): array
    {
        $facturas = $evento->facturas()->get();

        // 🔍 LOG: Verificar cuántas facturas se encontraron
        Log::info('🔍 Construyendo JSON de evento de contingencia', [
            'evento_id' => $evento->id,
            'facturas_encontradas' => $facturas->count(),
            'evento_cantidad_dte_campo' => $evento->cantidad_dte,
        ]);

        // Obtener el rango de fechas de los DTEs
        $fechaInicio = $evento->fecha_hora_inicio->format('Y-m-d');
        $fechaFin = $evento->fecha_hora_fin ? $evento->fecha_hora_fin->format('Y-m-d') : now()->format('Y-m-d');

        // Construir detalle de DTEs informados
        $detalleDte = $facturas->map(function ($factura, $index) {
            return [
                'noItem' => $index + 1, // Número secuencial desde 1
                'codigoGeneracion' => $factura->codigo_generacion,
                'tipoDoc' => $factura->tipo_dte,
            ];
        })->values()->toArray();

        // 🔍 LOG: Verificar el detalle construido
        Log::info('🔍 Detalle de DTEs construido', [
            'cantidad_items' => count($detalleDte),
            'detalle' => $detalleDte,
        ]);

        // Obtener datos de la dirección del emisor
        $direccionEmisor = config('dte.emisor.direccion');

        return [
            'nit' => config('dte.nit'),
            'activo' => true,
            'passwordPri' => config('dte.password_pri'),
            'dteJson' => [
                'identificacion' => [
                    'version' => 3,
                    'ambiente' => config('dte.ambiente', '00'),
                    'codigoGeneracion' => $evento->codigo_evento,
                    'fTransmision' => $evento->fecha_hora_inicio->format('Y-m-d'),
                    'hTransmision' => $evento->fecha_hora_inicio->format('H:i:s'),
                ],
                'emisor' => [
                    'nit' => config('dte.emisor.nit'),
                    'nombre' => config('dte.emisor.nombre'),
                    'nombreResponsable' => config('dte.emisor.nombre'),
                    'tipoDocResponsable' => '36',
                    'numeroDocResponsable' => config('dte.emisor.nit'),
                    'tipoEstablecimiento' => '01',
                    'codEstableMH' => null,
                    'codPuntoVenta' => null,
                    'telefono' => config('dte.emisor.telefono'),
                    'correo' => config('dte.emisor.correo'),
                ],
                'detalleDTE' => $detalleDte,
                'motivo' => [
                    'fInicio' => $fechaInicio,
                    'fFin' => $fechaFin,
                    'hInicio' => $evento->fecha_hora_inicio->format('H:i:s'),
                    'hFin' => $evento->fecha_hora_fin ? $evento->fecha_hora_fin->format('H:i:s') : now()->format('H:i:s'),
                    'tipoContingencia' => $evento->tipo_contingencia,
                    'motivoContingencia' => $evento->motivo_contingencia,
                ],
            ],
        ];
    }

    /**
     * Preparar lote de DTEs firmados para enviar al MH
     */
    public function prepararLote(EventoContingencia $evento): array
    {
        $facturas = $evento->facturas()
            ->where('estado', 'firmado_contingencia')
            ->limit(config('dte.max_dte_por_lote', 100))
            ->get();

        if ($facturas->isEmpty()) {
            Log::warning('⚠️ No hay facturas firmadas para enviar en lote', [
                'evento_id' => $evento->id,
            ]);
            return [];
        }

        $dtes = $facturas->map(function ($factura) {
            // Obtener el JWT firmado de la respuesta_mh
            $respuestaMh = is_string($factura->respuesta_mh)
                ? json_decode($factura->respuesta_mh, true)
                : $factura->respuesta_mh;

            return $respuestaMh['jwt_firmado'] ?? null;
        })->filter()->values()->toArray();

        return [
            'nit' => config('dte.nit'),
            'passwordPri' => config('dte.password_pri'),
            'documento' => [
                'codigoLote' => strtoupper(Str::uuid()->toString()),
                'codigoContingencia' => $evento->codigo_evento,
                'listadoDte' => $dtes,
            ],
        ];
    }

    /**
     * Obtener eventos listos para enviar
     */
    public function getEventosListosParaEnviar()
    {
        return EventoContingencia::where('estado', EventoContingencia::ESTADO_CERRADO)
            ->where('nit_emisor', config('dte.emisor.nit'))
            ->orderBy('fecha_hora_inicio', 'asc')
            ->get();
    }

    /**
     * Marcar factura como en contingencia
     */
    public function marcarFacturaEnContingencia(Factura $factura, EventoContingencia $evento): void
    {
        $factura->update([
            'en_contingencia' => true,
            'evento_contingencia_id' => $evento->id,
            'estado' => 'firmado_contingencia',
        ]);

        // 🔍 Verificar que se guardó correctamente
        $factura->refresh(); // Refrescar desde BD

        Log::info('📋 Factura marcada en contingencia', [
            'factura_id' => $factura->id,
            'evento_id' => $evento->id,
            'codigo_generacion' => $factura->codigo_generacion,
            'en_contingencia_verificado' => $factura->en_contingencia,
            'evento_contingencia_id_verificado' => $factura->evento_contingencia_id,
        ]);

        // 🔍 Verificar la relación inversa
        $totalFacturas = $evento->facturas()->count();
        Log::info('📊 Total de facturas en el evento después de marcar', [
            'evento_id' => $evento->id,
            'total_facturas' => $totalFacturas,
        ]);
    }
}
