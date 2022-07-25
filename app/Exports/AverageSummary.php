<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AverageSummary implements FromView, WithStyles, ShouldAutoSize
{
    public $dateFrom;
    public $dateTo;
    public $reports;
    public $routes;
    public $colspan;
    public $networkArea;
    public $sheet;

    //The constructor passes by value
    public function __construct($reports, $routes, $colspan, $dateFrom, $dateTo, $networkArea)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->routes = $routes;
        $this->reports = $reports;
        $this->networkArea = $networkArea;
        $this->colspan = $colspan;
    }

    public function view(): View
    {
        //dd($this->reports);
        return view('exports.averagesummary', [
            'routes' => $this->routes,
            'reports' => $this->reports,
            'colspan' => $this->colspan,
            'dateFrom' =>  $this->dateFrom,
            'dateTo' => $this->dateTo,
            'networkArea' => $this->networkArea,
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
        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle('A1:' .  $highestColumn . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:' . $highestColumn .  $highestRow)->applyFromArray($styleArray);
        return $sheet;
    }
}
