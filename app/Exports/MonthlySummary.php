<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlySummary implements FromView, WithStyles, ShouldAutoSize
{
    public $dateFrom;
    public $dateTo;
    public $routes;
    public $sheet;

    //The constructor passes by value
    public function __construct($data, $fromDate, $toDate)
    {
        $this->dateFrom = $fromDate;
        $this->dateTo = $toDate;
        $this->routes = $data;
    }

    public function view(): View
    {
        return view('exports.monthlysummary', [
            'routes' => $this->routes,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
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
        $sheet->getStyle('A1:O' . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:O' . $highestRow)->applyFromArray($styleArray);
        return $sheet;
    }
}
