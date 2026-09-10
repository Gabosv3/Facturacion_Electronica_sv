<?php

namespace App\Http\Controllers;

use App\Mail\FacturaElectronicaMail;
use App\Mail\InvalidacionMail;
use App\Models\EventoInvalidacion;
use App\Models\Factura;
use App\Services\InvalidacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class InvalidacionController extends Controller
{
    private $invalidacionService;

    public function __construct(InvalidacionService $invalidacionService)
    {
        $this->invalidacionService = $invalidacionService;
    }

    /**
     * Muestra la lista de eventos de invalidación
     */
    public function index()
    {
        $eventos = EventoInvalidacion::with(['factura', 'facturaReemplazo'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('invalidaciones.index', compact('eventos'));
    }

    /**
     * Muestra el formulario para crear un evento de invalidación
     */
    public function create(Request $request)
    {
        $facturaId = $request->get('factura_id');
        $factura = null;

        if ($facturaId) {
            $factura = Factura::findOrFail($facturaId);

            // Validar que la factura pueda ser invalidada
            $validacion = $this->invalidacionService->validarInvalidacion($facturaId);
            if (!$validacion['puede_invalidarse']) {
                return redirect()->route('facturas.index')
                    ->with('error', $validacion['mensaje']);
            }
        }

        // Obtener facturas que pueden ser invalidadas
        $facturasInvalidables = Factura::where('estado', 'enviado_mh')
            ->where('invalidada', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('invalidaciones.crear', compact('factura', 'facturasInvalidables'));
    }

    /**
     * Procesa la creación de un evento de invalidación
     */
    public function store(Request $request)
    {
        $request->validate([
            'factura_id' => 'required|exists:facturas,id',
            'tipo_invalidacion' => 'required|in:1,2,3', // CAT-24: 1=Error info, 2=Rescindir, 3=Otros
            'motivo_invalidacion' => 'required|string|max:500',
            'factura_reemplazo_id' => 'nullable|exists:facturas,id',
            'responsable_nombre' => 'required|string|max:200',
            'responsable_tipo_doc' => 'required|string|max:2',
            'responsable_num_doc' => 'required|string|max:20',
            'solicitante_nombre' => 'required|string|max:200',
            'solicitante_tipo_doc' => 'required|string|max:2',
            'solicitante_num_doc' => 'required|string|max:20',
            // Normativa DTE 2.0 (MH): el evento de invalidación exige correo y teléfono
            // del receptor en el objeto "documento", el MH lo rechaza sin esto.
            'receptor_correo' => 'required|email|max:100',
            'receptor_telefono' => 'required|string|max:20',
        ]);

        try {
            // Si es tipo 1 (anulación por errores formales), debe haber factura de reemplazo
            if ($request->tipo_invalidacion == '1' && !$request->factura_reemplazo_id) {
                return back()->with('error', 'Para invalidación por errores formales debe seleccionar la factura de reemplazo');
            }

            // Obtener código de generación de la factura de reemplazo
            $codigoGeneracionReemplazo = null;
            if ($request->factura_reemplazo_id) {
                $facturaReemplazo = Factura::find($request->factura_reemplazo_id);
                $codigoGeneracionReemplazo = $facturaReemplazo->codigo_generacion;
            }

            // Procesar invalidación completa
            $resultado = $this->invalidacionService->procesarInvalidacion([
                'factura_id' => $request->factura_id,
                'tipo_invalidacion' => $request->tipo_invalidacion,
                'motivo_invalidacion' => $request->motivo_invalidacion,
                'factura_reemplazo_id' => $request->factura_reemplazo_id,
                'codigo_generacion_reemplazo' => $codigoGeneracionReemplazo,
                'responsable_nombre' => $request->responsable_nombre,
                'responsable_tipo_doc' => $request->responsable_tipo_doc,
                'responsable_num_doc' => $request->responsable_num_doc,
                'solicitante_nombre' => $request->solicitante_nombre,
                'solicitante_tipo_doc' => $request->solicitante_tipo_doc,
                'solicitante_num_doc' => $request->solicitante_num_doc,
                'receptor_correo' => $request->receptor_correo,
                'receptor_telefono' => $request->receptor_telefono,
                'fecha_evento' => now(),
            ]);

            if ($resultado['success']) {
                $evento = $resultado['evento'];
                $mensaje = 'Evento de invalidación procesado exitosamente';

                // Notificación obligatoria al receptor según Normativa de Cumplimiento DTE 2.0 (MH):
                // debe entregarse el JSON del evento y la representación gráfica (PDF) del evento
                // y del DTE invalidado.
                $resultadoNotificacion = $this->notificarReceptor($evento);
                $mensaje .= '. ' . $resultadoNotificacion['mensaje'];

                $resultadoReemplazo = $this->notificarFacturaReemplazo($evento->fresh(['factura', 'facturaReemplazo']));
                if ($resultadoReemplazo !== null) {
                    $mensaje .= ' ' . $resultadoReemplazo['mensaje'];
                }

                $huboProblema = !$resultadoNotificacion['success'] || ($resultadoReemplazo && !$resultadoReemplazo['success']);

                return redirect()->route('invalidaciones.show', $evento->id)
                    ->with($huboProblema ? 'warning' : 'success', $mensaje);
            }

            return back()->with('error', $resultado['message']);
        } catch (\Exception $e) {
            Log::error('Error al crear evento de invalidación', [
                'exception' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al procesar invalidación: ' . $e->getMessage());
        }
    }

    /**
     * Notifica al receptor de la invalidación por correo (JSON + PDFs adjuntos),
     * conforme lo exige la Normativa de Cumplimiento DTE 2.0 del MH.
     */
    private function notificarReceptor(EventoInvalidacion $evento): array
    {
        // Preferir el correo capturado en el propio evento (obligatorio desde
        // Normativa DTE 2.0); si el evento es anterior a ese cambio, usar el de la factura.
        $correo = $evento->receptor_correo ?? $evento->factura->receptor_correo;

        if (empty($correo)) {
            Log::warning('No se pudo notificar invalidación: receptor sin correo registrado', [
                'evento_id' => $evento->id,
                'factura_id' => $evento->factura_id,
            ]);

            return [
                'success' => false,
                'mensaje' => 'No se pudo notificar al receptor: no tiene correo electrónico registrado. Debes reenviarla manualmente una vez tengas su correo.',
            ];
        }

        try {
            Mail::to($correo)->send(new InvalidacionMail($evento));

            Log::info('Notificación de invalidación enviada al receptor', [
                'evento_id' => $evento->id,
                'correo' => $correo,
            ]);

            return [
                'success' => true,
                'mensaje' => "Se notificó al receptor ({$correo}) con el JSON y los PDF del evento y del documento invalidado.",
            ];
        } catch (\Exception $e) {
            Log::error('Error al notificar invalidación al receptor', [
                'evento_id' => $evento->id,
                'correo' => $correo,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'mensaje' => 'No se pudo enviar la notificación por correo: ' . $e->getMessage() . '. Puedes reintentarlo manualmente.',
            ];
        }
    }

    /**
     * Si la invalidación es tipo 1 (error de datos) y tiene documento de reemplazo,
     * envía automáticamente ese nuevo DTE al receptor en un correo aparte, para que
     * quede con un documento válido tras la anulación del original.
     */
    private function notificarFacturaReemplazo(EventoInvalidacion $evento): ?array
    {
        if ($evento->tipo_invalidacion != '1' || !$evento->facturaReemplazo) {
            return null;
        }

        $facturaReemplazo = $evento->facturaReemplazo;
        $correo = $facturaReemplazo->receptor_correo ?? $evento->factura->receptor_correo;

        if (empty($correo)) {
            Log::warning('No se pudo enviar factura de reemplazo: sin correo registrado', [
                'evento_id' => $evento->id,
                'factura_reemplazo_id' => $facturaReemplazo->id,
            ]);

            return [
                'success' => false,
                'mensaje' => 'No se pudo enviar la factura de reemplazo: no hay correo registrado.',
            ];
        }

        if ($facturaReemplazo->estado !== 'enviado_mh') {
            return [
                'success' => false,
                'mensaje' => 'La factura de reemplazo aún no ha sido enviada al MH, no se pudo notificar.',
            ];
        }

        try {
            Mail::to($correo)->send(new FacturaElectronicaMail($facturaReemplazo));

            Log::info('Factura de reemplazo enviada tras invalidación', [
                'evento_id' => $evento->id,
                'factura_reemplazo_id' => $facturaReemplazo->id,
                'correo' => $correo,
            ]);

            return [
                'success' => true,
                'mensaje' => "Se envió el documento de reemplazo ({$facturaReemplazo->numero_control}) a {$correo}.",
            ];
        } catch (\Exception $e) {
            Log::error('Error al enviar factura de reemplazo tras invalidación', [
                'evento_id' => $evento->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'mensaje' => 'No se pudo enviar la factura de reemplazo: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Reenvía manualmente la notificación de invalidación al receptor
     */
    public function enviarEmail($id)
    {
        $evento = EventoInvalidacion::with(['factura', 'facturaReemplazo'])->findOrFail($id);

        if (empty($evento->receptor_correo) && empty($evento->factura->receptor_correo)) {
            return back()->with('error', 'Este evento no tiene un correo electrónico registrado para el receptor.');
        }

        if (!$evento->fueExitosa()) {
            return back()->with('warning', 'Solo se pueden notificar eventos de invalidación procesados exitosamente por el MH.');
        }

        $resultado = $this->notificarReceptor($evento);

        return back()->with($resultado['success'] ? 'success' : 'error', $resultado['mensaje']);
    }

    /**
     * Muestra los detalles de un evento de invalidación
     */
    public function show($id)
    {
        $evento = EventoInvalidacion::with(['factura', 'facturaReemplazo'])->findOrFail($id);

        return view('invalidaciones.detalle', compact('evento'));
    }

    /**
     * Descarga el PDF del evento de invalidación (el mismo que se adjunta
     * por correo), para poder enviarlo manualmente por WhatsApp u otro medio.
     */
    public function descargarPdf($id)
    {
        $evento = EventoInvalidacion::with(['factura', 'facturaReemplazo'])->findOrFail($id);

        try {
            $pdf = Pdf::loadView('invalidaciones.pdf', ['evento' => $evento]);

            return $pdf->download('invalidacion_' . $evento->factura->numero_control . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error al generar PDF de invalidación para descarga: ' . $e->getMessage(), [
                'evento_id' => $evento->id,
            ]);

            return back()->with('error', 'Error al generar el PDF: ' . $e->getMessage());
        }
    }

    /**
     * Reintenta el envío de un evento de invalidación
     */
    public function reintentar($id)
    {
        try {
            $evento = EventoInvalidacion::findOrFail($id);

            if (!$evento->puedeEnviarse()) {
                return back()->with('error', 'El evento no puede ser reenviado en su estado actual');
            }

            // Si no está firmado, firmar primero
            if ($evento->estado === 'pendiente') {
                $resultadoFirma = $this->invalidacionService->firmarEvento($evento);
                if (!$resultadoFirma['success']) {
                    return back()->with('error', $resultadoFirma['message']);
                }
            }

            // Enviar al MH
            $resultado = $this->invalidacionService->enviarEvento($evento);

            if ($resultado['success']) {
                $mensaje = 'Evento reenviado exitosamente';

                $eventoFresco = $evento->fresh(['factura', 'facturaReemplazo']);

                $resultadoNotificacion = $this->notificarReceptor($eventoFresco);
                $mensaje .= '. ' . $resultadoNotificacion['mensaje'];

                $resultadoReemplazo = $this->notificarFacturaReemplazo($eventoFresco);
                if ($resultadoReemplazo !== null) {
                    $mensaje .= ' ' . $resultadoReemplazo['mensaje'];
                }

                $huboProblema = !$resultadoNotificacion['success'] || ($resultadoReemplazo && !$resultadoReemplazo['success']);

                return redirect()->route('invalidaciones.show', $evento->id)
                    ->with($huboProblema ? 'warning' : 'success', $mensaje);
            }

            return back()->with('error', $resultado['message']);
        } catch (\Exception $e) {
            Log::error('Error al reintentar envío de invalidación', [
                'exception' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al reintentar envío: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el formulario para seleccionar la factura a invalidar
     */
    public function seleccionarFactura()
    {
        $facturas = Factura::where('estado', 'enviado_mh')
            ->where('invalidada', false)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('invalidaciones.seleccionar', compact('facturas'));
    }
}
