<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'nombre',
        'importe',
        'concepto',
        'fecha',
        'imagen_path',
        'anio',
    ];
}