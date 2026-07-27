<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loteria extends Model
{
    use HasFactory;

    protected $fillable = [
        'fecha',
        'nombre',
        'cantidad',
        'concepto',
        'tipo_operacion',
        'metodo_pago',
        'estado_pago',
        'importe',
        'anio',
        'sorteo', // 'navidad' o 'nino'
    ];
}