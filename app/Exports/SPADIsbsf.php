<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SPADIsbsf implements FromView, WithStyles, ShouldAutoSize
{
    public $reports;
    public $fromDate;
    public $toDate;
    public $allDates;
    public $colspan;
    public $sheet;
    public $months;
    public $days;
    public $networkArea;

    //The constructor passes by value
    public function __construct($data, $dateFrom, $dateTo, $colspan, $allDates, $months, $days, $networkArea)
    {
        $this->months = $months;
        $this->days = $days;
        $this->reports= $data;
        $this->fromDate = $dateFrom;
        $this->toDate = $dateTo;
        $this->allDates = $allDates;
        $this->colspan = $colspan;
        $this->networkArea = $networkArea;
    }

    public function view(): View
    {
        //dd($this->reports);
        return view('exports.spad.isbsf', [
            'reports' => $this->reports,
            'month' => $this->months,
            'days' => $this->days,
            'dateFrom' => $this->fromDate,
            'dateTo' => $this->toDate,
            'allDates'=> $this->allDates,
            'colspan'=> $this->colspan,
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
        $highestCol = $sheet->getHighestColumn();
        $sheet->getStyle('A1:'. $highestCol . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:'. $highestCol . $highestRow)->applyFromArray($styleArray);
        return $sheet;
    }
}
