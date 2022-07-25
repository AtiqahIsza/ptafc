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
    public $reports;
    public $routeNo;
    public $fromDate;
    public $toDate;
    public $networkArea;
    public $sheet;
    public $allDates = [];

    //The constructor passes by value
    public function __construct($dates, $data, $dateFrom, $dateTo, $networkArea)
    {
        $this->allDates = $dates;
        $this->reports = $data;
        $this->fromDate = $dateFrom;
        $this->toDate = $dateTo;
        $this->networkArea = $networkArea;
        /*$this->sheet = $sheetName;*/
    }

    public function view(): View
    {
        //dd($this->reports);
        return view('exports.spad.claimdetails', [
            'allDates' => $this->allDates,
            'reports' => $this->reports,
            'dateFrom' => $this->fromDate,
            'dateTo' => $this->toDate,
            'networkArea' => $this->networkArea
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
        $sheet->getStyle('A1:AH' . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:AH' . $highestRow)->applyFromArray($styleArray);
        return $sheet;
    }
}
