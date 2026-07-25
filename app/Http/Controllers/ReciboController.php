<?php

namespace App\Http\Controllers;

use App\Models\Recibo;
use App\Exports\RecibosExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

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
}
