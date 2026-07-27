<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotaController extends Controller
{
    public function index(Request $request)
    {
        $anio = (int) $request->input('anio', date('Y'));

        $notas = Nota::where('anio', $anio)
            ->orderBy('fijada', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();

        $aniosDisponibles = Nota::whereNotNull('anio')
            ->distinct()
            ->pluck('anio')
            ->map(fn($a) => (int)$a)
            ->toArray();

        if (empty($aniosDisponibles)) {
            $aniosDisponibles = [(int) date('Y')];
        }

        return Inertia::render('Notas/Index', [
            'notas'            => $notas,
            'anioSeleccionado' => $anio,
            'aniosDisponibles' => array_values($aniosDisponibles),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo'    => 'required|string|max:255',
            'contenido' => 'nullable|string',
            'fijada'    => 'boolean',
        ]);

        $validated['anio'] = (int) date('Y');

        Nota::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Nota $nota)
    {
        $validated = $request->validate([
            'titulo'    => 'required|string|max:255',
            'contenido' => 'nullable|string',
            'fijada'    => 'boolean',
        ]);

        $nota->update($validated);

        return redirect()->back();
    }

    public function togglePin(Nota $nota)
    {
        $nota->update([
            'fijada' => !$nota->fijada,
        ]);

        return redirect()->back();
    }

    public function destroy(Nota $nota)
    {
        $nota->delete();

        return redirect()->back();
    }
}