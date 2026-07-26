<?php

namespace App\Http\Controllers;

use App\Models\Loteria;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoteriaController extends Controller
{
    public function index(Request $request)
    {
        $anio = (int) $request->input('anio', date('Y'));

        $loterias = Loteria::where('anio', $anio)
            ->orderBy('fecha', 'desc')
            ->get();

        $aniosDisponibles = Loteria::whereNotNull('anio')
            ->distinct()
            ->pluck('anio')
            ->map(fn($a) => (int)$a)
            ->toArray();

        if (empty($aniosDisponibles)) {
            $aniosDisponibles = [(int) date('Y')];
        }

        return Inertia::render('Loteria/Index', [
            'loterias'         => $loterias,
            'anioSeleccionado' => $anio,
            'aniosDisponibles' => array_values($aniosDisponibles),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha'          => 'required|date',
            'nombre'         => 'required|string|max:255',
            'cantidad'       => 'required|integer|min:1',
            'concepto'       => 'required|string|max:255',
            'tipo_operacion' => 'required|in:compra,venta',
            'metodo_pago'    => 'required|in:metalico,bizum',
            'estado_pago'    => 'required|in:pagado,pendiente',
        ]);

        $validated['anio'] = (int) date('Y', strtotime($validated['fecha']));

        // Cálculo de importe en servidor por seguridad
        $precioUnitario = $validated['tipo_operacion'] === 'compra' ? 20 : 23;
        $validated['importe'] = $validated['cantidad'] * $precioUnitario;

        Loteria::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Loteria $loteria)
    {
        $validated = $request->validate([
            'fecha'          => 'required|date',
            'nombre'         => 'required|string|max:255',
            'cantidad'       => 'required|integer|min:1',
            'concepto'       => 'required|string|max:255',
            'tipo_operacion' => 'required|in:compra,venta',
            'metodo_pago'    => 'required|in:metalico,bizum',
            'estado_pago'    => 'required|in:pagado,pendiente',
        ]);

        $validated['anio'] = (int) date('Y', strtotime($validated['fecha']));

        $precioUnitario = $validated['tipo_operacion'] === 'compra' ? 20 : 23;
        $validated['importe'] = $validated['cantidad'] * $precioUnitario;

        $loteria->update($validated);

        return redirect()->back();
    }

    public function destroy(Loteria $loteria)
    {
        $loteria->delete();

        return redirect()->back();
    }
}