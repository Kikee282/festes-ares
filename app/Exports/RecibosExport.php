<?php

namespace App\Exports;

use App\Models\Recibo;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RecibosExport implements FromQuery, WithHeadings
{
    protected $anio;

    public function __construct(int $anio)
    {
        $this->anio = $anio;
    }

    public function query()
    {
        return Recibo::query()
            ->where('anio', $this->anio)
            ->select('numero', 'nombre', 'cantidad', 'concepto', 'fecha');
    }

    public function headings(): array
    {
        return ['Nº Recibo', 'Nombre / Casa', 'Cantidad (€)', 'Concepto', 'Fecha'];
    }
}
