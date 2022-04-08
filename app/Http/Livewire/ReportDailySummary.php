<?php

namespace App\Http\Livewire;

use App\Exports\DailySummary;
use App\Exports\MonthlySummary;
use App\Models\Bus;
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

            $busPerRoutes = Bus::where('route_id', $allRoute->id)->get();

            foreach($busPerRoutes as $busPerRoute) {

                $out->writeln("YOU ARE IN HERE loop busPerRoute");
                $allSales = TicketSalesTransaction::where('route_id', $allRoute->id)
                    ->where('bus_id', $busPerRoute->id)
                    ->where('sales_date', $validatedData['dailyDate'])
                    ->get();

                if ($allSales) {
                    $out->writeln("YOU ARE IN HERE if all sales");
                    foreach ($allSales as $allSale) {
                        $out->writeln("YOU ARE IN HERE all sales");
                        $countTrips = count($allSale);
                        $sumDistance = $allSale->route->inbound_distance + $allSale->route->outbound_distance;
                        $distance = $sumDistance * $countTrips;
                        $actual_distance = $distance * $countTrips;

                        $content['bus_no'] = $allSale->bus->bus_registration_number;
                        $content['route_name'] = $allSale->route->route_name;
                        $content['route_number'] = $allSale->route->route_number;
                        $content['count_trip'] = $countTrips;
                        $content['distance'] = $distance;
                        $content['count_bus'] = 1;
                        $content['count_actual_trip'] = $countTrips;
                        $content['actual_distance'] = $actual_distance;
                        $content['dead_distance'] = 0;

                        $data[$allRoute->route_name][$allSale->bus->bus_registration_number]['sales'] = $content;

                        $totCountTrip += $countTrips;
                        $totDistance += $distance;
                        $totCountBus += 1;
                        $totCountActualTrip += $countTrips;
                        $totActualDistance += $actual_distance;
                        $totDeadDistance += 0;
                    }
                }else{
                    $out->writeln("YOU ARE IN HERE no sales");
                    $content['bus_no'] = "No sales";
                    $content['route_name'] = "No sales";
                    $content['route_number'] = "No sales";
                    $content['count_trip'] = "No sales";
                    $content['distance'] = "No sales";
                    $content['count_bus'] = "No sales";
                    $content['count_actual_trip'] = "No sales";
                    $content['actual_distance'] = "No sales";
                    $content['dead_distance'] = "No sales";

                    $data[$allRoute->route_name][$busPerRoute->bus_registration_number]['sales'] = $content;
                }

                $total['total_count_trip'] = $totCountTrip;
                $total['total_distance'] = $totDistance;
                $total['total_count_bus'] = $totCountBus;
                $total['total_count_actual_trip'] = $totCountActualTrip;
                $total['total_actual_distance'] = $totActualDistance;
                $total['total_dead_distance'] = $totDeadDistance;

                $data[$allRoute->route_name][$busPerRoute->bus_registration_number]['total'] = $total;
                //$data['total'][$busPerRoute->id] = $total;
                //$dailyReport->add($data);

                $grandCountTrip += $totCountTrip;
                $grandDistance += $totDistance;
                $grandCountBus += $totCountBus;
                $grandCountActualTrip += $totCountActualTrip;
                $grandActualDistance += $totActualDistance;
                $grandDeadDistance += $totDeadDistance;
            }
        }
        $grand['grand_count_trip'] = $grandCountTrip;
        $grand['grand_distance'] = $grandDistance;
        $grand['grand_count_bus'] = $grandCountBus;
        $grand['grand_count_actual_trip'] = $grandCountActualTrip;
        $grand['grand_actual_distance'] = $grandActualDistance;
        $grand['grand_dead_distance'] = $grandDeadDistance;

        $main['data'] = $data;
        $main['grand']= $grand;
        $dailyReport->add($main);

        //$report['allData'] = $dailyReport;
        //$totalPlayerAdded['cumulativeFigurePerMonth'] = $monthlyCumPlayersCount;

        return Excel::download(new DailySummary($dailyReport, $validatedData['dailyDate']), 'DailyDetailsReport.xlsx');
    }
}
