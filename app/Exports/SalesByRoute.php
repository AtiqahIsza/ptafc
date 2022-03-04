<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesByRoute implements FromArray,ShouldAutoSize, WithHeadings, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public $points;

    public function __construct(array $points)
    {
        dd($points);
        $this->points=$points;
    }

    public function array(): array
    {
        return $this->points;
    }

    public function headings(): array
    {
        $headings=[
            ['a'],
            ['b'],
            ['c']
        ];

        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('A2:B2');
        $sheet->mergeCells('A3:B3');
        $sheet->mergeCells('A4:B4');
        $sheet->mergeCells('A5:B5');
        $sheet->mergeCells('A6:B6');
        $sheet->mergeCells('C1:D1');
        $sheet->mergeCells('C2:D2');
        $sheet->mergeCells('C3:D3');
        $sheet->mergeCells('C4:D4');
        $sheet->mergeCells('C5:D5');
        $sheet->mergeCells('C6:D6');
        $sheet->mergeCells('A7:D7');

        $sheet->setCellValue('C1','Xin chao');
        $sheet->setCellValue('C2','Xin chao');
        $sheet->setCellValue('C3','Xin chao');
        $sheet->setCellValue('C4','Xin chao');
        $sheet->setCellValue('C5','Xin chao');
        $sheet->setCellValue('C6','Xin chao');

        foreach(range(1,7) as $number){
            $sheet->getStyle('C'.$number)->getAlignment()->applyFromArray(
                array('horizontal'=>'left')
            );
        }
    }
}
