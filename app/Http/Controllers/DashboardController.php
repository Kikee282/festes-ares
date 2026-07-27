<?php

namespace App\Http\Controllers;

use App\Models\Recibo;
use App\Models\Ticket;
use App\Models\SaldoInicial;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $anio = (int) $request->input('anio', date('Y'));

        // Obtener Saldo Inicial del año seleccionado
        $registroSaldo = SaldoInicial::where('anio', $anio)->first();
        $saldoInicial = $registroSaldo !== null ? (float) $registroSaldo->monto : null;

        // Totales de Ingresos (Recibos) y Gastos (Tickets)
        $totalIngresos = (float) Recibo::where('anio', $anio)->sum('cantidad');
        $totalGastos   = (float) Ticket::where('anio', $anio)->sum('importe');

        // Cálculo del Saldo Actual Neto
        $saldoActual = $saldoInicial !== null 
            ? ($saldoInicial + $totalIngresos - $totalGastos) 
            : null;

        // Años disponibles ordenados
        $aniosRecibos = Recibo::pluck('anio')->toArray();
        $aniosTickets = Ticket::pluck('anio')->toArray();
        $aniosSaldos  = SaldoInicial::pluck('anio')->toArray();

        $todosAnios = array_merge([(int) date('Y')], $aniosRecibos, $aniosTickets, $aniosSaldos);
        $aniosDisponibles = array_values(array_unique(array_map('intval', $todosAnios)));
        rsort($aniosDisponibles);

        return Inertia::render('Dashboard', [
            'anioSeleccionado' => $anio,
            'aniosDisponibles' => $aniosDisponibles,
            'saldoInicial'     => $saldoInicial,
            'totalIngresos'    => $totalIngresos,
            'totalGastos'      => $totalGastos,
            'saldoActual'      => $saldoActual,
        ]);
    }

    public function guardarSaldoInicial(Request $request)
    {
        $validated = $request->validate([
            'anio'  => 'required|integer',
            'monto' => 'required|numeric|min:0',
        ]);

        $anio = (int) $validated['anio'];

        SaldoInicial::updateOrCreate(
            ['anio' => $anio],
            ['monto' => (float) $validated['monto']]
        );

        return redirect()->route('dashboard', ['anio' => $anio]);
    }
}