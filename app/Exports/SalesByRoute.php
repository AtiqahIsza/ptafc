<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesByRoute implements FromView, WithStyles, ShouldAutoSize
{
    public $reports;
    public $routeName;
    public $networkArea;
    public $dateFrom;
    public $dateTo;
    public $grandTotal;
    public $range = [];
    public $colspan;

    public $sheet;

    public function __construct($data, $grand, $dates, $colspan, $routeName, $networkArea, $dateFrom, $dateTo)
    {
        $this->reports = $data;
        $this->grandTotal = $grand;
        $this->range = $dates;
        $this->colspan = $colspan;
        $this->routeName = $routeName;
        $this->networkArea = $networkArea;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function view(): View
    {
        //dd($this->reports);
        //dd($this->grandTotal);
        return view('exports.salesbyroute', [
            'reports' => $this->reports,
            'grandTotal' => $this->grandTotal,
            'range' => $this->range,
            'colspan' => $this->colspan,
            'routeName' => $this->routeName,
            'networkArea' => $this->networkArea,
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
        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle('A1:K' . $highestColumn . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray($styleArray);
        return $sheet;
    }


}
