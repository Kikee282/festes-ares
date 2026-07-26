<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Exports\TicketsExport; // 👈 Importamos el Export
use Maatwebsite\Excel\Facades\Excel; // 👈 Importamos la Facade de Excel

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $anio = $request->input('anio', date('Y'));

        $tickets = Ticket::where('anio', $anio)->orderBy('fecha', 'desc')->get();
        $aniosDisponibles = Ticket::select('anio')->distinct()->pluck('anio');

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'anioSeleccionado' => (int)$anio,
            'aniosDisponibles' => $aniosDisponibles
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'   => 'required|string|max:255',
            'importe'  => 'required|numeric|min:0',
            'concepto' => 'required|string|max:255',
            'fecha'    => 'required|date',
            'imagen'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096', // Máx 4MB
        ]);

        $validated['anio'] = date('Y', strtotime($validated['fecha']));

        // Si se sube una imagen, la guardamos en storage/app/public/tickets
        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('tickets', 'public');
            $validated['imagen_path'] = '/storage/' . $path;
        }

        Ticket::create($validated);

        return redirect()->back();
    }

    public function destroy(Ticket $ticket)
    {
        // Borramos la imagen del disco si existe
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
