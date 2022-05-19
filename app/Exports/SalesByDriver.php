<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesByDriver implements FromView, WithStyles, ShouldAutoSize
{
    public $reports;
    public $sheet;

    //The constructor passes by value
    public function __construct($data)    {
        $this->reports = $data;
    }

    public function view(): View
    {
        //dd( $this->reports);
        return view('exports.salesbydriver', [
            'reports' => $this->reports,
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
        $sheet->getStyle('A1:AB' . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:AB' . $highestRow)->applyFromArray($styleArray);
        return $sheet;
    }
}
