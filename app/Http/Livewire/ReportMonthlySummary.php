<?php

namespace App\Http\Livewire;

use App\Exports\MonthlySummary;
use App\Models\Route;
use App\Models\Stage;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Console\Output\ConsoleOutput;

class ReportMonthlySummary extends Component
{
    public $state = [];

    public function render()
    {
        return view('livewire.report-monthly-summary');
    }

    public function print()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
        ])->validate();

        $dateFrom = $validatedData['dateFrom'];
        $dateTo = $validatedData['dateTo'];
        $allRoute = Route::all();

        return Excel::download(new MonthlySummary($allRoute,$dateFrom,$dateTo), 'MonthlySummary.xlsx');
    }
}
