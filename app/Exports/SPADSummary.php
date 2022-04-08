<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SPADSummary implements FromView, WithStyles, ShouldAutoSize
{
    public $reports;
    public $fromDate;
    public $toDate;
    public $sheet;
    public $allDates = [];

    //The constructor passes by value
    public function __construct($data, $dates, $dateFrom, $dateTo)
    {
        $this->allDates = $dates;
        $this->reports = $data;
        $this->fromDate = $dateFrom;
        $this->toDate = $dateTo;
    }

    public function view(): View
    {
        dd($this->reports);
        return view('exports.spad.summary', [
            'allDates' => $this->allDates,
            'reports' => $this->reports,
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
        $sheet->getStyle('A1:X' . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:X' . $highestRow)->applyFromArray($styleArray);
        return $sheet;
    }
}
