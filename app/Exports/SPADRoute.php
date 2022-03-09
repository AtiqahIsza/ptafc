<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SPADRoute implements FromView, WithStyles, ShouldAutoSize
{
    public $routes;
    public $routeNo;
    public $fromDate;
    public $toDate;
    public $sheet;

    //The constructor passes by value
    public function __construct($data, $dateFrom, $dateTo,$no)
    {
        $this->routes = $data;
        $this->fromDate = $dateFrom;
        $this->toDate = $dateTo;
        $this->routeNo = $no;
        /*$this->sheet = $sheetName;*/
    }

    public function view(): View
    {
        return view('exports.spad.route', [
            'routes' => $this->routes,
            'routeNo' => $this->routeNo,
            'dateFrom' => $this->fromDate,
            'dateTo' => $this->toDate
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
        $sheet->getStyle('A1:V' . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:V' . $highestRow)->applyFromArray($styleArray);
        return $sheet;
    }
}
