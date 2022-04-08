<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SPADServiceGroup implements FromView, WithStyles, ShouldAutoSize
{
    public $contents;
    public $fromDate;
    public $toDate;
    public $sheet;

    //The constructor passes by value
    public function __construct($data, $dateFrom, $dateTo)
    {
        $this->contents = $data;
        $this->fromDate = $dateFrom;
        $this->toDate = $dateTo;
    }

    public function view(): View
    {
        //dd($this->contents);
        return view('exports.spad.servicegroup', [
            'contents' => $this->contents,
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
