<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            if (!Schema::hasColumn('recibos', 'metodo_pago')) {
                $table->enum('metodo_pago', ['metalico', 'bizum'])->default('metalico')->after('concepto');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            if (Schema::hasColumn('recibos', 'metodo_pago')) {
                $table->dropColumn('metodo_pago');
            }
        });
    }
};