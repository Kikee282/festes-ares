<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('tickets', function (Blueprint $table) {
        $table->id();
        $table->string('nombre');                  // Proveedor o nombre del establecimiento
        $table->decimal('importe', 8, 2);          // Cantidad gastada
        $table->string('concepto');                // Descripción del gasto
        $table->date('fecha');                     // Fecha del ticket
        $table->string('imagen_path')->nullable(); // Ruta de la imagen (opcional)
        $table->integer('anio');                   // Año del ejercicio
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
