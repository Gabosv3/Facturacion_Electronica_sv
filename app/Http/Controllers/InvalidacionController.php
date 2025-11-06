<?php

namespace App\Http\Controllers;

use App\Models\EventoInvalidacion;
use App\Models\Factura;
use App\Services\InvalidacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
                'fecha_evento' => now(),
            ]);

            if ($resultado['success']) {
                return redirect()->route('invalidaciones.show', $resultado['evento']->id)
                    ->with('success', 'Evento de invalidación procesado exitosamente');
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
     * Muestra los detalles de un evento de invalidación
     */
    public function show($id)
    {
        $evento = EventoInvalidacion::with(['factura', 'facturaReemplazo'])->findOrFail($id);

        return view('invalidaciones.detalle', compact('evento'));
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
                return redirect()->route('invalidaciones.show', $evento->id)
                    ->with('success', 'Evento reenviado exitosamente');
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
