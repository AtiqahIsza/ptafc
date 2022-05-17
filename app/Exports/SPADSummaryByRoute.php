<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SPADSummaryByRoute implements FromView, WithStyles, ShouldAutoSize
{
    public $reports;
    public $networkArea;
    public $fromDate;
    public $toDate;
    public $sheet;
    public $routeNo;

    //The constructor passes by value
    public function __construct($data, $dateFrom, $dateTo, $networkArea, $routeNo)
    {
        $this->reports = $data;
        $this->fromDate = $dateFrom;
        $this->toDate = $dateTo;
        $this->networkArea = $networkArea;
        $this->routeNo = $routeNo;
    }

    public function view(): View
    {
        //dd($this->reports);
        return view('exports.spad.summarybyroute', [
            'reports' => $this->reports,
            'networkArea' => $this->networkArea,
            'dateFrom' => $this->fromDate,
            'dateTo' => $this->toDate,
            'routeNo' => $this->routeNo
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
        $sheet->getStyle('A1:M' . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:M' . $highestRow)->applyFromArray($styleArray);
        return $sheet;
    }
}
