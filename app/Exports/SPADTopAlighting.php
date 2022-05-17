<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SPADTopAlighting implements FromView, WithStyles, ShouldAutoSize
{
    public $reports;
    public $networkArea;
    public $fromDate;
    public $toDate;
    public $sheet;
    public $allDates = [];

    //The constructor passes by value
    public function __construct($data, $dateFrom, $dateTo, $networkArea)
    {
        $this->reports = $data;
        $this->fromDate = $dateFrom;
        $this->toDate = $dateTo;
        $this->networkArea = $networkArea;
        /*$this->sheet = $sheetName;*/
    }

    public function view(): View
    {
        //dd($this->reports);
        return view('exports.spad.topalighting', [
            'reports' => $this->reports,
            'networkArea' => $this->networkArea,
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
        $sheet->getStyle('A1:H' . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:H' . $highestRow)->applyFromArray($styleArray);
        return $sheet;
    }
}
