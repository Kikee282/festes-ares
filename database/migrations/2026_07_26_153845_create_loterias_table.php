<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loterias', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('nombre');
            $table->integer('cantidad');
            $table->string('concepto');
            $table->enum('tipo_operacion', ['compra', 'venta'])->default('venta');
            $table->enum('metodo_pago', ['metalico', 'bizum'])->default('metalico');
            $table->enum('estado_pago', ['pagado', 'pendiente'])->default('pagado');
            $table->decimal('importe', 8, 2);
            $table->integer('anio');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loterias');
    }
};