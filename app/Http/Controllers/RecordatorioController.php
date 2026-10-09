<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecordatorioController extends Controller
{
    private const ESTADOS_VALIDOS = ['enviado_mh', 'firmado', 'firmado_contingencia'];

    /**
     * Palabras clave que identifican servicios de mantenimiento recurrente
     * (principalmente cambios de aceite, que es el servicio más predecible
     * en un taller mecánico).
     */
    private const PALABRAS_CLAVE = ['aceite', 'filtro', 'afinacion', 'afinación', 'lubricante'];

    public function index(Request $request)
    {
        $diasUmbral = (int) $request->input('dias', 90);

        $query = DB::table('factura_items as fi')
            ->join('facturas as f', 'f.id', '=', 'fi.factura_id')
            ->whereIn('f.estado', self::ESTADOS_VALIDOS)
            ->whereNotNull('f.receptor_num_documento')
            ->where('f.receptor_nombre', '!=', 'CLIENTES VARIOS')
            ->where(function ($q) {
                foreach (self::PALABRAS_CLAVE as $palabra) {
                    $q->orWhere('fi.descripcion', 'LIKE', "%{$palabra}%");
                }
            })
            ->select('f.receptor_num_documento', 'f.receptor_nombre', 'f.receptor_telefono', 'f.receptor_correo')
            ->selectRaw('MAX(f.fecha_emision) as ultima_visita')
            ->selectRaw('COUNT(DISTINCT f.id) as veces_visitado')
            ->groupBy('f.receptor_num_documento', 'f.receptor_nombre', 'f.receptor_telefono', 'f.receptor_correo')
            ->orderBy('ultima_visita');

        $clientes = $query->get()->map(function ($row) {
            $ultimaVisita = \Carbon\Carbon::parse($row->ultima_visita);
            $row->dias_transcurridos = $ultimaVisita->diffInDays(now());
            $row->ultima_visita_formato = $ultimaVisita->format('d/m/Y');
            return $row;
        })->sortByDesc('dias_transcurridos')->values();

        $vencidos = $clientes->filter(fn($c) => $c->dias_transcurridos >= $diasUmbral);
        $proximos = $clientes->filter(fn($c) => $c->dias_transcurridos >= ($diasUmbral - 30) && $c->dias_transcurridos < $diasUmbral);
        $alDia = $clientes->filter(fn($c) => $c->dias_transcurridos < ($diasUmbral - 30));

        return view('recordatorios.index', compact('vencidos', 'proximos', 'alDia', 'diasUmbral'));
    }
}
