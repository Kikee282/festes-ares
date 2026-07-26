<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recibo extends Model
{
    protected $fillable = ['numero', 'nombre', 'cantidad', 'telefono', 'concepto', 'fecha', 'anio'];
}
