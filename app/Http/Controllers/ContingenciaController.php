<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EventoContingencia;
use App\Services\ContingenciaService;
use App\Services\MHApiService;
use Illuminate\Support\Facades\Log;

class ContingenciaController extends Controller
{
    protected $contingenciaService;
    protected $mhApiService;

    public function __construct(ContingenciaService $contingenciaService, MHApiService $mhApiService)
    {
        $this->contingenciaService = $contingenciaService;
        $this->mhApiService = $mhApiService;
    }

    /**
     * Mostrar lista de eventos de contingencia
     */
    public function index()
    {
        $eventos = EventoContingencia::where('nit_emisor', config('dte.emisor.nit'))
            ->with('facturas')
            ->orderBy('fecha_hora_inicio', 'desc')
            ->paginate(20);

        $eventoActivo = $this->contingenciaService->getEventoActivo();

        return view('contingencias.index', compact('eventos', 'eventoActivo'));
    }

    /**
     * Mostrar formulario para crear evento de contingencia
     */
    public function create()
    {
        // Verificar que no haya evento activo
        if ($this->contingenciaService->hayEventoActivo()) {
            return redirect()->route('contingencias.index')
                ->with('error', 'Ya existe un evento de contingencia activo. Debes cerrarlo antes de crear uno nuevo.');
        }

        return view('contingencias.create');
    }

    /**
     * Crear nuevo evento de contingencia
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_contingencia' => 'required|integer|min:1|max:5',
            'motivo_contingencia' => 'required|string|min:10|max:500',
        ]);

        // Verificar que no haya evento activo
        if ($this->contingenciaService->hayEventoActivo()) {
            return redirect()->route('contingencias.index')
                ->with('error', 'Ya existe un evento de contingencia activo.');
        }

        try {
            $evento = $this->contingenciaService->crearEvento(
                $validated['tipo_contingencia'],
                $validated['motivo_contingencia']
            );

            Log::info('✅ Evento de contingencia creado desde interfaz web', [
                'evento_id' => $evento->id,
                'tipo' => $validated['tipo_contingencia'],
            ]);

            return redirect()->route('contingencias.index')
                ->with('success', '🚨 Evento de contingencia activado. Todas las facturas nuevas se guardarán en modo contingencia.');
        } catch (\Exception $e) {
            Log::error('❌ Error al crear evento de contingencia', [
                'exception' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Error al crear evento de contingencia: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Ver detalles de un evento
     */
    public function show(EventoContingencia $evento)
    {
        // Refrescar el modelo desde la BD
        $evento = EventoContingencia::with('facturas')->findOrFail($evento->id);

        Log::info('📊 Mostrando evento de contingencia', [
            'evento_id' => $evento->id,
            'facturas_cargadas' => $evento->facturas->count(),
            'estado' => $evento->estado,
        ]);

        return view('contingencias.show', compact('evento'));
    }

    /**
     * Página de debug para evento
     */
    public function debug(EventoContingencia $evento)
    {
        // Refrescar el modelo desde la BD
        $evento = EventoContingencia::with('facturas')->findOrFail($evento->id);

        // Hacer query fresco
        $facturasFrescas = \App\Models\Factura::where('evento_contingencia_id', $evento->id)->get();

        // Count directo
        $countDirecto = \DB::table('facturas')->where('evento_contingencia_id', $evento->id)->count();

        return view('contingencias.debug', compact('evento', 'facturasFrescas', 'countDirecto'));
    }

