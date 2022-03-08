<?php

namespace App\Http\Livewire;

use App\Exports\DailySummary;
use App\Exports\MonthlySummary;
use App\Models\Route;
use App\Models\Stage;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Console\Output\ConsoleOutput;

class ReportDailySummary extends Component
{
    public $state = [];

    public function render()
    {
        return view('livewire.report-daily-summary');
    }

    public function print()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE");

        $validatedData = Validator::make($this->state,[
            'dailyDate' => ['required', 'date'],
        ])->validate();

        $allRoute = Route::all();
        $allStage = Stage::all();

        return Excel::download(new DailySummary($allRoute,$allStage,$validatedData['dailyDate']), 'DailyDetailsReport.xlsx');
    }
}
