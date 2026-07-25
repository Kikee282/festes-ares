<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $anio = $request->input('anio', date('Y'));

        $tickets = Ticket::where('anio', $anio)->latest()->get();
        $aniosDisponibles = Ticket::select('anio')->distinct()->pluck('anio');

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'anioSeleccionado' => (int)$anio,
            'aniosDisponibles' => $aniosDisponibles
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');
            $nombreOriginal = $file->getClientOriginalName();
            $path = $file->store('tickets', 'public');

            Ticket::create([
                'nombre_original' => $nombreOriginal,
                'ruta_archivo' => Storage::url($path),
                'anio' => date('Y'),
            ]);
        }

        return redirect()->back();
    }

    public function destroy(Ticket $ticket)
    {
        // Eliminar el archivo físico del disco (storage)
        $path = str_replace('/storage/', '', $ticket->ruta_archivo);
        \Illuminate\Support\Facades\Storage::disk('public')->delete($path);

        // Eliminar el registro de la base de datos
        $ticket->delete();

        return redirect()->back();
    }
}
