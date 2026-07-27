<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // Sintaxis para PostgreSQL (Supabase)
            DB::statement('ALTER TABLE loterias DROP CONSTRAINT IF EXISTS loterias_tipo_operacion_check;');
            DB::statement("ALTER TABLE loterias ADD CONSTRAINT loterias_tipo_operacion_check CHECK (tipo_operacion IN ('compra', 'venta', 'liquidacion'));");
        } 
        // Si es SQLite (Local), no hace falta alterar el check constraint 
        // porque SQLite no valida los ENUMs/CHECKs de esa manera por defecto.
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE loterias DROP CONSTRAINT IF EXISTS loterias_tipo_operacion_check;');
            DB::statement("ALTER TABLE loterias ADD CONSTRAINT loterias_tipo_operacion_check CHECK (tipo_operacion IN ('compra', 'venta'));");
        }
    }
};