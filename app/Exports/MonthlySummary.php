<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Console\Output\ConsoleOutput;

class MonthlySummary implements FromView, WithStyles, ShouldAutoSize
{
    public $dateFrom;
    public $dateTo;
    public $contents;
    public $sheet;

    //The constructor passes by value
    public function __construct($contents, $fromDate, $toDate)
    {
        $this->dateFrom = $fromDate;
        $this->dateTo = $toDate;
        $this->contents = $contents;
    }

    public function view(): View
    {
        //dd($this->contents);
        return view('exports.monthlysummary', [
            'contents' => $this->contents,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
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
        $sheet->getStyle('A1:P' . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:P' . $highestRow)->applyFromArray($styleArray);
        return $sheet;
    }
}
