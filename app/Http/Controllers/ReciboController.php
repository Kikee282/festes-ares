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

        $recibos = Recibo::where('anio', $anio)->get();

        // Ordenamos en PHP extrayendo la parte numérica después del guion (ej: "26-11" -> 11)
        $recibos = $recibos->sortByDesc(function ($recibo) {
            $partes = explode('-', $recibo->numero);
            return isset($partes[1]) ? (int) $partes[1] : 0;
        })->values();

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
        // 1. Ya NO pedimos 'numero' en la validación porque se genera solo
        $validated = $request->validate([
            'nombre'   => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'cantidad' => 'required|numeric',
            'concepto' => 'required|string|max:255',
            'metodo_pago' => 'required|in:metalico,bizum',
            'fecha'    => 'required|date',
        ]);

        // 2. Extraemos el año y los dos últimos dígitos (ej: 2026 -> "26")
        $anioCompleto = date('Y', strtotime($validated['fecha']));
        $dosDigitosAnio = date('y', strtotime($validated['fecha']));

        // 3. Obtenemos cuántos recibos existen ya registrados para ese año
        $ultimoContador = Recibo::where('anio', $anioCompleto)->count();
        $siguienteNumero = $ultimoContador + 1;

        // 4. Asignamos el número formateado (ej: "26-1", "26-2") y el año
        $validated['numero'] = $dosDigitosAnio . '-' . $siguienteNumero;
        $validated['anio']   = $anioCompleto;

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

    public function update(Request $request, Recibo $recibo)
    {
        // En la edición no sobreescribimos el 'numero' original, solo los datos modificables
        $validated = $request->validate([
            'nombre'   => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'cantidad' => 'required|numeric',
            'concepto' => 'required|string|max:255',
            'metodo_pago' => 'required|in:metalico,bizum',
            'fecha'    => 'required|date',
        ]);

        $validated['anio'] = date('Y', strtotime($validated['fecha']));

        $recibo->update($validated);

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
