<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            if (!Schema::hasColumn('recibos', 'telefono')) {
                $table->string('telefono')->nullable()->after('nombre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            if (Schema::hasColumn('recibos', 'telefono')) {
                $table->dropColumn('telefono');
            }
        });
    }
};