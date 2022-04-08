<?php

namespace App\Http\Livewire;

use App\Exports\SalesByBus;
use App\Exports\SPADClaimDetails;
use App\Exports\SPADIsbsf;
use App\Exports\SPADRoute;
use App\Exports\SPADServiceGroup;
use App\Exports\SPADSummary;
use App\Exports\SPADTopBoarding;
use App\Exports\SPADTrip;
use App\Models\Bus;
use App\Models\BusDriver;
use App\Models\BusStand;
use App\Models\Company;
use App\Models\Route;
use App\Models\RouteSchedulerDetail;
use App\Models\RouteSchedulerMSTR;
use App\Models\Stage;
use App\Models\TicketSalesTransaction;
use App\Models\TripDetail;
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
            'route_id' => ['int'],
        ])->validate();

        $out->writeln("datefrom Before:" . $validatedData['dateFrom']);
        $out->writeln("dateto Before:" . $validatedData['dateTo']);

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo'] . '11:59:59');

        $out->writeln("dateFrom After:" . $dateFrom);
        $out->writeln("dateto After:" . $dateTo);

        $prevStart = Carbon::create($validatedData['dateFrom'])->startOfMonth()->subMonthsNoOverflow()->toDateString();
        $prevEnd = Carbon::create($validatedData['dateTo'])->subMonthsNoOverflow()->endOfMonth()->toDateString();

        $previousStartMonth = new Carbon($prevStart);
        $previousEndMonth = new Carbon($prevEnd . '11:59:59');

        $out->writeln("prevStartMonth:" . $previousStartMonth );
        $out->writeln("prevEndMonth::" . $previousEndMonth);

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }
        $existInTrip = false;
        $existOutTrip = false;

        $summary = collect();

        if($this->selectedCompany){
            //Summary certain route for specific company
            if(!empty($this->state['route_id'])) {
                //Inbound
                $allTripInbounds = TripDetail::where('route_id', $validatedData['route_id'])
                    ->whereBetween('start_trip', [$dateFrom, $dateTo])
                    ->where('trip_code', 1)
                    ->get();

                if($allTripInbounds){
                    $existInTrip = true;
                    $totalFareboxIn = 0;
                    $totalRidershipIn = 0;
                    $totalKMPlannedIn = 0;
                    $totalKMServedIn = 0;
                    $totalKMGPSIn = 0;
                    $earlyDepartureIn = 0;
                    $lateDepartureIn = 0;
                    $earlyEndIn = 0;
                    $lateEndIn = 0;
                    foreach($allTripInbounds as $allTripInbound) {
                        //Ridership
                        $ridership = TicketSalesTransaction::where('trip_id', $allTripInbound->id)->count();
                        $totalRidershipIn += $ridership;

                        //Farebox Collection
                        $farebox = $allTripInbound->total_adult_amount + $allTripInbound->total_concession_amount;
                        $totalFareboxIn += $farebox;

                        //Total KM Service Planned
                        $kmPlanned = $allTripInbound->routeScheduleMSTR->inbound_distance * count($allTripInbounds);
                        $totalKMPlannedIn += $kmPlanned;

                        //Total KM Service Served
                        $kmServed = $allTripInbound->total_mileage;
                        $totalKMServedIn += $kmServed;

                        //Total KM Service Served by GPS
                        $kmGPS = $allTripInbound->total_mileage;
                        $totalKMGPSIn += $kmGPS;

                        //Total Early Departure
                        if($allTripInbound->routeScheduleMSTR->schedule_start_time > $allTripInbound->start_trip){
                            $earlyDepartureIn++;
                        }
                        //Total Late Departure
                        else{
                            $lateDepartureIn++;
                        }

                        //Total Early End
                        if($allTripInbound->routeScheduleMSTR->schedule_end_time > $allTripInbound->start_trip){
                            $earlyEndIn++;
                        }
                        //Total Late End
                        else{
                            $lateEndIn++;
                        }
                    }
                    //Previous Month Ridership collection
                    $prevRidershipIn = TicketSalesTransaction::whereBetween('sales_date', [$previousStartMonth, $previousEndMonth])->count();

                    //Increment ridership collection (%)
                    if($prevRidershipIn==0){
                        $increaseRidershipIn = 100;
                        $increaseRidershipFormatIn = 100;
                    }else{
                        $increaseRidershipIn = (($totalRidershipIn - $prevRidershipIn) / $prevRidershipIn) * 100;
                        $increaseRidershipFormatIn = number_format((float)$increaseRidershipIn, 2, '.', '');
                    }

                    //Previous month farebox collection
                    $adultPrevIn = TripDetail::where('route_id', $validatedData['route_id'])
                        ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                        ->where('trip_code', 1)
                        ->sum('total_adult_amount');
                    $concessionPrevIn = TripDetail::where('route_id', $validatedData['route_id'])
                        ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                        ->where('trip_code', 1)
                        ->sum('total_concession_amount');
                    $prevFareboxIn = $adultPrevIn + $concessionPrevIn;

                    //Increment farebox collection (%)
                    if($prevFareboxIn==0){
                        $increaseFareboxIn = 100;
                        $increaseFareboxFormatIn = 100;
                    }else{
                        $increaseFareboxIn = (($totalFareboxIn - $prevFareboxIn) / $prevFareboxIn) * 100;
                        $increaseFareboxFormatIn = number_format((float)$increaseFareboxIn, 2, '.', '');
                    }

                    //Average Fare per pax (RM)
                    $averageIn = $totalFareboxIn / $totalRidershipIn;
                    $averageFormatIn = number_format((float)$averageIn, 2, '.', '');

                    //Number of trips planned
                    $tripPlannedIn = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->count();

                    //Number of trips made
                    $tripMadeIn = count($allTripInbounds);

                    /**Number of Trips missed*/
                    //$tripMissed = MissedTrip::whereBetween('service_date', [$dateFrom, $dateTo])->count();
                    $tripMissedIn = 0;

                    /**Total Breakdown During Operation*/
                    $breakdownIn = 0;

                    //Total Bus In Used
                    $busUsedIn = TripDetail::where('id', $validatedData['route_id'])
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->where('trip_code', 1)
                        ->distinct('bus_id')
                        ->count();

                    /**Total Accidents caused by Operator*/
                    $accidentIn = 0;

                    /**Total Complaint*/
                    $complaintsIn = 0;

                    $inbound['ridership_in'] = $totalRidershipIn;
                    $inbound['prev_ridership_in'] = $prevRidershipIn;
                    $inbound['increase_ridership_in'] = $increaseRidershipFormatIn;
                    $inbound['farebox_in'] = $totalFareboxIn;
                    $inbound['prev_farebox_in'] = $prevFareboxIn;
                    $inbound['increase_farebox_in'] = $increaseFareboxFormatIn;
                    $inbound['average_fare_in'] = $averageFormatIn;
                    $inbound['trip_planned_in'] = $tripPlannedIn;
                    $inbound['trip_made_in'] = $tripMadeIn;
                    $inbound['trip_missed_in'] = $tripMissedIn;
                    $inbound['km_planned_in'] = $totalKMPlannedIn;
                    $inbound['km_served_in'] = $totalKMServedIn;
                    $inbound['km_served_gps_in'] = $totalKMGPSIn;
                    $inbound['early_departure_in'] = $earlyDepartureIn;
                    $inbound['late_departure_in'] = $lateDepartureIn;
                    $inbound['early_end_in'] = $earlyEndIn;
                    $inbound['late_end_in'] = $lateEndIn;
                    $inbound['breakdown_in'] = $breakdownIn;
                    $inbound['bus_used_in'] = $busUsedIn;
                    $inbound['accidents_in'] = $accidentIn;
                    $inbound['complaints_in'] = $complaintsIn;
                }
                //Outbound
                $allTripOutbounds = TripDetail::where('id', $validatedData['route_id'])
                    ->whereBetween('start_trip', [$dateFrom, $dateTo])
                    ->where('trip_code', 0)
                    ->get();

                if($allTripOutbounds){
                    $existOutTrip = true;
                    $totalFareboxOut = 0;
                    $totalRidershipOut = 0;
                    $totalKMPlannedOut = 0;
                    $totalKMServedOut = 0;
                    $totalKMGPSOut = 0;
                    $earlyDepartureOut = 0;
                    $lateDepartureOut = 0;
                    $earlyEndOut = 0;
                    $lateEndOut = 0;
                    foreach($allTripOutbounds as $allTripOutbound) {
                        //Ridership
                        $ridership = TicketSalesTransaction::where('trip_id', $allTripOutbound->id)->count();
                        $totalRidershipOut += $ridership;

                        //Farebox Collection
                        $farebox = $allTripOutbound->total_adult_amount + $allTripOutbound->total_concession_amount;
                        $totalFareboxOut += $farebox;

                        //Total KM Service Planned
                        $kmPlanned = $allTripOutbound->routeScheduleMSTR->inbound_distance * count($allTripInbounds);
                        $totalKMPlannedOut += $kmPlanned;

                        //Total KM Service Served
                        $kmServed = $allTripOutbound->total_mileage;
                        $totalKMServedOut += $kmServed;

                        //Total KM Service Served by GPS
                        $kmGPS = $allTripOutbound->total_mileage;
                        $totalKMGPSOut += $kmGPS;

                        //Total Early Departure
                        if($allTripOutbound->routeScheduleMSTR->schedule_start_time > $allTripOutbound->start_trip){
                            $earlyDepartureOut++;
                        }
                        //Total Late Departure
                        else{
                            $lateDepartureOut++;
                        }

                        //Total Early End
                        if($allTripOutbound->routeScheduleMSTR->schedule_end_time > $allTripOutbound->start_trip){
                            $earlyEndOut++;
                        }
                        //Total Late End
                        else{
                            $lateEndOut++;
                        }
                    }
                    //Previous Month Ridership collection
                    $prevRidershipOut = TicketSalesTransaction::whereBetween('sales_date', [$previousStartMonth, $previousEndMonth])->count();

                    //Increment ridership collection (%)
                    if($prevRidershipOut==0){
                        $increaseRidershipOut = 100;
                        $increaseRidershipFormatOut = 100;
                    }else{
                        $increaseRidershipOut = (($totalRidershipOut - $prevRidershipOut) / $prevRidershipOut) * 100;
                        $increaseRidershipFormatOut = number_format((float)$increaseRidershipOut, 2, '.', '');
                    }

                    //Previous month farebox collection
                    $adultPrevOut = TripDetail::where('route_id', $validatedData['route_id'])
                        ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                        ->where('trip_code', 0)
                        ->sum('total_adult_amount');
                    $concessionPrevOut = TripDetail::where('route_id', $validatedData['route_id'])
                        ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                        ->where('trip_code', 0)
                        ->sum('total_concession_amount');
                    $prevFareboxOut = $adultPrevOut + $concessionPrevOut;

                    //Increment farebox collection (%)
                    if($prevFareboxOut==0){
                        $increaseFareboxOut = 100;
                        $increaseFareboxFormatOut = 100;
                    }else{
                        $increaseFareboxOut = (($totalFareboxOut - $prevFareboxOut) / $prevFareboxOut) * 100;
                        $increaseFareboxFormatOut = number_format((float)$increaseFareboxOut, 2, '.', '');
                    }

                    //Average Fare per pax (RM)
                    if($totalFareboxOut==0 && $totalRidershipOut==0){
                        $averageOut = 0;
                        $averageFormatOut = 0;
                    }else{
                        $averageOut = $totalFareboxOut / $totalRidershipOut;
                        $averageFormatOut = number_format((float)$averageOut, 2, '.', '');
                    }

                    //Number of trips planned
                    $tripPlannedOut = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->count();

                    //Number of trips made
                    $tripMadeOut = count($allTripOutbounds);

                    /**Number of Trips missed*/
                    //$tripMissed = MissedTrip::whereBetween('service_date', [$dateFrom, $dateTo])->count();
                    $tripMissedOut = 0;

                    /**Total Breakdown During Operation*/
                    $breakdownOut = 0;

                    //Total Bus In Used
                    $busUsedOut = TripDetail::where('id', $validatedData['route_id'])
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->where('trip_code', 0)
                        ->distinct('bus_id')
                        ->count();

                    /**Total Accidents caused by Operator*/
                    $accidentOut = 0;

                    /**Total Complaint*/
                    $complaintsOut = 0;

                    $outbound['ridership_out'] = $totalRidershipOut;
                    $outbound['prev_ridership_out'] = $prevRidershipOut;
                    $outbound['increase_ridership_out'] = $increaseRidershipFormatOut;
                    $outbound['farebox_out'] = $totalFareboxOut;
                    $outbound['prev_farebox_out'] = $prevFareboxOut;
                    $outbound['increase_farebox_out'] = $increaseFareboxFormatOut;
                    $outbound['average_fare_out'] = $averageFormatOut;
                    $outbound['trip_planned_out'] = $tripPlannedOut;
                    $outbound['trip_made_out'] = $tripMadeOut;
                    $outbound['trip_missed_out'] = $tripMissedOut;
                    $outbound['km_planned_out'] = $totalKMPlannedOut;
                    $outbound['km_served_out'] = $totalKMServedOut;
                    $outbound['km_served_gps_out'] = $totalKMGPSOut;
                    $outbound['early_departure_out'] = $earlyDepartureOut;
                    $outbound['late_departure_out'] = $lateDepartureOut;
                    $outbound['early_end_out'] = $earlyEndOut;
                    $outbound['late_end_out'] = $lateEndOut;
                    $outbound['breakdown_out'] = $breakdownOut;
                    $outbound['bus_used_out'] = $busUsedOut;
                    $outbound['accidents_out'] = $accidentOut;
                    $outbound['complaints_out'] = $complaintsOut;
                }
                $inbound_data=[];
                $outbound_data=[];
                if($existInTrip==true && $existOutTrip==true){
                    $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                    $data['route_no'] = $selectedRoute->route_number;
                    $firstStage = Stage::where('route_id', $validatedData['route_id'])->first();
                    $lastStage = Stage::where('route_id', $validatedData['route_id'])->orderby('stage_order','DESC')->first();
                    $route_name_in = $selectedRoute->route_number . ' ' . $firstStage->stage_name . ' ' . $lastStage->stage_name;
                    $route_name_out = $selectedRoute->route_number . ' ' . $lastStage->stage_name . ' ' . $firstStage->stage_name;

                    $inbound_data[$route_name_in] = $inbound;
                    $outbound_data[$route_name_out] = $outbound;

                    $total['total_ridership'] = $totalRidershipOut + $totalRidershipIn;
                    $total['total_prev_ridership'] = $prevRidershipOut + $prevRidershipIn;

                    $sumIncreaseRidership = $increaseRidershipIn + $increaseRidershipOut;
                    $sumIncreaseRidershipFormat = number_format((float)$sumIncreaseRidership, 2, '.', '');
                    $total['total_increase_ridership'] = $sumIncreaseRidershipFormat;

                    $total['total_farebox'] = $totalFareboxOut + $totalFareboxIn;
                    $total['total_prev_farebox'] = $prevFareboxOut + $prevFareboxIn;

                    $sumIncreaseFarebox = $increaseFareboxIn + $increaseFareboxOut;
                    $sumIncreaseFareboxFormat = number_format((float)$sumIncreaseFarebox, 2, '.', '');
                    $total['total_increase_farebox'] = $sumIncreaseFareboxFormat;

                    $sumAverage = $averageIn + $averageOut;
                    $sumAverageFormat = number_format((float)$sumAverage, 2, '.', '');
                    $total['total_average_fare'] = $sumAverageFormat;

                    $total['total_trip_planned'] = $tripPlannedOut + $tripPlannedIn;
                    $total['total_trip_made'] = $tripMadeOut + $tripMadeIn;
                    $total['total_trip_missed'] = $tripMissedOut + $tripMissedIn;
                    $total['total_km_planned'] = $totalKMPlannedOut + $totalKMPlannedIn;
                    $total['total_km_served'] = $totalKMServedOut + $totalKMServedIn;
                    $total['total_km_served_gps'] = $totalKMGPSOut + $totalKMGPSIn;
                    $total['total_early_departure'] = $earlyDepartureOut + $earlyDepartureIn;
                    $total['total_late_departure'] = $lateDepartureOut + $lateDepartureIn;
                    $total['total_early_end'] = $earlyEndOut + $earlyEndIn;
                    $total['total_late_end'] = $lateEndOut + $lateEndIn;
                    $total['total_breakdown'] = $breakdownOut + $breakdownIn;
                    $total['total_bus_used'] = $busUsedOut + $busUsedIn;
                    $total['total_accidents'] = $accidentOut + $accidentIn;
                    $total['total_complaints'] = $complaintsOut + $complaintsIn;

                    $grand['grand_ridership'] = $totalRidershipOut + $totalRidershipIn;
                    $grand['grand_prev_ridership'] = $prevRidershipOut + $prevRidershipIn;

                    $sumIncreaseRidership = $increaseRidershipIn + $increaseRidershipOut;
                    $sumIncreaseRidershipFormat = number_format((float)$sumIncreaseRidership, 2, '.', '');
                    $grand['grand_increase_ridership'] = $sumIncreaseRidershipFormat;

                    $grand['grand_farebox'] = $totalFareboxOut + $totalFareboxIn;
                    $grand['grand_prev_farebox'] = $prevFareboxOut + $prevFareboxIn;

                    $sumIncreaseFarebox = $increaseFareboxIn + $increaseFareboxOut;
                    $sumIncreaseFareboxFormat = number_format((float)$sumIncreaseFarebox, 2, '.', '');
                    $grand['grand_increase_farebox'] = $sumIncreaseFareboxFormat;

                    $sumAverage = $averageIn + $averageOut;
                    $sumAverageFormat = number_format((float)$sumAverage, 2, '.', '');
                    $grand['grand_average_fare'] = $sumAverageFormat;

                    $grand['grand_trip_planned'] = $tripPlannedOut + $tripPlannedIn;
                    $grand['grand_trip_made'] = $tripMadeOut + $tripMadeIn;
                    $grand['grand_trip_missed'] = $tripMissedOut + $tripMissedIn;
                    $grand['grand_km_planned'] = $totalKMPlannedOut + $totalKMPlannedIn;
                    $grand['grand_km_served'] = $totalKMServedOut + $totalKMServedIn;
                    $grand['grand_km_served_gps'] = $totalKMGPSOut + $totalKMGPSIn;
                    $grand['grand_early_departure'] = $earlyDepartureOut + $earlyDepartureIn;
                    $grand['grandlate_departure'] = $lateDepartureOut + $lateDepartureIn;
                    $grand['grand_early_end'] = $earlyEndOut + $earlyEndIn;
                    $grand['grand_late_end'] = $lateEndOut + $lateEndIn;
                    $grand['grand_breakdown'] = $breakdownOut + $breakdownIn;
                    $grand['grand_bus_used'] = $busUsedOut + $busUsedIn;
                    $grand['grand_accidents'] = $accidentOut + $accidentIn;
                    $grand['grand_complaints'] = $complaintsOut + $complaintsIn;

                    $data['inbound_data'] = $inbound_data;
                    $data['outbound_data'] = $outbound_data;
                    $data['total'] = $total;
                    $data['grand'] = $grand;

                    $summary->add($data);

                }elseif($existInTrip==false && $existOutTrip==true){
                    $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                    $data['route_no'] = $selectedRoute->route_number;
                    $firstStage = Stage::where('route_id', $validatedData['route_id'])->first();
                    $lastStage = Stage::where('route_id', $validatedData['route_id'])->orderby('stage_order','DESC')->first();
                    $route_name_out = $selectedRoute->route_number . ' ' . $lastStage->stage_name . ' ' . $firstStage->stage_name;

                    $outbound_data[$route_name_out] = $outbound;

                    $total['total_ridership'] = $totalRidershipOut;
                    $total['total_prev_ridership'] = $prevRidershipOut;
                    $total['total_increase_ridership'] = $increaseRidershipFormatOut;
                    $total['total_farebox'] = $totalFareboxOut;
                    $total['total_prev_farebox'] = $prevFareboxOut;
                    $total['total_increase_farebox'] = $increaseFareboxFormatOut;
                    $total['total_average_fare'] = $averageFormatOut;
                    $total['total_trip_planned'] = $tripPlannedOut;
                    $total['total_trip_made'] = $tripMadeOut;
                    $total['total_trip_missed'] = $tripMissedOut;
                    $total['total_km_planned'] = $totalKMPlannedOut;
                    $total['total_km_served'] = $totalKMServedOut;
                    $total['total_km_served_gps'] = $totalKMGPSOut;
                    $total['total_early_departure'] = $earlyDepartureOut;
                    $total['total_late_departure'] = $lateDepartureOut;
                    $total['total_early_end'] = $earlyEndOut;
                    $total['total_late_end'] = $lateEndOut;
                    $total['total_breakdown'] = $breakdownOut;
                    $total['total_bus_used'] = $busUsedOut;
                    $total['total_accidents'] = $accidentOut;
                    $total['total_complaints'] = $complaintsOut;

                    $grand['grand_ridership'] = $totalRidershipOut;
                    $grand['grand_prev_ridership'] = $prevRidershipOut;
                    $grand['grand_increase_ridership'] = $increaseRidershipFormatOut;
                    $grand['grand_farebox'] = $totalFareboxOut;
                    $grand['grand_prev_farebox'] = $prevFareboxOut;
                    $grand['grand_increase_farebox'] = $increaseFareboxFormatOut;
                    $grand['grand_average_fare'] = $averageFormatOut;
                    $grand['grand_trip_planned'] = $tripPlannedOut;
                    $grand['grand_trip_made'] = $tripMadeOut;
                    $grand['grand_trip_missed'] = $tripMissedOut;
                    $grand['grand_km_planned'] = $totalKMPlannedOut;
                    $grand['grand_km_served'] = $totalKMServedOut;
                    $grand['grand_km_served_gps'] = $totalKMGPSOut;
                    $grand['grand_early_departure'] = $earlyDepartureOut;
                    $grand['grand_late_departure'] = $lateDepartureOut;
                    $grand['grand_early_end'] = $earlyEndOut;
                    $grand['grand_late_end'] = $lateEndOut;
                    $grand['grand_breakdown'] = $breakdownOut;
                    $grand['grand_bus_used'] = $busUsedOut;
                    $grand['grand_accidents'] = $accidentOut;
                    $grand['grand_complaints'] = $complaintsOut;

                    $data['outbound_data'] = $outbound_data;
                    $data['total'] = $total;
                    $data['grand'] = $grand;
                    $summary->add($data);

                }elseif($existInTrip==true && $existOutTrip==false){
                    $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                    $data['route_no'] = $selectedRoute->route_number;
                    $firstStage = Stage::where('route_id', $validatedData['route_id'])->first();
                    $lastStage = Stage::where('route_id', $validatedData['route_id'])->orderby('stage_order','DESC')->first();
                    $route_name_in = $selectedRoute->route_number . ' ' . $firstStage->stage_name . ' ' . $lastStage->stage_name;

                    $inbound_data[$route_name_in] = $inbound;

                    $total['total_ridership'] = $totalRidershipIn;
                    $total['total_prev_ridership'] = $prevRidershipIn;
                    $total['total_increase_ridership'] = $increaseRidershipFormatIn;
                    $total['total_farebox'] = $totalFareboxIn;
                    $total['total_prev_farebox'] = $prevFareboxIn;
                    $total['total_increase_farebox'] = $increaseFareboxFormatIn;
                    $total['total_average_fare'] = $averageFormatIn;
                    $total['total_trip_planned'] = $tripPlannedIn;
                    $total['total_trip_made'] = $tripMadeIn;
                    $total['total_trip_missed'] = $tripMissedIn;
                    $total['total_km_planned'] = $totalKMPlannedIn;
                    $total['total_km_served'] = $totalKMServedIn;
                    $total['total_km_served_gps'] = $totalKMGPSIn;
                    $total['total_early_departure'] = $earlyDepartureIn;
                    $total['total_late_departure'] = $lateDepartureIn;
                    $total['total_early_end'] = $earlyEndIn;
                    $total['total_late_end'] = $lateEndIn;
                    $total['total_breakdown'] = $breakdownIn;
                    $total['total_bus_used'] = $busUsedIn;
                    $total['total_accidents'] = $accidentIn;
                    $total['total_complaints'] = $complaintsIn;

                    $grand['grand_ridership'] = $totalRidershipIn;
                    $grand['grand_prev_ridership'] = $prevRidershipIn;
                    $grand['grand_increase_ridership'] = $increaseRidershipFormatIn;
                    $grand['grand_farebox'] = $totalFareboxIn;
                    $grand['grand_prev_farebox'] = $prevFareboxIn;
                    $grand['grand_increase_farebox'] = $increaseFareboxFormatIn;
                    $grand['grand_average_fare'] = $averageFormatIn;
                    $grand['grand_trip_planned'] = $tripPlannedIn;
                    $grand['grand_trip_made'] = $tripMadeIn;
                    $grand['grand_trip_missed'] = $tripMissedIn;
                    $grand['grand_km_planned'] = $totalKMPlannedIn;
                    $grand['grand_km_served'] = $totalKMServedIn;
                    $grand['grand_km_served_gps'] = $totalKMGPSIn;
                    $grand['grand_early_departure'] = $earlyDepartureIn;
                    $grand['grand_late_departure'] = $lateDepartureIn;
                    $grand['grand_early_end'] = $earlyEndIn;
                    $grand['grand_late_end'] = $lateEndIn;
                    $grand['grand_breakdown'] = $breakdownIn;
                    $grand['grand_bus_used'] = $busUsedIn;
                    $grand['grand_accidents'] = $accidentIn;
                    $grand['grand_complaints'] = $complaintsIn;

                    $data['inbound_data'] = $inbound_data;
                    $data['total'] = $total;
                    $data['grand'] = $grand;
                    $summary->add($data);

                }else{
                    $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                    $data['route_no'] = $selectedRoute->route_number;
                    $firstStage = Stage::where('route_id', $validatedData['route_id'])->first();
                    $lastStage = Stage::where('route_id', $validatedData['route_id'])->orderby('stage_order','DESC')->first();
                    $route_name_in = $selectedRoute->route_number . ' ' . $firstStage->stage_name . ' ' . $lastStage->stage_name;
                    $route_name_out = $selectedRoute->route_number . ' ' . $lastStage->stage_name . ' ' . $firstStage->stage_name;

                    $inbound_data[$route_name_in] = $inbound;
                    $outbound_data[$route_name_out] = $outbound;

                    $inbound['ridership_in'] = 0;
                    $inbound['prev_ridership_in'] = 0;
                    $inbound['increase_ridership_in'] = 0;
                    $inbound['farebox_in'] = 0;
                    $inbound['prev_farebox_in'] = 0;
                    $inbound['increase_farebox_in'] = 0;
                    $inbound['average_fare_in'] = 0;
                    $inbound['trip_planned_in'] = 0;
                    $inbound['trip_made_in'] = 0;
                    $inbound['trip_missed_in'] = 0;
                    $inbound['km_planned_in'] = 0;
                    $inbound['km_served_in'] = 0;
                    $inbound['km_served_gps_in'] = 0;
                    $inbound['early_departure_in'] = 0;
                    $inbound['late_departure_in'] = 0;
                    $inbound['early_end_in'] = 0;
                    $inbound['late_end_in'] = 0;
                    $inbound['breakdown_in'] = 0;
                    $inbound['bus_used_in'] = 0;
                    $inbound['accidents_in'] = 0;
                    $inbound['complaints_in'] = 0;

                    $outbound['ridership_out'] = 0;
                    $outbound['prev_ridership_out'] = 0;
                    $outbound['increase_ridership_out'] = 0;
                    $outbound['farebox_out'] = 0;
                    $outbound['prev_farebox_out'] = 0;
                    $outbound['increase_farebox_out'] = 0;
                    $outbound['average_fare_out'] = 0;
                    $outbound['trip_planned_out'] = 0;
                    $outbound['trip_made_out'] = 0;
                    $outbound['trip_missed_out'] = 0;
                    $outbound['km_planned_out'] = 0;
                    $outbound['km_served_out'] = 0;
                    $outbound['km_served_gps_out'] = 0;
                    $outbound['early_departure_out'] = 0;
                    $outbound['late_departure_out'] = 0;
                    $outbound['early_end_out'] = 0;
                    $outbound['late_end_out'] = 0;
                    $outbound['breakdown_out'] = 0;
                    $outbound['bus_used_out'] = 0;
                    $outbound['accidents_out'] = 0;
                    $outbound['complaints_out'] = 0;

                    $total['total_ridership'] = 0;
                    $total['total_prev_ridership'] = 0;
                    $total['total_increase_ridership'] = 0;
                    $total['total_farebox'] = 0;
                    $total['total_prev_farebox'] = 0;
                    $total['total_increase_farebox'] = 0;
                    $total['total_average_fare'] = 0;
                    $total['total_trip_planned'] = 0;
                    $total['total_trip_made'] = 0;
                    $total['total_trip_missed'] = 0;
                    $total['total_km_planned'] = 0;;
                    $total['total_km_served'] = 0;
                    $total['total_km_served_gps'] = 0;
                    $total['total_early_departure'] = 0;
                    $total['total_late_departure'] = 0;
                    $total['total_early_end'] = 0;
                    $total['total_late_end'] = 0;
                    $total['total_breakdown'] =0;
                    $total['total_bus_used'] = 0;
                    $total['total_accidents'] = 0;
                    $total['total_complaints'] = 0;

                    $grand['grand_ridership'] = 0;
                    $grand['grand_prev_ridership'] = 0;
                    $grand['grand_increase_ridership'] = 0;
                    $grand['grand_farebox'] = 0;
                    $grand['grand_prev_farebox'] = 0;
                    $grand['grand_increase_farebox'] = 0;
                    $grand['grand_average_fare'] = 0;
                    $grand['grand_trip_planned'] = 0;
                    $grand['grand_trip_made'] = 0;
                    $grand['grand_trip_missed'] = 0;
                    $grand['grand_km_planned'] = 0;;
                    $grand['grand_km_served'] = 0;
                    $grand['grand_km_served_gps'] = 0;
                    $grand['grand_early_departure'] = 0;
                    $grand['grand_late_departure'] = 0;
                    $grand['grand_early_end'] = 0;
                    $grand['grand_late_end'] = 0;
                    $grand['grand_breakdown'] =0;
                    $grand['grand_bus_used'] = 0;
                    $grand['grand_accidents'] = 0;
                    $grand['grand_complaints'] = 0;

                    $data['inbound_data'] = $inbound_data;
                    $data['outbound_data'] = $outbound_data;
                    $data['total'] = $total;
                    $data['grand'] = $grand;
                    $summary->add($data);
                }
            }
            //Summary all routes for specific company
            else{
                $grandRidership = 0;
                $grandPrevRidership = 0;
                $grandIncreaseRidership = 0;
                $grandFarebox = 0;
                $grandPrevFarebox = 0;
                $grandIncreaseFarebox = 0;
                $grandAverageFare = 0;
                $grandTripPlanned = 0;
                $grandTripMade = 0;
                $grandTripMissed = 0;
                $grandKMPlanned = 0;
                $grandKMServed = 0;
                $grandKMGPS = 0;
                $grandEarlyDeparture = 0;
                $grandLateDeparture = 0;
                $grandEarlyEnd = 0;
                $grandLateEnd = 0;
                $grandBreakdown = 0;
                $grandBusUsed = 0;
                $grandAccident = 0;
                $grandComplaint = 0;

                //Get all route for specific company
                $allRoutes = Route::where('company_id', $this->selectedCompany);

                foreach($allRoutes as $allRoute) {
                    //Inbound
                    $allTripInbounds = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->where('trip_code', 1)
                        ->get();

                    if ($allTripInbounds) {
                        $existInTrip = true;
                        $totalFareboxIn = 0;
                        $totalRidershipIn = 0;
                        $totalKMPlannedIn = 0;
                        $totalKMServedIn = 0;
                        $totalKMGPSIn = 0;
                        $earlyDepartureIn = 0;
                        $lateDepartureIn = 0;
                        $earlyEndIn = 0;
                        $lateEndIn = 0;
                        foreach ($allTripInbounds as $allTripInbound) {
                            //Ridership
                            $ridership = TicketSalesTransaction::where('trip_id', $allTripInbound->id)->count();
                            $totalRidershipIn += $ridership;

                            //Farebox Collection
                            $farebox = $allTripInbound->total_adult_amount + $allTripInbound->total_concession_amount;
                            $totalFareboxIn += $farebox;

                            //Total KM Service Planned
                            $kmPlanned = $allTripInbound->routeScheduleMSTR->inbound_distance * count($allTripInbounds);
                            $totalKMPlannedIn += $kmPlanned;

                            //Total KM Service Served
                            $kmServed = $allTripInbound->total_mileage;
                            $totalKMServedIn += $kmServed;

                            //Total KM Service Served by GPS
                            $kmGPS = $allTripInbound->total_mileage;
                            $totalKMGPSIn += $kmGPS;

                            //Total Early Departure
                            if ($allTripInbound->routeScheduleMSTR->schedule_start_time > $allTripInbound->start_trip) {
                                $earlyDepartureIn++;
                            } //Total Late Departure
                            else {
                                $lateDepartureIn++;
                            }

                            //Total Early End
                            if ($allTripInbound->routeScheduleMSTR->schedule_end_time > $allTripInbound->start_trip) {
                                $earlyEndIn++;
                            } //Total Late End
                            else {
                                $lateEndIn++;
                            }
                        }
                        //Previous Month Ridership collection
                        $prevRidershipIn = TicketSalesTransaction::whereBetween('sales_date', [$previousStartMonth, $previousEndMonth])->count();

                        //% Increase
                        $increaseRidershipIn = (($totalRidershipIn - $prevRidershipIn) / $prevRidershipIn) * 100;
                        $increaseRidershipFormatIn = number_format((float)$increaseRidershipIn, 2, '.', '');

                        //Previous month farebox collection
                        $adultPrevIn = TripDetail::where('route_id', $allRoute->id)
                            ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                            ->where('trip_code', 1)
                            ->sum('total_adult_amount');
                        $concessionPrevIn = TripDetail::where('route_id', $allRoute->id)
                            ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                            ->where('trip_code', 1)
                            ->sum('total_concession_amount');
                        $prevFareboxIn = $adultPrevIn + $concessionPrevIn;

                        //Incremeant farebox collection (%)
                        $increaseFareboxIn = (($totalFareboxIn - $prevFareboxIn) / $prevFareboxIn) * 100;
                        $increaseFareboxFormatIn = number_format((float)$increaseFareboxIn, 2, '.', '');

                        //Average Fare per pax (RM)
                        $averageIn = $totalFareboxIn / $totalRidershipIn;
                        $averageFormatIn = number_format((float)$averageIn, 2, '.', '');

                        //Number of trips planned
                        $tripPlannedIn = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->count();

                        //Number of trips made
                        $tripMadeIn = count($allTripInbounds);

                        /**Number of Trips missed*/
                        //$tripMissed = MissedTrip::whereBetween('service_date', [$dateFrom, $dateTo])->count();
                        $tripMissedIn = 0;

                        /**Total Breakdown During Operation*/
                        $breakdownIn = 0;

                        //Total Bus In Used
                        $busUsedIn = TripDetail::where('route_id', $allRoute->id)
                            ->whereBetween('start_trip', [$dateFrom, $dateTo])
                            ->where('trip_code', 1)
                            ->distinct('bus_id')
                            ->count();

                        /**Total Accidents caused by Operator*/
                        $accidentIn = 0;

                        /**Total Complaint*/
                        $complaintsIn = 0;

                        $inbound['ridership_in'] = $totalRidershipIn;
                        $inbound['prev_ridership_in'] = $prevRidershipIn;
                        $inbound['increase_ridership_in'] = $increaseRidershipFormatIn;
                        $inbound['farebox_in'] = $totalFareboxIn;
                        $inbound['prev_farebox_in'] = $prevFareboxIn;
                        $inbound['increase_farebox_in'] = $increaseFareboxFormatIn;
                        $inbound['average_fare_in'] = $averageFormatIn;
                        $inbound['trip_planned_in'] = $tripPlannedIn;
                        $inbound['trip_made_in'] = $tripMadeIn;
                        $inbound['trip_missed_in'] = $tripMissedIn;
                        $inbound['km_planned_in'] = $totalKMPlannedIn;
                        $inbound['km_served_in'] = $totalKMServedIn;
                        $inbound['km_served_gps_in'] = $totalKMGPSIn;
                        $inbound['early_departure_in'] = $earlyDepartureIn;
                        $inbound['late_departure_in'] = $lateDepartureIn;
                        $inbound['early_end_in'] = $earlyEndIn;
                        $inbound['late_end_in'] = $lateEndIn;
                        $inbound['breakdown_in'] = $breakdownIn;
                        $inbound['bus_used_in'] = $busUsedIn;
                        $inbound['accidents_in'] = $accidentIn;
                        $inbound['complaints_in'] = $complaintsIn;
                    }
                    //Outbound
                    $allTripOutbounds = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->where('trip_code', 0)
                        ->get();

                    if ($allTripOutbounds) {
                        $existOutTrip = true;
                        $totalFareboxOut = 0;
                        $totalRidershipOut = 0;
                        $earlyDepartureOut = 0;
                        $lateDepartureOut = 0;
                        $earlyEndOut = 0;
                        $lateEndOut = 0;
                        foreach ($allTripOutbounds as $allTripOutbound) {
                            //Ridership
                            $ridership = TicketSalesTransaction::where('trip_id', $allTripOutbound->id)->count();
                            $totalRidershipOut += $ridership;

                            //Farebox Collection
                            $farebox = $allTripOutbound->total_adult_amount + $allTripOutbound->total_concession_amount;
                            $totalFareboxOut += $farebox;

                            //Total KM Service Planned
                            $kmPlanned = $allTripOutbound->routeScheduleMSTR->inbound_distance * count($allTripInbounds);
                            $totalKMPlannedOut += $kmPlanned;

                            //Total KM Service Served
                            $kmServed = $allTripOutbound->total_mileage;
                            $totalKMServedOut += $kmServed;

                            //Total KM Service Served by GPS
                            $kmGPS = $allTripOutbound->total_mileage;
                            $totalKMGPSOut += $kmGPS;

                            //Total Early Departure
                            if ($allTripOutbound->routeScheduleMSTR->schedule_start_time > $allTripOutbound->start_trip) {
                                $earlyDepartureOut++;
                            } //Total Late Departure
                            else {
                                $lateDepartureOut++;
                            }

                            //Total Early End
                            if ($allTripOutbound->routeScheduleMSTR->schedule_end_time > $allTripOutbound->start_trip) {
                                $earlyEndOut++;
                            } //Total Late End
                            else {
                                $lateEndOut++;
                            }
                        }
                        //Previous Month Ridership collection
                        $prevRidershipOut = TicketSalesTransaction::whereBetween('sales_date', [$previousStartMonth, $previousEndMonth])->count();

                        //% Increase
                        $increaseRidershipOut = (($totalRidershipIn - $prevRidershipIn) / $prevRidershipIn) * 100;
                        $increaseRidershipFormatOut = number_format((float)$increaseRidershipOut, 2, '.', '');

                        //Previous month farebox collection
                        $adultPrevOut = TripDetail::where('route_id', $allRoute->id)
                            ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                            ->where('trip_code', 0)
                            ->sum('total_adult_amount');
                        $concessionPrevOut = TripDetail::where('route_id', $allRoute->id)
                            ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                            ->where('trip_code', 0)
                            ->sum('total_concession_amount');
                        $prevFareboxOut = $adultPrevOut + $concessionPrevOut;

                        //Incremeant farebox collection (%)
                        $increaseFareboxOut = (($totalFareboxOut - $prevFareboxOut) / $prevFareboxOut) * 100;
                        $increaseFareboxFormatOut = number_format((float)$increaseFareboxOut, 2, '.', '');

                        //Average Fare per pax (RM)
                        $averageOut = $totalFareboxOut / $totalRidershipOut;
                        $averageFormatOut = number_format((float)$averageOut, 2, '.', '');

                        //Number of trips planned
                        $tripPlannedOut = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->count();

                        //Number of trips made
                        $tripMadeOut = count($allTripOutbounds);

                        /**Number of Trips missed*/
                        //$tripMissed = MissedTrip::whereBetween('service_date', [$dateFrom, $dateTo])->count();
                        $tripMissedOut = 0;

                        /**Total Breakdown During Operation*/
                        $breakdownOut = 0;

                        //Total Bus In Used
                        $busUsedOut = TripDetail::where('id', $allRoute->id)
                            ->whereBetween('start_trip', [$dateFrom, $dateTo])
                            ->where('trip_code', 0)
                            ->distinct('bus_id')
                            ->count();

                        /**Total Accidents caused by Operator*/
                        $accidentOut = 0;

                        /**Total Complaint*/
                        $complaintsOut = 0;

                        $outbound['ridership_out'] = $totalRidershipOut;
                        $outbound['prev_ridership_out'] = $prevRidershipOut;
                        $outbound['increase_ridership_out'] = $increaseRidershipFormatOut;
                        $outbound['farebox_out'] = $totalFareboxOut;
                        $outbound['prev_farebox_out'] = $prevFareboxOut;
                        $outbound['increase_farebox_out'] = $increaseFareboxFormatOut;
                        $outbound['average_fare_out'] = $averageFormatOut;
                        $outbound['trip_planned_out'] = $tripPlannedOut;
                        $outbound['trip_made_out'] = $tripMadeOut;
                        $outbound['trip_missed_out'] = $tripMissedOut;
                        $outbound['km_planned_out'] = $totalKMPlannedOut;
                        $outbound['km_served_out'] = $totalKMServedOut;
                        $outbound['km_served_gps_out'] = $totalKMGPSOut;
                        $outbound['early_departure_out'] = $earlyDepartureOut;
                        $outbound['late_departure_out'] = $lateDepartureOut;
                        $outbound['early_end_out'] = $earlyEndOut;
                        $outbound['late_end_out'] = $lateEndOut;
                        $outbound['breakdown_out'] = $breakdownOut;
                        $outbound['bus_used_out'] = $busUsedOut;
                        $outbound['accidents_out'] = $accidentOut;
                        $outbound['complaints_out'] = $complaintsOut;
                    }

                    if ($existInTrip == true && $existOutTrip == true) {
                        $selectedRoute = Route::where('id', $allRoute->id)->first();
                        $firstStage = Stage::where('route_id', $allRoute->id)->first();
                        $lastStage = Stage::where('route_id', $allRoute->id)->orderby('DESC', 'stage_order')->first();
                        $route_name_in = $selectedRoute->route_number . ' ' . $firstStage->stage_name . ' ' . $lastStage->stage_name;
                        $route_name_out = $selectedRoute->route_number . ' ' . $lastStage->stage_name . ' ' . $firstStage->stage_name;

                        $inbound_data[$route_name_in] = $inbound;
                        $outbound_data[$route_name_out] = $outbound;

                        $total['total_ridership'] = $totalRidershipOut + $totalRidershipIn;
                        $total['total_prev_ridership'] = $prevRidershipOut + $prevRidershipIn;

                        $sumIncreaseRidership = $increaseRidershipIn + $increaseRidershipOut;
                        $sumIncreaseRidershipFormat = number_format((float)$sumIncreaseRidership, 2, '.', '');
                        $total['total_increase_ridership'] = $sumIncreaseRidershipFormat;

                        $total['total_farebox'] = $totalFareboxOut + $totalFareboxIn;
                        $total['total_prev_farebox'] = $prevFareboxOut + $prevFareboxIn;

                        $sumIncreaseFarebox = $increaseFareboxIn + $increaseFareboxOut;
                        $sumIncreaseFareboxFormat = number_format((float)$sumIncreaseFarebox, 2, '.', '');
                        $total['total_increase_farebox'] = $sumIncreaseFareboxFormat;

                        $sumAverage = $averageIn + $averageOut;
                        $sumAverageFormat = number_format((float)$sumAverage, 2, '.', '');
                        $total['total_average_fare'] = $sumAverageFormat;

                        $total['total_trip_planned'] = $tripPlannedOut + $tripPlannedIn;
                        $total['total_trip_made'] = $tripMadeOut + $tripMadeIn;
                        $total['total_trip_missed'] = $tripMissedOut + $tripMissedIn;
                        $total['total_km_planned'] = $totalKMPlannedOut + $totalKMPlannedIn;
                        $total['total_km_served'] = $totalKMServedOut + $totalKMServedIn;
                        $total['total_km_served_gps'] = $totalKMGPSOut + $totalKMGPSIn;
                        $total['total_early_departure'] = $earlyDepartureOut + $earlyDepartureIn;
                        $total['total_late_departure'] = $lateDepartureOut + $lateDepartureIn;
                        $total['total_early_end'] = $earlyEndOut + $earlyEndIn;
                        $total['total_late_end'] = $lateEndOut + $lateEndIn;
                        $total['total_breakdown'] = $breakdownOut + $breakdownIn;
                        $total['total_bus_used'] = $busUsedOut + $busUsedIn;
                        $total['total_accidents'] = $accidentOut + $accidentIn;
                        $total['total_complaints'] = $complaintsOut + $complaintsIn;

                        $content['inbound_data'] = $inbound_data;
                        $content['outbound_data'] = $outbound_data;
                        $content['total'] = $total;
                        $data[$selectedRoute->route_number] = $content;

                        $grandRidership += $total['total_ridership'];
                        $grandPrevRidership += $total['total_prev_ridership'];
                        $grandIncreaseRidership += $sumIncreaseRidership;
                        $grandFarebox += $total['total_farebox'];
                        $grandPrevFarebox += $total['total_prev_farebox'];
                        $grandIncreaseFarebox += $sumIncreaseFarebox;;
                        $grandAverageFare += $sumAverage;
                        $grandTripPlanned += $total['total_trip_planned'];
                        $grandTripMade += $total['total_trip_made'];
                        $grandTripMissed += $total['total_trip_missed'];
                        $grandKMPlanned += $total['total_km_planned'];
                        $grandKMServed += $total['total_km_served'];
                        $grandKMGPS += $total['total_km_served_gps'];
                        $grandEarlyDeparture += $total['total_early_departure'];
                        $grandLateDeparture += $total['total_late_departure'];
                        $grandEarlyEnd += $total['total_early_end'];
                        $grandLateEnd += $total['total_late_end'];
                        $grandBreakdown += $total['total_breakdown'];
                        $grandBusUsed += $total['total_bus_used'];
                        $grandAccident += $total['total_accidents'];
                        $grandComplaint += $total['total_complaints'];

                    } elseif ($existInTrip == false && $existOutTrip == true) {
                        $selectedRoute = Route::where('id', $allRoute->id)->first();
                        //$data['route_no'] = $selectedRoute->route_number;
                        $firstStage = Stage::where('route_id', $allRoute->id)->first();
                        $lastStage = Stage::where('route_id', $allRoute->id)->orderby('DESC', 'stage_order')->first();
                        $route_name_out = $selectedRoute->route_number . ' ' . $lastStage->stage_name . ' ' . $firstStage->stage_name;

                        $outbound_data[$route_name_out] = $outbound;

                        $total['total_ridership'] = $totalRidershipOut;
                        $total['total_prev_ridership'] = $prevRidershipOut;
                        $total['total_increase_ridership'] = $increaseRidershipFormatOut;
                        $total['total_farebox'] = $totalFareboxOut;
                        $total['total_prev_farebox'] = $prevFareboxOut;
                        $total['total_increase_farebox'] = $increaseFareboxFormatOut;
                        $total['total_average_fare'] = $averageFormatOut;
                        $total['total_trip_planned'] = $tripPlannedOut;
                        $total['total_trip_made'] = $tripMadeOut;
                        $total['total_trip_missed'] = $tripMissedOut;
                        $total['total_km_planned'] = $totalKMPlannedOut;
                        $total['total_km_served'] = $totalKMServedOut;
                        $total['total_km_served_gps'] = $totalKMGPSOut;
                        $total['total_early_departure'] = $earlyDepartureOut;
                        $total['total_late_departure'] = $lateDepartureOut;
                        $total['total_early_end'] = $earlyEndOut;
                        $total['total_late_end'] = $lateEndOut;
                        $total['total_breakdown'] = $breakdownOut;
                        $total['total_bus_used'] = $busUsedOut;
                        $total['total_accidents'] = $accidentOut;
                        $total['total_complaints'] = $complaintsOut;

                        $content['outbound_data'] = $outbound_data;
                        $content['total'] = $total;
                        $data[$selectedRoute->route_number] = $content;

                        $grandRidership += $total['total_ridership'];
                        $grandPrevRidership += $total['total_prev_ridership'];
                        $grandIncreaseRidership += $increaseRidershipOut;
                        $grandFarebox += $total['total_farebox'];
                        $grandPrevFarebox += $total['total_prev_farebox'];
                        $grandIncreaseFarebox += $increaseFareboxOut;;
                        $grandAverageFare += $averageOut;
                        $grandTripPlanned += $total['total_trip_planned'];
                        $grandTripMade += $total['total_trip_made'];
                        $grandTripMissed += $total['total_trip_missed'];
                        $grandKMPlanned += $total['total_km_planned'];
                        $grandKMServed += $total['total_km_served'];
                        $grandKMGPS += $total['total_km_served_gps'];
                        $grandEarlyDeparture += $total['total_early_departure'];
                        $grandLateDeparture += $total['total_late_departure'];
                        $grandEarlyEnd += $total['total_early_end'];
                        $grandLateEnd += $total['total_late_end'];
                        $grandBreakdown += $total['total_breakdown'];
                        $grandBusUsed += $total['total_bus_used'];
                        $grandAccident += $total['total_accidents'];
                        $grandComplaint += $total['total_complaints'];

                    } elseif ($existInTrip == true && $existOutTrip == false) {
                        $selectedRoute = Route::where('id', $allRoute->id)->first();
                        //$data['route_no'] = $selectedRoute->route_number;
                        $firstStage = Stage::where('route_id', $allRoute->id)->first();
                        $lastStage = Stage::where('route_id', $allRoute->id)->orderby('DESC', 'stage_order')->first();
                        $route_name_in = $selectedRoute->route_number . ' ' . $firstStage->stage_name . ' ' . $lastStage->stage_name;

                        $inbound_data[$route_name_in] = $inbound;

                        $total['total_ridership'] = $totalRidershipIn;
                        $total['total_prev_ridership'] = $prevRidershipIn;
                        $total['total_increase_ridership'] = $increaseRidershipFormatIn;
                        $total['total_farebox'] = $totalFareboxIn;
                        $total['total_prev_farebox'] = $prevFareboxIn;
                        $total['total_increase_farebox'] = $increaseFareboxFormatIn;
                        $total['total_average_fare'] = $averageFormatIn;
                        $total['total_trip_planned'] = $tripPlannedIn;
                        $total['total_trip_made'] = $tripMadeIn;
                        $total['total_trip_missed'] = $tripMissedIn;
                        $total['total_km_planned'] = $totalKMPlannedIn;
                        $total['total_km_served'] = $totalKMServedIn;
                        $total['total_km_served_gps'] = $totalKMGPSIn;
                        $total['total_early_departure'] = $earlyDepartureIn;
                        $total['total_late_departure'] = $lateDepartureIn;
                        $total['total_early_end'] = $earlyEndIn;
                        $total['total_late_end'] = $lateEndIn;
                        $total['total_breakdown'] = $breakdownIn;
                        $total['total_bus_used'] = $busUsedIn;
                        $total['total_accidents'] = $accidentIn;
                        $total['total_complaints'] = $complaintsIn;

                        $content['inbound_data'] = $inbound_data;
                        $content['total'] = $total;
                        $data[$selectedRoute->route_number] = $content;

                        $grandRidership += $total['total_ridership'];
                        $grandPrevRidership += $total['total_prev_ridership'];
                        $grandIncreaseRidership += $increaseRidershipIn;
                        $grandFarebox += $total['total_farebox'];
                        $grandPrevFarebox += $total['total_prev_farebox'];
                        $grandIncreaseFarebox += $increaseFareboxIn;;
                        $grandAverageFare += $averageIn;
                        $grandTripPlanned += $total['total_trip_planned'];
                        $grandTripMade += $total['total_trip_made'];
                        $grandTripMissed += $total['total_trip_missed'];
                        $grandKMPlanned += $total['total_km_planned'];
                        $grandKMServed += $total['total_km_served'];
                        $grandKMGPS += $total['total_km_served_gps'];
                        $grandEarlyDeparture += $total['total_early_departure'];
                        $grandLateDeparture += $total['total_late_departure'];
                        $grandEarlyEnd += $total['total_early_end'];
                        $grandLateEnd += $total['total_late_end'];
                        $grandBreakdown += $total['total_breakdown'];
                        $grandBusUsed += $total['total_bus_used'];
                        $grandAccident += $total['total_accidents'];
                        $grandComplaint += $total['total_complaints'];
                    } else {
                        $inbound['ridership_in'] = 0;
                        $inbound['prev_ridership_in'] = 0;
                        $inbound['increase_ridership_in'] = 0;
                        $inbound['farebox_in'] = 0;
                        $inbound['prev_farebox_in'] = 0;
                        $inbound['increase_farebox_in'] = 0;
                        $inbound['average_fare_in'] = 0;
                        $inbound['trip_planned_in'] = 0;
                        $inbound['trip_made_in'] = 0;
                        $inbound['trip_missed_in'] = 0;
                        $inbound['km_planned_in'] = 0;
                        $inbound['km_served_in'] = 0;
                        $inbound['km_served_gps_in'] = 0;
                        $inbound['early_departure_in'] = 0;
                        $inbound['late_departure_in'] = 0;
                        $inbound['early_end_in'] = 0;
                        $inbound['late_end_in'] = 0;
                        $inbound['breakdown_in'] = 0;
                        $inbound['bus_used_in'] = 0;
                        $inbound['accidents_in'] = 0;
                        $inbound['complaints_in'] = 0;

                        $outbound['ridership_out'] = 0;
                        $outbound['prev_ridership_out'] = 0;
                        $outbound['increase_ridership_out'] = 0;
                        $outbound['farebox_out'] = 0;
                        $outbound['prev_farebox_out'] = 0;
                        $outbound['increase_farebox_out'] = 0;
                        $outbound['average_fare_out'] = 0;
                        $outbound['trip_planned_out'] = 0;
                        $outbound['trip_made_out'] = 0;
                        $outbound['trip_missed_out'] = 0;
                        $outbound['km_planned_out'] = 0;
                        $outbound['km_served_out'] = 0;
                        $outbound['km_served_gps_out'] = 0;
                        $outbound['early_departure_out'] = 0;
                        $outbound['late_departure_out'] = 0;
                        $outbound['early_end_out'] = 0;
                        $outbound['late_end_out'] = 0;
                        $outbound['breakdown_out'] = 0;
                        $outbound['bus_used_out'] = 0;
                        $outbound['accidents_out'] = 0;
                        $outbound['complaints_out'] = 0;

                        $total['total_ridership'] = 0;
                        $total['total_prev_ridership'] = 0;
                        $total['total_increase_ridership'] = 0;
                        $total['total_farebox'] = 0;
                        $total['total_prev_farebox'] = 0;
                        $total['total_increase_farebox'] = 0;
                        $total['total_average_fare'] = 0;
                        $total['total_trip_planned'] = 0;
                        $total['total_trip_made'] = 0;
                        $total['total_trip_missed'] = 0;
                        $total['total_km_planned'] = 0;;
                        $total['total_km_served'] = 0;
                        $total['total_km_served_gps'] = 0;
                        $total['total_early_departure'] = 0;
                        $total['total_late_departure'] = 0;
                        $total['total_early_end'] = 0;
                        $total['total_late_end'] = 0;
                        $total['total_breakdown'] = 0;
                        $total['total_bus_used'] = 0;
                        $total['total_accidents'] = 0;
                        $total['total_complaints'] = 0;

                        $content['outbound_data'] = $outbound_data;
                        $content['inbound_data'] = $inbound_data;
                        $content['total'] = $total;
                        $data[$selectedRoute->route_number] = $content;

                        $grandRidership += 0;
                        $grandPrevRidership += 0;
                        $grandIncreaseRidership += 0;
                        $grandFarebox += 0;
                        $grandPrevFarebox += 0;
                        $grandIncreaseFarebox += 0;
                        $grandAverageFare += 0;
                        $grandTripPlanned += 0;
                        $grandTripMade += 0;
                        $grandTripMissed += 0;
                        $grandKMPlanned += 0;
                        $grandKMServed += 0;
                        $grandKMGPS += 0;
                        $grandEarlyDeparture += 0;
                        $grandLateDeparture += 0;
                        $grandEarlyEnd += 0;
                        $grandLateEnd += 0;
                        $grandBreakdown += 0;
                        $grandBusUsed += 0;
                        $grandAccident += 0;
                        $grandComplaint += 0;
                    }
                }
                $grand['grand_ridership'] = $grandRidership;
                $grand['grand_prev_ridership'] =  $grandPrevRidership;
                $grand['grand_increase_ridership'] = $grandIncreaseRidership;
                $grand['grand_farebox'] = $grandFarebox;
                $grand['grand_prev_farebox'] = $grandPrevFarebox;
                $grand['grand_increase_farebox'] =  $grandIncreaseFarebox;
                $grand['grand_average_fare'] = $grandAverageFare;
                $grand['grand_trip_planned'] = $grandTripPlanned ;
                $grand['grand_trip_made'] = $grandTripMade;
                $grand['grand_trip_missed'] = $grandTripMissed;
                $grand['grand_km_planned'] = $grandKMPlanned;;
                $grand['grand_km_served'] = $grandKMServed;
                $grand['grand_km_served_gps'] = $grandKMGPS;
                $grand['grand_early_departure'] = $grandEarlyDeparture;
                $grand['grand_late_departure'] = $grandLateDeparture;
                $grand['grand_early_end'] = $grandEarlyEnd;
                $grand['grand_late_end'] = $grandLateEnd;
                $grand['grand_breakdown'] = $grandBreakdown;
                $grand['grand_bus_used'] = $grandBusUsed;
                $grand['grand_accidents'] = $grandAccident;
                $grand['grand_complaints'] = $grandComplaint;

                $data['grand'] = $grand;
                $summary->add($data);
            }
        }
        //Summary all routes for all company
        else{
            $grandRidership = 0;
            $grandPrevRidership = 0;
            $grandIncreaseRidership = 0;
            $grandFarebox = 0;
            $grandPrevFarebox = 0;
            $grandIncreaseFarebox = 0;
            $grandAverageFare = 0;
            $grandTripPlanned = 0;
            $grandTripMade = 0;
            $grandTripMissed = 0;
            $grandKMPlanned = 0;
            $grandKMServed = 0;
            $grandKMGPS = 0;
            $grandEarlyDeparture = 0;
            $grandLateDeparture = 0;
            $grandEarlyEnd = 0;
            $grandLateEnd = 0;
            $grandBreakdown = 0;
            $grandBusUsed = 0;
            $grandAccident = 0;
            $grandComplaint = 0;

            //Get all route
            $allRoutes = Route::all();

            foreach($allRoutes as $allRoute) {
                //Inbound
                $allTripInbounds = TripDetail::where('route_id', $allRoute->id)
                    ->whereBetween('start_trip', [$dateFrom, $dateTo])
                    ->where('trip_code', 1)
                    ->get();

                if (count($allTripInbounds)>0) {
                    $out = new ConsoleOutput();
                    $out->writeln("YOU ARE IN HERE all route allTripInbounds()");
                    $existInTrip = true;
                    $totalFareboxIn = 0;
                    $totalRidershipIn = 0;
                    $totalKMPlannedIn = 0;
                    $totalKMServedIn = 0;
                    $totalKMGPSIn = 0;
                    $earlyDepartureIn = 0;
                    $lateDepartureIn = 0;
                    $earlyEndIn = 0;
                    $lateEndIn = 0;
                    $inbound = [];
                    foreach ($allTripInbounds as $allTripInbound) {
                        $out->writeln("existInTrip:" . $existInTrip );
                        //Ridership
                        $ridership = TicketSalesTransaction::where('trip_id', $allTripInbound->id)->count();
                        $totalRidershipIn += $ridership;

                        //Farebox Collection
                        $farebox = $allTripInbound->total_adult_amount + $allTripInbound->total_concession_amount;
                        $totalFareboxIn += $farebox;

                        //Total KM Service Planned
                        $kmPlanned = $allTripInbound->routeScheduleMSTR->inbound_distance * count($allTripInbounds);
                        $totalKMPlannedIn += $kmPlanned;

                        //Total KM Service Served
                        $kmServed = $allTripInbound->total_mileage;
                        $totalKMServedIn += $kmServed;

                        //Total KM Service Served by GPS
                        $kmGPS = $allTripInbound->total_mileage;
                        $totalKMGPSIn += $kmGPS;

                        //Total Early Departure
                        if ($allTripInbound->routeScheduleMSTR->schedule_start_time > $allTripInbound->start_trip) {
                            $earlyDepartureIn++;
                        } //Total Late Departure
                        else {
                            $lateDepartureIn++;
                        }

                        //Total Early End
                        if ($allTripInbound->routeScheduleMSTR->schedule_end_time > $allTripInbound->start_trip) {
                            $earlyEndIn++;
                        } //Total Late End
                        else {
                            $lateEndIn++;
                        }
                    }
                    //Previous Month Ridership collection
                    $prevRidershipIn = TicketSalesTransaction::whereBetween('sales_date', [$previousStartMonth, $previousEndMonth])->count();

                    //Increment ridership collection (%)
                    if($prevRidershipIn==0){
                        $increaseRidershipIn = 100;
                        $increaseRidershipFormatIn = 100;
                    }else{
                        $increaseRidershipIn = (($totalRidershipIn - $prevRidershipIn) / $prevRidershipIn) * 100;
                        $increaseRidershipFormatIn = number_format((float)$increaseRidershipIn, 2, '.', '');
                    }

                    //Previous month farebox collection
                    $adultPrevIn = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                        ->where('trip_code', 1)
                        ->sum('total_adult_amount');
                    $concessionPrevIn = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                        ->where('trip_code', 1)
                        ->sum('total_concession_amount');
                    $prevFareboxIn = $adultPrevIn + $concessionPrevIn;

                    //Incremeant farebox collection (%)
                    if($prevFareboxIn==0){
                        $increaseFareboxIn = 100;
                        $increaseFareboxFormatIn = 100;
                    }else{
                        $increaseFareboxIn = (($totalFareboxIn - $prevFareboxIn) / $prevFareboxIn) * 100;
                        $increaseFareboxFormatIn = number_format((float)$increaseFareboxIn, 2, '.', '');
                    }

                    //Average Fare per pax (RM)
                    $averageIn = $totalFareboxIn / $totalRidershipIn;
                    $averageFormatIn = number_format((float)$averageIn, 2, '.', '');

                    //Number of trips planned
                    $tripPlannedIn = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->count();

                    //Number of trips made
                    $tripMadeIn = count($allTripInbounds);

                    /**Number of Trips missed*/
                    //$tripMissed = MissedTrip::whereBetween('service_date', [$dateFrom, $dateTo])->count();
                    $tripMissedIn = 0;

                    /**Total Breakdown During Operation*/
                    $breakdownIn = 0;

                    //Total Bus In Used
                    $busUsedIn = TripDetail::where('id', $allRoute->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->where('trip_code', 1)
                        ->distinct('bus_id')
                        ->count();

                    /**Total Accidents caused by Operator*/
                    $accidentIn = 0;

                    /**Total Complaint*/
                    $complaintsIn = 0;

                    $inbound['ridership_in'] = $totalRidershipIn;
                    $inbound['prev_ridership_in'] = $prevRidershipIn;
                    $inbound['increase_ridership_in'] = $increaseRidershipFormatIn;
                    $inbound['farebox_in'] = $totalFareboxIn;
                    $inbound['prev_farebox_in'] = $prevFareboxIn;
                    $inbound['increase_farebox_in'] = $increaseFareboxFormatIn;
                    $inbound['average_fare_in'] = $averageFormatIn;
                    $inbound['trip_planned_in'] = $tripPlannedIn;
                    $inbound['trip_made_in'] = $tripMadeIn;
                    $inbound['trip_missed_in'] = $tripMissedIn;
                    $inbound['km_planned_in'] = $totalKMPlannedIn;
                    $inbound['km_served_in'] = $totalKMServedIn;
                    $inbound['km_served_gps_in'] = $totalKMGPSIn;
                    $inbound['early_departure_in'] = $earlyDepartureIn;
                    $inbound['late_departure_in'] = $lateDepartureIn;
                    $inbound['early_end_in'] = $earlyEndIn;
                    $inbound['late_end_in'] = $lateEndIn;
                    $inbound['breakdown_in'] = $breakdownIn;
                    $inbound['bus_used_in'] = $busUsedIn;
                    $inbound['accidents_in'] = $accidentIn;
                    $inbound['complaints_in'] = $complaintsIn;
                }
                //Outbound
                $allTripOutbounds = TripDetail::where('route_id',$allRoute->id)
                    ->whereBetween('start_trip', [$dateFrom, $dateTo])
                    ->where('trip_code', 0)
                    ->get();

                if (count($allTripOutbounds) > 0) {
                    $out = new ConsoleOutput();
                    $out->writeln("YOU ARE IN HERE all route allTripOutbounds()");
                    $existOutTrip = true;
                    $totalFareboxOut = 0;
                    $totalRidershipOut = 0;
                    $totalKMPlannedOut = 0;
                    $totalKMServedOut = 0;
                    $totalKMGPSOut = 0;
                    $earlyDepartureOut = 0;
                    $lateDepartureOut = 0;
                    $earlyEndOut = 0;
                    $lateEndOut = 0;
                    $outbound = [];
                    foreach ($allTripOutbounds as $allTripOutbound) {
                        $out->writeln("existOutTrip:" . $existOutTrip );
                        //Ridership
                        $ridership = TicketSalesTransaction::where('trip_id', $allTripOutbound->id)->count();
                        $totalRidershipOut += $ridership;

                        //Farebox Collection
                        $farebox = $allTripOutbound->total_adult_amount + $allTripOutbound->total_concession_amount;
                        $totalFareboxOut += $farebox;

                        //Total KM Service Planned
                        $kmPlanned = $allTripOutbound->routeScheduleMSTR->inbound_distance * count($allTripInbounds);
                        $totalKMPlannedOut += $kmPlanned;

                        //Total KM Service Served
                        $kmServed = $allTripOutbound->total_mileage;
                        $totalKMServedOut += $kmServed;

                        //Total KM Service Served by GPS
                        $kmGPS = $allTripOutbound->total_mileage;
                        $totalKMGPSOut += $kmGPS;

                        //Total Early Departure
                        if ($allTripOutbound->routeScheduleMSTR->schedule_start_time > $allTripOutbound->start_trip) {
                            $earlyDepartureOut++;
                        } //Total Late Departure
                        else {
                            $lateDepartureOut++;
                        }

                        //Total Early End
                        if ($allTripOutbound->routeScheduleMSTR->schedule_end_time > $allTripOutbound->start_trip) {
                            $earlyEndOut++;
                        } //Total Late End
                        else {
                            $lateEndOut++;
                        }
                    }
                    //Previous Month Ridership collection
                    $prevRidershipOut = TicketSalesTransaction::whereBetween('sales_date', [$previousStartMonth, $previousEndMonth])->count();

                    //Increment ridership collection (%)
                    if($prevRidershipOut==0){
                        $increaseRidershipOut = 100;
                        $increaseRidershipFormatOut = 100;
                    }else{
                        $increaseRidershipOut = (($totalRidershipOut - $prevRidershipOut) / $prevRidershipOut) * 100;
                        $increaseRidershipFormatOut = number_format((float)$increaseRidershipOut, 2, '.', '');
                    }

                    //Previous month farebox collection
                    $adultPrevOut = TripDetail::where('route_id',$allRoute->id)
                        ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                        ->where('trip_code', 0)
                        ->sum('total_adult_amount');
                    $concessionPrevOut = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                        ->where('trip_code', 0)
                        ->sum('total_concession_amount');
                    $prevFareboxOut = $adultPrevOut + $concessionPrevOut;

                    //Increment farebox collection (%)
                    if($prevFareboxOut==0){
                        $increaseFareboxOut = 100;
                        $increaseFareboxFormatOut = 100;
                    }else{
                        $increaseFareboxOut = (($totalFareboxOut - $prevFareboxOut) / $prevFareboxOut) * 100;
                        $increaseFareboxFormatOut = number_format((float)$increaseFareboxOut, 2, '.', '');
                    }

                    //Average Fare per pax (RM)
                    $averageOut = $totalFareboxOut / $totalRidershipOut;
                    $averageFormatOut = number_format((float)$averageOut, 2, '.', '');

                    //Number of trips planned
                    $tripPlannedOut = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->count();

                    //Number of trips made
                    $tripMadeOut = count($allTripOutbounds);

                    /**Number of Trips missed*/
                    //$tripMissed = MissedTrip::whereBetween('service_date', [$dateFrom, $dateTo])->count();
                    $tripMissedOut = 0;

                    /**Total Breakdown During Operation*/
                    $breakdownOut = 0;

                    //Total Bus In Used
                    $busUsedOut = TripDetail::where('id', $allRoute->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->where('trip_code', 0)
                        ->distinct('bus_id')
                        ->count();

                    /**Total Accidents caused by Operator*/
                    $accidentOut = 0;

                    /**Total Complaint*/
                    $complaintsOut = 0;

                    $outbound['ridership_out'] = $totalRidershipOut;
                    $outbound['prev_ridership_out'] = $prevRidershipOut;
                    $outbound['increase_ridership_out'] = $increaseRidershipFormatOut;
                    $outbound['farebox_out'] = $totalFareboxOut;
                    $outbound['prev_farebox_out'] = $prevFareboxOut;
                    $outbound['increase_farebox_out'] = $increaseFareboxFormatOut;
                    $outbound['average_fare_out'] = $averageFormatOut;
                    $outbound['trip_planned_out'] = $tripPlannedOut;
                    $outbound['trip_made_out'] = $tripMadeOut;
                    $outbound['trip_missed_out'] = $tripMissedOut;
                    $outbound['km_planned_out'] = $totalKMPlannedOut;
                    $outbound['km_served_out'] = $totalKMServedOut;
                    $outbound['km_served_gps_out'] = $totalKMGPSOut;
                    $outbound['early_departure_out'] = $earlyDepartureOut;
                    $outbound['late_departure_out'] = $lateDepartureOut;
                    $outbound['early_end_out'] = $earlyEndOut;
                    $outbound['late_end_out'] = $lateEndOut;
                    $outbound['breakdown_out'] = $breakdownOut;
                    $outbound['bus_used_out'] = $busUsedOut;
                    $outbound['accidents_out'] = $accidentOut;
                    $outbound['complaints_out'] = $complaintsOut;
                }

                $inbound_data=[];
                $outbound_data=[];
                if ($existInTrip == 1 && $existOutTrip == 1) {
                    $out = new ConsoleOutput();
                    $out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == true");
                    $selectedRoute = Route::where('id', $allRoute->id)->first();
                    $firstStage = Stage::where('route_id', $allRoute->id)->first();
                    $lastStage = Stage::where('route_id', $allRoute->id)->orderby('stage_order','DESC')->first();
                    $route_name_in = $selectedRoute->route_number . ' ' . $firstStage->stage_name . ' ' . $lastStage->stage_name;
                    $route_name_out = $selectedRoute->route_number . ' ' . $lastStage->stage_name . ' ' . $firstStage->stage_name;

                    $inbound_data[$route_name_in] = $inbound;
                    $outbound_data[$route_name_out] = $outbound;

                    $total['total_ridership'] = $totalRidershipOut + $totalRidershipIn;
                    $total['total_prev_ridership'] = $prevRidershipOut + $prevRidershipIn;

                    $sumIncreaseRidership = $increaseRidershipIn + $increaseRidershipOut;
                    $sumIncreaseRidershipFormat = number_format((float)$sumIncreaseRidership, 2, '.', '');
                    $total['total_increase_ridership'] = $sumIncreaseRidershipFormat;

                    $total['total_farebox'] = $totalFareboxOut + $totalFareboxIn;
                    $total['total_prev_farebox'] = $prevFareboxOut + $prevFareboxIn;

                    $sumIncreaseFarebox = $increaseFareboxIn + $increaseFareboxOut;
                    $sumIncreaseFareboxFormat = number_format((float)$sumIncreaseFarebox, 2, '.', '');
                    $total['total_increase_farebox'] = $sumIncreaseFareboxFormat;

                    $sumAverage = $averageIn + $averageOut;
                    $sumAverageFormat = number_format((float)$sumAverage, 2, '.', '');
                    $total['total_average_fare'] = $sumAverageFormat;

                    $total['total_trip_planned'] = $tripPlannedOut + $tripPlannedIn;
                    $total['total_trip_made'] = $tripMadeOut + $tripMadeIn;
                    $total['total_trip_missed'] = $tripMissedOut + $tripMissedIn;
                    $total['total_km_planned'] = $totalKMPlannedOut + $totalKMPlannedIn;
                    $total['total_km_served'] = $totalKMServedOut + $totalKMServedIn;
                    $total['total_km_served_gps'] = $totalKMGPSOut + $totalKMGPSIn;
                    $total['total_early_departure'] = $earlyDepartureOut + $earlyDepartureIn;
                    $total['total_late_departure'] = $lateDepartureOut + $lateDepartureIn;
                    $total['total_early_end'] = $earlyEndOut + $earlyEndIn;
                    $total['total_late_end'] = $lateEndOut + $lateEndIn;
                    $total['total_breakdown'] = $breakdownOut + $breakdownIn;
                    $total['total_bus_used'] = $busUsedOut + $busUsedIn;
                    $total['total_accidents'] = $accidentOut + $accidentIn;
                    $total['total_complaints'] = $complaintsOut + $complaintsIn;

                    $content['inbound_data'] = $inbound_data;
                    $content['outbound_data'] = $outbound_data;
                    $content['total'] = $total;
                    $data[$selectedRoute->route_number] = $content;

                    $grandRidership += $total['total_ridership'];
                    $grandPrevRidership += $total['total_prev_ridership'];
                    $grandIncreaseRidership += $sumIncreaseRidership;
                    $grandFarebox += $total['total_farebox'];
                    $grandPrevFarebox += $total['total_prev_farebox'];
                    $grandIncreaseFarebox += $sumIncreaseFarebox;;
                    $grandAverageFare += $sumAverage;
                    $grandTripPlanned += $total['total_trip_planned'];
                    $grandTripMade += $total['total_trip_made'];
                    $grandTripMissed += $total['total_trip_missed'];
                    $grandKMPlanned += $total['total_km_planned'];
                    $grandKMServed += $total['total_km_served'];
                    $grandKMGPS += $total['total_km_served_gps'];
                    $grandEarlyDeparture += $total['total_early_departure'];
                    $grandLateDeparture += $total['total_late_departure'];
                    $grandEarlyEnd += $total['total_early_end'];
                    $grandLateEnd += $total['total_late_end'];
                    $grandBreakdown += $total['total_breakdown'];
                    $grandBusUsed += $total['total_bus_used'];
                    $grandAccident += $total['total_accidents'];
                    $grandComplaint += $total['total_complaints'];

                } elseif ($existInTrip == 0 && $existOutTrip == 1) {
                    $out = new ConsoleOutput();
                    $out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == true");
                    $selectedRoute = Route::where('id', $allRoute->id)->first();
                    //$data['route_no'] = $selectedRoute->route_number;
                    $firstStage = Stage::where('route_id', $allRoute->id)->first();
                    $lastStage = Stage::where('route_id', $allRoute->id)->orderby('stage_order','DESC')->first();
                    $route_name_out = $selectedRoute->route_number . ' ' . $lastStage->stage_name . ' ' . $firstStage->stage_name;

                    $outbound_data[$route_name_out] = $outbound;

                    $total['total_ridership'] = $totalRidershipOut;
                    $total['total_prev_ridership'] = $prevRidershipOut;
                    $total['total_increase_ridership'] = $increaseRidershipFormatOut;
                    $total['total_farebox'] = $totalFareboxOut;
                    $total['total_prev_farebox'] = $prevFareboxOut;
                    $total['total_increase_farebox'] = $increaseFareboxFormatOut;
                    $total['total_average_fare'] = $averageFormatOut;
                    $total['total_trip_planned'] = $tripPlannedOut;
                    $total['total_trip_made'] = $tripMadeOut;
                    $total['total_trip_missed'] = $tripMissedOut;
                    $total['total_km_planned'] = $totalKMPlannedOut;
                    $total['total_km_served'] = $totalKMServedOut;
                    $total['total_km_served_gps'] = $totalKMGPSOut;
                    $total['total_early_departure'] = $earlyDepartureOut;
                    $total['total_late_departure'] = $lateDepartureOut;
                    $total['total_early_end'] = $earlyEndOut;
                    $total['total_late_end'] = $lateEndOut;
                    $total['total_breakdown'] = $breakdownOut;
                    $total['total_bus_used'] = $busUsedOut;
                    $total['total_accidents'] = $accidentOut;
                    $total['total_complaints'] = $complaintsOut;

                    $content['outbound_data'] = $outbound_data;
                    $content['total'] = $total;
                    $data[$selectedRoute->route_number] = $content;

                    $grandRidership += $total['total_ridership'];
                    $grandPrevRidership += $total['total_prev_ridership'];
                    $grandIncreaseRidership += $increaseRidershipOut;
                    $grandFarebox += $total['total_farebox'];
                    $grandPrevFarebox += $total['total_prev_farebox'];
                    $grandIncreaseFarebox += $increaseFareboxOut;;
                    $grandAverageFare += $averageOut;
                    $grandTripPlanned += $total['total_trip_planned'];
                    $grandTripMade += $total['total_trip_made'];
                    $grandTripMissed += $total['total_trip_missed'];
                    $grandKMPlanned += $total['total_km_planned'];
                    $grandKMServed += $total['total_km_served'];
                    $grandKMGPS += $total['total_km_served_gps'];
                    $grandEarlyDeparture += $total['total_early_departure'];
                    $grandLateDeparture += $total['total_late_departure'];
                    $grandEarlyEnd += $total['total_early_end'];
                    $grandLateEnd += $total['total_late_end'];
                    $grandBreakdown += $total['total_breakdown'];
                    $grandBusUsed += $total['total_bus_used'];
                    $grandAccident += $total['total_accidents'];
                    $grandComplaint += $total['total_complaints'];

                } elseif ($existInTrip == 1 && $existOutTrip == 0) {
                    $out = new ConsoleOutput();
                    $out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == false");
                    $selectedRoute = Route::where('id', $allRoute->id)->first();
                    //$data['route_no'] = $selectedRoute->route_number;
                    $firstStage = Stage::where('route_id', $allRoute->id)->first();
                    $lastStage = Stage::where('route_id', $allRoute->id)->orderby('stage_order','DESC')->first();
                    $route_name_in = $selectedRoute->route_number . ' ' . $firstStage->stage_name . ' ' . $lastStage->stage_name;

                    $inbound_data[$route_name_in] = $inbound;

                    $total['total_ridership'] = $totalRidershipIn;
                    $total['total_prev_ridership'] = $prevRidershipIn;
                    $total['total_increase_ridership'] = $increaseRidershipFormatIn;
                    $total['total_farebox'] = $totalFareboxIn;
                    $total['total_prev_farebox'] = $prevFareboxIn;
                    $total['total_increase_farebox'] = $increaseFareboxFormatIn;
                    $total['total_average_fare'] = $averageFormatIn;
                    $total['total_trip_planned'] = $tripPlannedIn;
                    $total['total_trip_made'] = $tripMadeIn;
                    $total['total_trip_missed'] = $tripMissedIn;
                    $total['total_km_planned'] = $totalKMPlannedIn;
                    $total['total_km_served'] = $totalKMServedIn;
                    $total['total_km_served_gps'] = $totalKMGPSIn;
                    $total['total_early_departure'] = $earlyDepartureIn;
                    $total['total_late_departure'] = $lateDepartureIn;
                    $total['total_early_end'] = $earlyEndIn;
                    $total['total_late_end'] = $lateEndIn;
                    $total['total_breakdown'] = $breakdownIn;
                    $total['total_bus_used'] = $busUsedIn;
                    $total['total_accidents'] = $accidentIn;
                    $total['total_complaints'] = $complaintsIn;

                    $content['inbound_data'] = $inbound_data;
                    $content['total'] = $total;
                    $data[$selectedRoute->route_number] = $content;

                    $grandRidership += $total['total_ridership'];
                    $grandPrevRidership += $total['total_prev_ridership'];
                    $grandIncreaseRidership += $increaseRidershipIn;
                    $grandFarebox += $total['total_farebox'];
                    $grandPrevFarebox += $total['total_prev_farebox'];
                    $grandIncreaseFarebox += $increaseFareboxIn;;
                    $grandAverageFare += $averageIn;
                    $grandTripPlanned += $total['total_trip_planned'];
                    $grandTripMade += $total['total_trip_made'];
                    $grandTripMissed += $total['total_trip_missed'];
                    $grandKMPlanned += $total['total_km_planned'];
                    $grandKMServed += $total['total_km_served'];
                    $grandKMGPS += $total['total_km_served_gps'];
                    $grandEarlyDeparture += $total['total_early_departure'];
                    $grandLateDeparture += $total['total_late_departure'];
                    $grandEarlyEnd += $total['total_early_end'];
                    $grandLateEnd += $total['total_late_end'];
                    $grandBreakdown += $total['total_breakdown'];
                    $grandBusUsed += $total['total_bus_used'];
                    $grandAccident += $total['total_accidents'];
                    $grandComplaint += $total['total_complaints'];
                } else {
                    $out = new ConsoleOutput();
                    $out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == false");
                    $selectedRoute = Route::where('id', $allRoute->id)->first();
                    //$data['route_no'] = $selectedRoute->route_number;
                    $firstStage = Stage::where('route_id', $allRoute->id)->first();
                    $lastStage = Stage::where('route_id', $allRoute->id)->orderby('stage_order','DESC')->first();
                    $route_name_in = $selectedRoute->route_number . ' ' . $firstStage->stage_name . ' ' . $lastStage->stage_name;
                    $route_name_out = $selectedRoute->route_number . ' ' . $lastStage->stage_name . ' ' . $firstStage->stage_name;

                    $inbound['ridership_in'] = 0;
                    $inbound['prev_ridership_in'] = 0;
                    $inbound['increase_ridership_in'] = 0;
                    $inbound['farebox_in'] = 0;
                    $inbound['prev_farebox_in'] = 0;
                    $inbound['increase_farebox_in'] = 0;
                    $inbound['average_fare_in'] = 0;
                    $inbound['trip_planned_in'] = 0;
                    $inbound['trip_made_in'] = 0;
                    $inbound['trip_missed_in'] = 0;
                    $inbound['km_planned_in'] = 0;
                    $inbound['km_served_in'] = 0;
                    $inbound['km_served_gps_in'] = 0;
                    $inbound['early_departure_in'] = 0;
                    $inbound['late_departure_in'] = 0;
                    $inbound['early_end_in'] = 0;
                    $inbound['late_end_in'] = 0;
                    $inbound['breakdown_in'] = 0;
                    $inbound['bus_used_in'] = 0;
                    $inbound['accidents_in'] = 0;
                    $inbound['complaints_in'] = 0;

                    $outbound['ridership_out'] = 0;
                    $outbound['prev_ridership_out'] = 0;
                    $outbound['increase_ridership_out'] = 0;
                    $outbound['farebox_out'] = 0;
                    $outbound['prev_farebox_out'] = 0;
                    $outbound['increase_farebox_out'] = 0;
                    $outbound['average_fare_out'] = 0;
                    $outbound['trip_planned_out'] = 0;
                    $outbound['trip_made_out'] = 0;
                    $outbound['trip_missed_out'] = 0;
                    $outbound['km_planned_out'] = 0;
                    $outbound['km_served_out'] = 0;
                    $outbound['km_served_gps_out'] = 0;
                    $outbound['early_departure_out'] = 0;
                    $outbound['late_departure_out'] = 0;
                    $outbound['early_end_out'] = 0;
                    $outbound['late_end_out'] = 0;
                    $outbound['breakdown_out'] = 0;
                    $outbound['bus_used_out'] = 0;
                    $outbound['accidents_out'] = 0;
                    $outbound['complaints_out'] = 0;

                    $inbound_data[$route_name_in] = $inbound;
                    $outbound_data[$route_name_out] = $outbound;

                    $total['total_ridership'] = 0;
                    $total['total_prev_ridership'] = 0;
                    $total['total_increase_ridership'] = 0;
                    $total['total_farebox'] = 0;
                    $total['total_prev_farebox'] = 0;
                    $total['total_increase_farebox'] = 0;
                    $total['total_average_fare'] = 0;
                    $total['total_trip_planned'] = 0;
                    $total['total_trip_made'] = 0;
                    $total['total_trip_missed'] = 0;
                    $total['total_km_planned'] = 0;;
                    $total['total_km_served'] = 0;
                    $total['total_km_served_gps'] = 0;
                    $total['total_early_departure'] = 0;
                    $total['total_late_departure'] = 0;
                    $total['total_early_end'] = 0;
                    $total['total_late_end'] = 0;
                    $total['total_breakdown'] = 0;
                    $total['total_bus_used'] = 0;
                    $total['total_accidents'] = 0;
                    $total['total_complaints'] = 0;

                    $content['outbound_data'] = $outbound_data;
                    $content['inbound_data'] = $inbound_data;
                    $content['total'] = $total;
                    $data[$selectedRoute->route_number] = $content;

                    $grandRidership += 0;
                    $grandPrevRidership += 0;
                    $grandIncreaseRidership += 0;
                    $grandFarebox += 0;
                    $grandPrevFarebox += 0;
                    $grandIncreaseFarebox += 0;
                    $grandAverageFare += 0;
                    $grandTripPlanned += 0;
                    $grandTripMade += 0;
                    $grandTripMissed += 0;
                    $grandKMPlanned += 0;
                    $grandKMServed += 0;
                    $grandKMGPS += 0;
                    $grandEarlyDeparture += 0;
                    $grandLateDeparture += 0;
                    $grandEarlyEnd += 0;
                    $grandLateEnd += 0;
                    $grandBreakdown += 0;
                    $grandBusUsed += 0;
                    $grandAccident += 0;
                    $grandComplaint += 0;
                }
            }
            $grand['grand_ridership'] = $grandRidership;
            $grand['grand_prev_ridership'] =  $grandPrevRidership;
            $grand['grand_increase_ridership'] = $grandIncreaseRidership;
            $grand['grand_farebox'] = $grandFarebox;
            $grand['grand_prev_farebox'] = $grandPrevFarebox;
            $grand['grand_increase_farebox'] =  $grandIncreaseFarebox;
            $grand['grand_average_fare'] = $grandAverageFare;
            $grand['grand_trip_planned'] = $grandTripPlanned ;
            $grand['grand_trip_made'] = $grandTripMade;
            $grand['grand_trip_missed'] = $grandTripMissed;
            $grand['grand_km_planned'] = $grandKMPlanned;;
            $grand['grand_km_served'] = $grandKMServed;
            $grand['grand_km_served_gps'] = $grandKMGPS;
            $grand['grand_early_departure'] = $grandEarlyDeparture;
            $grand['grand_late_departure'] = $grandLateDeparture;
            $grand['grand_early_end'] = $grandEarlyEnd;
            $grand['grand_late_end'] = $grandLateEnd;
            $grand['grand_breakdown'] = $grandBreakdown;
            $grand['grand_bus_used'] = $grandBusUsed;
            $grand['grand_accidents'] = $grandAccident;
            $grand['grand_complaints'] = $grandComplaint;

            $data['grand'] = $grand;
            $summary->add($data);
        }

        return Excel::download(new SPADSummary($summary, $all_dates,  $dateFrom, $dateTo), 'Summary_Report_SPAD.xlsx');
    }

    public function printServiceGroup()
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

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo']);

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        $serviceGroup = collect();

        if($this->selectedCompany){
            //Number of Scheduled Trips
            $numTripPerTime = RouteSchedulerMSTR::where('route_id', $validatedData['route_id'])->count();
            $numDay = count($all_dates);
            $numScheduleTrip = $numTripPerTime * $numDay;

            //Number of Trips Made
            $numTripMade = TripDetail::where('route_id', $validatedData['route_id'])->whereBetween('start_trip', [$dateFrom, $dateTo])->count();

            //Passengers Boarding Count
            $ticketSales = TicketSalesTransaction::where('route_id', $validatedData['route_id'])->whereBetween('sales_date', [$dateFrom, $dateTo])->get();
            $numPassenger = count($ticketSales);

            $calcAdult = 0;
            $calcConcession = 0;
            foreach ($ticketSales as $ticketSale){
                if($ticketSale->fare_type == 1){
                    $calcAdult++;
                }
                else {
                    $calcConcession++;
                }
            }
            $numAdult = $calcAdult;
            $numConcession = $calcConcession;

            $data['num_scheduled_trip'] = $numScheduleTrip;
            $data['num_trip_made'] = $numTripMade;
            $data['count_passenger_board'] = $numPassenger;
            $data['num_adult'] = $numAdult;
            $data['num_concession'] = $numConcession;

            $serviceGroup->add($data);
        }

        return Excel::download(new SPADServiceGroup($serviceGroup, $validatedData['dateFrom'], $validatedData['dateTo']), 'Service_Group_Report_SPAD.xlsx');
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

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo']);
        $routeSPAD = collect();

        if($this->selectedCompany){

            $totNumInDistance = 0;
            $totNumInDistanceServe = 0;
            $totNumInDistanceServeGPS = 0;
            $totNumScheduleTrip = 0;
            $totNumTripMade = 0;
            $totNumPassenger = 0;
            $totNumAdult = 0;
            $totNumConcession = 0;

            // Inbound[1] And Outbound[2]
            for($i=0; $i<2; $i++){

                $numInDistance = 0;
                $numInDistanceServe = 0;
                $numInDistanceServeGPS = 0;
                $numScheduleTrip = 0;
                $numTripMade = 0;
                $numPassenger = 0;
                $numAdult = 0;
                $numConcession = 0;

                $routeSQL = Route::where('id', $validatedData['route_id'])->first();
                $data[$i]['route_no'] = $routeSQL->route_number;

                if($i==0){
                    $firstStage = Stage::where('route_id', $validatedData['route_id'])->orderby('stage_order')->first();
                    $lastStage = Stage::where('route_id', $validatedData['route_id'])->orderby('stage_order','DESC')->first();
                    $data[$i]['route_name'] = $firstStage->stage_name . " - " . $lastStage->stage_name;
                    $out->writeln("1-first:" . $firstStage->stage_name);
                    $out->writeln("1-last:" . $lastStage->stage_name);
                }
                else{
                    $firstStage = Stage::where('route_id', $validatedData['route_id'])->orderby('stage_order','DESC')->first();
                    $lastStage = Stage::where('route_id', $validatedData['route_id'])->orderby('stage_order')->first();
                    $data[$i]['route_name'] = $firstStage->stage_name . " - " . $lastStage->stage_name;
                    $out->writeln("2-first:" . $firstStage->stage_name);
                    $out->writeln("2-last:" . $lastStage->stage_name);
                }

                //Total KM Planned
                $inDistancePerDay = RouteSchedulerMSTR::select('inbound_distance')
                    ->where('route_id', $validatedData['route_id'])
                    ->count();
                $numDay = count($all_dates);
                $numInDistance = $inDistancePerDay * $numDay;
                $totNumInDistance += $numInDistance;

                //Total KM Served
                $numInDistanceServe = $inDistancePerDay * $numDay;
                $totNumInDistanceServe += $numInDistanceServe;

                //Total KM Served GPS
                $numInDistanceServeGPS = $inDistancePerDay * $numDay;
                $totNumInDistanceServeGPS += $numInDistanceServeGPS;

                //Number of Scheduled Trips
                $numTripPerTime = RouteSchedulerMSTR::where('route_id', $validatedData['route_id'])->count();
                $numDay = count($all_dates);
                $numScheduleTrip = $numTripPerTime * $numDay;
                $totNumScheduleTrip += $numScheduleTrip;

                //Number of Trips Made
                $numTripMade = TripDetail::where('route_id', $validatedData['route_id'])->whereBetween('start_trip', [$dateFrom, $dateTo])->count();
                $totNumTripMade += $numTripMade;

                //Passengers Boarding Count
                $ticketSales = TicketSalesTransaction::where('route_id', $validatedData['route_id'])->whereBetween('sales_date', [$dateFrom, $dateTo])->get();
                $numPassenger = count($ticketSales);
                $totNumPassenger += $numPassenger;

                $calcAdult = 0;
                $calcConcession = 0;
                foreach ($ticketSales as $ticketSale){
                    if($ticketSale->fare_type == 1){
                        $calcAdult++;
                    }
                    else {
                        $calcConcession++;
                    }
                }
                $numAdult = $calcAdult;
                $numConcession = $calcConcession;
                $totNumAdult += $numAdult;
                $totNumConcession += $numConcession;

                //Transfer Count
                /**
                 * Need to do transfer count formulation here
                 */

                //Previous Highest Patronage - Total Pax
                /**
                 * Need to do Previous Highest Patronage - Total Pax formulation here
                 */

                //Previous Highest Patronage - % increase
                /**
                 * Need to do Previous Highest Patronage - % increase formulation here
                 */

                //Previous Highest Sales - Total Sales Amount
                /**
                 * Need to do Previous Highest Sales - Total Sales Amount formulation here
                 */

                //Previous Highest Sales  - % increase
                /**
                 * Need to do Previous Highest Sales  - % increase formulation here
                 */

                $data[$i]['num_km_planned'] = $numInDistance;
                $data[$i]['num_km_served'] = $numInDistanceServe;
                $data[$i]['num_km_served_gps'] = $numInDistanceServeGPS;
                $data[$i]['num_scheduled_trip'] = $numScheduleTrip;
                $data[$i]['num_trip_made'] = $numTripMade;
                $data[$i]['count_passenger_board'] = $numPassenger;
                $data[$i]['transfer_count'] = 0;
                $data[$i]['num_adult'] = $numAdult;
                $data[$i]['num_concession'] = $numConcession;
                $data[$i]['total_on'] = $numPassenger;
                $data[$i]['total_pax'] = 0;
                $data[$i]['total_pax_increase'] = 0;
                $data[$i]['total_sales'] = 0;
                $data[$i]['total_sales_increase'] = 0;
            }
            $total['tot_num_km_planned'] = $totNumInDistance;
            $total['tot_num_km_served'] = $totNumInDistanceServe;
            $total['tot_num_km_served_gps'] = $totNumInDistanceServeGPS;
            $total['tot_num_scheduled_trip'] = $totNumScheduleTrip;
            $total['tot_num_trip_made'] = $totNumTripMade;
            $total['tot_count_passenger_board'] = $totNumPassenger;
            $total['tot_transfer_count'] = 0;
            $total['tot_num_adult'] = $totNumAdult;
            $total['tot_num_concession'] = $totNumConcession;
            $total['tot_total_on'] = $numPassenger;
            $total['tot_total_pax'] = 0;
            $total['tot_total_pax_increase'] = 0;
            $total['tot_total_sales'] = 0;
            $total['tot_total_sales_increase'] = 0;

            $data['total'] = $total;

            $routeSPAD->add($data);
        }

        return Excel::download(new SPADRoute($routeSPAD, $validatedData['dateFrom'], $validatedData['dateTo']), 'Route_Report_SPAD.xlsx');
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

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo']);

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        if($this->selectedCompany){
            foreach ($all_dates as $all_date) {
                $data['date'] =  $all_date;
                $routeSchedules = RouteSchedulerMSTR::where('route_id', $validatedData['route_id'])->get();

                $trip = 0;
                //Inbound
                foreach($routeSchedules as $routeSchedule){
                    //Route_no
                    $routeNo = Route::where('id', $validatedData['route_id'])->first();
                    $data['route_no'] = $routeNo->route_number;

                    //OD
                    $firstStage = Stage::where('route_id', $validatedData['route_id'])->orderby('stage_order')->first();
                    $lastStage = Stage::where('route_id', $validatedData['route_id'])->orderby('stage_order','DESC')->first();
                    $data['OD'] = $firstStage->stage_name . " - " . $lastStage->stage_name;

                    //No. of Trips
                    $data['num_of_trip'] = $trip;

                    //Trip No
                    $data['trip_no'] = 'T' . $trip;

                    //Bus Number Plate
                    $busPlate = $routeSchedule->inbus()->bus_registration_number;
                    $data['bus_plate_number'] = $busPlate;

                    //Bus Driver ID
                    $busDriver = BusDriver::select('id')->where('bus_id', $routeSchedule->inbound_bus_id)->first();
                    $data['bus_driver_id'] = $busDriver;

                    //Service Date
                    $data['service_date'] = $all_date;

                    //Start Point
                    $data['start_point'] = $firstStage->stage_name;

                    //Service Start Time
                    $serviceStart = TicketSalesTransaction::where('route_id', $validatedData['route_id'])
                        ->where('bus_id', $routeSchedule->inbound_bus_id)
                        ->where('sales_date', $all_date)
                        ->get();
                    $data['service_start'] = $firstStage->stage_name;

                    //Actual Start Time
                    $data['actual_start'] = $firstStage->stage_name;

                    //Sales Start Time
                    $data['sales_start'] = $firstStage->stage_name;

                    //Service End Time
                    $data['service_end'] = $firstStage->stage_name;

                    //Actual End Time
                    $data['actual_end'] = $firstStage->stage_name;

                    //Sales End Time
                    $data['sales_end'] = $firstStage->stage_name;

                    //Passengers Boarding Count
                    $data['count_passenger_boarding'] = $firstStage->stage_name;

                    //Total Sales Amount (RM)
                    $data['total_sales_amount'] = $firstStage->stage_name;

                    //Total On
                    $data['total_on'] = $firstStage->stage_name;

                    //Transfer Count
                    $data['transfer_point'] = $firstStage->stage_name;

                    //Adult
                    $data['num_adult'] = $firstStage->stage_name;

                    //Concession
                    $data['num_concession'] = $firstStage->stage_name;
                }

                //Tot Inbound Passengers Boarding Count
                //Tot Inbound Total Sales Amount (RM)
                //Tot Inbound Total On
                //Tot Inbound Transfer Count
                //Tot Inbound Adult
                //Tot Inbound Concession

                //Outbound
                foreach($routeSchedules as $routeSchedule){
                    $busPlate = $routeSchedule->outbus()->bus_registration_number;
                    $busDriver = BusDriver::select('id')->where('bus_id', $routeSchedule->inbound_bus_id)->first();
                }

                //Tot Outbound Passsengers Boarding Count
                //Tot Outbound Total Sales Amount (RM)
                //Tot Outbound Total On
                //Tot Outbound Transfer Count
                //Tot Outbound Adult
                //Tot Outbound Concession

                $allRoute = Route::where('company_id', $this->selectedCompany)
                    ->where('id', $validatedData['route_id'])
                    ->get();
            }

        }

        foreach ($allRoute as $allRoutes){
            $routeNo = $allRoutes->route_number;
        }

        return Excel::download(new SPADTrip($all_dates, $allRoute, $validatedData['dateFrom'], $validatedData['dateTo'], $routeNo), 'Trip_Report_SPAD.xlsx');
    }

    public function printTopBoardings()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printTopBoarding()");
        $data = [];
        $finalTotalInbound = 0;
        $finalAdultInbound = 0;
        $finalConcessionInbound = 0;
        $finalTotalOutbound = 0;
        $finalAdultOutbound = 0;
        $finalConcessionOutbound = 0;

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required', 'int'],
        ])->validate();

        $out->writeln("datefrom:" . $validatedData['dateFrom']);
        $out->writeln("dateto:" . $validatedData['dateTo']);
        $out->writeln("route:" . $validatedData['route_id']);

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo']);

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        $topBoardingSPAD = collect();

        if($this->selectedCompany) {
            $validatedRoute = Route::where('id', $validatedData['route_id'])->first();
            $routeNo =  $validatedRoute->route_number;
            //$data['route_no'] =  $validatedRoute->route_number;
            $allBusStands = BusStand::where('route_id', $validatedData['route_id'])->get();

            foreach ($allBusStands as $allBusStand) {
                $content['bus_stop_id'] = $allBusStand->id;
                $content['bus_stop_id_public'] = $allBusStand->description;
                $content['bus_stop_desc'] = $allBusStand->description;

                /** Collection of ticket sales/passenger per bus stand between date
                 * Inbound
                 */
                //Total On
                $totalOnInbound = TicketSalesTransaction::where('bus_stand_in_id', $allBusStand->id)
                    ->whereBetween('sales_date', [$dateFrom, $dateTo])->count();

                //Adult
                $adultInbound = TicketSalesTransaction::where('bus_stand_in_id', $allBusStand->id)
                    ->whereBetween('sales_date', [$dateFrom, $dateTo])
                    ->where('passenger_type', 1)->count();

                //Concession
                $concessionInbound = TicketSalesTransaction::where('bus_stand_out_id', $allBusStand->id)
                    ->whereBetween('sales_date', [$dateFrom, $dateTo])
                    ->where('passenger_type', 2)->count();

                $content['inbound_total_on'] = $totalOnInbound ;
                $content['inbound_monthly_pass'] = 0;
                $content['inbound_adult'] = $adultInbound;
                $content['inbound_child'] = $concessionInbound;
                $content['inbound_senior'] = 0;
                $content['inbound_student'] = 0;
                $content['inbound_oku'] = 0;
                $content['inbound_jkm'] = 0;
                $content['inbound_main'] = 0;

                /** Collection of ticket sales/passenger per bus stand between date
                 * Outbound
                 */
                //Total On
                $totalOnOutbound = TicketSalesTransaction::where('bus_stand_out_id', $allBusStand->id)
                    ->whereBetween('sales_date', [$dateFrom, $dateTo])->count();

                //Adult
                $adultOutbound = TicketSalesTransaction::where('bus_stand_out_id', $allBusStand->id)
                    ->whereBetween('sales_date', [$dateFrom, $dateTo])
                    ->where('passenger_type', 1)->count();

                //Concession
                $concessionOutbound = TicketSalesTransaction::where('bus_stand_out_id', $allBusStand->id)
                    ->whereBetween('sales_date', [$dateFrom, $dateTo])
                    ->where('passenger_type', 2)->count();

                $content['outbound_total_on'] = $totalOnOutbound;
                $content['outbound_monthly_pass'] = 0;
                $content['outbound_adult'] = $adultOutbound;
                $content['outbound_child'] = $concessionOutbound;
                $content['outbound_senior'] = 0;
                $content['outbound_student'] = 0;
                $content['outbound_oku'] = 0;
                $content['outbound_jkm'] = 0;
                $content['outbound_main'] = 0;

                $data[$validatedRoute->route_number][$allBusStand->id] = $content;

                $finalTotalInbound += $totalOnInbound;
                $finalAdultInbound += $adultInbound;
                $finalConcessionInbound += $concessionInbound;

                $finalTotalOutbound += $totalOnOutbound;
                $finalAdultOutbound += $adultOutbound;
                $finalConcessionOutbound += $concessionOutbound;
            }
            $final['final_inbound_total_on'] = $finalTotalInbound ;
            $final['final_inbound_monthly_pass'] = 0;
            $final['final_inbound_adult'] = $finalAdultInbound;
            $final['final_inbound_child'] = $finalConcessionInbound;
            $final['final_inbound_senior'] = 0;
            $final['final_inbound_student'] = 0;
            $final['final_inbound_oku'] = 0;
            $final['final_inbound_jkm'] = 0;
            $final['final_inbound_main'] = 0;
            $final['final_final_outbound_total_on'] = $finalTotalOutbound;
            $final['final_outbound_monthly_pass'] = 0;
            $final['final_outbound_adult'] = $finalAdultOutbound ;
            $final['final_outbound_child'] = $finalConcessionOutbound;
            $final['final_outbound_senior'] = 0;
            $final['final_outbound_student'] = 0;
            $final['final_outbound_oku'] = 0;
            $final['final_outbound_jkm'] = 0;
            $final['final_outbound_main'] = 0;

            $data[$validatedRoute->route_number]['final'] = $final;

            $topBoardingSPAD->add($data);
            return Excel::download(new SPADTopBoarding($topBoardingSPAD, $validatedData['dateFrom'], $validatedData['dateTo'], $routeNo), 'Top_Boarding_Report_SPAD.xlsx');
        }
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

    public function printISBSF()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printISBSF()");
        $data = [];
        $finalPlannedTrip = 0;
        $finalCompletedIn = 0;
        $finalCompletedOut = 0;
        $finalTotalCompletedTrip = 0;
        $finalTripCompliance = 0;
        $finalTotalOffRoute = 0;
        $finalRouteCompliance = 0;
        $finalInboundDistance = 0;
        $finalOutboundDistance = 0;
        $finalTotalDistanceIn = 0;
        $finalTotalDistanceOut = 0;
        $finalTotalDistance = 0;
        $finalTripOnTime = 0;
        $finalPunctuality = 0;
        $finalTotalTripBreakdown = 0;
        $finalRealibility = 0;
        $finalNumBus = 0;
        $finalFarebox = 0;
        $finalRidership = 0;

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['int'],
        ])->validate();

        $out->writeln("datefrom:" . $validatedData['dateFrom']);
        $out->writeln("dateto:" . $validatedData['dateTo']);

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo']);

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        $isbsfSPAD = collect();
        $colspan = count($all_dates) + 3;

        if($this->selectedCompany) {
            if(!empty($this->state['route_id'])){
            //if ($validatedData['route_id']) {
                $out->writeln("route:" . $validatedData['route_id']);

                //Data of selected route based on selectedCompany

                $i = 0;
                $validatedRoute = Route::where('id', $validatedData['route_id'])->first();
                $route = $validatedRoute->route_name;
                $firstStage = Stage::where('route_id', $validatedData['route_id'])->first();
                $lastStage = Stage::where('route_id', $validatedData['route_id'])->orderby('stage_order','DESC')->first();
                $routeStage = $firstStage . '-' . $lastStage;
                $routeName = $route . $routeStage;

                foreach ($all_dates as $all_date) {
                    //Planned Trip
                    $plannedTrip = 0;
                    $schedules = RouteSchedulerDetail::where('schedule_date', $all_date)->get();

                    foreach ($schedules as $schedule) {
                        if ($validatedData['route_id'] == $schedule->RouteScheduleMSTR->route_id) {
                            $plannedTrip++;
                        }
                    }
                    $plannedTripArr[$all_date] = $plannedTrip;
                    $finalPlannedTrip += $plannedTrip;

                    //Completed Trip Out
                    $completedOut = 2;
                    //$completedOut = TripDetail::where('start_trip', $all_date)->where('route_id', $validatedData['route_id'])->count();
                    $completedOutArr[$all_date] = $completedOut;
                    $finalCompletedOut += $completedOut;

                    //Completed Trip In
                    $completedIn = 2;
                    //$completedIn = TripDetail::where('start_trip', $all_date)->where('route_id', $validatedData['route_id'])->count();
                    $completedInArr[$all_date] = $completedIn;
                    $finalCompletedIn += $completedIn;

                    //Total Completed Trip
                    $totalCompletedTrip = $completedOut + $completedIn;
                    $totalCompletedTripArr[$all_date] = $totalCompletedTrip;
                    $finalTotalCompletedTrip += $totalCompletedTrip;

                    //Trip Compliance
                    $tripCompliance = $plannedTrip / $totalCompletedTrip * 100;
                    $tripComplianceArr[$all_date] = $tripCompliance;
                    $finalTripCompliance += $tripCompliance;

                    //Off Route
                    $totalTripBreakdown = 0;
                    $totalTripBreakdownArr[$all_date] = $totalTripBreakdown;
                    $finalTotalOffRoute += $totalTripBreakdown;

                    //Route Compliance
                    $routeCompliance = 0;
                    $routeComplianceArr[$all_date] = $routeCompliance;
                    $finalRouteCompliance += $routeCompliance;

                    //KM 1 Way Outbound
                    $getScheduleID = TripDetail::where('start_trip', $all_date)->where('route_id', $validatedData['route_id'])->first();
                    $getDistance = RouteSchedulerMSTR::where('id', $getScheduleID->id)->get();
                    $outboundDistance = $getDistance->outbound_distance;
                    $outboundDistanceArr[$all_date] = $outboundDistance;
                    $finalOutboundDistance += $outboundDistance;

                    //KM 1 Way Inbound
                    $inboundDistance = $getDistance->inbound_distance;
                    $inboundDistanceArr[$all_date] = $inboundDistance;
                    $finalInboundDistance += $inboundDistance;

                    //Total KM Outbound
                    $totalDistanceOut = $outboundDistance * $totalCompletedTrip;
                    $totalDistanceOutArr[$all_date] = $totalDistanceOut;
                    $finalTotalDistanceOut += $totalDistanceOut;

                    //Total KM Inbound
                    $totalDistanceIn = $inboundDistance * $totalCompletedTrip;
                    $totalDistanceInArr[$all_date] = $totalDistanceIn;
                    $finalTotalDistanceIn += $totalDistanceIn;

                    //Total KM
                    $totalDistance = $totalDistanceIn + $totalDistanceOut;
                    $totalDistanceArr[$all_date] = $totalDistance;
                    $finalTotalDistance += $totalDistance;

                    //Total Trip On Time
                    $ontimeCount = 0;
                    foreach ($schedules as $schedule) {
                        if ($schedule->RouteScheduleMSTR->route_id == $validatedData['route_id']) {
                            $tripPerTime = TripDetail::where('route_schedule_mstr_id', $schedule->RouteScheduleMSTR->id)->first();
                            if ($schedule->schedule_start_time > $tripPerTime->start_trip) {
                                $ontimeCount++;
                            }
                        }
                    }
                    $totalTripOnTimeArr[$all_date] = $ontimeCount;
                    $finalTripOnTime += $ontimeCount;

                    //Punctuality Adherence
                    $punctuality = $ontimeCount / $totalCompletedTrip * 100;
                    $punctualityArr[$all_date] = $punctuality;
                    $finalPunctuality += $punctuality;

                    //Total Trip Breakdown
                    $totalTripBreakdown = 0;
                    $totalTripBreakdownArr[$all_date] = $totalTripBreakdown;
                    $finalTotalTripBreakdown += $totalTripBreakdown;

                    //Realibility Breakdown
                    $realibility = (($totalCompletedTrip - $totalTripBreakdown) / $totalCompletedTrip) * 100;
                    $realibilityArr[$all_date] = $realibility;
                    $finalRealibility += $realibility;

                    //Number of Bus
                    $numBus = TripDetail::distinct()
                        ->get(['bus_id'])
                        ->where('start_trip', $all_date)
                        ->where('route_id', $validatedData['route_id'])
                        ->count();
                    $numBusArr[$all_date] = $numBus;
                    $finalNumBus += $numBus;

                    //Farebox
                    $farebox = TicketSalesTransaction::where('sales_date', $all_date)
                        ->where('route_id', $validatedData['route_id'])
                        ->sum('actual_amount');
                    $fareboxArr[$all_date] = $farebox;
                    $finalFarebox += $farebox;

                    //Ridership
                    $ridership = TicketSalesTransaction::where('sales_date', $all_date)
                        ->where('route_id', $validatedData['route_id'])
                        ->count();
                    $ridershipArr[$all_date] = $ridership;
                    $finalRidership += $ridership;
                }

                $route['route_name'] = $routeName;

                $plannedTripArr['final_total'] = $finalPlannedTrip;
                $completedInArr['final_total'] = $finalCompletedIn;
                $completedOutArr['final_total'] = $finalCompletedOut;
                $totalCompletedTripArr['final_total'] = $finalTotalCompletedTrip;
                $tripComplianceArr['final_total'] = $finalTripCompliance;
                $totalOffRouteArr['final_total'] = $finalTotalOffRoute;
                $routeComplianceArr['final_total'] = $finalRouteCompliance;
                $inboundDistanceArr['final_total'] = $finalInboundDistance;
                $outboundDistanceArr['final_total'] = $finalOutboundDistance;
                $totalDistanceInArr['final_total'] = $finalTotalDistanceIn;
                $totalDistanceOutArr['final_total'] = $finalTotalDistanceOut;
                $totalDistanceArr['final_total'] = $finalTotalDistance;
                $totalTripOnTimeArr['final_total'] = $finalTripOnTime;
                $punctualityArr['final_total'] = $finalPunctuality;
                $totalTripBreakdownArr['final_total'] = $finalTotalTripBreakdown;
                $realibilityArr['final_total'] = $finalRealibility;
                $numBusArr['final_total'] = $finalNumBus;
                $fareboxArr['final_total'] = $finalFarebox;
                $ridershipArr['final_total'] = $finalRidership;

                $content['planned_trip'] = $plannedTripArr;
                $content['completed_trip_in'] = $completedInArr;
                $content['completed_trip_out'] = $completedOutArr;
                $content['total_completed_trip'] = $totalCompletedTripArr;
                $content['trip_compliance'] = $tripComplianceArr;
                $content['total_off_route'] = 0;
                $content['route_compliance'] = 0;
                $content['distance_in'] = $inboundDistanceArr;
                $content['distance_out'] = $outboundDistanceArr;
                $content['total_distance_in'] = $totalDistanceInArr;
                $content['total_distance_out'] = $totalDistanceOutArr;
                $content['total_distance'] = $totalDistanceArr;
                $content['total_trip_on_time'] = $totalTripOnTimeArr;
                $content['punctuality'] = $punctualityArr;
                $content['total_trip_breakdown'] = $totalTripBreakdownArr;
                $content['realibility'] = $realibilityArr;
                $content['num_of_bus'] = $numBusArr;
                $content['farebox'] = $fareboxArr;
                $content['ridership'] = $ridershipArr;

                $route['content'] = $content;
                //$data['route'] = $route;
                $data[$i++] = $route;

                $isbsfSPAD->add($data);
                return Excel::download(new SPADTopBoarding($isbsfSPAD, $validatedData['dateFrom'], $validatedData['dateTo'],$colspan, $all_dates), 'Top_Boarding_Report_SPAD.xlsx');
            } else {
                //Data of all route based on selectedCompany
                $routeByCompanies = Route::where('company_id', $this->selectedCompany)->get();
                $i = 0;

                foreach ($routeByCompanies as $routeByCompany) {

                    $route = $routeByCompany->route_name;
                    $firstStage = Stage::where('route_id', $routeByCompany->id)->first();
                    $lastStage = Stage::where('route_id', $routeByCompany->id)->orderby('stage_order','DESC')->first();
                    $routeStage = $firstStage . '-' . $lastStage;
                    $routeName = $route . $routeStage;

                    foreach ($all_dates as $all_date) {

                        //Planned Trip
                        $plannedTrip = 0;
                        $schedules = RouteSchedulerDetail::where('schedule_date', $all_date)->get();
                        foreach ($schedules as $schedule) {
                            if ($routeByCompany->id == $schedule->RouteScheduleMSTR->route_id) {
                                $plannedTrip++;
                            }
                        }
                        $plannedTripArr[$all_date] = $plannedTrip;
                        $finalPlannedTrip += $plannedTrip;

                        //Completed Trip Out
                        $completedOut = 2;
                        //$completedOut = TripDetail::where('start_trip', $all_date)->where('route_id', $routeByCompany->id)->count();
                        $completedOutArr[$all_date] = $completedOut;
                        $finalCompletedOut += $completedOut;

                        //Completed Trip In
                        $completedIn = 2;
                        //$completedIn = TripDetail::where('start_trip', $all_date)->where('route_id', $routeByCompany->id)->count();
                        $completedInArr[$all_date] = $completedIn;
                        $finalCompletedIn += $completedIn;

                        //Total Completed Trip
                        $totalCompletedTrip = $completedOut + $completedIn;
                        $totalCompletedTripArr[$all_date] = $totalCompletedTrip;
                        $finalTotalCompletedTrip += $totalCompletedTrip;

                        //Trip Compliance
                        $tripCompliance = $plannedTrip / $totalCompletedTrip * 100;
                        $tripComplianceArr[$all_date] = $tripCompliance;
                        $finalTripCompliance += $tripCompliance;

                        //Off Route
                        $totalTripBreakdown = 0;
                        $totalTripBreakdownArr[$all_date] = $totalTripBreakdown;
                        $finalTotalOffRoute += $totalTripBreakdown;

                        //Route Compliance
                        $routeCompliance = 0;
                        $routeComplianceArr[$all_date] = $routeCompliance;
                        $finalRouteCompliance += $routeCompliance;

                        //KM 1 Way Outbound
                        $getScheduleID = TripDetail::where('start_trip', $all_date)->where('route_id', $routeByCompany->id)->first();
                        $getDistance = RouteSchedulerMSTR::where('id', $getScheduleID->id)->get();
                        $outboundDistance = $getDistance->outbound_distance;
                        $outboundDistanceArr[$all_date] = $outboundDistance;
                        $finalOutboundDistance += $outboundDistance;

                        //KM 1 Way Inbound
                        $inboundDistance = $getDistance->inbound_distance;
                        $inboundDistanceArr[$all_date] = $inboundDistance;
                        $finalInboundDistance += $inboundDistance;

                        //Total KM Outbound
                        $totalDistanceOut = $outboundDistance * $totalCompletedTrip;
                        $totalDistanceOutArr[$all_date] = $totalDistanceOut;
                        $finalTotalDistanceOut += $totalDistanceOut;

                        //Total KM Inbound
                        $totalDistanceIn = $inboundDistance * $totalCompletedTrip;
                        $totalDistanceInArr[$all_date] = $totalDistanceIn;
                        $finalTotalDistanceIn += $totalDistanceIn;

                        //Total KM
                        $totalDistance = $totalDistanceIn + $totalDistanceOut;
                        $totalDistanceArr[$all_date] = $totalDistance;
                        $finalTotalDistance += $totalDistance;

                        //Total Trip On Time
                        $ontimeCount = 0;
                        foreach ($schedules as $schedule) {
                            if ($schedule->RouteScheduleMSTR->route_id == $routeByCompany->id) {
                                $tripPerTime = TripDetail::where('route_schedule_mstr_id', $schedule->RouteScheduleMSTR->id)->first();
                                if ($schedule->schedule_start_time > $tripPerTime->start_trip) {
                                    $ontimeCount++;
                                }
                            }
                        }
                        $totalTripOnTimeArr[$all_date] = $ontimeCount;
                        $finalTripOnTime += $ontimeCount;

                        //Punctuality Adherence
                        $punctuality = $ontimeCount / $totalCompletedTrip * 100;
                        $punctualityArr[$all_date] = $punctuality;
                        $finalPunctuality += $punctuality;

                        //Total Trip Breakdown
                        $totalTripBreakdown = 0;
                        $totalTripBreakdownArr[$all_date] = $totalTripBreakdown;
                        $finalTotalTripBreakdown += $totalTripBreakdown;

                        //Realibility Breakdown
                        $realibility = (($totalCompletedTrip - $totalTripBreakdown) / $totalCompletedTrip) * 100;
                        $realibilityArr[$all_date] = $realibility;
                        $finalRealibility += $realibility;

                        //Number of Bus
                        $numBus = TripDetail::distinct()
                            ->get(['bus_id'])
                            ->where('start_trip', $all_date)
                            ->where('route_id', $routeByCompany->id)
                            ->count();
                        $numBusArr[$all_date] = $numBus;
                        $finalNumBus += $numBus;

                        //Farebox
                        $farebox = TicketSalesTransaction::where('sales_date', $all_date)
                            ->where('route_id', $routeByCompany->id)
                            ->sum('actual_amount');
                        $fareboxArr[$all_date] = $farebox;
                        $finalFarebox += $farebox;

                        //Ridership
                        $ridership = TicketSalesTransaction::where('sales_date', $all_date)
                            ->where('route_id', $routeByCompany->id)
                            ->count();
                        $ridershipArr[$all_date] = $ridership;
                        $finalRidership += $ridership;
                    }

                    $route['route_name'] = $routeName;

                    $plannedTripArr['final_total'] = $finalPlannedTrip;
                    $completedInArr['final_total'] = $finalCompletedIn;
                    $completedOutArr['final_total'] = $finalCompletedOut;
                    $totalCompletedTripArr['final_total'] = $finalTotalCompletedTrip;
                    $tripComplianceArr['final_total'] = $finalTripCompliance;
                    $totalOffRouteArr['final_total'] = $finalTotalOffRoute;
                    $routeComplianceArr['final_total'] = $finalRouteCompliance;
                    $inboundDistanceArr['final_total'] = $finalInboundDistance;
                    $outboundDistanceArr['final_total'] = $finalOutboundDistance;
                    $totalDistanceInArr['final_total'] = $finalTotalDistanceIn;
                    $totalDistanceOutArr['final_total'] = $finalTotalDistanceOut;
                    $totalDistanceArr['final_total'] = $finalTotalDistance;
                    $totalTripOnTimeArr['final_total'] = $finalTripOnTime;
                    $punctualityArr['final_total'] = $finalPunctuality;
                    $totalTripBreakdownArr['final_total'] = $finalTotalTripBreakdown;
                    $realibilityArr['final_total'] = $finalRealibility;
                    $numBusArr['final_total'] = $finalNumBus;
                    $fareboxArr['final_total'] = $finalFarebox;
                    $ridershipArr['final_total'] = $finalRidership;

                    $content['planned_trip'] = $plannedTripArr;
                    $content['completed_trip_in'] = $completedInArr;
                    $content['completed_trip_out'] = $completedOutArr;
                    $content['total_completed_trip'] = $totalCompletedTripArr;
                    $content['trip_compliance'] = $tripComplianceArr;
                    $content['total_off_route'] = 0;
                    $content['route_compliance'] = 0;
                    $content['distance_in'] = $inboundDistanceArr;
                    $content['distance_out'] = $outboundDistanceArr;
                    $content['total_distance_in'] = $totalDistanceInArr;
                    $content['total_distance_out'] = $totalDistanceOutArr;
                    $content['total_distance'] = $totalDistanceArr;
                    $content['total_trip_on_time'] = $totalTripOnTimeArr;
                    $content['punctuality'] = $punctualityArr;
                    $content['total_trip_breakdown'] = $totalTripBreakdownArr;
                    $content['realibility'] = $realibilityArr;
                    $content['num_of_bus'] = $numBusArr;
                    $content['farebox'] = $fareboxArr;
                    $content['ridership'] = $ridershipArr;

                    $route['content'] = $content;
                    $data[$i++] = $route;
                }
                $isbsfSPAD->add($data);
                return Excel::download(new SPADIsbsf($isbsfSPAD, $validatedData['dateFrom'], $validatedData['dateTo'],$colspan, $all_dates), 'Top_Boarding_Report_SPAD.xlsx');
            }
        }
        else{
            //Data for all route of all company
            $allRoutes = Route::all();
            $i = 0;

            foreach ($allRoutes as $allRoute) {
                $route = $allRoute->route_name;
                $firstStage = Stage::where('route_id', $allRoute->id)->first();
                $lastStage = Stage::where('route_id', $allRoute->id)->orderby('stage_order','DESC')->first();
                $routeStage = $firstStage . '-' . $lastStage;
                $routeName = $route . $routeStage;

                foreach ($all_dates as $all_date) {

                    //Planned Trip
                    $plannedTrip = 0;
                    $schedules = RouteSchedulerDetail::where('schedule_date', $all_date)->get();
                    foreach ($schedules as $schedule) {
                        if ($allRoute->id == $schedule->RouteScheduleMSTR->route_id) {
                            $plannedTrip++;
                        }
                    }
                    $plannedTripArr[$all_date] = $plannedTrip;
                    $finalPlannedTrip += $plannedTrip;

                    //Completed Trip Out
                    $completedOut = 2;
                    //$completedOut = TripDetail::where('start_trip', $all_date)->where('route_id', $allRoute->id)->count();
                    $completedOutArr[$all_date] = $completedOut;
                    $finalCompletedOut += $completedOut;

                    //Completed Trip In
                    $completedIn = 2;
                    //$completedIn = TripDetail::where('start_trip', $all_date)->where('route_id', $allRoute->id)->count();
                    $completedInArr[$all_date] = $completedIn;
                    $finalCompletedIn += $completedIn;

                    //Total Completed Trip
                    $totalCompletedTrip = $completedOut + $completedIn;
                    $totalCompletedTripArr[$all_date] = $totalCompletedTrip;
                    $finalTotalCompletedTrip += $totalCompletedTrip;

                    //Trip Compliance
                    $tripCompliance = $plannedTrip / $totalCompletedTrip * 100;
                    $tripComplianceArr[$all_date] = $tripCompliance;
                    $finalTripCompliance += $tripCompliance;

                    //Off Route
                    $totalTripBreakdown = 0;
                    $totalTripBreakdownArr[$all_date] = $totalTripBreakdown;
                    $finalTotalOffRoute += $totalTripBreakdown;

                    //Route Compliance
                    $routeCompliance = 0;
                    $routeComplianceArr[$all_date] = $routeCompliance;
                    $finalRouteCompliance += $routeCompliance;

                    //KM 1 Way Outbound
                    $getScheduleID = TripDetail::where('start_trip', $all_date)->where('route_id', $allRoute->id)->first();
                    $getDistance = RouteSchedulerMSTR::where('id', $getScheduleID->id)->get();
                    $outboundDistance = $getDistance->outbound_distance;
                    $outboundDistanceArr[$all_date] = $outboundDistance;
                    $finalOutboundDistance += $outboundDistance;

                    //KM 1 Way Inbound
                    $inboundDistance = $getDistance->inbound_distance;
                    $inboundDistanceArr[$all_date] = $inboundDistance;
                    $finalInboundDistance += $inboundDistance;

                    //Total KM Outbound
                    $totalDistanceOut = $outboundDistance * $totalCompletedTrip;
                    $totalDistanceOutArr[$all_date] = $totalDistanceOut;
                    $finalTotalDistanceOut += $totalDistanceOut;

                    //Total KM Inbound
                    $totalDistanceIn = $inboundDistance * $totalCompletedTrip;
                    $totalDistanceInArr[$all_date] = $totalDistanceIn;
                    $finalTotalDistanceIn += $totalDistanceIn;

                    //Total KM
                    $totalDistance = $totalDistanceIn + $totalDistanceOut;
                    $totalDistanceArr[$all_date] = $totalDistance;
                    $finalTotalDistance += $totalDistance;

                    //Total Trip On Time
                    $ontimeCount = 0;
                    foreach ($schedules as $schedule) {
                        if ($schedule->RouteScheduleMSTR->route_id == $allRoute->id) {
                            $tripPerTime = TripDetail::where('route_schedule_mstr_id', $schedule->RouteScheduleMSTR->id)->first();
                            if ($schedule->schedule_start_time > $tripPerTime->start_trip) {
                                $ontimeCount++;
                            }
                        }
                    }
                    $totalTripOnTimeArr[$all_date] = $ontimeCount;
                    $finalTripOnTime += $ontimeCount;

                    //Punctuality Adherence
                    $punctuality = $ontimeCount / $totalCompletedTrip * 100;
                    $punctualityArr[$all_date] = $punctuality;
                    $finalPunctuality += $punctuality;

                    //Total Trip Breakdown
                    $totalTripBreakdown = 0;
                    $totalTripBreakdownArr[$all_date] = $totalTripBreakdown;
                    $finalTotalTripBreakdown += $totalTripBreakdown;

                    //Realibility Breakdown
                    $realibility = (($totalCompletedTrip - $totalTripBreakdown) / $totalCompletedTrip) * 100;
                    $realibilityArr[$all_date] = $realibility;
                    $finalRealibility += $realibility;

                    //Number of Bus
                    $numBus = TripDetail::distinct()
                        ->get(['bus_id'])
                        ->where('start_trip', $all_date)
                        ->where('route_id', $allRoute->id)
                        ->count();
                    $numBusArr[$all_date] = $numBus;
                    $finalNumBus += $numBus;

                    //Farebox
                    $farebox = TicketSalesTransaction::where('sales_date', $all_date)
                        ->where('route_id', $allRoute->id)
                        ->sum('actual_amount');
                    $fareboxArr[$all_date] = $farebox;
                    $finalFarebox += $farebox;

                    //Ridership
                    $ridership = TicketSalesTransaction::where('sales_date', $all_date)
                        ->where('route_id', $allRoute->id)
                        ->count();
                    $ridershipArr[$all_date] = $ridership;
                    $finalRidership += $ridership;
                }

                $route['route_name'] = $routeName;

                $plannedTripArr['final_total'] = $finalPlannedTrip;
                $completedInArr['final_total'] = $finalCompletedIn;
                $completedOutArr['final_total'] = $finalCompletedOut;
                $totalCompletedTripArr['final_total'] = $finalTotalCompletedTrip;
                $tripComplianceArr['final_total'] = $finalTripCompliance;
                $totalOffRouteArr['final_total'] = $finalTotalOffRoute;
                $routeComplianceArr['final_total'] = $finalRouteCompliance;
                $inboundDistanceArr['final_total'] = $finalInboundDistance;
                $outboundDistanceArr['final_total'] = $finalOutboundDistance;
                $totalDistanceInArr['final_total'] = $finalTotalDistanceIn;
                $totalDistanceOutArr['final_total'] = $finalTotalDistanceOut;
                $totalDistanceArr['final_total'] = $finalTotalDistance;
                $totalTripOnTimeArr['final_total'] = $finalTripOnTime;
                $punctualityArr['final_total'] = $finalPunctuality;
                $totalTripBreakdownArr['final_total'] = $finalTotalTripBreakdown;
                $realibilityArr['final_total'] = $finalRealibility;
                $numBusArr['final_total'] = $finalNumBus;
                $fareboxArr['final_total'] = $finalFarebox;
                $ridershipArr['final_total'] = $finalRidership;

                $content['planned_trip'] = $plannedTripArr;
                $content['completed_trip_in'] = $completedInArr;
                $content['completed_trip_out'] = $completedOutArr;
                $content['total_completed_trip'] = $totalCompletedTripArr;
                $content['trip_compliance'] = $tripComplianceArr;
                $content['total_off_route'] = 0;
                $content['route_compliance'] = 0;
                $content['distance_in'] = $inboundDistanceArr;
                $content['distance_out'] = $outboundDistanceArr;
                $content['total_distance_in'] = $totalDistanceInArr;
                $content['total_distance_out'] = $totalDistanceOutArr;
                $content['total_distance'] = $totalDistanceArr;
                $content['total_trip_on_time'] = $totalTripOnTimeArr;
                $content['punctuality'] = $punctualityArr;
                $content['total_trip_breakdown'] = $totalTripBreakdownArr;
                $content['realibility'] = $realibilityArr;
                $content['num_of_bus'] = $numBusArr;
                $content['farebox'] = $fareboxArr;
                $content['ridership'] = $ridershipArr;

                $route['content'] = $content;
                $data[$i++] = $route;
            }
            $isbsfSPAD->add($data);
            return Excel::download(new SPADIsbsf($isbsfSPAD, $validatedData['dateFrom'], $validatedData['dateTo'],$colspan, $all_dates), 'Top_Boarding_Report_SPAD.xlsx');
        }
    }
}
