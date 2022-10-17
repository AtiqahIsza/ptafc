<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DailySummary implements FromView, WithStyles, ShouldAutoSize
{
    public $dateFrom;
    public $dateTo;
    public $contents;
    public $allDates;
    public $totalRoute;
    public $totalCompany;
    public $networkArea;
    public $sheet;

    //The constructor passes by value
    public function __construct($contents, $dateFrom, $dateTo, $networkArea)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->contents = $contents;
        $this->networkArea = $networkArea;
    }

    public function view(): View
    {
        //dd($this->contents);
        return view('exports.dailysummary', [
            'contents' => $this->contents,
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
        $sheet->getStyle('A1:O' . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:O' . $highestRow)->applyFromArray($styleArray);
        return $sheet;
    }
}
