<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
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
        ]);

        $validated['anio'] = (int) date('Y', strtotime($validated['fecha']));

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
        ]);

        $validated['anio'] = (int) date('Y', strtotime($validated['fecha']));

        $ticket->update($validated);

        return redirect()->back();
    }

    public function destroy(Ticket $ticket)
    {
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