    /**
     * Cerrar evento de contingencia
     */
    public function cerrar(EventoContingencia $evento)
    {
        if (!$evento->isActivo()) {
            return redirect()->route('contingencias.index')
                ->with('error', 'El evento de contingencia no está activo.');
        }

        try {
            $evento->cerrar();
            $evento->refresh(); // Refrescar para obtener el valor actualizado

            Log::info('🔒 Evento de contingencia cerrado desde interfaz web', [
                'evento_id' => $evento->id,
                'cantidad_dte' => $evento->cantidad_dte,
            ]);

            return redirect()->route('contingencias.show', $evento)
                ->with('success', '✅ Evento de contingencia cerrado. Ahora puedes procesarlo para enviar al MH.');
        } catch (\Exception $e) {
            Log::error('❌ Error al cerrar evento de contingencia', [
                'evento_id' => $evento->id,
                'exception' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Error al cerrar evento: ' . $e->getMessage());
        }
    }

    /**
     * Procesar evento: enviar al MH
     */
    public function procesar(EventoContingencia $evento)
    {
        if (!$evento->isListo()) {
            return redirect()->route('contingencias.show', $evento)
                ->with('error', 'El evento debe estar cerrado antes de procesarlo.');
        }

        try {
            // Paso 1: Construir y firmar el evento de contingencia
            Log::info('📤 Enviando evento de contingencia desde interfaz web', [
                'evento_id' => $evento->id,
            ]);

            $eventoJson = $this->contingenciaService->construirJsonEvento($evento);

            // Guardar el JSON del evento en la base de datos
            $evento->update([
                'evento_json' => $eventoJson['dteJson'] ?? $eventoJson,
            ]);

            // Firmar el evento usando el API local de firma
            $apiUrl = config('dte.api_url');

            Log::info('🔐 Enviando evento a firmar', [
                'url' => $apiUrl . '/firmardocumento/',
                'evento_id' => $evento->id,
            ]);

            $responseFirma = \Http::timeout(30)->post($apiUrl . '/firmardocumento/', $eventoJson);

            if (!$responseFirma->successful()) {
                throw new \Exception('Error al firmar evento: ' . $responseFirma->body());
            }

            $responseData = $responseFirma->json();
            $eventoFirmado = $responseData['body'] ?? null;

            if (!$eventoFirmado) {
                throw new \Exception('No se recibió el evento firmado');
            }

            Log::info('✅ Evento firmado recibido', [
                'longitud_jwt' => strlen($eventoFirmado),
            ]);

            // Enviar evento firmado al MH
            $resultado = $this->mhApiService->enviarEventoContingencia($eventoJson, $eventoFirmado);

            if (!$resultado['success']) {
                throw new \Exception($resultado['message']);
            }

            // Verificar si el MH aceptó o rechazó el evento
            $estadoEvento = $resultado['data']['estado'] ?? null;

            if ($estadoEvento === 'RECHAZADO') {
                // Guardar respuesta pero NO cambiar estado
                $evento->update([
                    'respuesta_mh' => json_encode($resultado['data']),
                ]);

                $observaciones = $resultado['data']['observaciones'] ?? [];
                $mensajeError = is_array($observaciones) ? implode(', ', $observaciones) : 'Error desconocido';

                throw new \Exception('Evento rechazado por el MH: ' . $mensajeError);
            }

            // Solo marcar como enviado si fue aceptado
            $evento->marcarEnviado($resultado['data']);

            // Paso 2: Enviar DTEs de contingencia uno por uno al MH
            $facturas = $evento->facturas()
                ->where('estado', 'firmado_contingencia')
                ->get();

            if ($facturas->isEmpty()) {
                return redirect()->route('contingencias.show', $evento)
                    ->with('warning', '⚠️ Evento enviado pero no hay DTEs firmados para enviar.');
            }

            Log::info('📦 Enviando DTEs de contingencia al MH', [
                'cantidad_dtes' => $facturas->count(),
            ]);

            $exitosos = 0;
            $errores = [];

            foreach ($facturas as $factura) {
                // Obtener JWT firmado de la factura
                $respuestaMh = is_string($factura->respuesta_mh)
                    ? json_decode($factura->respuesta_mh, true)
                    : $factura->respuesta_mh;

                $jwtFirmado = $respuestaMh['jwt_firmado'] ?? null;

                if (!$jwtFirmado) {
                    $errores[] = "Factura #{$factura->id}: No tiene JWT firmado";
                    continue;
                }

                // Enviar DTE al MH usando el método normal
                $resultadoDTE = $this->mhApiService->enviarDTE($jwtFirmado, [
                    'ambiente' => config('dte.ambiente'),
                    'id_envio' => $factura->id,
                    'version' => $factura->tipo_dte === '03' ? 3 : 2,
                    'tipo_dte' => $factura->tipo_dte,
                    'codigo_generacion' => $factura->codigo_generacion,
                ]);

                if ($resultadoDTE['success']) {
                    $exitosos++;

                    // Solo actualizar si el DTE fue realmente aceptado
                    $respuesta = $resultadoDTE['data'] ?? [];
                    $estadoMH = $respuesta['estado'] ?? null;

                    if ($estadoMH === 'PROCESADO' || $estadoMH === 'RECIBIDO') {
                        $factura->update([
                            'estado' => 'enviado_mh',
                            'estado_lote' => 'PROCESADO',
                        ]);

                        // Si es una nota de crédito (DTE-05), actualizar el estado del documento relacionado
                        if ($factura->tipo_dte === '05' && $factura->documento_relacionado_id) {
                            $facturaRelacionada = Factura::find($factura->documento_relacionado_id);
                            if ($facturaRelacionada) {
                                // Mapear tipo_documento a estado legible
                                $estadoNotaCredito = match($factura->tipo_documento) {
                                    '1' => 'anulacion',
                                    '2' => 'ajuste_precio',
                                    '3' => 'devolucion',
                                    default => 'nota_credito',
                                };

                                $facturaRelacionada->update([
                                    'nota_credito_estado' => $estadoNotaCredito,
                                    'nota_credito_id' => $factura->id,
                                ]);

                                Log::info('📝 Estado de documento relacionado actualizado (contingencia)', [
                                    'factura_relacionada_id' => $facturaRelacionada->id,
                                    'numero_control' => $facturaRelacionada->numero_control,
                                    'nota_credito_estado' => $estadoNotaCredito,
                                    'nota_credito_id' => $factura->id,
                                ]);
                            }
                        }

                        Log::info('✅ DTE de contingencia enviado y aceptado', [
                            'factura_id' => $factura->id,
                            'numero_control' => $factura->numero_control,
                            'estado_mh' => $estadoMH,
                        ]);
                    } else {
                        $errores[] = "Factura #{$factura->id}: Rechazado por MH - {$estadoMH}";
                        Log::warning('⚠️ DTE de contingencia enviado pero rechazado', [
                            'factura_id' => $factura->id,
                            'estado_mh' => $estadoMH,
                        ]);
                    }
                } else {
                    $errores[] = "Factura #{$factura->id}: " . $resultadoDTE['message'];
                    Log::error('❌ Error al enviar DTE de contingencia', [
                        'factura_id' => $factura->id,
                        'error' => $resultadoDTE['message'],
                    ]);
                }
            }

            // Solo marcar como procesado si todos fueron exitosos
            if ($exitosos > 0 && $exitosos === $facturas->count()) {
                $evento->update(['estado' => 'procesado']);
            }

            $mensaje = "✅ Evento enviado. DTEs procesados: {$exitosos}/{$facturas->count()}";
            if (!empty($errores)) {
                $mensaje .= ". Errores: " . implode(', ', $errores);
            }

            return redirect()->route('contingencias.show', $evento)
                ->with($exitosos === $facturas->count() ? 'success' : 'warning', $mensaje);
        } catch (\Exception $e) {
            Log::error('❌ Error al procesar evento de contingencia', [
                'evento_id' => $evento->id,
                'exception' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Error al procesar evento: ' . $e->getMessage());
        }
    }

    /**
     * Consultar estado del lote
     */
    public function consultarLote(EventoContingencia $evento)
    {
        if (!$evento->codigo_lote) {
            return redirect()->route('contingencias.show', $evento)
                ->with('error', 'El evento no tiene un código de lote asignado.');
        }

        try {
            $resultado = $this->mhApiService->consultarLote($evento->codigo_lote);

            if (!$resultado['success']) {
                throw new \Exception($resultado['message']);
            }

            $estado = $resultado['estado'];

            if ($estado === 'PROCESADO') {
                $evento->marcarProcesado($resultado['data']);

                return redirect()->route('contingencias.show', $evento)
                    ->with('success', '🎉 Lote procesado completamente por el MH. ¡Contingencia finalizada!');
            }

            return redirect()->route('contingencias.show', $evento)
                ->with('info', "⏳ Estado del lote: {$estado}. El MH aún está procesando (puede tardar 2-3 minutos).");
        } catch (\Exception $e) {
            Log::error('❌ Error al consultar lote', [
                'evento_id' => $evento->id,
                'codigo_lote' => $evento->codigo_lote,
                'exception' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Error al consultar lote: ' . $e->getMessage());
        }
    }
}
