<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Exports\TicketsExport;
use Maatwebsite\Excel\Facades\Excel;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $anio = (int) $request->input('anio', date('Y'));

        $tickets = Ticket::where('anio', $anio)
            ->orderBy('fecha', 'desc')
            ->get();

        // Convertimos a array nativo de enteros
        $aniosDisponibles = Ticket::whereNotNull('anio')
            ->distinct()
            ->pluck('anio')
            ->map(fn($a) => (int)$a)
            ->toArray();

        // Si la tabla de tickets está vacía en producción, forzamos el año actual
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
    // Guarda directamente en el disco s3 (Supabase)
    // $path valdrá algo como "tickets/nombre_foto.jpg"
    $validated['imagen_path'] = $request->file('imagen')->store('tickets', 's3');
}

        Ticket::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'nombre'   => 'required|string|max:255',
            'importe'  => 'required|numeric|min:0',
            'concepto' => 'required|string|max:255',
            'fecha'    => 'required|date',
            'imagen'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        $validated['anio'] = (int) date('Y', strtotime($validated['fecha']));

        // Si sube una nueva imagen, eliminamos la anterior de Supabase y subimos la nueva
        if ($request->hasFile('imagen')) {
            if ($ticket->imagen_path) {
                Storage::disk('s3')->delete($ticket->imagen_path);
            }
            $validated['imagen_path'] = $request->file('imagen')->store('tickets', 's3');
        }

        $ticket->update($validated);

        return redirect()->back();
    }

    public function destroy(Ticket $ticket)
{
    if ($ticket->imagen_path) {
        Storage::disk('s3')->delete($ticket->imagen_path);
    }

    $ticket->delete();

    return redirect()->back();
}

    public function exportarExcel($anio)
    {
        return Excel::download(
            new TicketsExport($anio),
            "tickets_gastos_fiestas_{$anio}.xlsx"
        );
    }
}