<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ReciboController;
use App\Http\Controllers\TicketController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Recibos
    Route::get('/recibos', [ReciboController::class, 'index'])->name('recibos.index');
    Route::post('/recibos', [ReciboController::class, 'store'])->name('recibos.store');
    Route::get('/recibos/export/{anio}', [ReciboController::class, 'exportarExcel'])->name('recibos.export');
    Route::delete('/recibos/{recibo}', [ReciboController::class, 'destroy'])->name('recibos.destroy');
    // Tickets
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
});

Route::get('/recibos/{id}/pdf', [ReciboController::class, 'pdf'])->name('recibos.pdf');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
