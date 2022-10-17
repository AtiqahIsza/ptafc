<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Console\Output\ConsoleOutput;

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
    public function __construct($data, $dateFrom, $dateTo, $networkArea)
    {
        $this->reports = $data;
        $this->fromDate = $dateFrom;
        $this->toDate = $dateTo;
        $this->networkArea = $networkArea;
    }

    public function view(): View
    {
        //dd($this->reports);
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN SPADClaimDetails");
        return view('exports.spad.claimdetails', [
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
