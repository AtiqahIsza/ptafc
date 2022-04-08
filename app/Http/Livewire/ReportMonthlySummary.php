<?php

namespace App\Http\Livewire;

use App\Exports\MonthlySummary;
use App\Models\Bus;
use App\Models\Route;
use App\Models\Stage;
use App\Models\TicketSalesTransaction;
use App\Models\TripDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Console\Output\ConsoleOutput;

class ReportMonthlySummary extends Component
{
    public $state = [];
    public $data = [];

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

        $dateFrom =$validatedData['dateFrom'];
        $dateTo = $validatedData['dateTo'];

        $startDate = new Carbon($dateFrom);
        $endDate = new Carbon($dateTo);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        $totalTripPerDay = 0;
        $totalActualDistancePerMonth = 0.0;
        $totalNumOperatedBus = 0;
        $totalNumOperatedDay = 0;
        $totalNumPassenger = 0;
        $totalNumPassengerPeak1 = 0;
        $totalNumPassengerPeak2 = 0;
        $totalNumBusDriver = 0;

        $allRoutes = Route::all();
        $monthlySummary = collect();

        foreach($allRoutes as $allRoute){
            $actualDistancePerMonth = 0.0;

            //Jumlah trip sehari (sehala = 1 trip)
            $tripPerDay = TripDetail::where('start_trip', $dateFrom)->where('route_id', $allRoute->id)->get()->count();
            /*$tripPerDay = count($allTrips);*/
            $totalTripPerDay += $tripPerDay;

            //Jarak Perjalanan Sebulan, Jarak Perjalanan Sebenar Sebulan
            $sumDistance = $allRoute->inbound_distance + $allRoute->outbound_distance;
            $actualDistancePerDay = $sumDistance * $tripPerDay;
            $actualDistancePerMonth += $actualDistancePerDay;
            $distance = $actualDistancePerMonth;
            $totalActualDistancePerMonth += $actualDistancePerMonth;

            //Jumlah Bas Beroperasi
            $numOperatedBus = TripDetail::select('bus_id')
                ->whereBetween('start_trip', [$dateFrom, $dateTo])
                ->where('route_id', $allRoute->id)
                ->get()
                ->count();
            $totalNumOperatedBus += $numOperatedBus;

            //Jumlah hari Beroperasi / Sebulan
            $numOperatedDay = TripDetail::select('start_trip')
                ->whereBetween('start_trip', [$dateFrom, $dateTo])
                ->where('route_id', $allRoute->id)
                ->distinct()
                ->count();
            $totalNumOperatedDay += $numOperatedDay;

            //Jumlah Penumpang / Sebulan
            $numPassenger = TicketSalesTransaction::select('id')
                ->whereBetween('sales_date', [$dateFrom, $dateTo])
                ->where('route_id', $allRoute->id)
                ->count();
            $totalNumPassenger += $numPassenger;

            $numPassengerPeak1 = 0;
            $numPassengerPeak2 = 0;

            foreach($all_dates as $all_date){
                //Jumlah Penumpang Waktu Puncak 6Pg - 9Pg
                $startPeak1 = new Carbon($all_date . '06:00');
                $endPeak1 = new Carbon($all_date . '09:00');

                $countPassengerPeak1 = TicketSalesTransaction::select('id')
                    ->whereBetween('sales_date', [$startPeak1, $endPeak1])
                    ->where('route_id', $allRoute->id)
                    ->count();

                $numPassengerPeak1 += $countPassengerPeak1;
                $totalNumPassengerPeak1 += $numPassengerPeak1;

                //Jumlah Penumpang Waktu Puncak 4Ptg - 8Ptg
                $startPeak2 = new Carbon($all_date . '16:00');
                $endPeak2 = new Carbon($all_date . '19:00');

                $countPassengerPeak2 = TicketSalesTransaction::select('id')
                    ->whereBetween('sales_date', [$startPeak2, $endPeak2])
                    ->where('route_id', $allRoute->id)
                    ->count();

                $numPassengerPeak2 += $countPassengerPeak2;
                $totalNumPassengerPeak2 += $numPassengerPeak2;
            }

            //Kekerapan Bas Memasuki Terminal/ Sebulan

            //Jumlah Pemandu
            $numBusDriver = Bus::select('id')->where('route_id', $allRoute->id)->count();
            $totalNumBusDriver += $numBusDriver;

            //Gaji Pemandu

            //Pendapatan mengikut surat angkutan

            $perRoute['route_name'] = $allRoute->route_name;
            $perRoute['route_number'] = $allRoute->route_number;
            $perRoute['trip_per_day'] = $tripPerDay;
            $perRoute['actual_distance_per_month'] = $actualDistancePerMonth;
            $perRoute['dead_distance_per_month'] = 0;
            $perRoute['distance_per_month'] = $distance;
            $perRoute['operated_bus'] = $numOperatedBus;
            $perRoute['operated_day'] = $numOperatedDay;
            $perRoute['number_passenger'] = $numPassenger;
            $perRoute['number_passenger_peak1'] = $numPassengerPeak1;
            $perRoute['number_passenger_peak2'] = $numPassengerPeak2;
            $perRoute['number_bus_driver'] = $numBusDriver;

            $data['perRoute'][$allRoute->route_name] = $perRoute;
        }
        $total['total_trip_per_day'] = $totalTripPerDay;
        $total['total_actual_distance_per_month'] = $totalActualDistancePerMonth;
        $total['total_dead_distance_per_month'] = 0;
        $total['total_distance_per_month'] = $totalActualDistancePerMonth;
        $total['total_operated_bus'] = $totalNumOperatedBus;
        $total['total_operated_day'] = $totalNumOperatedDay;
        $total['total_number_passenger'] = $totalNumPassenger;
        $total['total_number_passenger_peak1'] = $totalNumPassengerPeak1;
        $total['total_number_passenger_peak2'] = $totalNumPassengerPeak2;
        $total['total_number_bus_driver'] = $totalNumBusDriver;

        $data['total'] = $total;

        $monthlySummary->add($data);

        return Excel::download(new MonthlySummary($monthlySummary,$dateFrom,$dateTo), 'MonthlySummary.xlsx');
    }
}
