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
        return Excel::download(
            new TicketsExport($anio),
            "tickets_gastos_fiestas_{$anio}.xlsx"
        );
    }
}