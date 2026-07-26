<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $anio = (int) $request->input('anio', date('Y'));

        $tickets = Ticket::where('anio', $anio)
            ->orderBy('fecha', 'desc')
            ->get();

        $aniosDisponibles = Ticket::whereNotNull('anio')
            ->distinct()
            ->pluck('anio')
            ->map(fn($a) => (int)$a)
            ->toArray();

        if (empty($aniosDisponibles)) {
            $aniosDisponibles = [(int) date('Y')];
        }

        return Inertia::render('Tickets/Index', [
            'tickets'          => $tickets,
            'anioSeleccionado' => $anio,
            'aniosDisponibles' => array_values($aniosDisponibles),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'   => 'required|string|max:255',
            'importe'  => 'required|numeric|min:0',
            'concepto' => 'required|string|max:255',
            'fecha'    => 'required|date',
            'imagen'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        $validated['anio'] = (int) date('Y', strtotime($validated['fecha']));

        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('tickets', 'public');
            $validated['imagen_path'] = '/storage/' . $path;
        }

        Ticket::create($validated);

        return redirect()->back();
    }

    public function destroy(Ticket $ticket)
    {
        if ($ticket->imagen_path) {
            $relativePath = str_replace('/storage/', '', $ticket->imagen_path);
            Storage::disk('public')->delete($relativePath);
        }

        $ticket->delete();

        return redirect()->back();
    }

    public function exportarExcel($anio)
    {
        $fileName = "tickets_gastos_fiestas_{$anio}.csv";

        $tickets = Ticket::where('anio', $anio)
            ->orderBy('fecha', 'asc')
            ->get();

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        return response()->stream(function () use ($tickets) {
            $file = fopen('php://output', 'w');
            
            // BOM para que Excel abra los acentos correctamente en UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Cabeceras de las columnas (separadas por punto y coma para Excel en español)
            fputcsv($file, ['Fecha', 'Establecimiento / Proveedor', 'Concepto', 'Importe (€)'], ';');

            $total = 0;
            foreach ($tickets as $ticket) {
                $total += $ticket->importe;
                fputcsv($file, [
                    $ticket->fecha,
                    $ticket->nombre,
                    $ticket->concepto,
                    number_format($ticket->importe, 2, ',', '')
                ], ';');
            }

            // Fila de Total
            fputcsv($file, ['', '', 'TOTAL GASTOS:', number_format($total, 2, ',', '')], ';');

            fclose($file);
        }, 200, $headers);
    }
}