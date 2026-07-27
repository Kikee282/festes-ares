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
        ->get()
        ->map(function ($ticket) {
            return [
                'id'          => $ticket->id,
                'nombre'      => $ticket->nombre,
                'importe'     => $ticket->importe,
                'concepto'    => $ticket->concepto,
                'fecha'       => $ticket->fecha,
                'anio'        => $ticket->anio,
                // Construir la URL pública de Supabase Storage
                'imagen_path' => $ticket->imagen_path 
                    ? Storage::disk('s3')->url($ticket->imagen_path) 
                    : null,
            ];
        });

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
    $request->validate([
        'nombre'   => 'required|string|max:255',
        'importe'  => 'required|numeric|min:0',
        'concepto' => 'required|string|max:255',
        'fecha'    => 'required|date',
        'imagen'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
    ]);

    $rutaImagen = null;

    if ($request->hasFile('imagen')) {
        // Obtenemos la ruta relativa en Supabase Storage (ej: tickets/nombre_foto.jpg)
        $rutaImagen = $request->file('imagen')->store('tickets', 's3');
    }

    Ticket::create([
        'nombre'      => $request->nombre,
        'importe'     => $request->importe,
        'concepto'    => $request->concepto,
        'fecha'       => $request->fecha,
        'anio'        => (int) date('Y', strtotime($request->fecha)),
        'imagen_path' => $rutaImagen, // Guardará la cadena de texto o null
    ]);

    return redirect()->back();
}

    public function update(Request $request, Ticket $ticket)
{
    $request->validate([
        'nombre'   => 'required|string|max:255',
        'importe'  => 'required|numeric|min:0',
        'concepto' => 'required|string|max:255',
        'fecha'    => 'required|date',
        'imagen'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
    ]);

    $rutaImagen = $ticket->imagen_path;

    if ($request->hasFile('imagen')) {
        // Si ya tenía una imagen anterior en s3, la borramos
        if ($ticket->imagen_path) {
            Storage::disk('s3')->delete($ticket->imagen_path);
        }
        $rutaImagen = $request->file('imagen')->store('tickets', 's3');
    }

    $ticket->update([
        'nombre'      => $request->nombre,
        'importe'     => $request->importe,
        'concepto'    => $request->concepto,
        'fecha'       => $request->fecha,
        'anio'        => (int) date('Y', strtotime($request->fecha)),
        'imagen_path' => $rutaImagen,
    ]);

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