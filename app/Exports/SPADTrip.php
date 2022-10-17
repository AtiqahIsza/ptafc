<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Console\Output\ConsoleOutput;

class SPADTrip implements FromView, WithStyles, ShouldAutoSize
{
    public $reports;
    public $totIn;
    public $totOut;
    public $totDate;
    public $totRoute;
    public $totGrand;
    public $networkArea;
    public $fromDate;
    public $toDate;
    public $sheet;
    public $allDates = [];

    //The constructor passes by value
    public function __construct($data, $totOutArr, $totInArr, $totDateArr, $totRouteArr, $totGrandArr, $dateFrom, $dateTo, $networkArea)
    {
        $out = new ConsoleOutput();
        $out->writeln("In SPADTrip");
        $this->reports = $data;
        $this->totOut = $totOutArr;
        $this->totIn = $totInArr;
        $this->totDate = $totDateArr;
        $this->totRoute = $totRouteArr;
        $this->totGrand = $totGrandArr;
        $this->fromDate = $dateFrom;
        $this->toDate = $dateTo;
        $this->networkArea = $networkArea;
        /*$this->sheet = $sheetName;*/
    }

    public function view(): View
    {
        //dd($this->reports);
        $out = new ConsoleOutput();
        $out->writeln("In SPADTrip view()");
        return view('exports.spad.trip', [
            'reports' => $this->reports,
            'totOuts' => $this->totOut,
            'totIns' => $this->totIn,
            'totDates' => $this->totDate,
            'totRoutes' => $this->totRoute,
            'totGrands' => $this->totGrand,
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
        $sheet->getStyle('A1:S' . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:S' . $highestRow)->applyFromArray($styleArray);
        return $sheet;
    }
}
