<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SPADRoute implements FromView, WithStyles, ShouldAutoSize
{
    public $reports;
    public $routeNo;
    public $fromDate;
    public $toDate;
    public $sheet;
    public $networkArea;

    //The constructor passes by value
    public function __construct($networkArea, $data, $dateFrom, $dateTo)
    {
        $this->networkArea = $networkArea;
        $this->reports = $data;
        $this->fromDate = $dateFrom;
        $this->toDate = $dateTo;
        /*$this->sheet = $sheetName;*/
    }

    public function view(): View
    {
        //dd($this->reports);
        return view('exports.spad.route', [
            'networkArea' => $this->networkArea,
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
        $sheet->getStyle('A1:O' . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:O' . $highestRow)->applyFromArray($styleArray);
        return $sheet;
    }
}
