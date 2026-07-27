<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ReciboController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\LoteriaController;
use App\Http\Controllers\DashboardController;
Route::middleware(['auth', 'verified'])->group(function () {
    // Recibos
    Route::get('/recibos', [ReciboController::class, 'index'])->name('recibos.index');
    Route::post('/recibos', [ReciboController::class, 'store'])->name('recibos.store');
    Route::get('/recibos/export/{anio}', [ReciboController::class, 'exportarExcel'])->name('recibos.export');
    Route::delete('/recibos/{recibo}', [ReciboController::class, 'destroy'])->name('recibos.destroy');
    Route::put('/recibos/{recibo}', [ReciboController::class, 'update'])->name('recibos.update');
    // Tickets
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
    Route::get('/tickets/exportar/{anio}', [TicketController::class, 'exportarExcel'])->name('tickets.exportar');
    Route::post('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    //Loteria
    Route::get('/loteria', [LoteriaController::class, 'index'])->name('loteria.index');
    Route::post('/loteria', [LoteriaController::class, 'store'])->name('loteria.store');
    Route::put('/loteria/{loteria}', [LoteriaController::class, 'update'])->name('loteria.update');
    Route::delete('/loteria/{loteria}', [LoteriaController::class, 'destroy'])->name('loteria.destroy');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/saldo-inicial', [DashboardController::class, 'guardarSaldoInicial'])->name('saldo.guardar');
});

Route::get('/recibos/{id}/pdf', [ReciboController::class, 'pdf'])
    ->name('recibos.pdf')
    ->middleware('signed');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
