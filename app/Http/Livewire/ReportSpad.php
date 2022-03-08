<?php

namespace App\Http\Livewire;

use App\Exports\SalesByBus;
use App\Exports\SPADClaimDetails;
use App\Exports\SPADRoute;
use App\Exports\SPADSummary;
use App\Exports\SPADTrip;
use App\Models\Bus;
use App\Models\Company;
use App\Models\Route;
use App\Models\Stage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Console\Output\ConsoleOutput;

class ReportSpad extends Component
{
    public $companies;
    public $routes;
    public $selectedCompany = NULL;
    public $state = [];

    public function render()
    {
        return view('livewire.report-spad');
    }

    public function mount()
    {
        $this->companies=Company::all();
        $this->routes=Route::all();
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->selectedCompany = $company;
            $this->routes = Route::where('company_id', $company)->get();
        }
    }

    public function printSummary()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printSummary()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required', 'int'],
        ])->validate();

        $out->writeln("datefrom:" . $validatedData['dateFrom']);
        $out->writeln("dateto:" . $validatedData['dateTo']);
        $out->writeln("route:" . $validatedData['route_id']);

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        if($this->selectedCompany){
            $allRoute = Route::where('company_id', $this->selectedCompany)
                ->where('id', $validatedData['route_id'])
                ->get();
        }

        foreach ($allRoute as $allRoutes){
            $routeNo = $allRoutes->route_number;
        }

        return Excel::download(new SPADSummary($all_dates, $allRoute, $validatedData['dateFrom'], $validatedData['dateTo'], $routeNo), 'Summary_Report_SPAD.xlsx');
    }

    public function printServiceGroup()
    {
        //
    }

    public function printRoute()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printRoute()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required', 'int'],
        ])->validate();

        $out->writeln("datefrom:" . $validatedData['dateFrom']);
        $out->writeln("dateto:" . $validatedData['dateTo']);
        $out->writeln("route:" . $validatedData['route_id']);

        if($this->selectedCompany){
            $allRoute = Route::where('company_id', $this->selectedCompany)
                ->where('id', $validatedData['route_id'])
                ->get();
        }

        foreach ($allRoute as $allRoutes){
            $routeNo = $allRoutes->route_number;
        }

        return Excel::download(new SPADRoute($allRoute, $validatedData['dateFrom'], $validatedData['dateTo'], $routeNo), 'Route_Report_SPAD.xlsx');
    }

    public function printTrip()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printTrip()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required', 'int'],
        ])->validate();

        $out->writeln("datefrom:" . $validatedData['dateFrom']);
        $out->writeln("dateto:" . $validatedData['dateTo']);
        $out->writeln("route:" . $validatedData['route_id']);

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        if($this->selectedCompany){
            $allRoute = Route::where('company_id', $this->selectedCompany)
                ->where('id', $validatedData['route_id'])
                ->get();
        }

        foreach ($allRoute as $allRoutes){
            $routeNo = $allRoutes->route_number;
        }

        return Excel::download(new SPADTrip($all_dates, $allRoute, $validatedData['dateFrom'], $validatedData['dateTo'], $routeNo), 'Trip_Report_SPAD.xlsx');
    }

    public function printTopBoarding()
    {
        //
    }

    public function printTopAlighting()
    {
        //
    }

    public function printBusTransfer()
    {
        //
    }

    public function printClaimDetails()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printClaimDetails()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required', 'int'],
        ])->validate();

        $out->writeln("datefrom:" . $validatedData['dateFrom']);
        $out->writeln("dateto:" . $validatedData['dateTo']);
        $out->writeln("route:" . $validatedData['route_id']);

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        if($this->selectedCompany){
            $allRoute = Route::where('company_id', $this->selectedCompany)
                ->where('id', $validatedData['route_id'])
                ->get();
        }

        foreach ($allRoute as $allRoutes){
            $routeNo = $allRoutes->route_number;
        }

        return Excel::download(new SPADClaimDetails($all_dates, $allRoute, $validatedData['dateFrom'], $validatedData['dateTo'], $routeNo), 'ClaimDetails_Report_SPAD.xlsx');
    }

    public function printClaimSummary()
    {
        //
    }

    public function printPenalty()
    {
        //
    }

    public function printTripMissed()
    {
        //
    }

    public function printSummaryByRoute()
    {
        //
    }

    public function printSummaryByNetwork()
    {
        //
    }
}
