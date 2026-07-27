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
        $sorteo = $request->input('sorteo', 'navidad'); // 'navidad' o 'nino'

        $loterias = Loteria::where('anio', $anio)
            ->where('sorteo', $sorteo)
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
            'loterias'          => $loterias,
            'anioSeleccionado'  => $anio,
            'sorteoSeleccionado'=> $sorteo,
            'aniosDisponibles'  => array_values($aniosDisponibles),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha'          => 'required|date',
            'nombre'         => 'required|string|max:255',
            'cantidad'       => 'nullable|integer|min:0',
            'concepto'       => 'required|string|max:255',
            'tipo_operacion' => 'required|in:compra,venta,liquidacion',
            'metodo_pago'    => 'required|in:metalico,bizum',
            'estado_pago'    => 'required|in:pagado,pendiente',
            'sorteo'         => 'required|in:navidad,nino',
            'importe_libre'  => 'nullable|numeric|min:0',
        ]);

        $validated['anio'] = (int) date('Y', strtotime($validated['fecha']));

        if ($validated['tipo_operacion'] === 'liquidacion') {
            $validated['cantidad'] = 0;
            $validated['importe'] = (float) ($request->input('importe_libre', 0));
        } else {
            $precioUnitario = $validated['tipo_operacion'] === 'compra' ? 20 : 23;
            $validated['cantidad'] = max(1, (int) $validated['cantidad']);
            $validated['importe'] = $validated['cantidad'] * $precioUnitario;
        }

        unset($validated['importe_libre']);

        Loteria::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Loteria $loteria)
    {
        $validated = $request->validate([
            'fecha'          => 'required|date',
            'nombre'         => 'required|string|max:255',
            'cantidad'       => 'nullable|integer|min:0',
            'concepto'       => 'required|string|max:255',
            'tipo_operacion' => 'required|in:compra,venta,liquidacion',
            'metodo_pago'    => 'required|in:metalico,bizum',
            'estado_pago'    => 'required|in:pagado,pendiente',
            'sorteo'         => 'required|in:navidad,nino',
            'importe_libre'  => 'nullable|numeric|min:0',
        ]);

        $validated['anio'] = (int) date('Y', strtotime($validated['fecha']));

        if ($validated['tipo_operacion'] === 'liquidacion') {
            $validated['cantidad'] = 0;
            $validated['importe'] = (float) ($request->input('importe_libre', 0));
        } else {
            $precioUnitario = $validated['tipo_operacion'] === 'compra' ? 20 : 23;
            $validated['cantidad'] = max(1, (int) $validated['cantidad']);
            $validated['importe'] = $validated['cantidad'] * $precioUnitario;
        }

        unset($validated['importe_libre']);

        $loteria->update($validated);

        return redirect()->back();
    }

    public function destroy(Loteria $loteria)
    {
        $loteria->delete();

        return redirect()->back();
    }
}