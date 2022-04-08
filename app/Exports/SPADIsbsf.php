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
    public $contents;
    public $fromDate;
    public $toDate;
    public $allDates;
    public $colspan;
    public $sheet;

    //The constructor passes by value
    public function __construct($data, $dateFrom, $dateTo, $colspan, $allDates)
    {
        $this->contents = $data;
        $this->fromDate = $dateFrom;
        $this->toDate = $dateTo;
        $this->allDates = $allDates;
        $this->colspan = $colspan;
    }

    public function view(): View
    {
        //dd($this->contents);
        return view('exports.spad.isbsf', [
            'contents' => $this->contents,
            'dateFrom' => $this->fromDate,
            'dateTo' => $this->toDate,
            'allDates'=> $this->allDates,
            'colspan'=> $this->colspan
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
