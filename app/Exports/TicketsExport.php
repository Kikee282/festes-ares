<?php

namespace App\Exports;

use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TicketsExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize, WithColumnFormatting
{
    protected $anio;

    public function __construct($anio)
    {
        $this->anio = $anio;
    }

    public function collection()
    {
        return Ticket::where('anio', $this->anio)
            ->orderBy('fecha', 'asc')
            ->get(['fecha', 'nombre', 'concepto', 'importe'])
            ->map(function ($ticket) {
                return [
                    'fecha'    => $ticket->fecha,
                    'nombre'   => $ticket->nombre,
                    'concepto' => $ticket->concepto,
                    'importe'  => (float) $ticket->importe,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Establecimiento / Proveedor',
            'Concepto',
            'Importe (€)',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                if ($highestRow < 2) {
                    return;
                }

                $totalRow = $highestRow + 1;

                // 1. Asignamos la etiqueta y la fórmula de suma
                $sheet->setCellValue("C{$totalRow}", 'TOTAL GASTOS:');
                $sheet->setCellValue("D{$totalRow}", "=SUM(D2:D{$highestRow})");

                // 2. Le decimos a Excel que fuerce el recálculo al abrir el documento
                $sheet->getParent()->getCalculationEngine()->setCalculationCacheEnabled(false);

                // 3. Aplicamos negrita a encabezados y al subtotal
                $sheet->getStyle("A1:D1")->getFont()->setBold(true);
                $sheet->getStyle("C{$totalRow}:D{$totalRow}")->getFont()->setBold(true);
            },
        ];
    }
}