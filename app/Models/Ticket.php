<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = ['nombre_original', 'ruta_archivo', 'anio'];
}
