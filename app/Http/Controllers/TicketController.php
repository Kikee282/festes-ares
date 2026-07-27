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

        // URL base pública de tu proyecto Supabase Storage
        $supabaseEndpoint = env('AWS_ENDPOINT', 'https://TU_PROJECT_REF.supabase.co/storage/v1/s3');
        $baseUrl = str_replace('/s3', '', $supabaseEndpoint) . '/object/public/' . env('AWS_BUCKET', 'tickets') . '/';

        $tickets = Ticket::where('anio', $anio)
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(function ($ticket) use ($baseUrl) {
                
                $fotoUrl = null;
                if ($ticket->imagen_path) {
                    // Si la ruta ya es una URL HTTP completa, la usa; si es una ruta relativa (ej: tickets/foto.jpg), le añade la URL base
                    $fotoUrl = str_starts_with($ticket->imagen_path, 'http')
                        ? $ticket->imagen_path
                        : $baseUrl . ltrim($ticket->imagen_path, '/');
                }

                return [
                    'id'          => $ticket->id,
                    'nombre'      => $ticket->nombre,
                    'importe'     => $ticket->importe,
                    'concepto'    => $ticket->concepto,
                    'fecha'       => $ticket->fecha,
                    'anio'        => $ticket->anio,
                    'imagen_path' => $fotoUrl,
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

    // Verificar explícitamente que se subió un archivo y es válido
    if ($request->hasFile('imagen') && $request->file('imagen')->isValid()) {
        $rutaImagen = $request->file('imagen')->store('tickets', 's3');
    }

    Ticket::create([
        'nombre'      => $request->nombre,
        'importe'     => $request->importe,
        'concepto'    => $request->concepto,
        'fecha'       => $request->fecha,
        'anio'        => (int) date('Y', strtotime($request->fecha)),
        'imagen_path' => $rutaImagen, // Si no hay foto, se guardará NULL explícito
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
            // Borramos la foto previa si existía
            if ($ticket->imagen_path && !str_starts_with($ticket->imagen_path, 'http')) {
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
        if ($ticket->imagen_path && !str_starts_with($ticket->imagen_path, 'http')) {
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