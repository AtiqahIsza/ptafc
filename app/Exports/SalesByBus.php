<?php

namespace App\Exports;

use App\Models\Stage;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Borders;

class SalesByBus implements FromView, WithStyles, ShouldAutoSize
{
    public $reports;
    public $companies;
    public $fromDate;
    public $toDate;
    public $sheet;

    //The constructor passes by value
    public function __construct($data,$companyName,$dateFrom, $dateTo)    
    {
        $this->reports = $data;
        $this->companies = $companyName;
        $this->fromDate = $dateFrom;
        $this->toDate = $dateTo;
    }

    public function view(): View
    {
        //dd( $this->reports);
        return view('exports.salesbybus', [
            'reports' => $this->reports,
            'company' => $this->companies,
            'dateFrom' => $this->fromDate,
            'dateTo' => $this->toDate,
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
        $sheet->getStyle('A1:K' . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:K' . $highestRow)->applyFromArray($styleArray);
        return $sheet;
    }

}
