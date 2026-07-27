<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loterias', function (Blueprint $table) {
            // Asignamos 'navidad' por defecto a todos los registros existentes
            $table->string('sorteo')->default('navidad')->after('anio');
        });
    }

    public function down(): void
    {
        Schema::table('loterias', function (Blueprint $table) {
            $table->dropColumn('sorteo');
        });
    }
};