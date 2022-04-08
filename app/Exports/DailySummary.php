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
    public $dateDaily;
    public $contents;
    public $sheet;

    //The constructor passes by value
    public function __construct($contents, $dailyDate)
    {
        $this->dateDaily = $dailyDate;
        $this->contents = $contents;
    }

    public function view(): View
    {
        //dd($this->contents);
        return view('exports.dailysummary', [
            'contents' => $this->contents,
            'dateDaily' => $this->dateDaily,
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
        $sheet->getStyle('A1:L' . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:L' . $highestRow)->applyFromArray($styleArray);
        return $sheet;
    }
}
