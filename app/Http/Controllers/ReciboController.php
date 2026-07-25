<?php

namespace App\Http\Controllers;

use App\Models\Recibo;
use App\Exports\RecibosExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReciboController extends Controller
{
    public function index(Request $request)
    {
        $anio = $request->input('anio', date('Y'));

        $recibos = Recibo::where('anio', $anio)->orderBy('numero', 'desc')->get();
        $aniosDisponibles = Recibo::select('anio')->distinct()->pluck('anio');

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

        // Carga la vista blade pasándole los datos del recibo
        $pdf = Pdf::loadView('pdf.recibo', compact('recibo'));

        // stream() abre el PDF directamente en el navegador
        return $pdf->stream("recibo-{$recibo->id}.pdf");
    }
}
