<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SPADClaimDetails implements FromView, WithStyles, ShouldAutoSize
{
    public $routes;
    public $routeNo;
    public $fromDate;
    public $toDate;
    public $sheet;
    public $allDates = [];

    //The constructor passes by value
    public function __construct($dates, $data, $dateFrom, $dateTo,$no)
    {
        $this->allDates = $dates;
        $this->routes = $data;
        $this->fromDate = $dateFrom;
        $this->toDate = $dateTo;
        $this->routeNo = $no;
        /*$this->sheet = $sheetName;*/
    }

    public function view(): View
    {
        return view('exports.spad.claimdetails', [
            'allDates' => $this->allDates,
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
        $sheet->getStyle('A1:AO' . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:AO' . $highestRow)->applyFromArray($styleArray);
        return $sheet;
    }
}
