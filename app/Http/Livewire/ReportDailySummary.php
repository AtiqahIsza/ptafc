<?php

namespace App\Http\Livewire;

use App\Exports\DailySummary;
use App\Exports\MonthlySummary;
use App\Models\Route;
use App\Models\Stage;
use App\Models\TicketSalesTransaction;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Console\Output\ConsoleOutput;

class ReportDailySummary extends Component
{
    public $state = [];
    public $data = [];

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

        $allRoutes = Route::all();

        $dailyReport = collect();
        $dailyReport_total = collect();
        $dailyReport_grand = collect();

        $grandCountTrip = 0;
        $grandDistance = 0.0;
        $grandCountBus = 0;
        $grandCountActualTrip = 0;
        $grandActualDistance = 0.0;
        $grandDeadDistance = 0.0;

        foreach ($allRoutes as $allRoute){
            $totCountTrip = 0;
            $totDistance = 0.0;
            $totCountBus = 0;
            $totCountActualTrip = 0;
            $totActualDistance = 0.0;
            $totDeadDistance = 0.0;

            $allSales = TicketSalesTransaction::where('route_id', $allRoute->id)
                ->where('sales_date', $validatedData['dailyDate'])
                ->get();
            if($allSales){
                foreach ($allSales as $allSale){
                    $data['bus_no'] = $allSales->bus->bus_registration_number;
                    $data['route_name'] = $allSales->route->route_name;
                    $data['route_number'] = $allSales->route->route_number;

                    $allTrips = TicketSalesTransaction::where('route_id', $allRoute->id)
                        ->where('bus_id', $allSales->bus_id)
                        ->where('sales_date', $validatedData['dailyDate'])
                        ->get();

                    $countTrips = count($allTrips);
                    $sumDistance = $allSales->route->inbound_distance + $allSales->route->outbound_distance;
                    $distance = $sumDistance * $countTrips;
                    $actual_distance = $distance * $countTrips;

                    $data['count_trip'] = $countTrips;
                    $data['distance'] = $distance;
                    $data['count_bus'] = 1;
                    $data['count_actual_trip'] = $countTrips;
                    $data['actual_distance'] = $actual_distance;
                    $data['dead_distance'] = 0;

                    $dailyReport->add($data);

                    $totCountTrip += $countTrips;
                    $totDistance += $distance;
                    $totCountBus += 1;
                    $totCountActualTrip += $countTrips;
                    $totActualDistance += $actual_distance;
                    $totDeadDistance += 0;
                }
            }
            $total['tot_count_trip'] = $totCountTrip;
            $total['tot_distance'] = $totDistance;
            $total['tot_count_bus'] = $totCountBus;
            $total['tot_count_actual_trip'] = $totCountActualTrip;
            $total['tot_actual_distance'] = $totActualDistance;
            $total['tot_dead_distance'] = $totDeadDistance;

            $data_tot['total'] = $total;
            $dailyReport->add($data_tot);

            $grandCountTrip += $totCountTrip;
            $grandDistance += $totDistance;
            $grandCountBus += $totCountBus;
            $grandCountActualTrip += $totCountActualTrip;
            $grandActualDistance += $totActualDistance;
            $grandDeadDistance += $totDeadDistance;
        }
        $grand['grand_count_trip'] = $grandCountTrip;
        $grand['grand_distance'] = $grandDistance;
        $grand['grand_count_bus'] = $grandCountBus;
        $grand['grand_count_actual_trip'] = $grandCountActualTrip;
        $grand['grand_actual_distance'] = $grandActualDistance;
        $grand['grand_dead_distance'] = $grandDeadDistance;

        $data_grand['grand'] = $grand;
        $dailyReport->add($data_grand);

        $report['allData'] = $dailyReport;
        //$totalPlayerAdded['cumulativeFigurePerMonth'] = $monthlyCumPlayersCount;

        return Excel::download(new DailySummary($report,$validatedData['dailyDate']), 'DailyDetailsReport.xlsx');
    }
}
