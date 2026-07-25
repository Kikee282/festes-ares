<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            $table->string('numero')->change();
        });
    }

    public function down(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            $table->integer('numero')->change();
        });
    }
};