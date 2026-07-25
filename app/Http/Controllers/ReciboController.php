<?php

namespace App\Http\Controllers;

use App\Models\Recibo;
use App\Exports\RecibosExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;

class ReciboController extends Controller
{
    public function index(Request $request)
    {
        $anio = $request->input('anio', date('Y'));

        $recibos = Recibo::where('anio', $anio)->orderBy('numero', 'desc')->get();
        $aniosDisponibles = Recibo::select('anio')->distinct()->pluck('anio');

        $recibos->transform(function ($recibo) {
            $recibo->url_pdf = URL::signedRoute('recibos.pdf', ['id' => $recibo->id]);
            return $recibo;
        });

        return Inertia::render('Recibos/Index', [
            'recibos' => $recibos,
            'anioSeleccionado' => (int)$anio,
            'aniosDisponibles' => $aniosDisponibles
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero' => 'required|integer',
            'nombre' => 'required|string|max:255',
            'cantidad' => 'required|numeric',
            'concepto' => 'required|string|max:255',
            'fecha' => 'required|date',
        ]);

        $validated['anio'] = date('Y', strtotime($validated['fecha']));

        Recibo::create($validated);

        return redirect()->back();
    }

    public function exportarExcel($anio)
    {
        return Excel::download(new RecibosExport($anio), "recibos_fiestas_{$anio}.xlsx");
    }

    public function destroy(Recibo $recibo)
    {
        $recibo->delete();

        return redirect()->back();
    }

    public function pdf($id)
{
    $recibo = Recibo::findOrFail($id);

    // 1. Limpiamos el nombre para evitar caracteres no válidos en archivos (/ \ : * ? " < > |)
    $nombreLimpio = preg_replace('/[\/\\\:\*\?"<>\|]/', '', $recibo->nombre);

    // 2. Construimos el nombre del archivo PDF (ej: Recibo - Juan Perez.pdf)
    $nombreArchivo = "Recibo - {$nombreLimpio}.pdf";

    // 3. Cargamos la vista de DomPDF
    $pdf = Pdf::loadView('pdf.recibo', compact('recibo'));

    // 4. Pasamos el nombre personalizado a stream()
    return $pdf->stream($nombreArchivo);
}
}
