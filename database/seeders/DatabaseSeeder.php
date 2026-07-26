<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crea tu usuario principal si no existe ya
        User::firstOrCreate(
            ['email' => 'admin@festesares.com'], // Cambia por el email que quieras
            [
                'name' => 'Ares',
                'password' => Hash::make('FestesAres69'), // Cambia por tu clave deseada
            ]
        );
    }
}
