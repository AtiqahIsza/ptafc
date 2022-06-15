<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportRoute implements FromView, WithStyles, ShouldAutoSize
{
     //The constructor passes by value
     public function __construct($data)
     {
         $this->data = $data;
     }
 
     public function view(): View
     {
         //dd($this->data);
         return view('exports.routes', [
             'data' => $this->data,
         ]);
     }
 
     public function styles(Worksheet $sheet)
     {
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:F' . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:F' . $highestRow)->applyFromArray($styleArray);
        return $sheet;
     }
}
