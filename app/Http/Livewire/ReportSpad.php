<?php

namespace App\Http\Livewire;

use App\Exports\SalesByBus;
use App\Exports\SPADClaimDetails;
use App\Exports\SPADClaimSummary;
use App\Exports\SPADIsbsf;
use App\Exports\SPADRoute;
use App\Exports\SPADSalesDetails;
use App\Exports\SPADServiceGroup;
use App\Exports\SPADSummary;
use App\Exports\SPADSummaryByNetwork;
use App\Exports\SPADSummaryByRoute;
use App\Exports\SPADTopAlighting;
use App\Exports\SPADTopBoarding;
use App\Exports\SPADTrip;
use App\Exports\SPADTripMissed;
use App\Exports\SPADTripPlanned;
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
use App\Models\VehiclePosition;
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
        $this->companies = Company::all();
        return view('livewire.report-spad');
    }

    public function mount()
    {
        $this->companies=collect();
        $this->routes=collect();
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
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
                $out->writeln("YOU ARE IN Summary certain route for specific company");
                //Inbound
                $allTripInbounds = TripDetail::where('route_id', $validatedData['route_id'])
                    ->whereBetween('start_trip', [$dateFrom, $dateTo])
                    ->where('trip_code', 1)
                    ->get();

                if (count($allTripInbounds)>0) {
                    $out->writeln("YOU ARE IN HERE certain route allTripInbounds()");
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
                $allTripOutbounds = TripDetail::where('route_id', $validatedData['route_id'])
                    ->whereBetween('start_trip', [$dateFrom, $dateTo])
                    ->where('trip_code', 0)
                    ->get();

                if (count($allTripOutbounds )>0) {
                    $out->writeln("YOU ARE IN HERE certain route allTripOutbounds()");
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
                $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                $firstStage = Stage::where('route_id', $validatedData['route_id'])->first();
                $lastStage = Stage::where('route_id', $validatedData['route_id'])->orderby('stage_order','DESC')->first();
                $route_name_in = $selectedRoute->route_number . ' ' . $firstStage->stage_name . ' - ' . $lastStage->stage_name;
                $route_name_out = $selectedRoute->route_number . ' ' . $lastStage->stage_name . ' - ' . $firstStage->stage_name;

                if($existInTrip==true && $existOutTrip==true){
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

                    $data['grand'] = $grand;

                    $summary->add($data);

                }elseif($existInTrip==false && $existOutTrip==true){
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

                    $data['grand'] = $grand;

                    $summary->add($data);

                }elseif($existInTrip==true && $existOutTrip==false){
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

                    $data['grand'] = $grand;
                    $summary->add($data);

                }else{
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
                    $total['total_breakdown'] =0;
                    $total['total_bus_used'] = 0;
                    $total['total_accidents'] = 0;
                    $total['total_complaints'] = 0;

                    $content['outbound_data'] = $outbound_data;
                    $content['inbound_data'] = $inbound_data;
                    $content['total'] = $total;
                    $data[$selectedRoute->route_number] = $content;

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

                $out->writeln("YOU ARE IN HERE all route of " . $this->selectedCompany);

                //Get all route for specific company
                $allRoutes = Route::where('company_id', $this->selectedCompany)->get();

                foreach($allRoutes as $allRoute) {
                    $existInTrip = false;
                    $existOutTrip = false;
                    $out->writeln("allRoute: " . $allRoute->id);
                    //Inbound
                    $allTripInbounds = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->where('trip_code', 1)
                        ->get();

                    $inbound = [];
                    $outbound = [];

                    if (count($allTripInbounds)>0) {
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

                    if (count($allTripOutbounds)>0) {
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

                        //Increment ridership collection (%)
                        if($prevRidershipOut==0){
                            $increaseRidershipOut = 100;
                            $increaseRidershipFormatOut = 100;
                        }else{
                            $increaseRidershipOut = (($totalRidershipOut - $prevRidershipOut) / $prevRidershipOut) * 100;
                            $increaseRidershipFormatOut = number_format((float)$increaseRidershipOut, 2, '.', '');
                        }

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
                        if($prevFareboxIn==0){
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
                    $selectedRoute = Route::where('id', $allRoute->id)->first();
                    $firstStage = Stage::where('route_id', $allRoute->id)->first();
                    $lastStage = Stage::where('route_id', $allRoute->id)->orderby('stage_order','DESC')->first();
                    $route_name_in = $selectedRoute->route_number . ' ' . $firstStage->stage_name . ' - ' . $lastStage->stage_name;
                    $route_name_out = $selectedRoute->route_number . ' ' . $lastStage->stage_name . ' - ' . $firstStage->stage_name;

                    if ($existInTrip == true && $existOutTrip == true) {
                        $out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == true");
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
                        $out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == true");

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
                        $out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == false");

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
                        $out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == false");
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
                $existInTrip = false;
                $existOutTrip = false;
                //Inbound
                $allTripInbounds = TripDetail::where('route_id', $allRoute->id)
                    ->whereBetween('start_trip', [$dateFrom, $dateTo])
                    ->where('trip_code', 1)
                    ->get();

                if (count($allTripInbounds)>0) {
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
        $out->writeln("YOU ARE IN printServiceGroup()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['int'],
        ])->validate();

        $out->writeln("datefrom:" . $validatedData['dateFrom']);
        $out->writeln("dateto:" . $validatedData['dateTo']);

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo'] . '11:59:59');

        $out->writeln("dateFrom After:" . $dateFrom);
        $out->writeln("dateto After:" . $dateTo);

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        $serviceGroup = collect();

        if($this->selectedCompany){
            //ServiceGroup certain route for specific company
            $selectedCompany = Company::where('id', $this->selectedCompany)->first();
            $networkArea = $selectedCompany->company_name;

            if(!empty($this->state['route_id'])) {
                $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                $networkArea = $selectedRoute->company->company_name;

                $out->writeln("YOU ARE IN HERE ServiceGroup certain route for specific company");
                $totalScheduledTrip=0;
                $totalTripMade=0;
                $totalSumPassenger=0;
                $totalAdult=0;
                $totalConcession=0;

                foreach ($all_dates as $all_date) {
                    $firstDate = new Carbon($all_date);
                    $lastDate = new Carbon($all_date . '11:59:59');

                    //Number of Scheduled Trips
                    $scheduledTrip = 0;
                    $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate,$lastDate])->get();
                    foreach($schedules as $schedule){
                        if($schedule->RouteScheduleMSTR->route_id == $validatedData['route_id']){
                            $scheduledTrip++;
                        }
                    }
                    $totalScheduledTrip += $scheduledTrip;

                    $allTrips = TripDetail::where('route_id', $validatedData['route_id'])
                        ->whereBetween('start_trip', [$firstDate,$lastDate])
                        ->get();

                    foreach ($allTrips as $allTrip){
                        //Passengers Boarding Count
                        $adult = $allTrip->total_adult;
                        $concession = $allTrip->total_concession;
                        $sumPassenger = $adult + $concession;

                        $totalAdult += $adult;
                        $totalConcession += $concession;
                        $totalSumPassenger += $sumPassenger;
                    }

                    //Number of Trips Made
                    $tripMade = count($allTrips);
                    $totalTripMade += $tripMade;
                }

                $data['num_scheduled_trip'] = $totalScheduledTrip;
                $data['num_trip_made'] = $totalTripMade;
                $data['count_passenger_board'] = $totalSumPassenger;
                $data['num_adult'] = $totalAdult;
                $data['num_concession'] = $totalConcession;

                $serviceGroup->add($data);
            }
            //ServiceGroup all route for specific company
            else{

                $routeByCompanies = Route::where('company_id', $this->selectedCompany)->get();
                $out->writeln("YOU ARE IN HERE ServiceGroup all route for specific company");
                $totalScheduledTrip=0;
                $totalTripMade=0;
                $totalSumPassenger=0;
                $totalAdult=0;
                $totalConcession=0;

                foreach($routeByCompanies as $routeByCompany){
                    foreach ($all_dates as $all_date) {
                        $firstDate = new Carbon($all_date);
                        $lastDate = new Carbon($all_date . '11:59:59');

                        //Number of Scheduled Trips
                        $scheduledTrip = 0;
                        $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate,$lastDate])->get();
                        foreach($schedules as $schedule){
                            if($schedule->RouteScheduleMSTR->route_id == $validatedData['route_id']){
                                $scheduledTrip++;
                            }
                        }
                        $totalScheduledTrip += $scheduledTrip;

                        $allTrips = TripDetail::where('route_id', $routeByCompany->id)
                            ->whereBetween('start_trip', [$firstDate,$lastDate])
                            ->get();

                        foreach ($allTrips as $allTrip){
                            //Passengers Boarding Count
                            $adult = $allTrip->total_adult;
                            $concession = $allTrip->total_concession;
                            $sumPassenger = $adult + $concession;

                            $totalAdult += $adult;
                            $totalConcession += $concession;
                            $totalSumPassenger += $sumPassenger;
                        }

                        //Number of Trips Made
                        $tripMade = count($allTrips);
                        $totalTripMade += $tripMade;
                    }
                }
                $data['num_scheduled_trip'] = $totalScheduledTrip;
                $data['num_trip_made'] = $totalTripMade;
                $data['count_passenger_board'] = $totalSumPassenger;
                $data['num_adult'] = $totalAdult;
                $data['num_concession'] = $totalConcession;

                $serviceGroup->add($data);
            }
        }
        //ServiceGroup all route for all company
        else{
            $networkArea = 'ALL';

            $allRoutes = Route::all();
            $out->writeln("YOU ARE IN HERE ServiceGroup all route for all company");
            $totalScheduledTrip=0;
            $totalTripMade=0;
            $totalSumPassenger=0;
            $totalAdult=0;
            $totalConcession=0;

            foreach($allRoutes as $allRoute){
                foreach ($all_dates as $all_date) {
                    $firstDate = new Carbon($all_date);
                    $lastDate = new Carbon($all_date . '11:59:59');

                    //Number of Scheduled Trips
                    $scheduledTrip = 0;
                    $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate,$lastDate])->get();
                    foreach($schedules as $schedule){
                        if($schedule->RouteScheduleMSTR->route_id == $validatedData['route_id']){
                            $scheduledTrip++;
                        }
                    }
                    $totalScheduledTrip += $scheduledTrip;

                    $allTrips = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$firstDate,$lastDate])
                        ->get();

                    foreach ($allTrips as $allTrip){
                        //Passengers Boarding Count
                        $adult = $allTrip->total_adult;
                        $concession = $allTrip->total_concession;
                        $sumPassenger = $adult + $concession;

                        $totalAdult += $adult;
                        $totalConcession += $concession;
                        $totalSumPassenger += $sumPassenger;
                    }

                    //Number of Trips Made
                    $tripMade = count($allTrips);
                    $totalTripMade += $tripMade;
                }
            }
            $data['num_scheduled_trip'] = $totalScheduledTrip;
            $data['num_trip_made'] = $totalTripMade;
            $data['count_passenger_board'] = $totalSumPassenger;
            $data['num_adult'] = $totalAdult;
            $data['num_concession'] = $totalConcession;

            $serviceGroup->add($data);
        }

        return Excel::download(new SPADServiceGroup($networkArea,$serviceGroup, $validatedData['dateFrom'], $validatedData['dateTo']), 'Service_Group_Report_SPAD.xlsx');
    }

    public function printRoute()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printRoute()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['int'],
        ])->validate();

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo'] . '11:59:59');

        $out->writeln("datefrom:" . $validatedData['dateFrom']);
        $out->writeln("dateto:" . $validatedData['dateTo']);

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
        $routeSPAD = collect();

        if($this->selectedCompany){
            //Route specific route specific company
            $selectedCompany = Company::where('id', $this->selectedCompany)->first();
            $networkArea = $selectedCompany->company_name;

            if(!empty($this->state['route_id'])) {
                $out->writeln("YOU ARE IN HERE Route specific route specific company");
                $selectedRoute = Route::where('id', $validatedData['route_id'])->first();

                //Scheduled Trips
                //KM Planned In & Out
                $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom,$dateTo])->get();
                $tripPlanned = 0;
                $servicePlannedIn = 0;
                $servicePlannedOut = 0;
                foreach ($schedules as $schedule){
                    if($schedule->RouteScheduleMSTR->route_id == $validatedData['route_id']){
                        $tripPlanned++;
                        $servicePlannedIn += $schedule->RouteScheduleMSTR->inbound_distance;
                        $servicePlannedOut += $schedule->RouteScheduleMSTR->outbound_distance;
                    }
                }

                //Inbound Trip
                $allInboundTrips = TripDetail::where('route_id', $selectedRoute->id)
                    ->whereBetween('start_trip', [$dateFrom,$dateTo])
                    ->where('trip_code',1)
                    ->get();

                $tripMadeIn = 0;
                $kmServedIn = 0;
                $kmServedGPSIn = 0;
                $totalAdultIn = 0;
                $totalConcessionIn = 0;
                $totalRidershipIn = 0;
                $totalSalesIn = 0;
                $firstStageIn = Stage::where('route_id', $selectedRoute->id)->orderby('stage_order')->first();
                $lastStageIn = Stage::where('route_id', $selectedRoute->id)->orderby('stage_order', 'DESC')->first();
                $routeNameIn = $firstStageIn->stage_name . " - " . $lastStageIn->stage_name;

                if(count($allInboundTrips)>0) {
                    foreach ($allInboundTrips as $allInboundTrip) {
                        $tripMadeIn++;
                        $kmServedIn += $allInboundTrip->total_mileage;
                        $kmServedGPSIn += $allInboundTrip->total_mileage;

                        $adult = $allInboundTrip->total_adult;
                        $concession = $allInboundTrip->total_concession;
                        $sum = $adult + $concession;

                        $adultSales = $allInboundTrip->total_adult_amount;
                        $concessionSales = $allInboundTrip->total_concession_amount;
                        $sumSales = $adultSales + $concessionSales;

                        $totalAdultIn += $adult;
                        $totalConcessionIn += $concession;
                        $totalRidershipIn += $sum;
                        $totalSalesIn += $sumSales;
                    }
                }

                //Previous Month Ridership Inbound collection
                $prevTripsIn = TripDetail::whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                    ->where('route_id', $selectedRoute->id)
                    ->where('trip_code',1)
                    ->get();

                $prevRidershipIn = 0;
                $prevSalesIn = 0;

                if(count($prevTripsIn)>0) {
                    foreach ($prevTripsIn as $prevTripIn) {
                        $adult = $prevTripIn->total_adult;
                        $concession = $prevTripIn->total_concession;
                        $sumRidership = $adult + $concession;
                        $prevRidershipIn += $sumRidership;

                        $adultSales = $prevTripIn->total_adult_amount;
                        $concessionSales = $prevTripIn->total_concession_amount;
                        $sumSales = $adultSales + $concessionSales;
                        $prevSalesIn += $sumSales;
                    }
                    //Increment ridership inbound collection (%)
                    $increaseRidershipIn = (($totalRidershipIn - $prevRidershipIn) / $prevRidershipIn) * 100;
                    $increaseRidershipFormatIn = number_format((float)$increaseRidershipIn, 2, '.', '');
                    //Increment farebox inbound collection (%)
                    $increaseSalesIn = (($totalSalesIn - $prevSalesIn) / $prevSalesIn) * 100;
                    $increaseSalesFormatIn = number_format((float)$increaseSalesIn, 2, '.', '');
                }else {
                    $increaseRidershipFormatIn = 100;
                    $increaseSalesFormatIn = 100;
                }
                $inbound['route_name'] = $routeNameIn;
                $inbound['num_km_planned'] = $servicePlannedIn;
                $inbound['num_km_served'] = $kmServedIn;
                $inbound['num_km_served_gps'] = $kmServedGPSIn;
                $inbound['num_scheduled_trip'] = $tripPlanned;
                $inbound['num_trip_made'] = $tripMadeIn;
                $inbound['count_passenger_board'] = $totalRidershipIn;
                $inbound['num_adult'] = $totalAdultIn;
                $inbound['num_concession'] = $totalConcessionIn;
                $inbound['total_on'] = $totalRidershipIn;
                $inbound['total_pax'] = $prevRidershipIn;
                $inbound['total_pax_increase'] = $increaseRidershipFormatIn;
                $inbound['total_sales'] = $prevSalesIn;
                $inbound['total_sales_increase'] = $increaseSalesFormatIn;

                //Outbound Trip
                $allOutboundTrips = TripDetail::where('route_id', $selectedRoute->id)
                    ->whereBetween('start_trip', [$dateFrom,$dateTo])
                    ->where('trip_code',0)
                    ->get();

                $tripMadeOut = 0;
                $kmServedOut = 0;
                $kmServedGPSOut = 0;
                $totalAdultOut = 0;
                $totalConcessionOut = 0;
                $totalRidershipOut = 0;
                $totalSalesOut = 0;
                $lastStageOut = Stage::where('route_id', $selectedRoute->id)->orderby('stage_order')->first();
                $firstStageOut = Stage::where('route_id', $selectedRoute->id)->orderby('stage_order', 'DESC')->first();
                $routeNameOut = $firstStageOut->stage_name . " - " . $lastStageOut->stage_name;

                if(count($allOutboundTrips)>0) {
                    foreach ($allOutboundTrips as $allOutboundTrip) {
                        $tripMadeOut++;
                        $kmServedOut += $allOutboundTrip->total_mileage;
                        $kmServedGPSOut += $allOutboundTrip->total_mileage;

                        $adult = $allOutboundTrip->total_adult;
                        $concession = $allOutboundTrip->total_concession;
                        $sumRidership = $adult + $concession;

                        $adultSales = $allOutboundTrip->total_adult_amount;
                        $concessionSales = $allOutboundTrip->total_concession_amount;
                        $sumSales = $adultSales + $concessionSales;

                        $totalAdultOut += $adult;
                        $totalConcessionOut += $concession;
                        $totalRidershipOut += $sumRidership;
                        $totalSalesOut += $sumSales;
                    }
                }

                //Previous Month Ridership Outbound collection
                $prevTripsOut = TripDetail::whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                    ->where('route_id', $selectedRoute->id)
                    ->where('trip_code',0)
                    ->get();

                $prevRidershipOut = 0;
                $prevSalesOut = 0;
                if(count($prevTripsOut)>0) {
                    foreach ($prevTripsOut as $prevTripOut) {
                        $adult = $prevTripOut->total_adult;
                        $concession = $prevTripOut->total_concession;
                        $sumRidership = $adult + $concession;
                        $prevRidershipOut += $sumRidership;

                        $adultSales = $prevTripOut->total_adult_amount;
                        $concessionSales = $prevTripOut->total_concession_amount;
                        $sumSales = $adultSales + $concessionSales;
                        $prevSalesOut += $sumSales;
                    }
                    //Increment ridership outbound collection (%)
                    $increaseRidershipOut = (($totalRidershipOut - $prevRidershipOut) / $prevRidershipOut) * 100;
                    $increaseRidershipFormatOut = number_format((float)$increaseRidershipOut, 2, '.', '');
                    //Increment farebox outbound collection (%)
                    $increaseSalesOut = (($totalSalesOut - $prevSalesOut) / $prevSalesOut) * 100;
                    $increaseSalesFormatOut = number_format((float)$increaseSalesOut, 2, '.', '');
                }else{
                    $increaseRidershipFormatOut = 100;
                    $increaseSalesFormatOut  = 100;
                }

                $outbound['route_name'] = $routeNameOut;
                $outbound['num_km_planned'] = $servicePlannedOut;
                $outbound['num_km_served'] = $kmServedOut;
                $outbound['num_km_served_gps'] = $kmServedGPSOut;
                $outbound['num_scheduled_trip'] = $tripPlanned;
                $outbound['num_trip_made'] = $tripMadeOut;
                $outbound['count_passenger_board'] = $totalRidershipOut;
                $outbound['num_adult'] = $totalAdultOut;
                $outbound['num_concession'] = $totalConcessionOut;
                $outbound['total_on'] = $totalRidershipOut;
                $outbound['total_pax'] = $prevRidershipOut;
                $outbound['total_pax_increase'] = $increaseRidershipFormatOut;
                $outbound['total_sales'] = $prevSalesOut;
                $outbound['total_sales_increase'] = $increaseSalesFormatOut;

                $total['tot_num_km_planned'] = $inbound['num_km_planned'] + $outbound['num_km_planned'];
                $total['tot_num_km_served'] = $inbound['num_km_served'] + $outbound['num_km_planned'];
                $total['tot_num_km_served_gps'] = $inbound['num_km_served_gps'] + $outbound['num_km_served_gps'];
                $total['tot_num_scheduled_trip'] = $inbound['num_scheduled_trip'] + $outbound['num_scheduled_trip'];
                $total['tot_num_trip_made'] = $inbound['num_trip_made'] + $outbound['num_trip_made'];
                $total['tot_count_passenger_board'] = $inbound['count_passenger_board'] + $outbound['count_passenger_board'];
                $total['tot_num_adult'] = $inbound['num_adult'] + $outbound['num_adult'];
                $total['tot_num_concession'] = $inbound['num_concession'] + $outbound['num_concession'];
                $total['tot_total_on'] = $inbound['total_on'] + $outbound['total_on'];
                $total['tot_total_pax'] = $inbound['total_pax']+ $outbound['total_pax'];

                //total_pax_increase
                $sumtotalPaxIncrease = $inbound['total_pax_increase'] + $outbound['total_pax_increase'];
                $calcPaxIncrease = ($sumtotalPaxIncrease/200)*100;
                $total['tot_total_pax_increase'] = $calcPaxIncrease;

                $total['tot_total_sales'] = $inbound['total_sales'] + $outbound['total_sales'];

                //total_sales_increase
                $sumtotalSalesIncrease =  $inbound['total_sales_increase'] + $outbound['total_sales_increase'];
                $calcSalesIncrease = ($sumtotalSalesIncrease/200)*100;
                $total['tot_total_sales_increase'] = $calcSalesIncrease;

                $grand['grand_num_km_planned'] = $total['tot_num_km_planned'];
                $grand['grand_num_km_served'] = $total['tot_num_km_served'];
                $grand['grand_num_km_served_gps'] = $total['tot_num_km_served_gps'];
                $grand['grand_num_scheduled_trip'] = $total['tot_num_scheduled_trip'];
                $grand['grand_num_trip_made'] = $total['tot_num_trip_made'];
                $grand['grand_count_passenger_board'] = $total['tot_count_passenger_board'];
                $grand['grand_num_adult'] = $total['tot_num_adult'];
                $grand['grand_num_concession'] = $total['tot_num_concession'];
                $grand['grand_total_on'] = $total['tot_total_on'];
                $grand['grand_total_pax'] = $total['tot_total_pax'];
                $grand['grand_total_pax_increase'] = $calcPaxIncrease;
                $grand['grand_total_sales'] = $total['tot_total_sales'];
                $grand['grand_total_sales_increase'] = $calcSalesIncrease;

                $perRoute['inbound'] = $inbound;
                $perRoute['outbound'] = $outbound;
                $perRoute['total'] = $total;
                $data[$selectedRoute->route_number] = $perRoute;
                $data['grand'] = $grand;

                $routeSPAD->add($data);
            }
            //Route all route specific company
            else{
                $out->writeln("YOU ARE IN HERE Route all route specific company");
                $grandKMPlanned = 0;
                $grandKMServed = 0;
                $grandKMServedGPS = 0;
                $grandScheduledTrip = 0;
                $grandTripMade = 0;
                $grandPassenger = 0;
                $grandAdult = 0;
                $grandConcession = 0;
                $grandTotalOn = 0;
                $grandPrevPax = 0;
                $grandPrevPaxIncrease = 0;
                $grandPrevSales = 0;
                $grandPrevSalesIncrease = 0;
                $allRoutePaxIncrease = 0;
                $allRouteSalesIncrease = 0;

                $routeByCompanies = Route::where('company_id', $this->selectedCompany)->get();

                foreach ($routeByCompanies as $routeByCompany) {

                    //Scheduled Trips
                    //KM Planned In & Out
                    $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->get();
                    $tripPlanned = 0;
                    $servicePlannedIn = 0;
                    $servicePlannedOut = 0;
                    foreach ($schedules as $schedule) {
                        if ($schedule->RouteScheduleMSTR->route_id == $routeByCompany->id) {
                            $tripPlanned++;
                            $servicePlannedIn += $schedule->RouteScheduleMSTR->inbound_distance;
                            $servicePlannedOut += $schedule->RouteScheduleMSTR->outbound_distance;
                        }
                    }

                    //Inbound Trip
                    $allInboundTrips = TripDetail::where('route_id', $routeByCompany->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->where('trip_code', 1)
                        ->get();

                    $tripMadeIn = 0;
                    $kmServedIn = 0;
                    $kmServedGPSIn = 0;
                    $totalAdultIn = 0;
                    $totalConcessionIn = 0;
                    $totalRidershipIn = 0;
                    $totalSalesIn = 0;
                    $firstStageIn = Stage::where('route_id', $routeByCompany->id)->orderby('stage_order')->first();
                    $lastStageIn = Stage::where('route_id', $routeByCompany->id)->orderby('stage_order', 'DESC')->first();
                    $routeNameIn = $firstStageIn->stage_name . " - " . $lastStageIn->stage_name;

                    if (count($allInboundTrips) > 0) {
                        foreach ($allInboundTrips as $allInboundTrip) {
                            $tripMadeIn++;
                            $kmServedIn += $allInboundTrip->total_mileage;
                            $kmServedGPSIn += $allInboundTrip->total_mileage;

                            $adult = $allInboundTrip->total_adult;
                            $concession = $allInboundTrip->total_concession;
                            $sum = $adult + $concession;

                            $adultSales = $allInboundTrip->total_adult_amount;
                            $concessionSales = $allInboundTrip->total_concession_amount;
                            $sumSales = $adultSales + $concessionSales;

                            $totalAdultIn += $adult;
                            $totalConcessionIn += $concession;
                            $totalRidershipIn += $sum;
                            $totalSalesIn += $sumSales;
                        }
                    }

                    //Previous Month Ridership Inbound collection
                    $prevTripsIn = TripDetail::whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                        ->where('route_id', $routeByCompany->id)
                        ->where('trip_code', 1)
                        ->get();

                    $prevRidershipIn = 0;
                    $prevSalesIn = 0;

                    if (count($prevTripsIn) > 0) {
                        foreach ($prevTripsIn as $prevTripIn) {
                            $adult = $prevTripIn->total_adult;
                            $concession = $prevTripIn->total_concession;
                            $sumRidership = $adult + $concession;
                            $prevRidershipIn += $sumRidership;

                            $adultSales = $prevTripIn->total_adult_amount;
                            $concessionSales = $prevTripIn->total_concession_amount;
                            $sumSales = $adultSales + $concessionSales;
                            $prevSalesIn += $sumSales;
                        }
                        //Increment ridership inbound collection (%)
                        $increaseRidershipIn = (($totalRidershipIn - $prevRidershipIn) / $prevRidershipIn) * 100;
                        $increaseRidershipFormatIn = number_format((float)$increaseRidershipIn, 2, '.', '');
                        //Increment farebox inbound collection (%)
                        $increaseSalesIn = (($totalSalesIn - $prevSalesIn) / $prevSalesIn) * 100;
                        $increaseSalesFormatIn = number_format((float)$increaseSalesIn, 2, '.', '');
                    } else {
                        $increaseRidershipFormatIn = 100;
                        $increaseSalesFormatIn = 100;
                    }
                    $inbound['route_name'] = $routeNameIn;
                    $inbound['num_km_planned'] = $servicePlannedIn;
                    $inbound['num_km_served'] = $kmServedIn;
                    $inbound['num_km_served_gps'] = $kmServedGPSIn;
                    $inbound['num_scheduled_trip'] = $tripPlanned;
                    $inbound['num_trip_made'] = $tripMadeIn;
                    $inbound['count_passenger_board'] = $totalRidershipIn;
                    $inbound['num_adult'] = $totalAdultIn;
                    $inbound['num_concession'] = $totalConcessionIn;
                    $inbound['total_on'] = $totalRidershipIn;
                    $inbound['total_pax'] = $prevRidershipIn;
                    $inbound['total_pax_increase'] = $increaseRidershipFormatIn;
                    $inbound['total_sales'] = $prevSalesIn;
                    $inbound['total_sales_increase'] = $increaseSalesFormatIn;

                    //Outbound Trip
                    $allOutboundTrips = TripDetail::where('route_id', $routeByCompany->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->where('trip_code', 0)
                        ->get();

                    $tripMadeOut = 0;
                    $kmServedOut = 0;
                    $kmServedGPSOut = 0;
                    $totalAdultOut = 0;
                    $totalConcessionOut = 0;
                    $totalRidershipOut = 0;
                    $totalSalesOut = 0;
                    $lastStageOut = Stage::where('route_id', $routeByCompany->id)->orderby('stage_order')->first();
                    $firstStageOut = Stage::where('route_id', $routeByCompany->id)->orderby('stage_order', 'DESC')->first();
                    $routeNameOut = $firstStageOut->stage_name . " - " . $lastStageOut->stage_name;

                    if (count($allOutboundTrips) > 0) {
                        foreach ($allOutboundTrips as $allOutboundTrip) {
                            $tripMadeOut++;
                            $kmServedOut += $allOutboundTrip->total_mileage;
                            $kmServedGPSOut += $allOutboundTrip->total_mileage;

                            $adult = $allOutboundTrip->total_adult;
                            $concession = $allOutboundTrip->total_concession;
                            $sumRidership = $adult + $concession;

                            $adultSales = $allOutboundTrip->total_adult_amount;
                            $concessionSales = $allOutboundTrip->total_concession_amount;
                            $sumSales = $adultSales + $concessionSales;

                            $totalAdultOut += $adult;
                            $totalConcessionOut += $concession;
                            $totalRidershipOut += $sumRidership;
                            $totalSalesOut += $sumSales;
                        }
                    }

                    //Previous Month Ridership Outbound collection
                    $prevTripsOut = TripDetail::whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                        ->where('route_id', $routeByCompany->id)
                        ->where('trip_code', 0)
                        ->get();

                    $prevRidershipOut = 0;
                    $prevSalesOut = 0;
                    if (count($prevTripsOut) > 0) {
                        foreach ($prevTripsOut as $prevTripOut) {
                            $adult = $prevTripOut->total_adult;
                            $concession = $prevTripOut->total_concession;
                            $sumRidership = $adult + $concession;
                            $prevRidershipOut += $sumRidership;

                            $adultSales = $prevTripOut->total_adult_amount;
                            $concessionSales = $prevTripOut->total_concession_amount;
                            $sumSales = $adultSales + $concessionSales;
                            $prevSalesOut += $sumSales;
                        }
                        //Increment ridership outbound collection (%)
                        $increaseRidershipOut = (($totalRidershipOut - $prevRidershipOut) / $prevRidershipOut) * 100;
                        $increaseRidershipFormatOut = number_format((float)$increaseRidershipOut, 2, '.', '');
                        //Increment farebox outbound collection (%)
                        $increaseSalesOut = (($totalSalesOut - $prevSalesOut) / $prevSalesOut) * 100;
                        $increaseSalesFormatOut = number_format((float)$increaseSalesOut, 2, '.', '');
                    } else {
                        $increaseRidershipFormatOut = 100;
                        $increaseSalesFormatOut = 100;
                    }

                    $outbound['route_name'] = $routeNameOut;
                    $outbound['num_km_planned'] = $servicePlannedOut;
                    $outbound['num_km_served'] = $kmServedOut;
                    $outbound['num_km_served_gps'] = $kmServedGPSOut;
                    $outbound['num_scheduled_trip'] = $tripPlanned;
                    $outbound['num_trip_made'] = $tripMadeOut;
                    $outbound['count_passenger_board'] = $totalRidershipOut;
                    $outbound['num_adult'] = $totalAdultOut;
                    $outbound['num_concession'] = $totalConcessionOut;
                    $outbound['total_on'] = $totalRidershipOut;
                    $outbound['total_pax'] = $prevRidershipOut;
                    $outbound['total_pax_increase'] = $increaseRidershipFormatOut;
                    $outbound['total_sales'] = $prevSalesOut;
                    $outbound['total_sales_increase'] = $increaseSalesFormatOut;

                    $total['tot_num_km_planned'] = $inbound['num_km_planned'] + $outbound['num_km_planned'];
                    $total['tot_num_km_served'] = $inbound['num_km_served'] + $outbound['num_km_planned'];
                    $total['tot_num_km_served_gps'] = $inbound['num_km_served_gps'] + $outbound['num_km_served_gps'];
                    $total['tot_num_scheduled_trip'] = $inbound['num_scheduled_trip'] + $outbound['num_scheduled_trip'];
                    $total['tot_num_trip_made'] = $inbound['num_trip_made'] + $outbound['num_trip_made'];
                    $total['tot_count_passenger_board'] = $inbound['count_passenger_board'] + $outbound['count_passenger_board'];
                    $total['tot_num_adult'] = $inbound['num_adult'] + $outbound['num_adult'];
                    $total['tot_num_concession'] = $inbound['num_concession'] + $outbound['num_concession'];
                    $total['tot_total_on'] = $inbound['total_on'] + $outbound['total_on'];
                    $total['tot_total_pax'] = $inbound['total_pax'] + $outbound['total_pax'];

                    //total_pax_increase
                    $sumtotalPaxIncrease = $inbound['total_pax_increase'] + $outbound['total_pax_increase'];
                    $calcPaxIncrease = ($sumtotalPaxIncrease/200)*100;
                    $total['tot_total_pax_increase'] = $calcPaxIncrease;

                    $total['tot_total_sales'] = $inbound['total_sales'] + $outbound['total_sales'];

                    //total_sales_increase
                    $sumtotalSalesIncrease =  $inbound['total_sales_increase'] + $outbound['total_sales_increase'];
                    $calcSalesIncrease = ($sumtotalSalesIncrease/200)*100;
                    $total['tot_total_sales_increase'] = $calcSalesIncrease;

                    $grandKMPlanned += $total['tot_num_km_planned'];
                    $grandKMServed += $total['tot_num_km_served'];
                    $grandKMServedGPS += $total['tot_num_km_served_gps'];
                    $grandScheduledTrip += $total['tot_num_scheduled_trip'];
                    $grandTripMade += $total['tot_num_trip_made'];
                    $grandPassenger += $total['tot_count_passenger_board'];
                    $grandAdult += $total['tot_num_adult'];
                    $grandConcession += $total['tot_num_concession'];
                    $grandTotalOn += $total['tot_total_on'];
                    $grandPrevPax += $total['tot_total_pax'];
                    $grandPrevPaxIncrease += $total['tot_total_pax_increase'];
                    $grandPrevSales += $total['tot_total_sales'];
                    $grandPrevSalesIncrease += $total['tot_total_sales_increase'];
                    $allRoutePaxIncrease += 100;
                    $allRouteSalesIncrease += 100;

                    $perRoute['inbound'] = $inbound;
                    $perRoute['outbound'] = $outbound;
                    $perRoute['total'] = $total;
                    $data[$routeByCompany->route_number] = $perRoute;
                }
                $grand['grand_num_km_planned'] = $grandKMPlanned;
                $grand['grand_num_km_served'] = $grandKMServed;
                $grand['grand_num_km_served_gps'] = $grandKMServedGPS;
                $grand['grand_num_scheduled_trip'] = $grandScheduledTrip;
                $grand['grand_num_trip_made'] = $grandTripMade;
                $grand['grand_count_passenger_board'] = $grandPassenger;
                $grand['grand_num_adult'] = $grandAdult;
                $grand['grand_num_concession'] = $grandConcession;
                $grand['grand_total_on'] = $grandTotalOn;
                $grand['grand_total_pax'] =  $grandPrevPax;

                //grand_pax_increase
                $calcGrandPaxIncrease = ($grandPrevPaxIncrease/$allRoutePaxIncrease)*100;
                $calcGrandPaxIncreaseFormat = number_format((float)$calcGrandPaxIncrease, 2, '.', '');
                $grand['grand_total_pax_increase'] = $calcGrandPaxIncreaseFormat;

                $grand['grand_total_sales'] = $grandPrevSales;

                //grand_sales_increase
                $calcGrandSalesIncrease = ($grandPrevSalesIncrease/$allRouteSalesIncrease)*100;
                $calcGrandSalesIncreaseFormat = number_format((float)$calcGrandSalesIncrease, 2, '.', '');
                $grand['grand_total_sales_increase'] = $calcGrandSalesIncreaseFormat;

                $data['grand'] = $grand;
                $routeSPAD->add($data);
            }
        }
        //Route all route all company
        else{
            $networkArea = 'ALL';

            $out->writeln("YOU ARE IN HERE Route all route specific company");
            $grandKMPlanned = 0;
            $grandKMServed = 0;
            $grandKMServedGPS = 0;
            $grandScheduledTrip = 0;
            $grandTripMade = 0;
            $grandPassenger = 0;
            $grandAdult = 0;
            $grandConcession = 0;
            $grandTotalOn = 0;
            $grandPrevPax = 0;
            $grandPrevPaxIncrease = 0;
            $grandPrevSales = 0;
            $grandPrevSalesIncrease = 0;
            $allRoutePaxIncrease = 0;
            $allRouteSalesIncrease = 0;

            $allRoutes = Route::all();

            foreach ($allRoutes as $allRoute) {

                //Scheduled Trips
                //KM Planned In & Out
                $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->get();
                $tripPlanned = 0;
                $servicePlannedIn = 0;
                $servicePlannedOut = 0;
                foreach ($schedules as $schedule) {
                    if ($schedule->RouteScheduleMSTR->route_id == $allRoute->id) {
                        $tripPlanned++;
                        $servicePlannedIn += $schedule->RouteScheduleMSTR->inbound_distance;
                        $servicePlannedOut += $schedule->RouteScheduleMSTR->outbound_distance;
                    }
                }

                //Inbound Trip
                $allInboundTrips = TripDetail::where('route_id', $allRoute->id)
                    ->whereBetween('start_trip', [$dateFrom, $dateTo])
                    ->where('trip_code', 1)
                    ->get();

                $tripMadeIn = 0;
                $kmServedIn = 0;
                $kmServedGPSIn = 0;
                $totalAdultIn = 0;
                $totalConcessionIn = 0;
                $totalRidershipIn = 0;
                $totalSalesIn = 0;
                $firstStageIn = Stage::where('route_id', $allRoute->id)->orderby('stage_order')->first();
                $lastStageIn = Stage::where('route_id', $allRoute->id)->orderby('stage_order', 'DESC')->first();
                $routeNameIn = $firstStageIn->stage_name . " - " . $lastStageIn->stage_name;

                if (count($allInboundTrips) > 0) {
                    foreach ($allInboundTrips as $allInboundTrip) {
                        $tripMadeIn++;
                        $kmServedIn += $allInboundTrip->total_mileage;
                        $kmServedGPSIn += $allInboundTrip->total_mileage;

                        $adult = $allInboundTrip->total_adult;
                        $concession = $allInboundTrip->total_concession;
                        $sum = $adult + $concession;

                        $adultSales = $allInboundTrip->total_adult_amount;
                        $concessionSales = $allInboundTrip->total_concession_amount;
                        $sumSales = $adultSales + $concessionSales;

                        $totalAdultIn += $adult;
                        $totalConcessionIn += $concession;
                        $totalRidershipIn += $sum;
                        $totalSalesIn += $sumSales;
                    }
                }

                //Previous Month Ridership Inbound collection
                $prevTripsIn = TripDetail::whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                    ->where('route_id', $allRoute->id)
                    ->where('trip_code', 1)
                    ->get();

                $prevRidershipIn = 0;
                $prevSalesIn = 0;

                if (count($prevTripsIn) > 0) {
                    foreach ($prevTripsIn as $prevTripIn) {
                        $adult = $prevTripIn->total_adult;
                        $concession = $prevTripIn->total_concession;
                        $sumRidership = $adult + $concession;
                        $prevRidershipIn += $sumRidership;

                        $adultSales = $prevTripIn->total_adult_amount;
                        $concessionSales = $prevTripIn->total_concession_amount;
                        $sumSales = $adultSales + $concessionSales;
                        $prevSalesIn += $sumSales;
                    }
                    //Increment ridership inbound collection (%)
                    $increaseRidershipIn = (($totalRidershipIn - $prevRidershipIn) / $prevRidershipIn) * 100;
                    $increaseRidershipFormatIn = number_format((float)$increaseRidershipIn, 2, '.', '');
                    //Increment farebox inbound collection (%)
                    $increaseSalesIn = (($totalSalesIn - $prevSalesIn) / $prevSalesIn) * 100;
                    $increaseSalesFormatIn = number_format((float)$increaseSalesIn, 2, '.', '');
                } else {
                    $increaseRidershipFormatIn = 100;
                    $increaseSalesFormatIn = 100;
                }
                $inbound['route_name'] = $routeNameIn;
                $inbound['num_km_planned'] = $servicePlannedIn;
                $inbound['num_km_served'] = $kmServedIn;
                $inbound['num_km_served_gps'] = $kmServedGPSIn;
                $inbound['num_scheduled_trip'] = $tripPlanned;
                $inbound['num_trip_made'] = $tripMadeIn;
                $inbound['count_passenger_board'] = $totalRidershipIn;
                $inbound['num_adult'] = $totalAdultIn;
                $inbound['num_concession'] = $totalConcessionIn;
                $inbound['total_on'] = $totalRidershipIn;
                $inbound['total_pax'] = $prevRidershipIn;
                $inbound['total_pax_increase'] = $increaseRidershipFormatIn;
                $inbound['total_sales'] = $prevSalesIn;
                $inbound['total_sales_increase'] = $increaseSalesFormatIn;

                //Outbound Trip
                $allOutboundTrips = TripDetail::where('route_id', $allRoute->id)
                    ->whereBetween('start_trip', [$dateFrom, $dateTo])
                    ->where('trip_code', 0)
                    ->get();

                $tripMadeOut = 0;
                $kmServedOut = 0;
                $kmServedGPSOut = 0;
                $totalAdultOut = 0;
                $totalConcessionOut = 0;
                $totalRidershipOut = 0;
                $totalSalesOut = 0;
                $lastStageOut = Stage::where('route_id', $allRoute->id)->orderby('stage_order')->first();
                $firstStageOut = Stage::where('route_id', $allRoute->id)->orderby('stage_order', 'DESC')->first();
                $routeNameOut = $firstStageOut->stage_name . " - " . $lastStageOut->stage_name;

                if (count($allOutboundTrips) > 0) {
                    foreach ($allOutboundTrips as $allOutboundTrip) {
                        $tripMadeOut++;
                        $kmServedOut += $allOutboundTrip->total_mileage;
                        $kmServedGPSOut += $allOutboundTrip->total_mileage;

                        $adult = $allOutboundTrip->total_adult;
                        $concession = $allOutboundTrip->total_concession;
                        $sumRidership = $adult + $concession;

                        $adultSales = $allOutboundTrip->total_adult_amount;
                        $concessionSales = $allOutboundTrip->total_concession_amount;
                        $sumSales = $adultSales + $concessionSales;

                        $totalAdultOut += $adult;
                        $totalConcessionOut += $concession;
                        $totalRidershipOut += $sumRidership;
                        $totalSalesOut += $sumSales;
                    }
                }

                //Previous Month Ridership Outbound collection
                $prevTripsOut = TripDetail::whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                    ->where('route_id', $allRoute->id)
                    ->where('trip_code', 0)
                    ->get();

                $prevRidershipOut = 0;
                $prevSalesOut = 0;
                if (count($prevTripsOut) > 0) {
                    foreach ($prevTripsOut as $prevTripOut) {
                        $adult = $prevTripOut->total_adult;
                        $concession = $prevTripOut->total_concession;
                        $sumRidership = $adult + $concession;
                        $prevRidershipOut += $sumRidership;

                        $adultSales = $prevTripOut->total_adult_amount;
                        $concessionSales = $prevTripOut->total_concession_amount;
                        $sumSales = $adultSales + $concessionSales;
                        $prevSalesOut += $sumSales;
                    }
                    //Increment ridership outbound collection (%)
                    $increaseRidershipOut = (($totalRidershipOut - $prevRidershipOut) / $prevRidershipOut) * 100;
                    $increaseRidershipFormatOut = number_format((float)$increaseRidershipOut, 2, '.', '');
                    //Increment farebox outbound collection (%)
                    $increaseSalesOut = (($totalSalesOut - $prevSalesOut) / $prevSalesOut) * 100;
                    $increaseSalesFormatOut = number_format((float)$increaseSalesOut, 2, '.', '');
                } else {
                    $increaseRidershipFormatOut = 100;
                    $increaseSalesFormatOut = 100;
                }

                $outbound['route_name'] = $routeNameOut;
                $outbound['num_km_planned'] = $servicePlannedOut;
                $outbound['num_km_served'] = $kmServedOut;
                $outbound['num_km_served_gps'] = $kmServedGPSOut;
                $outbound['num_scheduled_trip'] = $tripPlanned;
                $outbound['num_trip_made'] = $tripMadeOut;
                $outbound['count_passenger_board'] = $totalRidershipOut;
                $outbound['num_adult'] = $totalAdultOut;
                $outbound['num_concession'] = $totalConcessionOut;
                $outbound['total_on'] = $totalRidershipOut;
                $outbound['total_pax'] = $prevRidershipOut;
                $outbound['total_pax_increase'] = $increaseRidershipFormatOut;
                $outbound['total_sales'] = $prevSalesOut;
                $outbound['total_sales_increase'] = $increaseSalesFormatOut;

                $total['tot_num_km_planned'] = $inbound['num_km_planned'] + $outbound['num_km_planned'];
                $total['tot_num_km_served'] = $inbound['num_km_served'] + $outbound['num_km_planned'];
                $total['tot_num_km_served_gps'] = $inbound['num_km_served_gps'] + $outbound['num_km_served_gps'];
                $total['tot_num_scheduled_trip'] = $inbound['num_scheduled_trip'] + $outbound['num_scheduled_trip'];
                $total['tot_num_trip_made'] = $inbound['num_trip_made'] + $outbound['num_trip_made'];
                $total['tot_count_passenger_board'] = $inbound['count_passenger_board'] + $outbound['count_passenger_board'];
                $total['tot_num_adult'] = $inbound['num_adult'] + $outbound['num_adult'];
                $total['tot_num_concession'] = $inbound['num_concession'] + $outbound['num_concession'];
                $total['tot_total_on'] = $inbound['total_on'] + $outbound['total_on'];
                $total['tot_total_pax'] = $inbound['total_pax'] + $outbound['total_pax'];

                //total_pax_increase
                $sumtotalPaxIncrease = $inbound['total_pax_increase'] + $outbound['total_pax_increase'];
                $calcPaxIncrease = ($sumtotalPaxIncrease/200)*100;
                $total['tot_total_pax_increase'] = $calcPaxIncrease;

                $total['tot_total_sales'] = $inbound['total_sales'] + $outbound['total_sales'];

                //total_sales_increase
                $sumtotalSalesIncrease =  $inbound['total_sales_increase'] + $outbound['total_sales_increase'];
                $calcSalesIncrease = ($sumtotalSalesIncrease/200)*100;
                $total['tot_total_sales_increase'] = $calcSalesIncrease;

                $grandKMPlanned += $total['tot_num_km_planned'];
                $grandKMServed += $total['tot_num_km_served'];
                $grandKMServedGPS += $total['tot_num_km_served_gps'];
                $grandScheduledTrip += $total['tot_num_scheduled_trip'];
                $grandTripMade += $total['tot_num_trip_made'];
                $grandPassenger += $total['tot_count_passenger_board'];
                $grandAdult += $total['tot_num_adult'];
                $grandConcession += $total['tot_num_concession'];
                $grandTotalOn += $total['tot_total_on'];
                $grandPrevPax += $total['tot_total_pax'];
                $grandPrevPaxIncrease += $total['tot_total_pax_increase'];
                $grandPrevSales += $total['tot_total_sales'];
                $grandPrevSalesIncrease += $total['tot_total_sales_increase'];
                $allRoutePaxIncrease += 100;
                $allRouteSalesIncrease += 100;

                $perRoute['inbound'] = $inbound;
                $perRoute['outbound'] = $outbound;
                $perRoute['total'] = $total;
                $data[$allRoute->route_number] = $perRoute;
            }
            $grand['grand_num_km_planned'] = $grandKMPlanned;
            $grand['grand_num_km_served'] = $grandKMServed;
            $grand['grand_num_km_served_gps'] = $grandKMServedGPS;
            $grand['grand_num_scheduled_trip'] = $grandScheduledTrip;
            $grand['grand_num_trip_made'] = $grandTripMade;
            $grand['grand_count_passenger_board'] = $grandPassenger;
            $grand['grand_num_adult'] = $grandAdult;
            $grand['grand_num_concession'] = $grandConcession;
            $grand['grand_total_on'] = $grandTotalOn;
            $grand['grand_total_pax'] =  $grandPrevPax;

            //grand_pax_increase
            $calcGrandPaxIncrease = ($grandPrevPaxIncrease/$allRoutePaxIncrease)*100;
            $calcGrandPaxIncreaseFormat = number_format((float)$calcGrandPaxIncrease, 2, '.', '');
            $grand['grand_total_pax_increase'] = $calcGrandPaxIncreaseFormat;

            $grand['grand_total_sales'] = $grandPrevSales;

            //grand_sales_increase
            $calcGrandSalesIncrease = ($grandPrevSalesIncrease/$allRouteSalesIncrease)*100;
            $calcGrandSalesIncreaseFormat = number_format((float)$calcGrandSalesIncrease, 2, '.', '');
            $grand['grand_total_sales_increase'] = $calcGrandSalesIncreaseFormat;


            $data['grand'] = $grand;
            $routeSPAD->add($data);
        }

        return Excel::download(new SPADRoute($networkArea, $routeSPAD, $validatedData['dateFrom'], $validatedData['dateTo']), 'Route_Report_SPAD.xlsx');
    }

    public function printTrip()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printTrip()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['int'],
        ])->validate();

        $out->writeln("datefrom:" . $validatedData['dateFrom']);
        $out->writeln("dateto:" . $validatedData['dateTo']);

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo'] . '11:59:59');

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }
        $tripSPAD = collect();

        if($this->selectedCompany){
            //Trip specific route specific company
            $companyDetails = Company::where('id', $this->selectedCompany)->first();
            $networkArea = $companyDetails->company_name;
            $grandPassengerCount = 0;
            $grandSalesAmount  = 0;
            $grandAdult  = 0;
            $grandConcession  = 0;
            $perRoute = [];

            if(!empty($this->state['route_id'])) {
                $out->writeln("YOU ARE IN HERE Trip specific route specific company");
                $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                $perRoutePassengerCount = 0;
                $perRouteSalesAmount = 0;
                $perRouteAdult = 0;
                $perRouteConcession = 0;
                $perDate = [];

                foreach ($all_dates as $all_date) {
                    $existOutTrip = false;
                    $existInTrip = false;
                    $firstDate = new Carbon($all_date);
                    $lastDate = new Carbon($all_date . '11:59:59');
                    $out->writeln("YOU ARE IN HERE all_date loop. firstDate: ". $firstDate . " lastDate: " . $lastDate);

                    //Inbound Trip
                    $allInboundTrips = TripDetail::where('route_id', $selectedRoute->id)
                        ->whereBetween('start_trip', [$firstDate, $lastDate])
                        ->where('trip_code', 1)
                        ->get();

                    $firstStageIn = Stage::where('route_id', $selectedRoute->id)->orderby('stage_order')->first();
                    $lastStageIn = Stage::where('route_id', $selectedRoute->id)->orderby('stage_order', 'DESC')->first();
                    $routeNameIn = $firstStageIn->stage_name . " - " . $lastStageIn->stage_name;

                    $inbound = [];
                    $inboundPassenger = 0;
                    $inboundSales = 0;
                    $inboundAdult = 0;
                    $inboundConcession = 0;
                    if (count($allInboundTrips) > 0) {
                        $existInTrip = true;
                        $countTripIn = 0;
                        foreach ($allInboundTrips as $allInboundTrip) {
                            $countTripIn++;
                            $out->writeln("YOU ARE IN HERE allInboundTrip loop. countTripIn: ". $countTripIn);

                            $tripIn['trip_no'] = 'T' . $countTripIn;
                            $tripIn['bus_no'] = $allInboundTrip->bus->bus_registration_number;
                            $tripIn['driver_id'] = $allInboundTrip->busDriver->driver_number;
                            $tripIn['service_date'] = $all_date;
                            $tripIn['start_point'] = $firstStageIn->stage_name;
                            $tripIn['service_start'] = Carbon::create($allInboundTrip->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                            $tripIn['actual_start'] = date("H:i", strtotime($allInboundTrip->start_trip));
                            $firstTicket = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                ->orderby('sales_date')
                                ->first();
                            $tripIn['sales_start'] = date("H:i", strtotime($firstTicket->sales_date));
                            $tripIn['service_end'] = Carbon::create($allInboundTrip->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                            $tripIn['actual_end'] = date("H:i", strtotime($allInboundTrip->end_trip));
                            $lastTicket = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                ->orderby('sales_date', 'DESC')
                                ->first();
                            $tripIn['sales_end'] = date("H:i", strtotime($lastTicket->sales_date));

                            $adult = $allInboundTrip->total_adult;
                            $concession = $allInboundTrip->total_concession;
                            $tripIn['passenger_count'] = $adult + $concession;

                            $adultFarebox = $allInboundTrip->total_adult_amount;
                            $concessionFarebox = $allInboundTrip->total_concession_amount;
                            $tripIn['sales_amount'] = $adultFarebox + $concessionFarebox;
                            $tripIn['total_on'] = $tripIn['passenger_count'];
                            $tripIn['adult'] = $adult;
                            $tripIn['concession'] =$concession;

                            $inbound[$countTripIn] = $tripIn;

                            $inboundPassenger += $tripIn['passenger_count'];
                            $inboundSales += $tripIn['sales_amount'];
                            $inboundAdult += $tripIn['adult'];
                            $inboundConcession += $tripIn['concession'];
                        }
                        $totalInbound['passenger_count'] = $inboundPassenger;
                        $totalInbound['sales_amount'] = $inboundSales;
                        $totalInbound['total_on'] = $inboundPassenger;
                        $totalInbound['adult'] = $inboundAdult;
                        $totalInbound['concession'] = $inboundConcession;

                        $inbound['total'] = $totalInbound;
                    }

                    //Outbound Trip
                    $allOutboundTrips = TripDetail::where('route_id', $selectedRoute->id)
                        ->whereBetween('start_trip', [$firstDate, $lastDate])
                        ->where('trip_code', 0)
                        ->get();

                    $lastStageOut = Stage::where('route_id',$selectedRoute->id)->orderby('stage_order')->first();
                    $firstStageOut = Stage::where('route_id', $selectedRoute->id)->orderby('stage_order', 'DESC')->first();
                    $routeNameOut = $firstStageOut->stage_name . " - " . $lastStageOut->stage_name;

                    $outbound = [];
                    $outboundPassenger = 0;
                    $outboundSales  = 0;
                    $outboundAdult  = 0;
                    $outboundConcession  = 0;
                    if (count($allOutboundTrips) > 0) {
                        $existOutTrip = true;
                        $countTripOut = 0;
                        foreach ($allOutboundTrips as $allOutboundTrip) {
                            $countTripOut++;
                            $out->writeln("YOU ARE IN HERE allInboundTrip loop. countTripOut: ". $countTripOut);

                            $tripOut['trip_no'] = 'T' . $countTripOut;
                            $tripOut['bus_no'] = $allOutboundTrip->bus->bus_registration_number;
                            $tripOut['driver_id'] = $allOutboundTrip->busDriver->driver_number;
                            $tripOut['service_date'] = $all_date;
                            $tripOut['start_point'] = $firstStageIn->stage_name;
                            $tripOut['service_start'] = Carbon::create($allOutboundTrip->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                            $tripOut['actual_start'] = date("H:i", strtotime($allOutboundTrip->start_trip));
                            $firstTicket = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                ->orderby('sales_date')
                                ->first();
                            $tripOut['sales_start'] = date("H:i", strtotime($firstTicket->sales_date));
                            $tripOut['service_end'] = Carbon::create($allOutboundTrip->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                            $tripOut['actual_end'] = date("H:i", strtotime($allOutboundTrip->end_trip));
                            $lastTicket = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                ->orderby('sales_date', 'DESC')
                                ->first();
                            $tripOut['sales_end'] = date("H:i", strtotime($lastTicket->sales_date));

                            $adult = $allOutboundTrip->total_adult;
                            $concession = $allOutboundTrip->total_concession;
                            $tripOut['passenger_count'] = $adult + $concession;

                            $adultFarebox = $allOutboundTrip->total_adult_amount;
                            $concessionFarebox = $allOutboundTrip->total_concession_amount;
                            $tripOut['sales_amount'] = $adultFarebox + $concessionFarebox;
                            $tripOut['total_on'] = $tripOut['passenger_count'];
                            $tripOut['adult'] = $adult;
                            $tripOut['concession'] = $concession;
                            $outbound[$countTripOut] = $tripOut;

                            $outboundPassenger += $tripOut['passenger_count'];
                            $outboundSales += $tripOut['sales_amount'];
                            $outboundAdult += $tripOut['adult'];
                            $outboundConcession += $tripOut['concession'];
                        }
                        $totalOutbound['passenger_count'] = $outboundPassenger;
                        $totalOutbound['sales_amount'] = $outboundSales;
                        $totalOutbound['total_on'] = $outboundPassenger;
                        $totalOutbound['adult'] = $outboundAdult;
                        $totalOutbound['concession'] = $outboundConcession;

                        $outbound['total'] = $totalOutbound;
                    }

                    $out->writeln("existInTrip: " . $existInTrip . " existOutTrip: " . $existOutTrip);

                    $route_data = [];
                    $sumPassengerCount = 0;
                    $sumSalesAmount = 0;
                    $sumAdult = 0;
                    $sumConcession = 0;
                    if ($existInTrip == true && $existOutTrip == true) {
                        $out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == true");
                        //$existTrip = true;
                        $sumPassengerCount = $outboundPassenger + $inboundPassenger;
                        $sumSalesAmount = $outboundSales + $inboundSales;
                        $sumAdult = $outboundAdult +$inboundAdult;
                        $sumConcession = $outboundConcession + $inboundConcession;

                        $totalPerDate['passenger_count'] = $sumPassengerCount;
                        $totalPerDate['sales_amount'] = $sumSalesAmount;
                        $totalPerDate['total_on'] = $sumPassengerCount;
                        $totalPerDate['adult'] = $sumAdult;
                        $totalPerDate['concession'] = $sumConcession;

                        $route_data[$routeNameIn] = $inbound;
                        $route_data[$routeNameOut] = $outbound;
                        $route_data['total_per_date'] = $totalPerDate;
                        $perDate[$all_date] = $route_data;

                    } elseif ($existInTrip == false && $existOutTrip == true) {
                        $out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == true");
                        //$existTrip = true;
                        $sumPassengerCount = $outboundPassenger;
                        $sumSalesAmount = $outboundSales;
                        $sumAdult = $outboundAdult;
                        $sumConcession = $outboundConcession;

                        $totalPerDate['passenger_count'] = $sumPassengerCount;
                        $totalPerDate['sales_amount'] = $sumSalesAmount;
                        $totalPerDate['total_on'] = $sumPassengerCount;
                        $totalPerDate['adult'] = $sumAdult;
                        $totalPerDate['concession'] = $sumConcession;

                        $route_data[$routeNameIn] = [];
                        $route_data[$routeNameOut] = $outbound;
                        $route_data['total_per_date'] = $totalPerDate;
                        $perDate[$all_date] = $route_data;

                    } elseif ($existInTrip == true && $existOutTrip == false) {
                        $out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == false");
                        //$existTrip = true;
                        $sumPassengerCount =  $inboundPassenger;
                        $sumSalesAmount = $inboundSales;
                        $sumAdult = $inboundAdult;
                        $sumConcession = $inboundConcession;

                        $totalPerDate['passenger_count'] = $sumPassengerCount;
                        $totalPerDate['sales_amount'] = $sumSalesAmount;
                        $totalPerDate['total_on'] = $sumPassengerCount;
                        $totalPerDate['adult'] = $sumAdult;
                        $totalPerDate['concession'] = $sumConcession;

                        $out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == true");
                        $route_data[$routeNameIn] = $inbound;
                        $route_data[$routeNameOut] = [];
                        $route_data['total_per_date'] = $totalPerDate;
                        $perDate[$all_date] = $route_data;
                    }else{
                        $out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == false");
                        $perDate[$all_date] = [];
                    }
                    $perRoutePassengerCount += $sumPassengerCount;
                    $perRouteSalesAmount += $sumSalesAmount;
                    $perRouteAdult += $sumAdult;
                    $perRouteConcession += $sumConcession;
                }
                /*if($existTrip==true){*/
                $totalPerRoute['passenger_count'] = $perRoutePassengerCount;
                $totalPerRoute['sales_amount'] = $perRouteSalesAmount;
                $totalPerRoute['total_on'] = $perRoutePassengerCount;
                $totalPerRoute['adult'] = $perRouteAdult;
                $totalPerRoute['concession'] = $perRouteConcession;

                $perDate['total_per_route'] = $totalPerRoute;

                $perRoute[$selectedRoute->route_number] = $perDate;

                $grand['passenger_count'] = $perRoutePassengerCount;
                $grand['sales_amount'] = $perRouteSalesAmount;
                $grand['total_on'] = $perRoutePassengerCount;
                $grand['adult'] = $perRouteAdult;
                $grand['concession'] = $perRouteConcession;

                $data['allRoute'] = $perRoute;
                $data['grand'] = $grand;

                $tripSPAD->add($data);
            }
            //Trip all route specific company
            else{
                $out->writeln("YOU ARE IN HERE Trip all route specific company");
                $routeByCompanies = Route::where('company_id', $this->selectedCompany)->get();

                foreach ($routeByCompanies as $routeByCompany){
                    $out->writeln("YOU ARE IN HERE routeByCompany loop");
                    $perRoutePassengerCount = 0;
                    $perRouteSalesAmount = 0;
                    $perRouteAdult = 0;
                    $perRouteConcession = 0;
                    $perDate = [];

                    foreach ($all_dates as $all_date) {
                        $existOutTrip = false;
                        $existInTrip = false;
                        $firstDate = new Carbon($all_date);
                        $lastDate = new Carbon($all_date . '11:59:59');
                        $out->writeln("YOU ARE IN HERE all_date loop. firstDate: ". $firstDate . " lastDate: " . $lastDate);

                        //Inbound Trip
                        $allInboundTrips = TripDetail::where('route_id', $routeByCompany->id)
                            ->whereBetween('start_trip', [$firstDate, $lastDate])
                            ->where('trip_code', 1)
                            ->get();

                        $firstStageIn = Stage::where('route_id', $routeByCompany->id)->orderby('stage_order')->first();
                        $lastStageIn = Stage::where('route_id', $routeByCompany->id)->orderby('stage_order', 'DESC')->first();
                        $routeNameIn = $firstStageIn->stage_name . " - " . $lastStageIn->stage_name;

                        $inbound = [];
                        $inboundPassenger = 0;
                        $inboundSales = 0;
                        $inboundAdult = 0;
                        $inboundConcession = 0;
                        if (count($allInboundTrips) > 0) {
                            $existInTrip = true;
                            $countTripIn = 0;
                            foreach ($allInboundTrips as $allInboundTrip) {
                                $countTripIn++;
                                $out->writeln("YOU ARE IN HERE allInboundTrip loop. countTripIn: ". $countTripIn);

                                $tripIn['trip_no'] = 'T' . $countTripIn;
                                $tripIn['bus_no'] = $allInboundTrip->bus->bus_registration_number;
                                $tripIn['driver_id'] = $allInboundTrip->busDriver->driver_number;
                                $tripIn['service_date'] = $all_date;
                                $tripIn['start_point'] = $firstStageIn->stage_name;
                                $tripIn['service_start'] = Carbon::create($allInboundTrip->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                $tripIn['actual_start'] = date("H:i", strtotime($allInboundTrip->start_trip));
                                $firstTicket = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                    ->orderby('sales_date')
                                    ->first();
                                $tripIn['sales_start'] = date("H:i", strtotime($firstTicket->sales_date));
                                $tripIn['service_end'] = Carbon::create($allInboundTrip->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                $tripIn['actual_end'] = date("H:i", strtotime($allInboundTrip->end_trip));
                                $lastTicket = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                    ->orderby('sales_date', 'DESC')
                                    ->first();
                                $tripIn['sales_end'] = date("H:i", strtotime($lastTicket->sales_date));

                                $adult = $allInboundTrip->total_adult;
                                $concession = $allInboundTrip->total_concession;
                                $tripIn['passenger_count'] = $adult + $concession;

                                $adultFarebox = $allInboundTrip->total_adult_amount;
                                $concessionFarebox = $allInboundTrip->total_concession_amount;
                                $tripIn['sales_amount'] = $adultFarebox + $concessionFarebox;
                                $tripIn['total_on'] = $tripIn['passenger_count'];
                                $tripIn['adult'] = $adult;
                                $tripIn['concession'] = $concession;
                                $inbound[$countTripIn] = $tripIn;

                                $inboundPassenger += $tripIn['passenger_count'];
                                $inboundSales += $tripIn['sales_amount'];
                                $inboundAdult += $tripIn['adult'];
                                $inboundConcession += $tripIn['concession'];
                            }
                            $totalInbound['passenger_count'] = $inboundPassenger;
                            $totalInbound['sales_amount'] = $inboundSales;
                            $totalInbound['total_on'] = $inboundPassenger;
                            $totalInbound['adult'] = $inboundAdult;
                            $totalInbound['concession'] = $inboundConcession;

                            $inbound['total'] = $totalInbound;
                        }

                        //Outbound Trip
                        $allOutboundTrips = TripDetail::where('route_id', $routeByCompany->id)
                            ->whereBetween('start_trip', [$firstDate, $lastDate])
                            ->where('trip_code', 0)
                            ->get();

                        $lastStageOut = Stage::where('route_id', $routeByCompany->id)->orderby('stage_order')->first();
                        $firstStageOut = Stage::where('route_id', $routeByCompany->id)->orderby('stage_order', 'DESC')->first();
                        $routeNameOut = $firstStageOut->stage_name . " - " . $lastStageOut->stage_name;

                        $outbound = [];
                        $outboundPassenger = 0;
                        $outboundSales  = 0;
                        $outboundAdult  = 0;
                        $outboundConcession  = 0;
                        if (count($allOutboundTrips) > 0) {
                            $existOutTrip = true;
                            $countTripOut = 0;
                            foreach ($allOutboundTrips as $allOutboundTrip) {
                                $countTripOut++;
                                $out->writeln("YOU ARE IN HERE allInboundTrip loop. countTripOut: ". $countTripOut);

                                $tripOut['trip_no'] = 'T' . $countTripOut;
                                $tripOut['bus_no'] = $allOutboundTrip->bus->bus_registration_number;
                                $tripOut['driver_id'] = $allOutboundTrip->busDriver->driver_number;
                                $tripOut['service_date'] = $all_date;
                                $tripOut['start_point'] = $firstStageIn->stage_name;
                                $tripOut['service_start'] = Carbon::create($allOutboundTrip->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                $tripOut['actual_start'] = date("H:i", strtotime($allOutboundTrip->start_trip));
                                $firstTicket = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                    ->orderby('sales_date')
                                    ->first();
                                $tripOut['sales_start'] = date("H:i", strtotime($firstTicket->sales_date));
                                $tripOut['service_end'] = Carbon::create($allOutboundTrip->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                $tripOut['actual_end'] = date("H:i", strtotime($allOutboundTrip->end_trip));
                                $lastTicket = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                    ->orderby('sales_date', 'DESC')
                                    ->first();
                                $tripOut['sales_end'] = date("H:i", strtotime($lastTicket->sales_date));

                                $adult = $allOutboundTrip->total_adult;
                                $concession = $allOutboundTrip->total_concession;
                                $tripOut['passenger_count'] = $adult + $concession;

                                $adultFarebox = $allOutboundTrip->total_adult_amount;
                                $concessionFarebox = $allOutboundTrip->total_concession_amount;
                                $tripOut['sales_amount'] = $adultFarebox + $concessionFarebox;
                                $tripOut['total_on'] = $tripOut['passenger_count'];
                                $tripOut['adult'] = $adult;
                                $tripOut['concession'] = $concession;
                                $outbound[$countTripOut] = $tripOut;

                                $outboundPassenger += $tripOut['passenger_count'];
                                $outboundSales += $tripOut['sales_amount'];
                                $outboundAdult += $tripOut['adult'];
                                $outboundConcession += $tripOut['concession'];
                            }
                            $totalOutbound['passenger_count'] = $outboundPassenger;
                            $totalOutbound['sales_amount'] = $outboundSales;
                            $totalOutbound['total_on'] = $outboundPassenger;
                            $totalOutbound['adult'] = $outboundAdult;
                            $totalOutbound['concession'] = $outboundConcession;

                            $outbound['total'] = $totalOutbound;
                        }

                        $out->writeln("existInTrip: " . $existInTrip . " existOutTrip: " . $existOutTrip);

                        $route_data = [];
                        $sumPassengerCount = 0;
                        $sumSalesAmount = 0;
                        $sumAdult = 0;
                        $sumConcession = 0;
                        if ($existInTrip == true && $existOutTrip == true) {
                            $out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == true");
                            //$existTrip = true;
                            $sumPassengerCount = $outboundPassenger + $inboundPassenger;
                            $sumSalesAmount = $outboundSales + $inboundSales;
                            $sumAdult = $outboundAdult +$inboundAdult;
                            $sumConcession = $outboundConcession + $inboundConcession;

                            $totalPerDate['passenger_count'] = $sumPassengerCount;
                            $totalPerDate['sales_amount'] = $sumSalesAmount;
                            $totalPerDate['total_on'] = $sumPassengerCount;
                            $totalPerDate['adult'] = $sumAdult;
                            $totalPerDate['concession'] = $sumConcession;

                            $route_data[$routeNameIn] = $inbound;
                            $route_data[$routeNameOut] = $outbound;
                            $route_data['total_per_date'] = $totalPerDate;
                            $perDate[$all_date] = $route_data;

                        } elseif ($existInTrip == false && $existOutTrip == true) {
                            $out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == true");
                            //$existTrip = true;
                            $sumPassengerCount = $outboundPassenger;
                            $sumSalesAmount = $outboundSales;
                            $sumAdult = $outboundAdult;
                            $sumConcession = $outboundConcession;

                            $totalPerDate['passenger_count'] = $sumPassengerCount;
                            $totalPerDate['sales_amount'] = $sumSalesAmount;
                            $totalPerDate['total_on'] = $sumPassengerCount;
                            $totalPerDate['adult'] = $sumAdult;
                            $totalPerDate['concession'] = $sumConcession;

                            $route_data[$routeNameIn] = [];
                            $route_data[$routeNameOut] = $outbound;
                            $route_data['total_per_date'] = $totalPerDate;
                            $perDate[$all_date] = $route_data;

                        } elseif ($existInTrip == true && $existOutTrip == false) {
                            $out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == false");
                            //$existTrip = true;
                            $sumPassengerCount =  $inboundPassenger;
                            $sumSalesAmount = $inboundSales;
                            $sumAdult = $inboundAdult;
                            $sumConcession = $inboundConcession;

                            $totalPerDate['passenger_count'] = $sumPassengerCount;
                            $totalPerDate['sales_amount'] = $sumSalesAmount;
                            $totalPerDate['total_on'] = $sumPassengerCount;
                            $totalPerDate['adult'] = $sumAdult;
                            $totalPerDate['concession'] = $sumConcession;

                            $out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == true");
                            $route_data[$routeNameIn] = $inbound;
                            $route_data[$routeNameOut] = [];
                            $route_data['total_per_date'] = $totalPerDate;
                            $perDate[$all_date] = $route_data;
                        }else{
                            $out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == false");
                            $perDate[$all_date] = [];
                        }
                        $perRoutePassengerCount += $sumPassengerCount;
                        $perRouteSalesAmount += $sumSalesAmount;
                        $perRouteAdult += $sumAdult;
                        $perRouteConcession += $sumConcession;
                    }
                    /*if($existTrip==true){*/
                    $totalPerRoute['passenger_count'] = $perRoutePassengerCount;
                    $totalPerRoute['sales_amount'] = $perRouteSalesAmount;
                    $totalPerRoute['total_on'] = $perRoutePassengerCount;
                    $totalPerRoute['adult'] = $perRouteAdult;
                    $totalPerRoute['concession'] = $perRouteConcession;

                    $perDate['total_per_route'] = $totalPerRoute;

                    $perRoute[$routeByCompany->route_number] = $perDate;

                    $grandPassengerCount += $perRoutePassengerCount;
                    $grandSalesAmount += $perRouteSalesAmount;
                    $grandAdult += $perRouteAdult;
                    $grandConcession += $perRouteConcession;
                }
                $grand['passenger_count'] = $grandPassengerCount;
                $grand['sales_amount'] = $grandSalesAmount;
                $grand['total_on'] = $grandPassengerCount;
                $grand['adult'] = $grandAdult;
                $grand['concession'] = $grandConcession;

                $data['allRoute'] = $perRoute;
                $data['grand'] = $grand;

                $tripSPAD->add($data);
            }
        }
        //Trip all route all company
        else{
            $networkArea = 'All';
            $out->writeln("YOU ARE IN HERE Trip all route all company");
            $allRoutes = Route::all();
            $grandPassengerCount = 0;
            $grandSalesAmount  = 0;
            $grandAdult  = 0;
            $grandConcession  = 0;
            $perRoute = [];

            foreach ($allRoutes as $allRoute){
                $out->writeln("YOU ARE IN HERE allRoute loop");
                $perRoutePassengerCount = 0;
                $perRouteSalesAmount = 0;
                $perRouteAdult = 0;
                $perRouteConcession = 0;
                $perDate = [];

                foreach ($all_dates as $all_date) {
                    $existOutTrip = false;
                    $existInTrip = false;
                    $firstDate = new Carbon($all_date);
                    $lastDate = new Carbon($all_date . '11:59:59');
                    $out->writeln("YOU ARE IN HERE all_date loop. firstDate: ". $firstDate . " lastDate: " . $lastDate);

                    //Inbound Trip
                    $allInboundTrips = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$firstDate, $lastDate])
                        ->where('trip_code', 1)
                        ->get();

                    $firstStageIn = Stage::where('route_id', $allRoute->id)->orderby('stage_order')->first();
                    $lastStageIn = Stage::where('route_id', $allRoute->id)->orderby('stage_order', 'DESC')->first();
                    $routeNameIn = $firstStageIn->stage_name . " - " . $lastStageIn->stage_name;

                    $inbound = [];
                    $inboundPassenger = 0;
                    $inboundSales = 0;
                    $inboundAdult = 0;
                    $inboundConcession = 0;
                    if (count($allInboundTrips) > 0) {
                        $existInTrip = true;
                        $countTripIn = 0;
                        foreach ($allInboundTrips as $allInboundTrip) {
                            $countTripIn++;
                            $out->writeln("YOU ARE IN HERE allInboundTrip loop. countTripIn: ". $countTripIn);

                            $tripIn['trip_no'] = 'T' . $countTripIn;
                            $tripIn['bus_no'] = $allInboundTrip->bus->bus_registration_number;
                            $tripIn['driver_id'] = $allInboundTrip->busDriver->driver_number;
                            $tripIn['service_date'] = $all_date;
                            $tripIn['start_point'] = $firstStageIn->stage_name;
                            $tripIn['service_start'] = Carbon::create($allInboundTrip->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                            $tripIn['actual_start'] = date("H:i", strtotime($allInboundTrip->start_trip));
                            $firstTicket = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                ->orderby('sales_date')
                                ->first();
                            $tripIn['sales_start'] = date("H:i", strtotime($firstTicket->sales_date));
                            $tripIn['service_end'] = Carbon::create($allInboundTrip->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                            $tripIn['actual_end'] = date("H:i", strtotime($allInboundTrip->end_trip));
                            $lastTicket = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                ->orderby('sales_date', 'DESC')
                                ->first();
                            $tripIn['sales_end'] = date("H:i", strtotime($lastTicket->sales_date));

                            $adult = $allInboundTrip->total_adult;
                            $concession = $allInboundTrip->total_concession;
                            $tripIn['passenger_count'] = $adult + $concession;

                            $adultFarebox = $allInboundTrip->total_adult_amount;
                            $concessionFarebox = $allInboundTrip->total_concession_amount;
                            $tripIn['sales_amount'] = $adultFarebox + $concessionFarebox;
                            $tripIn['total_on'] = $tripIn['passenger_count'];
                            $tripIn['adult'] = $adult;
                            $tripIn['concession'] = $concession;

                            $inbound[$countTripIn] = $tripIn;

                            $inboundPassenger += $tripIn['passenger_count'];
                            $inboundSales += $tripIn['sales_amount'];
                            $inboundAdult += $tripIn['adult'];
                            $inboundConcession += $tripIn['concession'];
                        }
                        $totalInbound['passenger_count'] = $inboundPassenger;
                        $totalInbound['sales_amount'] = $inboundSales;
                        $totalInbound['total_on'] = $inboundPassenger;
                        $totalInbound['adult'] = $inboundAdult;
                        $totalInbound['concession'] = $inboundConcession;

                        $inbound['total'] = $totalInbound;
                    }

                    //Outbound Trip
                    $allOutboundTrips = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$firstDate, $lastDate])
                        ->where('trip_code', 0)
                        ->get();

                    $lastStageOut = Stage::where('route_id', $allRoute->id)->orderby('stage_order')->first();
                    $firstStageOut = Stage::where('route_id', $allRoute->id)->orderby('stage_order', 'DESC')->first();
                    $routeNameOut = $firstStageOut->stage_name . " - " . $lastStageOut->stage_name;

                    $outbound = [];
                    $outboundPassenger = 0;
                    $outboundSales  = 0;
                    $outboundAdult  = 0;
                    $outboundConcession  = 0;
                    if (count($allOutboundTrips) > 0) {
                        $existOutTrip = true;
                        $countTripOut = 0;
                        foreach ($allOutboundTrips as $allOutboundTrip) {
                            $countTripOut++;
                            $out->writeln("YOU ARE IN HERE allInboundTrip loop. countTripOut: ". $countTripOut);

                            $tripOut['trip_no'] = 'T' . $countTripOut;
                            $tripOut['bus_no'] = $allOutboundTrip->bus->bus_registration_number;
                            $tripOut['driver_id'] = $allOutboundTrip->busDriver->driver_number;
                            $tripOut['service_date'] = $all_date;
                            $tripOut['start_point'] = $firstStageIn->stage_name;
                            $tripOut['service_start'] = Carbon::create($allOutboundTrip->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                            $tripOut['actual_start'] = date("H:i", strtotime($allOutboundTrip->start_trip));
                            $firstTicket = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                ->orderby('sales_date')
                                ->first();
                            $tripOut['sales_start'] = date("H:i", strtotime($firstTicket->sales_date));
                            $tripOut['service_end'] = Carbon::create($allOutboundTrip->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                            $tripOut['actual_end'] = date("H:i", strtotime($allOutboundTrip->end_trip));
                            $lastTicket = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                ->orderby('sales_date', 'DESC')
                                ->first();
                            $tripOut['sales_end'] = date("H:i", strtotime($lastTicket->sales_date));

                            $adult = $allOutboundTrip->total_adult;
                            $concession = $allOutboundTrip->total_concession;
                            $tripOut['passenger_count'] = $adult + $concession;

                            $adultFarebox = $allOutboundTrip->total_adult_amount;
                            $concessionFarebox = $allOutboundTrip->total_concession_amount;
                            $tripOut['sales_amount'] = $adultFarebox + $concessionFarebox;

                            $tripOut['total_on'] = $tripOut['passenger_count'];
                            $tripOut['adult'] = $adult;
                            $tripOut['concession'] = $concession;
                            $outbound[$countTripOut] = $tripOut;

                            $outboundPassenger += $tripOut['passenger_count'];
                            $outboundSales += $tripOut['sales_amount'];
                            $outboundAdult += $tripOut['adult'];
                            $outboundConcession += $tripOut['concession'];
                        }
                        $totalOutbound['passenger_count'] = $outboundPassenger;
                        $totalOutbound['sales_amount'] = $outboundSales;
                        $totalOutbound['total_on'] = $outboundPassenger;
                        $totalOutbound['adult'] = $outboundAdult;
                        $totalOutbound['concession'] = $outboundConcession;

                        $outbound['total'] = $totalOutbound;
                    }

                    $out->writeln("existInTrip: " . $existInTrip . " existOutTrip: " . $existOutTrip);

                    $route_data = [];
                    $sumPassengerCount = 0;
                    $sumSalesAmount = 0;
                    $sumAdult = 0;
                    $sumConcession = 0;
                    if ($existInTrip == true && $existOutTrip == true) {
                        $out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == true");
                        //$existTrip = true;
                        $sumPassengerCount = $outboundPassenger + $inboundPassenger;
                        $sumSalesAmount = $outboundSales + $inboundSales;
                        $sumAdult = $outboundAdult +$inboundAdult;
                        $sumConcession = $outboundConcession + $inboundConcession;

                        $totalPerDate['passenger_count'] = $sumPassengerCount;
                        $totalPerDate['sales_amount'] = $sumSalesAmount;
                        $totalPerDate['total_on'] = $sumPassengerCount;
                        $totalPerDate['adult'] = $sumAdult;
                        $totalPerDate['concession'] = $sumConcession;

                        $route_data[$routeNameIn] = $inbound;
                        $route_data[$routeNameOut] = $outbound;
                        $route_data['total_per_date'] = $totalPerDate;
                        $perDate[$all_date] = $route_data;

                    } elseif ($existInTrip == false && $existOutTrip == true) {
                        $out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == true");
                        //$existTrip = true;
                        $sumPassengerCount = $outboundPassenger;
                        $sumSalesAmount = $outboundSales;
                        $sumAdult = $outboundAdult;
                        $sumConcession = $outboundConcession;

                        $totalPerDate['passenger_count'] = $sumPassengerCount;
                        $totalPerDate['sales_amount'] = $sumSalesAmount;
                        $totalPerDate['total_on'] = $sumPassengerCount;
                        $totalPerDate['adult'] = $sumAdult;
                        $totalPerDate['concession'] = $sumConcession;

                        $route_data[$routeNameIn] = [];
                        $route_data[$routeNameOut] = $outbound;
                        $route_data['total_per_date'] = $totalPerDate;
                        $perDate[$all_date] = $route_data;

                    } elseif ($existInTrip == true && $existOutTrip == false) {
                        $out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == false");
                        //$existTrip = true;
                        $sumPassengerCount =  $inboundPassenger;
                        $sumSalesAmount = $inboundSales;
                        $sumAdult = $inboundAdult;
                        $sumConcession = $inboundConcession;

                        $totalPerDate['passenger_count'] = $sumPassengerCount;
                        $totalPerDate['sales_amount'] = $sumSalesAmount;
                        $totalPerDate['total_on'] = $sumPassengerCount;
                        $totalPerDate['adult'] = $sumAdult;
                        $totalPerDate['concession'] = $sumConcession;

                        $out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == true");
                        $route_data[$routeNameIn] = $inbound;
                        $route_data[$routeNameOut] = [];
                        $route_data['total_per_date'] = $totalPerDate;
                        $perDate[$all_date] = $route_data;
                    }else{
                        $out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == false");
                        $perDate[$all_date] = [];
                    }
                    $perRoutePassengerCount += $sumPassengerCount;
                    $perRouteSalesAmount += $sumSalesAmount;
                    $perRouteAdult += $sumAdult;
                    $perRouteConcession += $sumConcession;
                }
                /*if($existTrip==true){*/
                $totalPerRoute['passenger_count'] = $perRoutePassengerCount;
                $totalPerRoute['sales_amount'] = $perRouteSalesAmount;
                $totalPerRoute['total_on'] = $perRoutePassengerCount;
                $totalPerRoute['adult'] = $perRouteAdult;
                $totalPerRoute['concession'] = $perRouteConcession;

                $perDate['total_per_route'] = $totalPerRoute;

                $perRoute[$allRoute->route_number] = $perDate;

                $grandPassengerCount += $perRoutePassengerCount;
                $grandSalesAmount += $perRouteSalesAmount;
                $grandAdult += $perRouteAdult;
                $grandConcession += $perRouteConcession;
            }

            $grand['passenger_count'] = $grandPassengerCount;
            $grand['sales_amount'] = $grandSalesAmount;
            $grand['total_on'] = $grandPassengerCount;
            $grand['adult'] = $grandAdult;
            $grand['concession'] = $grandConcession;

            $data['allRoute'] = $perRoute;
            $data['grand'] = $grand;

            $tripSPAD->add($data);
        }

        return Excel::download(new SPADTrip($tripSPAD, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Trip_Report_SPAD.xlsx');
    }

    public function printTopBoardings()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printTopBoarding()");
        $data = [];

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['int'],
        ])->validate();

        $out->writeln("datefrom:" . $validatedData['dateFrom']);
        $out->writeln("dateto:" . $validatedData['dateTo']);

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo'] . '11:59:59');

        $topBoardingSPAD = collect();

        if($this->selectedCompany) {
            $grandTotalOnIn = 0;
            $grandAdultIn = 0;
            $grandConcessionIn = 0;
            $grandTotalOnOut = 0;
            $grandAdultOut = 0;
            $grandConcessionOut = 0;
            $perRoute = [];

            //Top Boarding route specific company
            $selectedCompany = Company::where('id', $this->selectedCompany)->first();
            $networkArea = $selectedCompany->company_name;

            if (!empty($this->state['route_id'])) {
                $out->writeln("YOU ARE IN HERE Top Boarding route specific company");
                $validatedRoute = Route::where('id', $validatedData['route_id'])->first();

                $allBusStands = BusStand::where('route_id', $validatedRoute->id)->get();
                $totalTotalOnIn = 0;
                $totalAdultIn = 0;
                $totalConcessionIn = 0;
                $totalTotalOnOut = 0;
                $totalAdultOut = 0;
                $totalConcessionOut = 0;
                $perBusStand = [];

                foreach ($allBusStands as $allBusStand) {
                    $out->writeln(" allBusStand: " . $allBusStand->description);
                    $trip_data = [];

                    //Inbound Trip
                    $allInboundTrips = TripDetail::where('route_id', $validatedRoute->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->where('trip_code', 1)
                        ->get();

                    $countIn = 0;
                    $adultIn = 0;
                    $concessionIn = 0;
                    if (count($allInboundTrips) > 0) {
                        foreach ($allInboundTrips as $allInboundTrip) {
                            $ticketInTrips = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                ->where('bus_stand_id', $allBusStand->id)
                                ->get();

                            if (count($ticketInTrips) > 0) {
                                foreach ($ticketInTrips as $ticketInTrip) {
                                    $countIn++;
                                    if ($ticketInTrip->passenger_type == 0) {
                                        $adultIn++;
                                    } elseif ($ticketInTrip->passenger_type == 1) {
                                        $concessionIn++;
                                    }
                                }
                            }
                        }
                    }

                    //Outbound Trip
                    $allOutboundTrips = TripDetail::where('route_id', $validatedRoute->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->where('trip_code', 0)
                        ->get();

                    $countOut = 0;
                    $adultOut = 0;
                    $concessionOut = 0;
                    if (count($allInboundTrips) > 0) {
                        foreach ($allOutboundTrips as $allOutboundTrip) {
                            $ticketOutTrips = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                ->where('bus_stand_id', $allBusStand->id)
                                ->get();

                            if (count($ticketOutTrips) > 0) {
                                foreach ($ticketOutTrips as $ticketOutTrip) {
                                    $countOut++;
                                    if ($ticketOutTrip->passenger_type == 0) {
                                        $adultOut++;
                                    } elseif ($ticketOutTrip->passenger_type == 1) {
                                        $concessionOut++;
                                    }
                                }
                            }
                        }
                    }
                    $trip_data['total_on_in'] = $countIn;
                    $trip_data['adult_in'] = $adultIn;
                    $trip_data['concession_in'] = $concessionIn;
                    $trip_data['total_on_out'] = $countOut;
                    $trip_data['adult_out'] = $adultOut;
                    $trip_data['concession_out'] = $concessionOut;

                    $perBusStand[$allBusStand->description] = $trip_data;

                    $totalTotalOnIn += $countIn;
                    $totalAdultIn += $adultIn;
                    $totalConcessionIn += $concessionIn;
                    $totalTotalOnOut += $countOut;
                    $totalAdultOut += $adultOut;
                    $totalConcessionOut += $concessionOut;
                }

                if ($totalTotalOnIn == 0 && $totalAdultIn == 0 && $totalConcessionIn == 0 && $totalTotalOnOut == 0 && $totalAdultOut == 0 && $totalConcessionOut == 0) {
                    //$out->writeln("NO TOTAL");
                    $perRoute[$validatedRoute->route_number] = [];
                } else {
                    $total_in = [];
                    foreach ($perBusStand as $key => $row) {
                        //$out->writeln("You are in loop array multisort");
                        $total_in[$key] = $row['total_on_in'];
                    }
                    array_multisort($total_in, SORT_DESC, $perBusStand);

                    $total['total_on_in'] = $totalTotalOnIn;
                    $total['adult_in'] = $totalAdultIn;
                    $total['concession_in'] = $totalConcessionIn;
                    $total['total_on_out'] = $totalTotalOnOut;
                    $total['adult_out'] = $totalAdultOut;
                    $total['concession_out'] = $totalConcessionOut;

                    $perBusStand['total_per_route'] = $total;
                    $perRoute[$validatedRoute->route_number] = $perBusStand;
                }

                $grandTotalOnIn = $totalTotalOnIn;
                $grandAdultIn = $totalAdultIn;
                $grandConcessionIn = $totalConcessionIn;
                $grandTotalOnOut = $totalTotalOnOut;
                $grandAdultOut = $totalAdultOut;
                $grandConcessionOut = $totalConcessionOut;

                $grand['total_on_in'] = $grandTotalOnIn;
                $grand['adult_in'] = $grandAdultIn;
                $grand['concession_in'] = $grandConcessionIn;
                $grand['total_on_out'] = $grandTotalOnOut;
                $grand['adult_out'] = $grandAdultOut;
                $grand['concession_out'] = $grandConcessionOut;

                $data['allRoute'] = $perRoute;
                $data['grand'] = $grand;
                $topBoardingSPAD->add($data);
            } //Top Boarding all route specific company
            else {
                $out->writeln("YOU ARE IN HERE Top Boarding all route specific company");
                $routeByCompanies = Route::where('company_id', $this->selectedCompany)->get();
                $grandTotalOnIn = 0;
                $grandAdultIn = 0;
                $grandConcessionIn = 0;
                $grandTotalOnOut = 0;
                $grandAdultOut = 0;
                $grandConcessionOut = 0;
                $perRoute = [];

                foreach ($routeByCompanies as $routeByCompany) {
                    $out->writeln("allRoute: " . $routeByCompany->route_number);
                    $allBusStands = BusStand::where('route_id', $routeByCompany->id)->get();
                    $totalTotalOnIn = 0;
                    $totalAdultIn = 0;
                    $totalConcessionIn = 0;
                    $totalTotalOnOut = 0;
                    $totalAdultOut = 0;
                    $totalConcessionOut = 0;
                    $perBusStand = [];

                    foreach ($allBusStands as $allBusStand) {
                        $out->writeln(" allBusStand: " . $allBusStand->description);
                        $trip_data = [];

                        //Inbound Trip
                        $allInboundTrips = TripDetail::where('route_id', $routeByCompany->id)
                            ->whereBetween('start_trip', [$dateFrom, $dateTo])
                            ->where('trip_code', 1)
                            ->get();

                        $countIn = 0;
                        $adultIn = 0;
                        $concessionIn = 0;
                        if (count($allInboundTrips) > 0) {
                            foreach ($allInboundTrips as $allInboundTrip) {
                                $ticketInTrips = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                    ->where('bus_stand_id', $allBusStand->id)
                                    ->get();

                                if (count($ticketInTrips) > 0) {
                                    foreach ($ticketInTrips as $ticketInTrip) {
                                        $countIn++;
                                        if ($ticketInTrip->passenger_type == 0) {
                                            $adultIn++;
                                        } elseif ($ticketInTrip->passenger_type == 1) {
                                            $concessionIn++;
                                        }
                                    }
                                }
                            }
                        }

                        //Outbound Trip
                        $allOutboundTrips = TripDetail::where('route_id', $routeByCompany->id)
                            ->whereBetween('start_trip', [$dateFrom, $dateTo])
                            ->where('trip_code', 0)
                            ->get();

                        $countOut = 0;
                        $adultOut = 0;
                        $concessionOut = 0;
                        if (count($allInboundTrips) > 0) {
                            foreach ($allOutboundTrips as $allOutboundTrip) {
                                $ticketOutTrips = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                    ->where('bus_stand_id', $allBusStand->id)
                                    ->get();

                                if (count($ticketOutTrips) > 0) {
                                    foreach ($ticketOutTrips as $ticketOutTrip) {
                                        $countOut++;
                                        if ($ticketOutTrip->passenger_type == 0) {
                                            $adultOut++;
                                        } elseif ($ticketOutTrip->passenger_type == 1) {
                                            $concessionOut++;
                                        }
                                    }
                                }
                            }
                        }
                        $trip_data['total_on_in'] = $countIn;
                        $trip_data['adult_in'] = $adultIn;
                        $trip_data['concession_in'] = $concessionIn;
                        $trip_data['total_on_out'] = $countOut;
                        $trip_data['adult_out'] = $adultOut;
                        $trip_data['concession_out'] = $concessionOut;

                        $perBusStand[$allBusStand->description] = $trip_data;

                        $totalTotalOnIn += $countIn;
                        $totalAdultIn += $adultIn;
                        $totalConcessionIn += $concessionIn;
                        $totalTotalOnOut += $countOut;
                        $totalAdultOut += $adultOut;
                        $totalConcessionOut += $concessionOut;
                    }

                    if ($totalTotalOnIn == 0 && $totalAdultIn == 0 && $totalConcessionIn == 0 && $totalTotalOnOut == 0 && $totalAdultOut == 0 && $totalConcessionOut == 0) {
                        //$out->writeln("NO TOTAL");
                        $perRoute[$routeByCompany->route_number] = [];
                    } else {
                        $total_in = [];
                        foreach ($perBusStand as $key => $row) {
                            //$out->writeln("You are in loop array multisort");
                            $total_in[$key] = $row['total_on_in'];
                        }
                        array_multisort($total_in, SORT_DESC, $perBusStand);

                        $total['total_on_in'] = $totalTotalOnIn;
                        $total['adult_in'] = $totalAdultIn;
                        $total['concession_in'] = $totalConcessionIn;
                        $total['total_on_out'] = $totalTotalOnOut;
                        $total['adult_out'] = $totalAdultOut;
                        $total['concession_out'] = $totalConcessionOut;

                        $perBusStand['total_per_route'] = $total;
                        $perRoute[$routeByCompany->route_number] = $perBusStand;
                    }

                    $grandTotalOnIn += $totalTotalOnIn;
                    $grandAdultIn += $totalAdultIn;
                    $grandConcessionIn += $totalConcessionIn;
                    $grandTotalOnOut += $totalTotalOnOut;
                    $grandAdultOut += $totalAdultOut;
                    $grandConcessionOut += $totalConcessionOut;
                }
                $grand['total_on_in'] = $grandTotalOnIn;
                $grand['adult_in'] = $grandAdultIn;
                $grand['concession_in'] = $grandConcessionIn;
                $grand['total_on_out'] = $grandTotalOnOut;
                $grand['adult_out'] = $grandAdultOut;
                $grand['concession_out'] = $grandConcessionOut;

                $data['allRoute'] = $perRoute;
                $data['grand'] = $grand;
                $topBoardingSPAD->add($data);
            }
        }//Top Boarding all route all company
        else{
            $networkArea = 'All';
            $out->writeln("YOU ARE IN HERE Top Boarding all route all company");
            $allRoutes = Route::all();
            $grandTotalOnIn = 0;
            $grandAdultIn = 0;
            $grandConcessionIn = 0;
            $grandTotalOnOut = 0;
            $grandAdultOut = 0;
            $grandConcessionOut = 0;
            $perRoute = [];

            foreach($allRoutes as $allRoute) {
                $out->writeln("allRoute: " . $allRoute->route_number);
                $allBusStands = BusStand::where('route_id', $allRoute->id)->get();
                $totalTotalOnIn = 0;
                $totalAdultIn = 0;
                $totalConcessionIn = 0;
                $totalTotalOnOut = 0;
                $totalAdultOut = 0;
                $totalConcessionOut = 0;
                $perBusStand = [];

                foreach ($allBusStands as $allBusStand) {
                    $out->writeln(" allBusStand: " .  $allBusStand->description);
                    $trip_data = [];

                    //Inbound Trip
                    $allInboundTrips = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->where('trip_code', 1)
                        ->get();

                    $countIn = 0;
                    $adultIn = 0;
                    $concessionIn = 0;
                    if (count($allInboundTrips) > 0) {
                        foreach ($allInboundTrips as $allInboundTrip) {
                            $ticketInTrips = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                ->where('bus_stand_id', $allBusStand->id)
                                ->get();

                            if (count($ticketInTrips) > 0) {
                                foreach ($ticketInTrips as $ticketInTrip) {
                                    $countIn++;
                                    if ($ticketInTrip->passenger_type == 0) {
                                        $adultIn++;
                                    } elseif ($ticketInTrip->passenger_type == 1) {
                                        $concessionIn++;
                                    }
                                }
                            }
                        }
                    }

                    //Outbound Trip
                    $allOutboundTrips = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->where('trip_code', 0)
                        ->get();

                    $countOut = 0;
                    $adultOut = 0;
                    $concessionOut = 0;
                    if (count($allInboundTrips) > 0) {
                        foreach ($allOutboundTrips as $allOutboundTrip) {
                            $ticketOutTrips = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                ->where('bus_stand_id', $allBusStand->id)
                                ->get();

                            if (count($ticketOutTrips) > 0) {
                                foreach ($ticketOutTrips as $ticketOutTrip) {
                                    $countOut++;
                                    if ($ticketOutTrip->passenger_type == 0) {
                                        $adultOut++;
                                    } elseif ($ticketOutTrip->passenger_type == 1) {
                                        $concessionOut++;
                                    }
                                }
                            }
                        }
                    }
                    $trip_data['total_on_in'] = $countIn;
                    $trip_data['adult_in'] = $adultIn;
                    $trip_data['concession_in'] = $concessionIn;
                    $trip_data['total_on_out'] = $countOut;
                    $trip_data['adult_out'] = $adultOut;
                    $trip_data['concession_out'] = $concessionOut;

                    $perBusStand[$allBusStand->description] = $trip_data;

                    $totalTotalOnIn += $countIn;
                    $totalAdultIn += $adultIn;
                    $totalConcessionIn += $concessionIn;
                    $totalTotalOnOut += $countOut;
                    $totalAdultOut += $adultOut;
                    $totalConcessionOut += $concessionOut;
                }

                if ($totalTotalOnIn == 0 && $totalAdultIn == 0 && $totalConcessionIn == 0 && $totalTotalOnOut == 0 && $totalAdultOut == 0 && $totalConcessionOut == 0) {
                    $out->writeln("NO TOTAL");
                    $perRoute[$allRoute->route_number] = [];
                } else{
                    $total_in = [];
                    foreach ($perBusStand as $key => $row) {
                        $out->writeln("You are in loop array multisort");
                        $total_in[$key] = $row['total_on_in'];
                    }
                    array_multisort($total_in, SORT_DESC, $perBusStand);

                    $total['total_on_in'] = $totalTotalOnIn;
                    $total['adult_in'] = $totalAdultIn;
                    $total['concession_in'] = $totalConcessionIn;
                    $total['total_on_out'] = $totalTotalOnOut;
                    $total['adult_out'] = $totalAdultOut;
                    $total['concession_out'] = $totalConcessionOut;

                    $perBusStand['total_per_route'] = $total;
                    $perRoute[$allRoute->route_number] = $perBusStand;
                }

                $grandTotalOnIn += $totalTotalOnIn;
                $grandAdultIn += $totalAdultIn;
                $grandConcessionIn += $totalConcessionIn;
                $grandTotalOnOut += $totalTotalOnOut;
                $grandAdultOut += $totalAdultOut;
                $grandConcessionOut += $totalConcessionOut;
            }
            $grand['total_on_in'] = $grandTotalOnIn;
            $grand['adult_in'] = $grandAdultIn;
            $grand['concession_in'] = $grandConcessionIn;
            $grand['total_on_out'] = $grandTotalOnOut;
            $grand['adult_out'] = $grandAdultOut;
            $grand['concession_out'] = $grandConcessionOut;

            $data['allRoute'] = $perRoute;
            $data['grand'] = $grand;
            $topBoardingSPAD->add($data);
        }

        return Excel::download(new SPADTopBoarding($topBoardingSPAD, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Top_Boarding_Report_SPAD.xlsx');
    }

    public function printTopAlighting()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printTopAlighting()");
        $data = [];

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['int'],
        ])->validate();

        $out->writeln("datefrom:" . $validatedData['dateFrom']);
        $out->writeln("dateto:" . $validatedData['dateTo']);

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo'] . '11:59:59');

        $topAlightingSPAD = collect();

        if($this->selectedCompany) {
            $grandTotalOnIn = 0;
            $grandAdultIn = 0;
            $grandConcessionIn = 0;
            $grandTotalOnOut = 0;
            $grandAdultOut = 0;
            $grandConcessionOut = 0;
            $perRoute = [];

            //Top Alighting specific route specific company
            $companyDetails = Company::where('id', $this->selectedCompany)->first();
            $networkArea = $companyDetails->company_name;

            if (!empty($this->state['route_id'])) {
                $out->writeln("YOU ARE IN HERE Top Alighting specific route specific company");
                $validatedRoute = Route::where('id', $validatedData['route_id'])->first();

                $allStages = Stage::where('route_id', $validatedRoute->id)->get();
                $totalTotalOnIn = 0;
                $totalAdultIn = 0;
                $totalConcessionIn = 0;
                $totalTotalOnOut = 0;
                $totalAdultOut = 0;
                $totalConcessionOut = 0;
                $perStage = [];

                foreach ($allStages as $allStage) {
                    $trip_data = [];

                    //Inbound Trip
                    $allInboundTrips = TripDetail::where('route_id', $validatedRoute->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->where('trip_code', 1)
                        ->get();

                    $countIn = 0;
                    $adultIn = 0;
                    $concessionIn = 0;
                    if (count($allInboundTrips) > 0) {
                        foreach ($allInboundTrips as $allInboundTrip) {
                            $ticketInTrips = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                ->where('tostage_stage_id',  $allStage->id)
                                ->get();

                            if (count($ticketInTrips) > 0) {
                                foreach ($ticketInTrips as $ticketInTrip) {
                                    $countIn++;
                                    if ($ticketInTrip->passenger_type == 0) {
                                        $adultIn++;
                                    } elseif ($ticketInTrip->passenger_type == 1) {
                                        $concessionIn++;
                                    }
                                }
                            }
                        }
                    }

                    //Outbound Trip
                    $allOutboundTrips = TripDetail::where('route_id', $validatedRoute->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->where('trip_code', 0)
                        ->get();

                    $countOut = 0;
                    $adultOut = 0;
                    $concessionOut = 0;
                    if (count($allInboundTrips) > 0) {
                        foreach ($allOutboundTrips as $allOutboundTrip) {
                            $ticketOutTrips = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                ->where('tostage_stage_id',  $allStage->id)
                                ->get();

                            if (count($ticketOutTrips) > 0) {
                                foreach ($ticketOutTrips as $ticketOutTrip) {
                                    $countOut++;
                                    if ($ticketOutTrip->passenger_type == 0) {
                                        $adultOut++;
                                    } elseif ($ticketOutTrip->passenger_type == 1) {
                                        $concessionOut++;
                                    }
                                }
                            }
                        }
                    }
                    $trip_data['total_on_in'] = $countIn;
                    $trip_data['adult_in'] = $adultIn;
                    $trip_data['concession_in'] = $concessionIn;
                    $trip_data['total_on_out'] = $countOut;
                    $trip_data['adult_out'] = $adultOut;
                    $trip_data['concession_out'] = $concessionOut;

                    $perStage[$allStage->stage_name] = $trip_data;

                    $totalTotalOnIn += $countIn;
                    $totalAdultIn += $adultIn;
                    $totalConcessionIn += $concessionIn;
                    $totalTotalOnOut += $countOut;
                    $totalAdultOut += $adultOut;
                    $totalConcessionOut += $concessionOut;
                }

                if ($totalTotalOnIn == 0 && $totalAdultIn == 0 && $totalConcessionIn == 0 && $totalTotalOnOut == 0 && $totalAdultOut == 0 && $totalConcessionOut == 0) {
                    $perRoute[$validatedRoute->route_number] = [];
                } else {
                    $total_in = [];
                    foreach ($perStage as $key => $row) {
                        $total_in[$key] = $row['total_on_in'];
                    }
                    array_multisort($total_in, SORT_DESC, $perStage);

                    $total['total_on_in'] = $totalTotalOnIn;
                    $total['adult_in'] = $totalAdultIn;
                    $total['concession_in'] = $totalConcessionIn;
                    $total['total_on_out'] = $totalTotalOnOut;
                    $total['adult_out'] = $totalAdultOut;
                    $total['concession_out'] = $totalConcessionOut;

                    $perStage['total_per_route'] = $total;
                    $perRoute[$validatedRoute->route_number] = $perStage;
                }

                $grandTotalOnIn = $totalTotalOnIn;
                $grandAdultIn = $totalAdultIn;
                $grandConcessionIn = $totalConcessionIn;
                $grandTotalOnOut = $totalTotalOnOut;
                $grandAdultOut = $totalAdultOut;
                $grandConcessionOut = $totalConcessionOut;

                $grand['total_on_in'] = $grandTotalOnIn;
                $grand['adult_in'] = $grandAdultIn;
                $grand['concession_in'] = $grandConcessionIn;
                $grand['total_on_out'] = $grandTotalOnOut;
                $grand['adult_out'] = $grandAdultOut;
                $grand['concession_out'] = $grandConcessionOut;

                $data['allRoute'] = $perRoute;
                $data['grand'] = $grand;
                $topAlightingSPAD->add($data);
            } //Top Boarding all route specific company
            else {
                $out->writeln("YOU ARE IN HERE Top Boarding all route specific company");
                $routeByCompanies = Route::where('company_id', $this->selectedCompany)->get();

                foreach ($routeByCompanies as $routeByCompany) {
                    $out->writeln("allRoute: " . $routeByCompany->route_number);
                    $allStages = Stage::where('route_id', $routeByCompany->id)->get();
                    $totalTotalOnIn = 0;
                    $totalAdultIn = 0;
                    $totalConcessionIn = 0;
                    $totalTotalOnOut = 0;
                    $totalAdultOut = 0;
                    $totalConcessionOut = 0;
                    $perStage = [];

                    foreach ($allStages as $allStage) {
                        $trip_data = [];

                        //Inbound Trip
                        $allInboundTrips = TripDetail::where('route_id', $routeByCompany->id)
                            ->whereBetween('start_trip', [$dateFrom, $dateTo])
                            ->where('trip_code', 1)
                            ->get();

                        $countIn = 0;
                        $adultIn = 0;
                        $concessionIn = 0;
                        if (count($allInboundTrips) > 0) {
                            foreach ($allInboundTrips as $allInboundTrip) {
                                $ticketInTrips = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                    ->where('tostage_stage_id', $allStage->id)
                                    ->get();

                                if (count($ticketInTrips) > 0) {
                                    foreach ($ticketInTrips as $ticketInTrip) {
                                        $countIn++;
                                        if ($ticketInTrip->passenger_type == 0) {
                                            $adultIn++;
                                        } elseif ($ticketInTrip->passenger_type == 1) {
                                            $concessionIn++;
                                        }
                                    }
                                }
                            }
                        }

                        //Outbound Trip
                        $allOutboundTrips = TripDetail::where('route_id', $routeByCompany->id)
                            ->whereBetween('start_trip', [$dateFrom, $dateTo])
                            ->where('trip_code', 0)
                            ->get();

                        $countOut = 0;
                        $adultOut = 0;
                        $concessionOut = 0;
                        if (count($allInboundTrips) > 0) {
                            foreach ($allOutboundTrips as $allOutboundTrip) {
                                $ticketOutTrips = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                    ->where('tostage_stage_id', $allStage->id)
                                    ->get();

                                if (count($ticketOutTrips) > 0) {
                                    foreach ($ticketOutTrips as $ticketOutTrip) {
                                        $countOut++;
                                        if ($ticketOutTrip->passenger_type == 0) {
                                            $adultOut++;
                                        } elseif ($ticketOutTrip->passenger_type == 1) {
                                            $concessionOut++;
                                        }
                                    }
                                }
                            }
                        }
                        $trip_data['total_on_in'] = $countIn;
                        $trip_data['adult_in'] = $adultIn;
                        $trip_data['concession_in'] = $concessionIn;
                        $trip_data['total_on_out'] = $countOut;
                        $trip_data['adult_out'] = $adultOut;
                        $trip_data['concession_out'] = $concessionOut;

                        $perStage[$allStage->stage_name] = $trip_data;

                        $totalTotalOnIn += $countIn;
                        $totalAdultIn += $adultIn;
                        $totalConcessionIn += $concessionIn;
                        $totalTotalOnOut += $countOut;
                        $totalAdultOut += $adultOut;
                        $totalConcessionOut += $concessionOut;
                    }

                    if ($totalTotalOnIn == 0 && $totalAdultIn == 0 && $totalConcessionIn == 0 && $totalTotalOnOut == 0 && $totalAdultOut == 0 && $totalConcessionOut == 0) {
                        $perRoute[$routeByCompany->route_number] = [];
                    } else {
                        $total_in = [];
                        foreach ($perStage as $key => $row) {
                            $total_in[$key] = $row['total_on_in'];
                        }
                        array_multisort($total_in, SORT_DESC, $perStage);

                        $total['total_on_in'] = $totalTotalOnIn;
                        $total['adult_in'] = $totalAdultIn;
                        $total['concession_in'] = $totalConcessionIn;
                        $total['total_on_out'] = $totalTotalOnOut;
                        $total['adult_out'] = $totalAdultOut;
                        $total['concession_out'] = $totalConcessionOut;

                        $perStage['total_per_route'] = $total;
                        $perRoute[$routeByCompany->route_number] = $perStage;
                    }

                    $grandTotalOnIn += $totalTotalOnIn;
                    $grandAdultIn += $totalAdultIn;
                    $grandConcessionIn += $totalConcessionIn;
                    $grandTotalOnOut += $totalTotalOnOut;
                    $grandAdultOut += $totalAdultOut;
                    $grandConcessionOut += $totalConcessionOut;
                }
                $grand['total_on_in'] = $grandTotalOnIn;
                $grand['adult_in'] = $grandAdultIn;
                $grand['concession_in'] = $grandConcessionIn;
                $grand['total_on_out'] = $grandTotalOnOut;
                $grand['adult_out'] = $grandAdultOut;
                $grand['concession_out'] = $grandConcessionOut;

                $data['allRoute'] = $perRoute;
                $data['grand'] = $grand;
                $topAlightingSPAD->add($data);
            }
        }//Top Boarding all route all company
        else{
            $networkArea = 'All';
            $out->writeln("YOU ARE IN HERE Top Alighting all route all company");
            $allRoutes = Route::all();
            $grandTotalOffIn = 0;
            $grandAdultIn = 0;
            $grandConcessionIn = 0;
            $grandTotalOffOut = 0;
            $grandAdultOut = 0;
            $grandConcessionOut = 0;
            $perRoute = [];

            foreach($allRoutes as $allRoute) {
                $out->writeln("allRoute: " . $allRoute->route_number);
                $allStages = Stage::where('route_id', $allRoute->id)->get();
                $totalTotalOffIn = 0;
                $totalAdultIn = 0;
                $totalConcessionIn = 0;
                $totalTotalOffOut = 0;
                $totalAdultOut = 0;
                $totalConcessionOut = 0;
                $perStage = [];

                foreach ($allStages as $allStage) {
                    $trip_data = [];

                    //Inbound Trip
                    $allInboundTrips = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->where('trip_code', 1)
                        ->get();

                    $countIn = 0;
                    $adultIn = 0;
                    $concessionIn = 0;
                    if (count($allInboundTrips) > 0) {
                        foreach ($allInboundTrips as $allInboundTrip) {
                            $out->writeln("YOU ARE IN HERE Top Alighting allInboundTrip loop");
                            $out->writeln("allInboundTrip ID: " . $allInboundTrip->id);
                            $ticketInTrips = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                ->where('tostage_stage_id', $allStage->id)
                                ->get();

                            if (count($ticketInTrips) > 0) {
                                foreach ($ticketInTrips as $ticketInTrip) {
                                    $out->writeln("YOU ARE IN HERE Top Alighting allInboundTrip ticketInTriploop");
                                    $out->writeln("ticketInTrip ID: " . $ticketInTrip->id);
                                    $countIn++;
                                    if ($ticketInTrip->passenger_type == 0) {
                                        $adultIn++;
                                    } elseif ($ticketInTrip->passenger_type == 1) {
                                        $concessionIn++;
                                    }
                                    $out->writeln("countIn: " .$countIn);
                                    $out->writeln("adultIn: " .$adultIn);
                                    $out->writeln("concessionIn: " .$concessionIn);
                                }
                            }
                        }
                    }

                    //Outbound Trip
                    $allOutboundTrips = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->where('trip_code', 0)
                        ->get();

                    $countOut = 0;
                    $adultOut = 0;
                    $concessionOut = 0;
                    if (count($allInboundTrips) > 0) {
                        foreach ($allOutboundTrips as $allOutboundTrip) {
                            $out->writeln("YOU ARE IN HERE Top Alighting allOutboundTrip loop");
                            $out->writeln("allOutboundTrip ID: " . $allOutboundTrip->id);
                            $ticketOutTrips = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                ->where('tostage_stage_id', $allStage->id)
                                ->get();

                            if (count($ticketOutTrips) > 0) {
                                foreach ($ticketOutTrips as $ticketOutTrip) {
                                    $out->writeln("YOU ARE IN HERE Top Alighting allOutboundTrip ticketOutTrip loop");
                                    $out->writeln("ticketOutTrip ID: " . $ticketOutTrip->id);
                                    $countOut++;
                                    if ($ticketOutTrip->passenger_type == 0) {
                                        $adultOut++;
                                    } elseif ($ticketOutTrip->passenger_type == 1) {
                                        $concessionOut++;
                                    }
                                    $out->writeln("countOut: " .$countOut);
                                    $out->writeln("adultOut: " .$adultOut);
                                    $out->writeln("concessionOut: " .$concessionOut);
                                }
                            }
                        }
                    }
                    $trip_data['total_on_in'] = $countIn;
                    $trip_data['adult_in'] = $adultIn;
                    $trip_data['concession_in'] = $concessionIn;
                    $trip_data['total_on_out'] = $countOut;
                    $trip_data['adult_out'] = $adultOut;
                    $trip_data['concession_out'] = $concessionOut;

                    $perStage[$allStage->stage_name] = $trip_data;

                    $totalTotalOffIn += $countIn;
                    $totalAdultIn += $adultIn;
                    $totalConcessionIn += $concessionIn;
                    $totalTotalOffOut += $countOut;
                    $totalAdultOut += $adultOut;
                    $totalConcessionOut += $concessionOut;
                    $out->writeln("totalOffIn:" . $totalTotalOffIn);
                    $out->writeln("totalOOffOut:" . $totalTotalOffOut);
                }

                if ($totalTotalOffIn == 0 && $totalAdultIn == 0 && $totalConcessionIn == 0 && $totalTotalOffOut == 0 && $totalAdultOut == 0 && $totalConcessionOut == 0) {
                    $out->writeln("NO TOTAL");
                    $perRoute[$allRoute->route_number] = [];
                } else{
                    $total_in = [];
                    foreach ($perStage as $key => $row) {
                        $out->writeln("You are in loop array multisort");
                        $total_in[$key] = $row['total_on_in'];
                    }
                    array_multisort($total_in, SORT_DESC, $perStage);

                    $total['total_on_in'] = $totalTotalOffIn;
                    $total['adult_in'] = $totalAdultIn;
                    $total['concession_in'] = $totalConcessionIn;
                    $total['total_on_out'] = $totalTotalOffOut;
                    $total['adult_out'] = $totalAdultOut;
                    $total['concession_out'] = $totalConcessionOut;

                    $perStage['total_per_route'] = $total;
                    $perRoute[$allRoute->route_number] = $perStage;
                }

                $grandTotalOffIn += $totalTotalOffIn;
                $grandAdultIn += $totalAdultIn;
                $grandConcessionIn += $totalConcessionIn;
                $grandTotalOffOut += $totalTotalOffOut;
                $grandAdultOut += $totalAdultOut;
                $grandConcessionOut += $totalConcessionOut;
            }
            $grand['total_on_in'] = $grandTotalOffIn;
            $grand['adult_in'] = $grandAdultIn;
            $grand['concession_in'] = $grandConcessionIn;
            $grand['total_on_out'] = $grandTotalOffOut;
            $grand['adult_out'] = $grandAdultOut;
            $grand['concession_out'] = $grandConcessionOut;


            $data['allRoute'] = $perRoute;
            $data['grand'] = $grand;
            $topAlightingSPAD->add($data);
        }

        return Excel::download(new SPADTopAlighting($topAlightingSPAD, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Top_Alighting_Report_SPAD.xlsx');
    }

    public function printBusTransfer()
    {
        //
    }

    public function printTransferPoint()
    {
        //
    }

    public function printPenalty(){
        //
    }

    public function printSalesDetails()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN  printSalesDetails()");

        $validatedData = Validator::make($this->state, [
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

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)) {
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        $salesDetails = collect();

        if ($this->selectedCompany) {
            $companyDetails = Company::where('id', $this->selectedCompany)->first();
            $networkArea = $companyDetails->company_name;

            //printSalesDetails() certain route for specific company
            if (!empty($this->state['route_id'])) {
                $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                $countTicket = 0;
                $sales = [];

                $allTrips = TripDetail::whereBetween('start_trip', [$dateFrom , $dateTo])
                    ->where('route_id',$selectedRoute->id)
                    ->get();

                if(count($allTrips) > 0) {
                    foreach ($allTrips as $allTrip) {
                        $allTickets = TicketSalesTransaction::where('trip_id', $allTrip->id)
                            ->orderBy('sales_date')
                            ->get();

                        if (count($allTickets) > 0) {
                            foreach ($allTickets as $allTicket) {
                                $out->writeln("YOU ARE IN allTicket loop");

                                $countTicket++;
                                $out->writeln("countTicket:" . $countTicket);

                                $salesTime = new Carbon($allTicket->sales_date);
                                $perTicket['sales_date'] = $salesTime->toDateString();
                                $out->writeln("salesDate:" . $perTicket['sales_date']);

                                $perTicket['sales_time'] = $salesTime->toTimeString();
                                $out->writeln("salesTime:" . $perTicket['sales_time']);

                                $perTicket['ticket_no'] = $allTicket->ticket_number;
                                $out->writeln("ticket_no:" . $perTicket['ticket_no']);

                                $perTicket['from'] = $allTicket->fromstage->stage_name;
                                $out->writeln("from:" . $perTicket['from']);

                                $perTicket['to'] = $allTicket->tostage->stage_name;
                                $out->writeln("to:" . $perTicket['to']);

                                if ($allTicket->pasenger_type == 0) {
                                    $passType = 'ADULT';
                                } elseif ($allTicket->pasenger_type == 1) {
                                    $passType = 'CONCESSION';
                                }
                                $perTicket['passenger_type'] = $passType;
                                $out->writeln("passenger_type:" . $perTicket['passenger_type']);

                                $perTicket['price'] = $allTicket->amount;
                                $out->writeln("price:" . $perTicket['price']);

                                $perTicket['bus_no'] = $allTrip->Bus->bus_registration_number;
                                $out->writeln("bus_no:" . $perTicket['bus_no']);

                                $perTicket['route_no'] = $selectedRoute->route_number;
                                $out->writeln("route_no:" . $perTicket['route_no']);

                                if ($allTrip->trip_code == 1) {
                                    $IBOB = 'IB';
                                } elseif ($allTrip->trip_code == 0) {
                                    $IBOB = 'OB';
                                }
                                $perTicket['IBOB'] = $IBOB;
                                $out->writeln("IBOB:" . $perTicket['IBOB']);

                                $lastStage = Stage::where('route_id', $allTrip->route_id)
                                    ->orderBy('stage_order', 'DESC')
                                    ->first();
                                $perTicket['route_destination'] = $lastStage->stage_name;
                                $out->writeln("route_destination:" . $perTicket['route_destination']);

                                $perTicket['route_name'] = $allTrip->route->route_name;
                                $out->writeln("route_name:" . $perTicket['route_name']);

                                $tripTime = new Carbon($allTrip->start_trip);
                                $perTicket['trip_time'] = $tripTime->toTimeString();
                                $out->writeln("trip_time:" . $perTicket['trip_time']);

                                $perTicket['trip_no'] = $allTrip->id;
                                $out->writeln("trip_no:" . $perTicket['trip_no']);

                                $perTicket['driver_id'] = $allTrip->busdriver->id_number;
                                $out->writeln("driver_id:" . $perTicket['driver_id']);

                                $perTicket['driver_name'] = $allTrip->busdriver->driver_name;
                                $out->writeln("driver_name:" . $perTicket['driver_name']);

                                if ($allTicket->fare_type == 1) {
                                    $payment = 'CARD';
                                } elseif ($allTicket->fare_type == 0) {
                                    $payment = 'CASH';
                                }
                                $perTicket['payment'] = $payment;
                                $out->writeln("payment:" . $perTicket['payment']);

                                $sales[$countTicket] = $perTicket;
                            }
                        }
                    }
                }
                $salesDetails->add($sales);

            } //printSalesDetails() all route for specific company
            else {
                $routeByCompanies = Route::where('company_id', $companyDetails->id)->get();
                $countTicket = 0;
                $sales = [];

                foreach($routeByCompanies as $routeByCompany){
                    $allTrips = TripDetail::whereBetween('start_trip', [$dateFrom , $dateTo])
                        ->where('route_id',$routeByCompany->id)
                        ->get();

                    if(count($allTrips) > 0) {
                        foreach ($allTrips as $allTrip) {
                            $allTickets = TicketSalesTransaction::where('trip_id', $allTrip->id)
                                ->orderBy('sales_date')
                                ->get();

                            if (count($allTickets) > 0) {
                                foreach ($allTickets as $allTicket) {
                                    $out->writeln("YOU ARE IN allTicket loop");

                                    $countTicket++;
                                    $out->writeln("countTicket:" . $countTicket);

                                    $salesTime = new Carbon($allTicket->sales_date);
                                    $perTicket['sales_date'] = $salesTime->toDateString();
                                    $out->writeln("salesDate:" . $perTicket['sales_date']);

                                    $perTicket['sales_time'] = $salesTime->toTimeString();
                                    $out->writeln("salesTime:" . $perTicket['sales_time']);

                                    $perTicket['ticket_no'] = $allTicket->ticket_number;
                                    $out->writeln("ticket_no:" . $perTicket['ticket_no']);

                                    $perTicket['from'] = $allTicket->fromstage->stage_name;
                                    $out->writeln("from:" . $perTicket['from']);

                                    $perTicket['to'] = $allTicket->tostage->stage_name;
                                    $out->writeln("to:" . $perTicket['to']);

                                    if ($allTicket->pasenger_type == 0) {
                                        $passType = 'ADULT';
                                    } elseif ($allTicket->pasenger_type == 1) {
                                        $passType = 'CONCESSION';
                                    }
                                    $perTicket['passenger_type'] = $passType;
                                    $out->writeln("passenger_type:" . $perTicket['passenger_type']);

                                    $perTicket['price'] = $allTicket->amount;
                                    $out->writeln("price:" . $perTicket['price']);

                                    $perTicket['bus_no'] = $allTrip->Bus->bus_registration_number;
                                    $out->writeln("bus_no:" . $perTicket['bus_no']);

                                    $perTicket['route_no'] = $routeByCompany->route_number;
                                    $out->writeln("route_no:" . $perTicket['route_no']);

                                    if ($allTrip->trip_code == 1) {
                                        $IBOB = 'IB';
                                    } elseif ($allTrip->trip_code == 0) {
                                        $IBOB = 'OB';
                                    }
                                    $perTicket['IBOB'] = $IBOB;
                                    $out->writeln("IBOB:" . $perTicket['IBOB']);

                                    $lastStage = Stage::where('route_id', $allTrip->route_id)
                                        ->orderBy('stage_order', 'DESC')
                                        ->first();
                                    $perTicket['route_destination'] = $lastStage->stage_name;
                                    $out->writeln("route_destination:" . $perTicket['route_destination']);

                                    $perTicket['route_name'] = $allTrip->route->route_name;
                                    $out->writeln("route_name:" . $perTicket['route_name']);

                                    $tripTime = new Carbon($allTrip->start_trip);
                                    $perTicket['trip_time'] = $tripTime->toTimeString();
                                    $out->writeln("trip_time:" . $perTicket['trip_time']);

                                    $perTicket['trip_no'] = $allTrip->id;
                                    $out->writeln("trip_no:" . $perTicket['trip_no']);

                                    $perTicket['driver_id'] = $allTrip->busdriver->id_number;
                                    $out->writeln("driver_id:" . $perTicket['driver_id']);

                                    $perTicket['driver_name'] = $allTrip->busdriver->driver_name;
                                    $out->writeln("driver_name:" . $perTicket['driver_name']);

                                    if ($allTicket->fare_type == 1) {
                                        $payment = 'CARD';
                                    } elseif ($allTicket->fare_type == 0) {
                                        $payment = 'CASH';
                                    }
                                    $perTicket['payment'] = $payment;
                                    $out->writeln("payment:" . $perTicket['payment']);

                                    $sales[$countTicket] = $perTicket;
                                }
                            }
                        }
                    }
                }
                $salesDetails->add($sales);
            }
        } //printSalesDetails() all route for all company
        else {
            $out->writeln("YOU ARE IN printSalesDetails() all route for all company");
            $networkArea = "ALL";
            $allTickets = TicketSalesTransaction::whereBetween('sales_date', [$dateFrom , $dateTo])
                ->orderBy('sales_date')
                ->get();

            $countTicket = 0;
            $sales = [];
            if (count($allTickets) > 0) {
                foreach ($allTickets as $allTicket) {
                    $out->writeln("YOU ARE IN allTicket loop");

                    $countTicket++;
                    $out->writeln("countTicket:" . $countTicket);

                    $salesTime = new Carbon($allTicket->sales_date);
                    $perTicket['sales_date'] = $salesTime->toDateString();
                    $out->writeln("salesDate:". $perTicket['sales_date']);

                    $perTicket['sales_time'] = $salesTime->toTimeString();
                    $out->writeln("salesTime:". $perTicket['sales_time']);

                    $perTicket['ticket_no'] = $allTicket->ticket_number;
                    $out->writeln("ticket_no:". $perTicket['ticket_no']);

                    $perTicket['from'] = $allTicket->fromstage->stage_name;
                    $out->writeln("from:". $perTicket['from']);

                    $perTicket['to'] = $allTicket->tostage->stage_name;
                    $out->writeln("to:". $perTicket['to']);

                    if ($allTicket->pasenger_type == 0) {
                        $passType = 'ADULT';
                    } elseif ($allTicket->pasenger_type == 1) {
                        $passType = 'CONCESSION';
                    }
                    $perTicket['passenger_type'] = $passType;
                    $out->writeln("passenger_type:". $perTicket['passenger_type']);

                    $perTicket['price'] = $allTicket->amount;
                    $out->writeln("price:". $perTicket['price']);

                    $perTicket['bus_no'] = $allTicket->tripDetail->Bus->bus_registration_number;
                    $out->writeln("bus_no:". $perTicket['bus_no']);

                    $perTicket['route_no'] = $allTicket->tripDetail->route->route_number;
                    $out->writeln("route_no:". $perTicket['route_no']);

                    if ($allTicket->tripDetail->trip_code == 1) {
                        $IBOB = 'IB';
                    } elseif ($allTicket->tripDetail->trip_code == 0) {
                        $IBOB = 'OB';
                    }
                    $perTicket['IBOB'] = $IBOB;
                    $out->writeln("IBOB:". $perTicket['IBOB']);

                    $lastStage = Stage::where('route_id', $allTicket->tripDetail->route_id)
                        ->orderBy('stage_order', 'DESC')
                        ->first();
                    $perTicket['route_destination'] = $lastStage->stage_name;
                    $out->writeln("route_destination:". $perTicket['route_destination']);

                    $perTicket['route_name'] = $allTicket->tripDetail->route->route_name;
                    $out->writeln("route_name:". $perTicket['route_name']);

                    $tripTime = new Carbon($allTicket->tripDetail->start_trip);
                    $perTicket['trip_time'] = $tripTime->toTimeString();
                    $out->writeln("trip_time:". $perTicket['trip_time']);

                    $perTicket['trip_no'] = $allTicket->trip_id;
                    $out->writeln("trip_no:". $perTicket['trip_no']);

                    $perTicket['driver_id'] = $allTicket->tripDetail->busdriver->id_number;
                    $out->writeln("driver_id:". $perTicket['driver_id']);

                    $perTicket['driver_name'] = $allTicket->tripDetail->busdriver->driver_name;
                    $out->writeln("driver_name:". $perTicket['driver_name']);

                    if ($allTicket->fare_type == 1) {
                        $payment = 'CARD';
                    } elseif ($allTicket->fare_type == 0) {
                        $payment = 'CASH';
                    }
                    $perTicket['payment'] = $payment;
                    $out->writeln("payment:". $perTicket['payment']);

                    $sales[$countTicket] = $perTicket;
                }
            }
            $salesDetails->add($sales);
        }
        return Excel::download(new SPADSalesDetails($salesDetails, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Sales_Details_Report_SPAD.xlsx');
    }

    public function printClaimDetails()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printClaimDetails())");

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

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        $claimDetails = collect();

        if($this->selectedCompany) {
            //ClaimDetails certain route for specific company
            if (!empty($this->state['route_id'])) {
                $out->writeln("YOU ARE IN HERE ClaimDetails certain route for specific company");
                $grandBusStop = 0;
                $grandTravel = 0;
                $grandClaim = 0;
                $grandTravelGPS = 0;
                $grandClaimGPS = 0;
                $grandCountPassenger = 0;
                $grandSale = 0;
                $grandTotal = 0;
                $grandTotalAdult = 0;
                $grandTotalConcession = 0;

                foreach ($all_dates as $all_date) {
                    $out->writeln("YOU ARE IN HERE ClaimDetails all route all company all_date loop");
                    $totalBusStopIn = 0;
                    $totalTravelIn = 0;
                    $totalClaimIn = 0;
                    $totalTravelGPSIn = 0;
                    $totalClaimGPSIn = 0;
                    $totalCountPassengerIn = 0;
                    $totalSalesIn = 0;
                    $totalTotalIn = 0;
                    $totalAdultIn = 0;
                    $totalConcessionIn = 0;
                    $existInTrip = false;
                    $existOutTrip = false;

                    $firstDate = new Carbon($all_date);
                    $lastDate = new Carbon($all_date . '11:59:59');

                    //Inbound
                    $allTripInbounds = TripDetail::where('route_id', $validatedData['route_id'])
                        ->whereBetween('start_trip', [$firstDate,$lastDate])
                        ->where('trip_code', 1)
                        ->get();

                    if (count($allTripInbounds) > 0) {
                        $out->writeln("YOU ARE IN HERE certain route allTripInbounds()");
                        $existInTrip = true;
                        $countIn = 0;

                        foreach ($allTripInbounds as $allTripInbound) {
                            $inbound['trip_type'] = "IB";
                            $firstStage = Stage::where('route_id', $allTripInbound->route_id)->orderby('stage_order')->first();
                            $inbound['start_point'] = $firstStage->stage_name;
                            $inbound['trip_no'] = "T" . $allTripInbound->id;
                            $inbound['rph_no'] = $allTripInbound->trip_number;
                            $inbound['bus_plate_no'] = $allTripInbound->bus->bus_registration_number;
                            $inbound['bus_age'] = Carbon::parse($allTripInbound->bus->bus_manufacturing_date)->diff(Carbon::now())->y;

                            $charge = 0;
                            $inbound['charge_km'] = $charge;

                            $inbound['driver_id'] = $allTripInbound->driver_id;

                            $busStop = BusStand::where('route_id', $allTripInbound->route_id)->count();
                            $inbound['bus_stop_travel'] = $busStop;
                            $totalBusStopIn += $busStop;

                            $travel = $allTripInbound->route->inbound_distance;
                            $inbound['travel'] = $travel;
                            $totalTravelIn += $travel;

                            $claim = $charge * $travel;
                            $inbound['claim'] = $claim;
                            $totalClaimIn += $claim;

                            $travelGPS = $allTripInbound->total_mileage;
                            $inbound['travel_gps'] = $travelGPS;
                            $totalTravelGPSIn += $travelGPS;

                            $claimGPS = $charge * $travelGPS;
                            $inbound['claim_gps'] = $claimGPS;
                            $totalClaimGPSIn += $claimGPS;

                            $inbound['status'] = "Complete";
                            $inbound['travel_BOP'] = $travelGPS;
                            $inbound['claim_BOP'] = $charge * $travelGPS;

                            $serviceStart = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                            $serviceEnd = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                            $inbound['start_point_time'] = $serviceStart;
                            $inbound['service_start'] = $serviceStart;
                            $inbound['service_end'] = $serviceEnd;

                            $actualStart = date("H:i", strtotime($allTripInbound->start_trip));
                            $actualEnd = date("H:i", strtotime($allTripInbound->end_trip));
                            $inbound['actual_start'] = $actualStart;
                            $inbound['actual_end'] = $actualEnd;

                            $firstSales = TicketSalesTransaction::where('trip_id', $allTripInbound->id)->orderby('sales_date')->first();
                            $salesStart = date("H:i", strtotime($firstSales->sales_date));
                            $inbound['sales_start'] = $salesStart;
                            $lastSales = TicketSalesTransaction::where('trip_id', $allTripInbound->id)->orderby('sales_date', 'DESC')->first();
                            $salesEnd = date("H:i", strtotime($lastSales->sales_date));
                            $inbound['sales_end'] = $salesEnd;

                            $diff = strtotime($serviceStart) - strtotime($actualStart);

                            if ($diff > 10 || $diff < -10) {
                                $inbound['punctuality'] = "NOT PUNCTUAL";
                            } else {
                                $inbound['punctuality'] = "ONTIME";
                            }

                            $countPassenger = $allTripInbound->total_adult + $allTripInbound->total_concession;
                            $inbound['pass_count'] = $countPassenger;
                            $totalCountPassengerIn += $countPassenger;

                            $sales = $allTripInbound->total_adult_amount + $allTripInbound->total_concession_amount;
                            $inbound['total_sales'] = $sales;
                            $totalSalesIn += $sales;

                            $inbound['total_on'] = $countPassenger;
                            $totalTotalIn += $countPassenger;

                            $adult = $allTripInbound->total_adult;
                            $inbound['adult'] = $adult;
                            $totalAdultIn += $adult;

                            $concession = $allTripInbound->total_concession;
                            $inbound['concession'] = $concession;
                            $totalConcessionIn += $concession;

                            $allTripIn[$countIn] = $inbound;
                            $countIn++;
                        }
                        $totIn['total_bus_stop_in'] = $totalBusStopIn;
                        $totIn['total_travel_in'] = $totalTravelIn;
                        $totIn['total_claim_in'] = $totalClaimIn;
                        $totIn['total_travel_gps_in'] = $totalTravelGPSIn;
                        $totIn['total_claim_gps_in'] = $totalClaimGPSIn;
                        $totIn['total_count_passenger_in'] = $totalCountPassengerIn;
                        $totIn['total_sales_in'] = $totalSalesIn;
                        $totIn['total_total_in'] = $totalTotalIn;
                        $totIn['total_adult_in'] = $totalAdultIn;
                        $totIn['total_concession_in'] = $totalConcessionIn;
                        $allTripIn['total_inbound'] = $totIn;
                    }

                    //Outbound
                    $allTripOutbounds = TripDetail::where('route_id', $validatedData['route_id'])
                        ->whereBetween('start_trip', [$firstDate,$lastDate])
                        ->where('trip_code', 0)
                        ->get();

                    $totalBusStopOut = 0;
                    $totalTravelOut = 0;
                    $totalClaimOut = 0;
                    $totalTravelGPSOut = 0;
                    $totalClaimGPSOut = 0;
                    $totalCountPassengerOut = 0;
                    $totalSalesOut = 0;
                    $totalTotalOut = 0;
                    $totalAdultOut = 0;
                    $totalConcessionOut = 0;
                    if (count($allTripOutbounds) > 0) {
                        $out->writeln("YOU ARE IN HERE certain route allTripOutbounds");
                        $existOutTrip = true;
                        $countOut = 0;

                        foreach ($allTripOutbounds as $allTripOutbound) {
                            $outbound['trip_type'] = "OB";
                            $firstStage = Stage::where('route_id', $allTripOutbound->route_id)->orderby('stage_order','DESC')->first();
                            $outbound['start_point'] = $firstStage->stage_name;
                            $outbound['trip_no'] = "T" . $allTripOutbound->id;
                            $outbound['rph_no'] = $allTripOutbound->trip_number;
                            $outbound['bus_plate_no'] = $allTripOutbound->bus->bus_registration_number;
                            $outbound['bus_age'] = Carbon::parse($allTripOutbound->bus->bus_manufacturing_date)->diff(Carbon::now())->y;

                            $charge = 0;
                            $outbound['charge_km'] = $charge;

                            $outbound['driver_id'] = $allTripOutbound->driver_id;

                            $busStop = BusStand::where('route_id', $allTripOutbound->route_id)->count();
                            $outbound['bus_stop_travel'] = $busStop;
                            $totalBusStopOut += $busStop;

                            $travel = $allTripOutbound->route->inbound_distance;
                            $outbound['travel'] = $travel;
                            $totalTravelOut += $travel;

                            $claim = $charge * $travel;
                            $outbound['claim'] = $claim;
                            $totalClaimOut += $claim;

                            $travelGPS = $allTripOutbound->total_mileage;
                            $outbound['travel_gps'] = $travelGPS;
                            $totalTravelGPSOut += $travelGPS;

                            $claimGPS = $charge * $travelGPS;
                            $outbound['claim_gps'] = $claimGPS;
                            $totalClaimGPSOut += $claimGPS;

                            $outbound['status'] = "Complete";
                            $outbound['travel_BOP'] = $travelGPS;
                            $outbound['claim_BOP'] = $charge * $travelGPS;

                            $serviceStart = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                            $serviceEnd = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                            $outbound['start_point_time'] = $serviceStart;
                            $outbound['service_start'] = $serviceStart;
                            $outbound['service_end'] = $serviceEnd;

                            $actualStart = date("H:i", strtotime($allTripOutbound->start_trip));
                            $actualEnd = date("H:i", strtotime($allTripOutbound->end_trip));
                            $outbound['actual_start'] = $actualStart;
                            $outbound['actual_end'] = $actualEnd;

                            $firstSales = TicketSalesTransaction::where('trip_id', $allTripOutbound->id)->orderby('sales_date')->first();
                            $salesStart = date("H:i", strtotime($firstSales->sales_date));
                            $outbound['sales_start'] = $salesStart;
                            $lastSales = TicketSalesTransaction::where('trip_id', $allTripOutbound->id)->orderby('sales_date', 'DESC')->first();
                            $salesEnd = date("H:i", strtotime($lastSales->sales_date));
                            $outbound['sales_end'] = $salesEnd;

                            $diff = strtotime($actualStart) - strtotime($salesStart);

                            if ($diff > 10 || $diff < -10) {
                                $outbound['punctuality'] = "NOT PUNCTUAL";
                            } else {
                                $outbound['punctuality'] = "ONTIME";
                            }

                            $countPassenger = $allTripOutbound->total_adult + $allTripOutbound->total_concession;
                            $outbound['pass_count'] = $countPassenger;
                            $totalCountPassengerOut += $countPassenger;

                            $sales = $allTripOutbound->total_adult_amount + $allTripOutbound->total_concession_amount;
                            $outbound['total_sales'] = $sales;
                            $totalSalesOut += $sales;

                            $outbound['total_on'] = $countPassenger;
                            $totalTotalOut += $countPassenger;

                            $adult = $allTripOutbound->total_adult;
                            $outbound['adult'] = $adult;
                            $totalAdultOut += $adult;

                            $concession = $allTripOutbound->total_concession;
                            $outbound['concession'] = $concession;
                            $totalConcessionOut += $concession;

                            $allTripOut[$countOut] = $outbound;
                            $countOut++;
                        }
                        $totOut['total_bus_stop_out'] = $totalBusStopOut;
                        $totOut['total_travel_out'] = $totalTravelOut;
                        $totOut['total_claim_out'] = $totalClaimOut;
                        $totOut['total_travel_gps_out'] = $totalTravelGPSOut;
                        $totOut['total_claim_gps_out'] = $totalClaimGPSOut;
                        $totOut['total_count_passenger_out'] = $totalCountPassengerOut;
                        $totOut['total_sales_out'] = $totalSalesOut;
                        $totOut['total_total_out'] = $totalTotalOut;
                        $totOut['total_adult_out'] = $totalAdultOut;
                        $totOut['total_concession_out'] = $totalConcessionOut;
                        $allTripOut['total_outbound'] = $totOut;
                    }

                    $data_perDate = [];
                    $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                    if ($existInTrip == true && $existOutTrip == true) {
                        $totalBusStopDate = $totalBusStopIn + $totalBusStopOut;
                        $totalTravelDate = $totalTravelIn + $totalTravelOut;
                        $totalClaimDate = $totalClaimIn + $totalClaimOut;
                        $totalTravelGPSDate = $totalTravelGPSIn + $totalTravelGPSOut;
                        $totalClaimGPSDate = $totalClaimGPSIn + $totalClaimGPSOut;
                        $totalCountPassengerDate = $totalCountPassengerIn + $totalCountPassengerOut;
                        $totalSalesDate = $totalSalesIn + $totalSalesOut;
                        $totalTotalDate = $totalTotalIn + $totalTotalOut;
                        $totalAdultDate = $totalAdultIn + $totalAdultOut;
                        $totalConcessionDate = $totalConcessionIn + $totalConcessionOut;

                        $perDate['total_bus_stop_date'] = $totalBusStopDate;
                        $perDate['total_travel_date'] = $totalTravelDate;
                        $perDate['total_claim_date'] = $totalClaimDate;
                        $perDate['total_travel_gps_date'] = $totalTravelGPSDate;
                        $perDate['total_claim_gps_date'] = $totalClaimGPSDate;
                        $perDate['total_count_passenger_date'] = $totalCountPassengerDate;
                        $perDate['total_sales_date'] = $totalSalesDate;
                        $perDate['total_total_date'] = $totalTotalDate;
                        $perDate['total_adult_date'] = $totalAdultDate;
                        $perDate['total_concession_date'] = $totalConcessionDate;

                        $data_perDate['inbound_data'] = $allTripIn;
                        $data_perDate['outbound_data'] = $allTripOut;
                        $data_perDate['total_per_date'] = $perDate;

                        $allDate[$all_date] = $data_perDate;
                        $allData['data'] = $allDate;
                        $allData['route_name'] = $selectedRoute->route_name;
                        $route[$selectedRoute->route_number] = $allData;
                        $data['allRoute'] = $route;

                    } elseif ($existInTrip == true && $existOutTrip == false) {
                        $totalBusStopDate = $totalBusStopIn;
                        $totalTravelDate = $totalTravelIn;
                        $totalClaimDate = $totalClaimIn;
                        $totalTravelGPSDate = $totalTravelGPSIn;
                        $totalClaimGPSDate = $totalClaimGPSIn;
                        $totalCountPassengerDate = $totalCountPassengerIn;
                        $totalSalesDate = $totalSalesIn;
                        $totalTotalDate = $totalTotalIn;
                        $totalAdultDate = $totalAdultIn;
                        $totalConcessionDate = $totalConcessionIn;

                        $perDate['total_bus_stop_date'] = $totalBusStopDate;
                        $perDate['total_travel_date'] = $totalTravelDate;
                        $perDate['total_claim_date'] = $totalClaimDate;
                        $perDate['total_travel_gps_date'] = $totalTravelGPSDate;
                        $perDate['total_claim_gps_date'] = $totalClaimGPSDate;
                        $perDate['total_count_passenger_date'] = $totalCountPassengerDate;
                        $perDate['total_sales_date'] = $totalSalesDate;
                        $perDate['total_total_date'] = $totalTotalDate;
                        $perDate['total_adult_date'] = $totalAdultDate;
                        $perDate['total_concession_date'] = $totalConcessionDate;

                        $data_perDate['inbound_data'] = $allTripIn;
                        $data_perDate['total_per_date'] = $perDate;

                        $allDate[$all_date] = $data_perDate;
                        $allData['route_name'] = $selectedRoute->route_name;
                        $allData['data'] = $allDate;
                        $route[$selectedRoute->route_number] = $allData;
                        $data['allRoute'] = $route;

                    } elseif ($existInTrip == false && $existOutTrip == true) {
                        $totalBusStopDate = $totalBusStopOut;
                        $totalTravelDate = $totalTravelOut;
                        $totalClaimDate = $totalClaimOut;
                        $totalTravelGPSDate = $totalTravelGPSOut;
                        $totalClaimGPSDate = $totalClaimGPSOut;
                        $totalCountPassengerDate = $totalCountPassengerOut;
                        $totalSalesDate = $totalSalesOut;
                        $totalTotalDate = $totalTotalOut;
                        $totalAdultDate = $totalAdultOut;
                        $totalConcessionDate = $totalConcessionOut;

                        $perDate['total_bus_stop_date'] = $totalBusStopDate;
                        $perDate['total_travel_date'] = $totalTravelDate;
                        $perDate['total_claim_date'] = $totalClaimDate;
                        $perDate['total_travel_gps_date'] = $totalTravelGPSDate;
                        $perDate['total_claim_gps_date'] = $totalClaimGPSDate;
                        $perDate['total_count_passenger_date'] = $totalCountPassengerDate;
                        $perDate['total_sales_date'] = $totalSalesDate;
                        $perDate['total_total_date'] = $totalTotalDate;
                        $perDate['total_adult_date'] = $totalAdultDate;
                        $perDate['total_concession_date'] = $totalConcessionDate;

                        $data_perDate['outbound_data'] = $allTripOut;
                        $data_perDate['total_per_date'] = $perDate;

                        $allDate[$all_date] = $data_perDate;
                        $allData['route_name'] = $selectedRoute->route_name;
                        $allData['data'] = $allDate;
                        $route[$selectedRoute->route_number] = $allData;
                        $data['allRoute'] = $route;
                    } else {
                        $totalBusStopDate = 0;
                        $totalTravelDate = 0;
                        $totalClaimDate = 0;
                        $totalTravelGPSDate = 0;
                        $totalClaimGPSDate = 0;
                        $totalCountPassengerDate = 0;
                        $totalSalesDate = 0;
                        $totalTotalDate = 0;
                        $totalAdultDate = 0;
                        $totalConcessionDate = 0;

                        $data_perDate['inbound_data'] = [];
                        $data_perDate['outbound_data'] = [];
                        $data_perDate['total_per_date'] = [];

                        $allDate[$all_date] = $data_perDate;
                        $allData['route_name'] = $selectedRoute->route_name;
                        $allData['data'] = $allDate;
                        $route[$selectedRoute->route_number] = $allData;
                        $data['allRoute'] = $route;
                    }

                    $grandBusStop += $totalBusStopDate;
                    $grandTravel += $totalTravelDate;
                    $grandClaim += $totalClaimDate;
                    $grandTravelGPS += $totalTravelGPSDate;
                    $grandClaimGPS += $totalClaimGPSDate;
                    $grandCountPassenger += $totalCountPassengerDate;
                    $grandSale += $totalSalesDate;
                    $grandTotal += $totalTotalDate;
                    $grandTotalAdult+= $totalAdultDate;
                    $grandTotalConcession += $totalConcessionDate;
                }
                $grand['grand_bus_stop'] = $grandBusStop;
                $grand['grand_travel'] = $grandTravel;
                $grand['grand_claim'] = $grandClaim;
                $grand['grand_travel_gps'] = $grandTravelGPS;
                $grand['grand_claim_gps'] = $grandClaimGPS;
                $grand['grand_count_passenger'] = $grandCountPassenger;
                $grand['grand_sales'] = $grandSale;
                $grand['grand_total'] = $grandTotal;
                $grand['grand_adult'] = $grandTotalAdult;
                $grand['grand_concession'] = $grandTotalConcession;

                $data['grand'] = $grand;
                $claimDetails->add($data);
            } //ClaimDetails all routes for specific company
            else {
                $out->writeln("YOU ARE IN HERE ClaimDetails all route for specific company");
                $grandBusStop = 0;
                $grandTravel = 0;
                $grandClaim = 0;
                $grandTravelGPS = 0;
                $grandClaimGPS = 0;
                $grandCountPassenger = 0;
                $grandSale = 0;
                $grandTotal = 0;
                $grandTotalAdult = 0;
                $grandTotalConcession = 0;

                $allRouteCompanies = Route::where('company_id', $this->selectedCompany)->get();

                foreach ($allRouteCompanies as $allRouteCompany) {

                    foreach ($all_dates as $all_date) {
                        $out->writeln("YOU ARE IN ClaimDetails all routes for specific company");
                        $existInTrip = false;
                        $existOutTrip = false;
                        $totalBusStopIn = 0;
                        $totalTravelIn = 0;
                        $totalClaimIn = 0;
                        $totalTravelGPSIn = 0;
                        $totalClaimGPSIn = 0;
                        $totalCountPassengerIn = 0;
                        $totalSalesIn = 0;
                        $totalTotalIn = 0;
                        $totalAdultIn = 0;
                        $totalConcessionIn = 0;

                        $firstDate = new Carbon($all_date);
                        $lastDate = new Carbon($all_date . '11:59:59');

                        //Inbound
                        $allTripInbounds = TripDetail::where('route_id', $allRouteCompany->id)
                            ->whereBetween('start_trip', [$firstDate,$lastDate])
                            ->where('trip_code', 1)
                            ->get();

                        if (count($allTripInbounds) > 0) {
                            $out->writeln("YOU ARE IN HERE all route 1 comp allTripInbounds()");
                            $existInTrip = true;
                            $countIn = 0;

                            foreach ($allTripInbounds as $allTripInbound) {
                                $inbound['trip_type'] = "IB";
                                $firstStage = Stage::where('route_id', $allTripInbound->route_id)->orderby('stage_order')->first();
                                $inbound['start_point'] = $firstStage->stage_name;
                                $inbound['trip_no'] = "T" . $allTripInbound->id;
                                $inbound['rph_no'] = $allTripInbound->trip_number;
                                $inbound['bus_plate_no'] = $allTripInbound->bus->bus_registration_number;
                                $inbound['bus_age'] = Carbon::parse($allTripInbound->bus->bus_manufacturing_date)->diff(Carbon::now())->y;

                                $charge = 0;
                                $inbound['charge_km'] = $charge;

                                $inbound['driver_id'] = $allTripInbound->driver_id;

                                $busStop = BusStand::where('route_id', $allTripInbound->route_id)->count();
                                $inbound['bus_stop_travel'] = $busStop;
                                $totalBusStopIn += $busStop;

                                $travel = $allTripInbound->route->inbound_distance;
                                $inbound['travel'] = $travel;
                                $totalTravelIn += $travel;

                                $claim = $charge * $travel;
                                $inbound['claim'] = $claim;
                                $totalClaimIn += $claim;

                                $travelGPS = $allTripInbound->total_mileage;
                                $inbound['travel_gps'] = $travelGPS;
                                $totalTravelGPSIn += $travelGPS;

                                $claimGPS = $charge * $travelGPS;
                                $inbound['claim_gps'] = $claimGPS;
                                $totalClaimGPSIn += $claimGPS;

                                $inbound['status'] = "Complete";
                                $inbound['travel_BOP'] = $travelGPS;
                                $inbound['claim_BOP'] = $charge * $travelGPS;

                                $serviceStart = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                $serviceEnd = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                $inbound['start_point_time'] = $serviceStart;
                                $inbound['service_start'] = $serviceStart;
                                $inbound['service_end'] = $serviceEnd;

                                $actualStart = date("H:i", strtotime($allTripInbound->start_trip));
                                $actualEnd = date("H:i", strtotime($allTripInbound->end_trip));
                                $inbound['actual_start'] = $actualStart;
                                $inbound['actual_end'] = $actualEnd;

                                $firstSales = TicketSalesTransaction::where('trip_id', $allTripInbound->id)->orderby('sales_date')->first();
                                $salesStart = date("H:i", strtotime($firstSales->sales_date));
                                $inbound['sales_start'] = $salesStart;
                                $lastSales = TicketSalesTransaction::where('trip_id', $allTripInbound->id)->orderby('sales_date', 'DESC')->first();
                                $salesEnd = date("H:i", strtotime($lastSales->sales_date));
                                $inbound['sales_end'] = $salesEnd;

                                $diff = strtotime($actualStart) - strtotime($salesStart);

                                if ($diff > 10 || $diff < -10) {
                                    $inbound['punctuality'] = "NOT PUNCTUAL";
                                } else {
                                    $inbound['punctuality'] = "ONTIME";
                                }

                                $countPassenger = $allTripInbound->total_adult + $allTripInbound->total_concession;
                                $inbound['pass_count'] = $countPassenger;
                                $totalCountPassengerIn += $countPassenger;

                                $sales = $allTripInbound->total_adult_amount + $allTripInbound->total_concession_amount;
                                $inbound['total_sales'] = $sales;
                                $totalSalesIn += $sales;

                                $inbound['total_on'] = $countPassenger;
                                $totalTotalIn += $countPassenger;

                                $adult = $allTripInbound->total_adult;
                                $inbound['adult'] = $adult;
                                $totalAdultIn += $adult;

                                $concession = $allTripInbound->total_concession;
                                $inbound['concession'] = $concession;
                                $totalConcessionIn += $concession;

                                $allTripIn[$countIn] = $inbound;
                                $countIn++;
                            }
                            $totIn['total_bus_stop_in'] = $totalBusStopIn;
                            $totIn['total_travel_in'] = $totalTravelIn;
                            $totIn['total_claim_in'] = $totalClaimIn;
                            $totIn['total_travel_gps_in'] = $totalTravelGPSIn;
                            $totIn['total_claim_gps_in'] = $totalClaimGPSIn;
                            $totIn['total_count_passenger_in'] = $totalCountPassengerIn;
                            $totIn['total_sales_in'] = $totalSalesIn;
                            $totIn['total_total_in'] = $totalTotalIn;
                            $totIn['total_adult_in'] = $totalAdultIn;
                            $totIn['total_concession_in'] = $totalConcessionIn;
                            $allTripIn['total_inbound'] = $totIn;
                        }

                        //Outbound
                        $allTripOutbounds = TripDetail::where('route_id', $allRouteCompany->id)
                            ->whereBetween('start_trip', [$firstDate,$lastDate])
                            ->where('trip_code', 0)
                            ->get();

                        $totalBusStopOut = 0;
                        $totalTravelOut = 0;
                        $totalClaimOut = 0;
                        $totalTravelGPSOut = 0;
                        $totalClaimGPSOut = 0;
                        $totalCountPassengerOut = 0;
                        $totalSalesOut = 0;
                        $totalTotalOut = 0;
                        $totalAdultOut = 0;
                        $totalConcessionOut = 0;
                        if (count($allTripOutbounds) > 0) {
                            $out->writeln("YOU ARE IN HERE all route 1 comp allTripOutbounds");
                            $existOutTrip = true;
                            $countOut = 0;

                            foreach ($allTripOutbounds as $allTripOutbound) {
                                $outbound['trip_type'] = "OB";
                                $firstStage = Stage::where('route_id', $allTripOutbound->route_id)->orderby('stage_order', 'DESC')->first();
                                $outbound['start_point'] = $firstStage->stage_name;
                                $outbound['trip_no'] = "T" . $allTripOutbound->id;
                                $outbound['rph_no'] = $allTripOutbound->trip_number;
                                $outbound['bus_plate_no'] = $allTripOutbound->bus->bus_registration_number;
                                $outbound['bus_age'] = Carbon::parse($allTripOutbound->bus->bus_manufacturing_date)->diff(Carbon::now())->y;

                                $charge = 0;
                                $outbound['charge_km'] = $charge;

                                $outbound['driver_id'] = $allTripOutbound->driver_id;

                                $busStop = BusStand::where('route_id', $allTripOutbound->route_id)->count();
                                $outbound['bus_stop_travel'] = $busStop;
                                $totalBusStopOut += $busStop;

                                $travel = $allTripOutbound->route->inbound_distance;
                                $outbound['travel'] = $travel;
                                $totalTravelOut += $travel;

                                $claim = $charge * $travel;
                                $outbound['claim'] = $claim;
                                $totalClaimOut += $claim;

                                $travelGPS = $allTripOutbound->total_mileage;
                                $outbound['travel_gps'] = $travelGPS;
                                $totalTravelGPSOut += $travelGPS;

                                $claimGPS = $charge * $travelGPS;
                                $outbound['claim_gps'] = $claimGPS;
                                $totalClaimGPSOut += $claimGPS;

                                $outbound['status'] = "Complete";
                                $outbound['travel_BOP'] = $travelGPS;
                                $outbound['claim_BOP'] = $charge * $travelGPS;

                                $serviceStart = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                $serviceEnd = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                $outbound['start_point_time'] = $serviceStart;
                                $outbound['service_start'] = $serviceStart;
                                $outbound['service_end'] = $serviceEnd;

                                $actualStart = date("H:i", strtotime($allTripOutbound->start_trip));
                                $actualEnd = date("H:i", strtotime($allTripOutbound->end_trip));
                                $outbound['actual_start'] = $actualStart;
                                $outbound['actual_end'] = $actualEnd;

                                $firstSales = TicketSalesTransaction::where('trip_id', $allTripOutbound->id)->orderby('sales_date')->first();
                                $salesStart = date("H:i", strtotime($firstSales->sales_date));
                                $outbound['sales_start'] = $salesStart;
                                $lastSales = TicketSalesTransaction::where('trip_id', $allTripOutbound->id)->orderby('sales_date', 'DESC')->first();
                                $salesEnd = date("H:i", strtotime($lastSales->sales_date));
                                $outbound['sales_end'] = $salesEnd;

                                $diff = strtotime($actualStart) - strtotime($salesStart);

                                if ($diff > 10 || $diff < -10) {
                                    $outbound['punctuality'] = "NOT PUNCTUAL";
                                } else {
                                    $outbound['punctuality'] = "ONTIME";
                                }

                                $countPassenger = $allTripOutbound->total_adult + $allTripOutbound->total_concession;
                                $outbound['pass_count'] = $countPassenger;
                                $totalCountPassengerOut += $countPassenger;

                                $sales = $allTripOutbound->total_adult_amount + $allTripOutbound->total_concession_amount;
                                $outbound['total_sales'] = $sales;
                                $totalSalesOut += $sales;

                                $outbound['total_on'] = $countPassenger;
                                $totalTotalOut += $countPassenger;

                                $adult = $allTripOutbound->total_adult;
                                $outbound['adult'] = $adult;
                                $totalAdultOut += $adult;

                                $concession = $allTripOutbound->total_concession;
                                $outbound['concession'] = $concession;
                                $totalConcessionOut += $concession;

                                $allTripOut[$countOut] = $outbound;
                                $countOut++;
                            }
                            $totOut['total_bus_stop_out'] = $totalBusStopOut;
                            $totOut['total_travel_out'] = $totalTravelOut;
                            $totOut['total_claim_out'] = $totalClaimOut;
                            $totOut['total_travel_gps_out'] = $totalTravelGPSOut;
                            $totOut['total_claim_gps_out'] = $totalClaimGPSOut;
                            $totOut['total_count_passenger_out'] = $totalCountPassengerOut;
                            $totOut['total_sales_out'] = $totalSalesOut;
                            $totOut['total_total_out'] = $totalTotalOut;
                            $totOut['total_adult_out'] = $totalAdultOut;
                            $totOut['total_concession_out'] = $totalConcessionOut;
                            $allTripOut['total_outbound'] = $totOut;
                        }

                        $data_perDate = [];
                        if ($existInTrip == true && $existOutTrip == true) {
                            $totalBusStopDate = $totalBusStopIn + $totalBusStopOut;
                            $totalTravelDate = $totalTravelIn + $totalTravelOut;
                            $totalClaimDate = $totalClaimIn + $totalClaimOut;
                            $totalTravelGPSDate = $totalTravelGPSIn + $totalTravelGPSOut;
                            $totalClaimGPSDate = $totalClaimGPSIn + $totalClaimGPSOut;
                            $totalCountPassengerDate = $totalCountPassengerIn + $totalCountPassengerOut;
                            $totalSalesDate = $totalSalesIn + $totalSalesOut;
                            $totalTotalDate = $totalTotalIn + $totalTotalOut;
                            $totalAdultDate = $totalAdultIn + $totalAdultOut;
                            $totalConcessionDate = $totalConcessionIn + $totalConcessionOut;

                            $perDate['total_bus_stop_date'] = $totalBusStopDate;
                            $perDate['total_travel_date'] = $totalTravelDate;
                            $perDate['total_claim_date'] = $totalClaimDate;
                            $perDate['total_travel_gps_date'] = $totalTravelGPSDate;
                            $perDate['total_claim_gps_date'] = $totalClaimGPSDate;
                            $perDate['total_count_passenger_date'] = $totalCountPassengerDate;
                            $perDate['total_sales_date'] = $totalSalesDate;
                            $perDate['total_total_date'] = $totalTotalDate;
                            $perDate['total_adult_date'] = $totalAdultDate;
                            $perDate['total_concession_date'] = $totalConcessionDate;

                            $data_perDate['inbound_data'] = $allTripIn;
                            $data_perDate['outbound_data'] = $allTripOut;
                            $data_perDate['total_per_date'] = $perDate;

                            $allDate[$all_date] = $data_perDate;
                            $allData['data'] = $allDate;
                            $allData['route_name'] = $allRouteCompany->route_name;
                            $route[$allRouteCompany->route_number] = $allData;
                            $data['allRoute'] = $route;

                        } elseif ($existInTrip == true && $existOutTrip == false) {
                            $totalBusStopDate = $totalBusStopIn;
                            $totalTravelDate = $totalTravelIn;
                            $totalClaimDate = $totalClaimIn;
                            $totalTravelGPSDate = $totalTravelGPSIn;
                            $totalClaimGPSDate = $totalClaimGPSIn;
                            $totalCountPassengerDate = $totalCountPassengerIn;
                            $totalSalesDate = $totalSalesIn;
                            $totalTotalDate = $totalTotalIn;
                            $totalAdultDate = $totalAdultIn;
                            $totalConcessionDate = $totalConcessionIn;

                            $perDate['total_bus_stop_date'] = $totalBusStopDate;
                            $perDate['total_travel_date'] = $totalTravelDate;
                            $perDate['total_claim_date'] = $totalClaimDate;
                            $perDate['total_travel_gps_date'] = $totalTravelGPSDate;
                            $perDate['total_claim_gps_date'] = $totalClaimGPSDate;
                            $perDate['total_count_passenger_date'] = $totalCountPassengerDate;
                            $perDate['total_sales_date'] = $totalSalesDate;
                            $perDate['total_total_date'] = $totalTotalDate;
                            $perDate['total_adult_date'] = $totalAdultDate;
                            $perDate['total_concession_date'] = $totalConcessionDate;

                            $data_perDate['inbound_data'] = $allTripIn;
                            $data_perDate['total_per_date'] = $perDate;

                            $allDate[$all_date] = $data_perDate;
                            $allData['route_name'] = $allRouteCompany->route_name;
                            $allData['data'] = $allDate;
                            $route[$allRouteCompany->route_number] = $allData;
                            $data['allRoute'] = $route;

                        } elseif ($existInTrip == false && $existOutTrip == true) {
                            $totalBusStopDate = $totalBusStopOut;
                            $totalTravelDate = $totalTravelOut;
                            $totalClaimDate = $totalClaimOut;
                            $totalTravelGPSDate = $totalTravelGPSOut;
                            $totalClaimGPSDate = $totalClaimGPSOut;
                            $totalCountPassengerDate = $totalCountPassengerOut;
                            $totalSalesDate = $totalSalesOut;
                            $totalTotalDate = $totalTotalOut;
                            $totalAdultDate = $totalAdultOut;
                            $totalConcessionDate = $totalConcessionOut;

                            $perDate['total_bus_stop_date'] = $totalBusStopDate;
                            $perDate['total_travel_date'] = $totalTravelDate;
                            $perDate['total_claim_date'] = $totalClaimDate;
                            $perDate['total_travel_gps_date'] = $totalTravelGPSDate;
                            $perDate['total_claim_gps_date'] = $totalClaimGPSDate;
                            $perDate['total_count_passenger_date'] = $totalCountPassengerDate;
                            $perDate['total_sales_date'] = $totalSalesDate;
                            $perDate['total_total_date'] = $totalTotalDate;
                            $perDate['total_adult_date'] = $totalAdultDate;
                            $perDate['total_concession_date'] = $totalConcessionDate;

                            $data_perDate['outbound_data'] = $allTripOut;
                            $data_perDate['total_per_date'] = $perDate;

                            $allDate[$all_date] = $data_perDate;
                            $allData['route_name'] = $allRouteCompany->route_name;
                            $allData['data'] = $allDate;
                            $route[$allRouteCompany->route_number] = $allData;
                            $data['allRoute'] = $route;
                        } else {
                            $totalBusStopDate = 0;
                            $totalTravelDate = 0;
                            $totalClaimDate = 0;
                            $totalTravelGPSDate = 0;
                            $totalClaimGPSDate = 0;
                            $totalCountPassengerDate = 0;
                            $totalSalesDate = 0;
                            $totalTotalDate = 0;
                            $totalAdultDate = 0;
                            $totalConcessionDate = 0;

                            $data_perDate['inbound_data'] = [];
                            $data_perDate['outbound_data'] = [];
                            $data_perDate['total_per_date'] = [];

                            $allDate[$all_date] = $data_perDate;
                            $allData['route_name'] = $allRouteCompany->route_name;
                            $allData['data'] = $allDate;
                            $route[$allRouteCompany->route_number] = $allData;
                            $data['allRoute'] = $route;
                        }
                        $grandBusStop += $totalBusStopDate;
                        $grandTravel += $totalTravelDate;
                        $grandClaim += $totalClaimDate;
                        $grandTravelGPS += $totalTravelGPSDate;
                        $grandClaimGPS += $totalClaimGPSDate;
                        $grandCountPassenger += $totalCountPassengerDate;
                        $grandSale += $totalSalesDate;
                        $grandTotal += $totalTotalDate;
                        $grandTotalAdult+= $totalAdultDate;
                        $grandTotalConcession += $totalConcessionDate;
                    }
                }
                $grand['grand_bus_stop'] = $grandBusStop;
                $grand['grand_travel'] = $grandTravel;
                $grand['grand_claim'] = $grandClaim;
                $grand['grand_travel_gps'] = $grandTravelGPS;
                $grand['grand_claim_gps'] = $grandClaimGPS;
                $grand['grand_count_passenger'] = $grandCountPassenger;
                $grand['grand_sales'] = $grandSale;
                $grand['grand_total'] = $grandTotal;
                $grand['grand_adult'] = $grandTotalAdult;
                $grand['grand_concession'] = $grandTotalConcession;

                $data['grand'] = $grand;
                $claimDetails->add($data);
            }
        }
        //ClaimDetails all routes for all company
        else{
            $out->writeln("YOU ARE IN HERE ClaimDetails all route all company");
            $grandBusStop = 0;
            $grandTravel = 0;
            $grandClaim = 0;
            $grandTravelGPS = 0;
            $grandClaimGPS = 0;
            $grandCountPassenger = 0;
            $grandSale = 0;
            $grandTotal = 0;
            $grandTotalAdult = 0;
            $grandTotalConcession = 0;

            //Get all route
            $allRoutes = Route::all();

            foreach($allRoutes as $allRoute) {
                $out->writeln("YOU ARE IN HERE ClaimDetails all route all company allRoute loop");

                foreach($all_dates as $all_date) {
                    $out->writeln("YOU ARE IN HERE ClaimDetails all route all company all_date loop");
                    $totalBusStopIn = 0;
                    $totalTravelIn = 0;
                    $totalClaimIn = 0;
                    $totalTravelGPSIn = 0;
                    $totalClaimGPSIn = 0;
                    $totalCountPassengerIn = 0;
                    $totalSalesIn = 0;
                    $totalTotalIn = 0;
                    $totalAdultIn = 0;
                    $totalConcessionIn = 0;
                    $existInTrip = false;
                    $existOutTrip = false;

                    $firstDate = new Carbon($all_date);
                    $lastDate = new Carbon($all_date . '11:59:59');

                    //Inbound
                    $allTripInbounds = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$firstDate,$lastDate])
                        ->where('trip_code', 1)
                        ->get();

                    if (count($allTripInbounds) > 0) {
                        $out->writeln("YOU ARE IN HERE all route all company allTripInbounds()");
                        $existInTrip = true;
                        $countIn = 0;

                        foreach ($allTripInbounds as $allTripInbound) {
                            $out->writeln("existInTrip:" . $existInTrip);

                            $inbound['trip_type'] = "IB";
                            $firstStage = Stage::where('route_id', $allTripInbound->route_id)->orderby('stage_order')->first();
                            $inbound['start_point'] = $firstStage->stage_name;
                            $inbound['trip_no'] = "T" . $allTripInbound->id;
                            $inbound['rph_no'] = $allTripInbound->trip_number;
                            $inbound['bus_plate_no'] = $allTripInbound->bus->bus_registration_number;
                            $inbound['bus_age'] = Carbon::parse($allTripInbound->bus->bus_manufacturing_date)->diff(Carbon::now())->y;

                            $charge = 0;
                            $inbound['charge_km'] = $charge;

                            $inbound['driver_id'] = $allTripInbound->driver_id;

                            $busStop = BusStand::where('route_id', $allTripInbound->route_id)->count();
                            $inbound['bus_stop_travel'] = $busStop;
                            $totalBusStopIn += $busStop;

                            $travel = $allTripInbound->route->inbound_distance;
                            $inbound['travel'] = $travel;
                            $totalTravelIn += $travel;

                            $claim = $charge * $travel;
                            $inbound['claim'] = $claim;
                            $totalClaimIn += $claim;

                            $travelGPS = $allTripInbound->total_mileage;
                            $inbound['travel_gps'] = $travelGPS;
                            $totalTravelGPSIn += $travelGPS;

                            $claimGPS = $charge * $travelGPS;
                            $inbound['claim_gps'] = $claimGPS;
                            $totalClaimGPSIn += $claimGPS;

                            $inbound['status'] = "Complete";
                            $inbound['travel_BOP'] = $travelGPS;
                            $inbound['claim_BOP'] = $charge * $travelGPS;

                            $serviceStart = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                            $serviceEnd = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                            $inbound['start_point_time'] = $serviceStart;
                            $inbound['service_start'] = $serviceStart;
                            $inbound['service_end'] = $serviceEnd;

                            $actualStart = date("H:i", strtotime($allTripInbound->start_trip));
                            $actualEnd = date("H:i", strtotime($allTripInbound->end_trip));
                            $inbound['actual_start'] = $actualStart;
                            $inbound['actual_end'] = $actualEnd;

                            $firstSales = TicketSalesTransaction::where('trip_id', $allTripInbound->id)->orderby('sales_date')->first();
                            $salesStart = date("H:i", strtotime($firstSales->sales_date));
                            $inbound['sales_start'] = $salesStart;
                            $lastSales = TicketSalesTransaction::where('trip_id', $allTripInbound->id)->orderby('sales_date', 'DESC')->first();
                            $salesEnd = date("H:i", strtotime($lastSales->sales_date));
                            $inbound['sales_end'] = $salesEnd;

                            $diff = strtotime($actualStart) - strtotime($salesStart);

                            if ($diff > 10 || $diff < -10) {
                                $inbound['punctuality'] = "NOT PUNCTUAL";
                            } else {
                                $inbound['punctuality'] = "ONTIME";
                            }

                            $countPassenger = $allTripInbound->total_adult + $allTripInbound->total_concession;
                            $inbound['pass_count'] = $countPassenger;
                            $totalCountPassengerIn += $countPassenger;

                            $sales = $allTripInbound->total_adult_amount + $allTripInbound->total_concession_amount;
                            $inbound['total_sales'] = $sales;
                            $totalSalesIn += $sales;

                            $inbound['total_on'] = $countPassenger;
                            $totalTotalIn += $countPassenger;

                            $adult = $allTripInbound->total_adult;
                            $inbound['adult'] = $adult;
                            $totalAdultIn += $adult;

                            $concession = $allTripInbound->total_concession;
                            $inbound['concession'] = $concession;
                            $totalConcessionIn += $concession;

                            $allTripIn[$countIn] = $inbound;
                            $countIn++;
                        }
                        $totIn['total_bus_stop_in'] = $totalBusStopIn;
                        $totIn['total_travel_in'] = $totalTravelIn;
                        $totIn['total_claim_in'] = $totalClaimIn;
                        $totIn['total_travel_gps_in'] = $totalTravelGPSIn;
                        $totIn['total_claim_gps_in'] = $totalClaimGPSIn;
                        $totIn['total_count_passenger_in'] = $totalCountPassengerIn;
                        $totIn['total_sales_in'] = $totalSalesIn;
                        $totIn['total_total_in'] = $totalTotalIn;
                        $totIn['total_adult_in'] = $totalAdultIn;
                        $totIn['total_concession_in'] = $totalConcessionIn;
                        $allTripIn['total_inbound'] = $totIn;
                    }

                    //Outbound
                    $allTripOutbounds = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$firstDate,$lastDate])
                        ->where('trip_code', 0)
                        ->get();

                    $totalBusStopOut = 0;
                    $totalTravelOut = 0;
                    $totalClaimOut = 0;
                    $totalTravelGPSOut = 0;
                    $totalClaimGPSOut = 0;
                    $totalCountPassengerOut = 0;
                    $totalSalesOut = 0;
                    $totalTotalOut = 0;
                    $totalAdultOut = 0;
                    $totalConcessionOut = 0;
                    if (count($allTripOutbounds) > 0) {
                        $out->writeln("YOU ARE IN HERE certain route allTripOutbounds");
                        $existOutTrip = true;
                        $countOut = 0;

                        foreach ($allTripOutbounds as $allTripOutbound) {
                            $outbound['trip_type'] = "OB";
                            $firstStage = Stage::where('route_id', $allTripOutbound->route_id)->orderby('stage_order', 'DESC')->first();
                            $outbound['start_point'] = $firstStage->stage_name;
                            $outbound['trip_no'] = "T" . $allTripOutbound->id;
                            $outbound['rph_no'] = $allTripOutbound->trip_number;
                            $outbound['bus_plate_no'] = $allTripOutbound->bus->bus_registration_number;
                            $outbound['bus_age'] = Carbon::parse($allTripOutbound->bus->bus_manufacturing_date)->diff(Carbon::now())->y;

                            $charge = 0;
                            $outbound['charge_km'] = $charge;

                            $outbound['driver_id'] = $allTripOutbound->driver_id;

                            $busStop = BusStand::where('route_id', $allTripOutbound->route_id)->count();
                            $outbound['bus_stop_travel'] = $busStop;
                            $totalBusStopOut += $busStop;

                            $travel = $allTripOutbound->route->inbound_distance;
                            $outbound['travel'] = $travel;
                            $totalTravelOut += $travel;

                            $claim = $charge * $travel;
                            $outbound['claim'] = $claim;
                            $totalClaimOut += $claim;

                            $travelGPS = $allTripOutbound->total_mileage;
                            $outbound['travel_gps'] = $travelGPS;
                            $totalTravelGPSOut += $travelGPS;

                            $claimGPS = $charge * $travelGPS;
                            $outbound['claim_gps'] = $claimGPS;
                            $totalClaimGPSOut += $claimGPS;

                            $outbound['status'] = "Complete";
                            $outbound['travel_BOP'] = $travelGPS;
                            $outbound['claim_BOP'] = $charge * $travelGPS;

                            $serviceStart = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                            $serviceEnd = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                            $outbound['start_point_time'] = $serviceStart;
                            $outbound['service_start'] = $serviceStart;
                            $outbound['service_end'] = $serviceEnd;

                            $actualStart = date("H:i", strtotime($allTripOutbound->start_trip));
                            $actualEnd = date("H:i", strtotime($allTripOutbound->end_trip));
                            $outbound['actual_start'] = $actualStart;
                            $outbound['actual_end'] = $actualEnd;

                            $firstSales = TicketSalesTransaction::where('trip_id', $allTripOutbound->id)->orderby('sales_date')->first();
                            $salesStart = date("H:i", strtotime($firstSales->sales_date));
                            $outbound['sales_start'] = $salesStart;
                            $lastSales = TicketSalesTransaction::where('trip_id', $allTripOutbound->id)->orderby('sales_date', 'DESC')->first();
                            $salesEnd = date("H:i", strtotime($lastSales->sales_date));
                            $outbound['sales_end'] = $salesEnd;

                            $diff = strtotime($actualStart) - strtotime($salesStart);

                            if ($diff > 10 || $diff < -10) {
                                $outbound['punctuality'] = "NOT PUNCTUAL";
                            } else {
                                $outbound['punctuality'] = "ONTIME";
                            }

                            $countPassenger = $allTripOutbound->total_adult + $allTripOutbound->total_concession;
                            $outbound['pass_count'] = $countPassenger;
                            $totalCountPassengerOut += $countPassenger;

                            $sales = $allTripOutbound->total_adult_amount + $allTripOutbound->total_concession_amount;
                            $outbound['total_sales'] = $sales;
                            $totalSalesOut += $sales;

                            $outbound['total_on'] = $countPassenger;
                            $totalTotalOut += $countPassenger;

                            $adult = $allTripOutbound->total_adult;
                            $outbound['adult'] = $adult;
                            $totalAdultOut += $adult;

                            $concession = $allTripOutbound->total_concession;
                            $outbound['concession'] = $concession;
                            $totalConcessionOut += $concession;

                            $allTripOut[$countOut] = $outbound;
                            $countOut++;
                        }
                        $totOut['total_bus_stop_out'] = $totalBusStopOut;
                        $totOut['total_travel_out'] = $totalTravelOut;
                        $totOut['total_claim_out'] = $totalClaimOut;
                        $totOut['total_travel_gps_out'] = $totalTravelGPSOut;
                        $totOut['total_claim_gps_out'] = $totalClaimGPSOut;
                        $totOut['total_count_passenger_out'] = $totalCountPassengerOut;
                        $totOut['total_sales_out'] = $totalSalesOut;
                        $totOut['total_total_out'] = $totalTotalOut;
                        $totOut['total_adult_out'] = $totalAdultOut;
                        $totOut['total_concession_out'] = $totalConcessionOut;
                        $allTripOut['total_outbound'] = $totOut;
                    }

                    $data_perDate = [];
                    if ($existInTrip == true && $existOutTrip == true) {
                        $totalBusStopDate = $totalBusStopIn + $totalBusStopOut;
                        $totalTravelDate = $totalTravelIn + $totalTravelOut;
                        $totalClaimDate = $totalClaimIn + $totalClaimOut;
                        $totalTravelGPSDate = $totalTravelGPSIn + $totalTravelGPSOut;
                        $totalClaimGPSDate = $totalClaimGPSIn + $totalClaimGPSOut;
                        $totalCountPassengerDate = $totalCountPassengerIn + $totalCountPassengerOut;
                        $totalSalesDate = $totalSalesIn + $totalSalesOut;
                        $totalTotalDate = $totalTotalIn + $totalTotalOut;
                        $totalAdultDate = $totalAdultIn + $totalAdultOut;
                        $totalConcessionDate = $totalConcessionIn + $totalConcessionOut;

                        $perDate['total_bus_stop_date'] = $totalBusStopDate;
                        $perDate['total_travel_date'] = $totalTravelDate;
                        $perDate['total_claim_date'] = $totalClaimDate;
                        $perDate['total_travel_gps_date'] = $totalTravelGPSDate;
                        $perDate['total_claim_gps_date'] = $totalClaimGPSDate;
                        $perDate['total_count_passenger_date'] = $totalCountPassengerDate;
                        $perDate['total_sales_date'] = $totalSalesDate;
                        $perDate['total_total_date'] = $totalTotalDate;
                        $perDate['total_adult_date'] = $totalAdultDate;
                        $perDate['total_concession_date'] = $totalConcessionDate;

                        $data_perDate['inbound_data'] = $allTripIn;
                        $data_perDate['outbound_data'] = $allTripOut;
                        $data_perDate['total_per_date'] = $perDate;

                        $allDate[$all_date] = $data_perDate;
                        $allData['data'] = $allDate;
                        $allData['route_name'] = $allRoute->route_name;
                        $route[$allRoute->route_number] = $allData;
                        $data['allRoute'] = $route;
                    } elseif ($existInTrip == true && $existOutTrip == false) {
                        $totalBusStopDate = $totalBusStopIn;
                        $totalTravelDate = $totalTravelIn;
                        $totalClaimDate = $totalClaimIn;
                        $totalTravelGPSDate = $totalTravelGPSIn;
                        $totalClaimGPSDate = $totalClaimGPSIn;
                        $totalCountPassengerDate = $totalCountPassengerIn;
                        $totalSalesDate = $totalSalesIn;
                        $totalTotalDate = $totalTotalIn;
                        $totalAdultDate = $totalAdultIn;
                        $totalConcessionDate = $totalConcessionIn;

                        $perDate['total_bus_stop_date'] = $totalBusStopDate;
                        $perDate['total_travel_date'] = $totalTravelDate;
                        $perDate['total_claim_date'] = $totalClaimDate;
                        $perDate['total_travel_gps_date'] = $totalTravelGPSDate;
                        $perDate['total_claim_gps_date'] = $totalClaimGPSDate;
                        $perDate['total_count_passenger_date'] = $totalCountPassengerDate;
                        $perDate['total_sales_date'] = $totalSalesDate;
                        $perDate['total_total_date'] = $totalTotalDate;
                        $perDate['total_adult_date'] = $totalAdultDate;
                        $perDate['total_concession_date'] = $totalConcessionDate;

                        $data_perDate['inbound_data'] = $allTripIn;
                        $data_perDate['total_per_date'] = $perDate;

                        $allDate[$all_date] = $data_perDate;
                        $allData['route_name'] = $allRoute->route_name;
                        $allData['data'] = $allDate;
                        $route[$allRoute->route_number] = $allData;
                        $data['allRoute'] = $route;
                    } elseif ($existInTrip == false && $existOutTrip == true) {
                        $totalBusStopDate = $totalBusStopOut;
                        $totalTravelDate = $totalTravelOut;
                        $totalClaimDate = $totalClaimOut;
                        $totalTravelGPSDate = $totalTravelGPSOut;
                        $totalClaimGPSDate = $totalClaimGPSOut;
                        $totalCountPassengerDate = $totalCountPassengerOut;
                        $totalSalesDate = $totalSalesOut;
                        $totalTotalDate = $totalTotalOut;
                        $totalAdultDate = $totalAdultOut;
                        $totalConcessionDate = $totalConcessionOut;

                        $perDate['total_bus_stop_date'] = $totalBusStopDate;
                        $perDate['total_travel_date'] = $totalTravelDate;
                        $perDate['total_claim_date'] = $totalClaimDate;
                        $perDate['total_travel_gps_date'] = $totalTravelGPSDate;
                        $perDate['total_claim_gps_date'] = $totalClaimGPSDate;
                        $perDate['total_count_passenger_date'] = $totalCountPassengerDate;
                        $perDate['total_sales_date'] = $totalSalesDate;
                        $perDate['total_total_date'] = $totalTotalDate;
                        $perDate['total_adult_date'] = $totalAdultDate;
                        $perDate['total_concession_date'] = $totalConcessionDate;

                        $data_perDate['outbound_data'] = $allTripOut;
                        $data_perDate['total_per_date'] = $perDate;

                        $allDate[$all_date] = $data_perDate;
                        $allData['route_name'] = $allRoute->route_name;
                        $allData['data'] = $allDate;
                        $route[$allRoute->route_number] = $allData;
                        $data['allRoute'] = $route;
                    } else {
                        $totalBusStopDate = 0;
                        $totalTravelDate = 0;
                        $totalClaimDate = 0;
                        $totalTravelGPSDate = 0;
                        $totalClaimGPSDate = 0;
                        $totalCountPassengerDate = 0;
                        $totalSalesDate = 0;
                        $totalTotalDate = 0;
                        $totalAdultDate = 0;
                        $totalConcessionDate = 0;

                        $data_perDate['inbound_data'] = [];
                        $data_perDate['outbound_data'] = [];
                        $data_perDate['total_per_date'] = [];

                        $allDate[$all_date] = $data_perDate;
                        $allData['route_name'] = $allRoute->route_name;
                        $allData['data'] = $allDate;
                        $route[$allRoute->route_number] = $allData;
                        $data['allRoute'] = $route;
                    }

                    $grandBusStop += $totalBusStopDate;
                    $grandTravel += $totalTravelDate;
                    $grandClaim += $totalClaimDate;
                    $grandTravelGPS += $totalTravelGPSDate;
                    $grandClaimGPS += $totalClaimGPSDate;
                    $grandCountPassenger += $totalCountPassengerDate;
                    $grandSale += $totalSalesDate;
                    $grandTotal += $totalTotalDate;
                    $grandTotalAdult+= $totalAdultDate;
                    $grandTotalConcession += $totalConcessionDate;
                }
            }

            $grand['grand_bus_stop'] = $grandBusStop;
            $grand['grand_travel'] = $grandTravel;
            $grand['grand_claim'] = $grandClaim;
            $grand['grand_travel_gps'] = $grandTravelGPS;
            $grand['grand_claim_gps'] = $grandClaimGPS;
            $grand['grand_count_passenger'] = $grandCountPassenger;
            $grand['grand_sales'] = $grandSale;
            $grand['grand_total'] = $grandTotal;
            $grand['grand_adult'] = $grandTotalAdult;
            $grand['grand_concession'] = $grandTotalConcession;

            $data['grand'] = $grand;
            $claimDetails->add($data);
        }

        return Excel::download(new SPADClaimDetails($all_dates, $claimDetails, $validatedData['dateFrom'], $validatedData['dateTo']), 'ClaimDetails_Report_SPAD.xlsx');
    }

    public function printClaimDetailGPS(){
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printClaimDetailGPS()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required','int'],
        ])->validate();

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        $claimDetailGPSSPAD = collect();

        if($this->selectedCompany) {
            //Claim Details GPS specific route specific company
            $selectedCompany = Company::where('id', $this->selectedCompany)->first();
            $networkArea = $selectedCompany->company_name;

            if (!empty($this->state['route_id'])) {
                $out->writeln("YOU ARE IN HERE Claim Details GPS specific route specific company");
                $selectedRoute = Route::where('id', $this->state['route_id'])->first();
                $routeNo = $selectedRoute->route_number;
                $routeNameIn = $selectedRoute->route_name;
                $data = [];

                foreach ($all_dates as $all_date) {
                    $out->writeln("YOU ARE IN HERE Claim Details GPS all_date loop");

                    $firstDate = new Carbon($all_date);
                    $lastDate = new Carbon($all_date . '11:59:59');

                    $tripPerDates = TripDetail::where('route_id', $routeNo)
                        ->whereBetween('start_trip', [$firstDate,$lastDate])
                        ->get();

                    if (count($tripPerDates) > 0) {
                        foreach ($tripPerDates as $tripPerDate) {
                            if ($tripPerDate->trip_code == 1) {
                                $title = $tripPerDate->id . ' - ' . $routeNo . ' - ' . $routeNameIn . ' - IB  - ' . ' - ';
                            } else {
                                $routeNameOut = implode(" - ", array_reverse(explode(" - ", $routeNameIn)));
                                $title = $tripPerDate->id . ' - ' . $routeNo . ' - ' . $routeNameOut . ' - OB  - ' . ' - ';
                            }

                            $vehiclePositions = VehiclePosition::where('trip_id', $tripPerDate->id)->get();

                            $i=0;
                            $allGPS = [];
                            if (count($vehiclePositions) > 0) {
                                foreach ($vehiclePositions as $vehiclePosition) {
                                    //duration = upload_date - creation_date
                                    $duration = strtotime($vehiclePosition->date_time) - strtotime($vehiclePosition->date_time);
                                    $gps['bus_no'] = $vehiclePosition->Bus->bus_registration_number;
                                    $gps['creation_date'] = $vehiclePosition->date_time;
                                    $gps['speed'] = $vehiclePosition->speed;
                                    $gps['pmhs_status'] = 'PMHS STATUS';
                                    $gps['pmhs_upload_date'] = 'PMHS UPLOAD DATE';
                                    $gps['pmhs_id'] = 'PMHS ID';
                                    $gps['duration'] = $duration;

                                    $allGPS[$i++] = $gps;
                                }
                            }
                            $data[$title] = $allGPS;
                        }
                    }
                }
                $claimDetailGPSSPAD->add($data);
            }
        }
    }

    public function printClaimSummary()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN  printClaimSummary()");

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

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        $claimSummary = collect();

        if($this->selectedCompany) {
            //ClaimSummary certain route for specific company
            if(!empty($this->state['route_id'])) {
                $out->writeln("YOU ARE IN HERE ClaimSummary certain route for specific company");
                $grandTripPlanned= 0;
                $grandTripMade = 0;
                $grandServicePlanned = 0;
                $grandServiceServed = 0;
                $grandClaim = 0;
                $grandTravelGPS = 0;
                $grandClaimGPS = 0;

                $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                $firstStage = Stage::where('route_id', $validatedData['route_id'])->first();
                $lastStage = Stage::where('route_id', $validatedData['route_id'])->orderby('stage_order','DESC')->first();
                $route_name_in = $selectedRoute->route_number . ' ' . $firstStage->stage_name . ' - ' . $lastStage->stage_name;
                $route_name_out = $selectedRoute->route_number . ' ' . $lastStage->stage_name . ' - ' . $firstStage->stage_name;

                foreach ($all_dates as $all_date) {
                    $out->writeln("YOU ARE IN HERE ClaimSummary all route all company all_date loop");
                    $tripPlanned= 0;
                    $tripMadeIn = 0;
                    $tripMadeOut = 0;
                    $servicePlannedIn = 0;
                    $servicePlannedOut = 0;
                    $serviceServedIn = 0;
                    $serviceServedOut = 0;
                    $travelGPSIn = 0;
                    $travelGPSOut = 0;
                    $existInTrip = false;
                    $existOutTrip = false;

                    $firstDate = new Carbon($all_date);
                    $lastDate = new Carbon($all_date . '11:59:59');

                    //Trip Planned
                    $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate,$lastDate])->get();
                    foreach ($schedules as $schedule){
                        if($schedule->RouteScheduleMSTR->route_id == $validatedData['route_id']){
                            $tripPlanned++;
                            $servicePlannedIn += $schedule->RouteScheduleMSTR->inbound_distance;
                            $servicePlannedOut += $schedule->RouteScheduleMSTR->outbound_distance;
                        }
                    }

                    //Inbound
                    $allTripInbounds = TripDetail::where('route_id', $validatedData['route_id'])
                        ->whereBetween('start_trip', [$firstDate,$lastDate])
                        ->where('trip_code', 1)
                        ->get();

                    if (count($allTripInbounds) > 0) {
                        $out->writeln("YOU ARE IN HERE certain route allTripInbounds()");
                        $existInTrip = true;

                        foreach ($allTripInbounds as $allTripInbound) {
                            $tripMadeIn++;
                            $mileage = $allTripInbound->total_mileage;
                            $serviceServedIn += $mileage;
                            /**Need to recalculate totalTravelGPSIn*/
                            $travelGPSIn += $mileage;
                        }

                        /**Need to recalculate totalClaim, totalClaimGPS */
                        $claimIn = $serviceServedIn * 2.95;
                        $claimGPSIn = $travelGPSIn * 2.95;

                        $inbound['route_name'] = $route_name_in;
                        $inbound['trip_planned_in'] = $tripPlanned;
                        $inbound['service_planned_in'] = $servicePlannedIn;
                        $inbound['trip_made_in'] = $tripMadeIn;
                        $inbound['service_served_in'] = $serviceServedIn;
                        $inbound['travel_gps_in'] = $serviceServedIn;
                        $inbound['claim_in'] = $claimIn;
                        $inbound['claim_gps_in'] = $claimGPSIn;
                    }

                    //Outbound
                    $allTripOutbounds = TripDetail::where('route_id', $validatedData['route_id'])
                        ->whereBetween('start_trip', [$firstDate,$lastDate])
                        ->where('trip_code', 0)
                        ->get();

                    if (count($allTripOutbounds) > 0) {
                        $out->writeln("YOU ARE IN HERE certain route allTripOutbounds");
                        $existOutTrip = true;

                        foreach ($allTripOutbounds as $allTripOutbound) {
                            $tripMadeOut++;
                            $mileage = $allTripOutbound->total_mileage;
                            $serviceServedOut += $mileage;
                            /**Need to recalculate totalTravelGPSOut*/
                            $travelGPSOut += $mileage;
                        }

                        /**Need to recalculate claimOut, claimGPSOut */
                        $claimOut = $serviceServedOut * 2.95;
                        $claimGPSOut = $travelGPSOut * 2.95;

                        $outbound['route_name'] = $route_name_out;
                        $outbound['trip_planned_out'] = $tripPlanned;
                        $outbound['service_planned_out'] = $servicePlannedOut;
                        $outbound['trip_made_out'] = $tripMadeOut;
                        $outbound['service_served_out'] = $serviceServedOut;
                        $outbound['travel_gps_out'] = $serviceServedOut;
                        $outbound['claim_out'] = $claimOut;
                        $outbound['claim_gps_out'] = $claimGPSOut;
                    }

                    if ($existInTrip == true && $existOutTrip == true) {
                        $totalTripPlanned = $inbound['trip_planned_in'] + $outbound['trip_planned_out'];
                        $totalTripMade =  $inbound['trip_made_in'] + $outbound['trip_made_out'];
                        $totalServicePlanned = $inbound['service_planned_in'] + $outbound['service_planned_out'];
                        $totalServiceServed = $inbound['service_served_in'] + $outbound['service_served_out'];
                        $totalClaim = $inbound['claim_in'] + $outbound['claim_out'];
                        $totalTravelGPS = $inbound['travel_gps_in'] + $outbound['travel_gps_out'];
                        $totalClaimGPS = $inbound['claim_gps_in'] + $outbound['claim_gps_out'];

                        $perDate['total_trip_planned'] = $totalTripPlanned;
                        $perDate['total_trip_made'] =  $totalTripMade;
                        $perDate['total_service_planned'] = $totalServicePlanned;
                        $perDate['total_service_served'] = $totalServiceServed;
                        $perDate['total_claim'] = $totalClaim;
                        $perDate['total_travel_gps'] = $totalTravelGPS;
                        $perDate['total_claim_gps'] = $totalClaimGPS;

                        $data_perDate['inbound_data'] = $inbound;
                        $data_perDate['outbound_data'] = $outbound;
                        $data_perDate['total_per_date'] = $perDate;

                        $allDate[$all_date] = $data_perDate;
                        /*$allData['data'] = $allDate;
                        $allData['route_name'] = $selectedRoute->route_name;*/
                        $route[$selectedRoute->route_number] = $allDate;
                        $data['allRoute'] = $route;

                    } elseif ($existInTrip == true && $existOutTrip == false) {
                        $totalTripPlanned = $inbound['trip_planned_in'];
                        $totalTripMade =  $inbound['trip_made_in'];
                        $totalServicePlanned = $inbound['service_planned_in'];
                        $totalServiceServed = $inbound['service_served_in'];
                        $totalClaim = $inbound['claim_in'];
                        $totalTravelGPS = $inbound['travel_gps_in'];
                        $totalClaimGPS = $inbound['claim_gps_in'];

                        $perDate['total_trip_planned'] = $totalTripPlanned;
                        $perDate['total_trip_made'] =  $totalTripMade;
                        $perDate['total_service_planned'] = $totalServicePlanned;
                        $perDate['total_service_served'] = $totalServiceServed;
                        $perDate['total_claim'] =$totalClaim;
                        $perDate['total_travel_gps'] = $totalTravelGPS;
                        $perDate['total_claim_gps'] = $totalClaimGPS;

                        $data_perDate['inbound_data'] = $inbound;
                        $data_perDate['outbound_data'] = [];
                        $data_perDate['total_per_date'] = $perDate;

                        $allDate[$all_date] = $data_perDate;
                        /*$allData['data'] = $allDate;
                        $allData['route_name'] = $selectedRoute->route_name;*/
                        $route[$selectedRoute->route_number] = $allDate;
                        $data['allRoute'] = $route;

                    } elseif ($existInTrip == false && $existOutTrip == true) {
                        $totalTripPlanned = $outbound['trip_planned_out'];
                        $totalTripMade =  $outbound['trip_made_out'];
                        $totalServicePlanned = $outbound['service_planned_out'];
                        $totalServiceServed = $outbound['service_served_out'];
                        $totalClaim =  $outbound['claim_out'];
                        $totalTravelGPS = $outbound['travel_gps_out'];
                        $totalClaimGPS = $outbound['claim_gps_out'];

                        $perDate['total_trip_planned'] =$totalTripPlanned;
                        $perDate['total_trip_made'] =  $totalTripMade;
                        $perDate['total_service_planned'] =  $totalServicePlanned;
                        $perDate['total_service_served'] = $totalServiceServed;
                        $perDate['total_claim'] =  $totalClaim;
                        $perDate['total_travel_gps'] = $totalTravelGPS;
                        $perDate['total_claim_gps'] = $totalClaimGPS;

                        $data_perDate['inbound_data'] = [];
                        $data_perDate['outbound_data'] = $outbound;
                        $data_perDate['total_per_date'] = $perDate;

                        $allDate[$all_date] = $data_perDate;
                        /*$allData['data'] = $allDate;
                        $allData['route_name'] = $selectedRoute->route_name;*/
                        $route[$selectedRoute->route_number] = $allDate;
                        $data['allRoute'] = $route;
                    } else {
                        $totalTripPlanned = 0;
                        $totalTripMade = 0;
                        $totalServicePlanned = 0;
                        $totalServiceServed = 0;
                        $totalClaim =  0;
                        $totalTravelGPS = 0;
                        $totalClaimGPS = 0;

                        $data_perDate['inbound_data'] = [];
                        $data_perDate['outbound_data'] = [];
                        $data_perDate['total_per_date'] = [];

                        $allDate[$all_date] = $data_perDate;
                        /*$allData['data'] = $allDate;
                        $allData['route_name'] = $selectedRoute->route_name;*/
                        $route[$selectedRoute->route_number] = $allDate;
                        $data['allRoute'] = $route;
                    }

                    $grandTripPlanned +=  $totalTripPlanned;
                    $grandTripMade += $totalTripMade;
                    $grandServicePlanned += $totalServicePlanned;
                    $grandServiceServed += $totalServiceServed;
                    $grandClaim += $totalClaim;
                    $grandTravelGPS += $totalTravelGPS;
                    $grandClaimGPS += $totalClaimGPS;
                }
                $grand['grand_trip_planned'] = $grandTripPlanned;
                $grand['grand_trip_made'] = $grandTripMade;
                $grand['grand_service_planned'] = $grandServicePlanned;
                $grand['grand_service_served'] = $grandServiceServed;
                $grand['grand_claim'] = $grandClaim;
                $grand['grand_travel_gps'] = $grandTravelGPS;
                $grand['grand_claim_gps'] = $grandClaimGPS;

                $data['grand'] = $grand;
                $claimSummary->add($data);
            } //ClaimSummary all routes for specific company
            else {
                $out->writeln("YOU ARE IN HERE ClaimSummary all route for specific company");
                $grandTripPlanned= 0;
                $grandTripMade = 0;
                $grandServicePlanned = 0;
                $grandServiceServed = 0;
                $grandClaim = 0;
                $grandTravelGPS = 0;
                $grandClaimGPS = 0;

                $allRoutes = Route::where('company_id', $this->selectedCompany)->get();

                foreach($allRoutes as $allRoute) {
                    $firstStage = Stage::where('route_id', $allRoute->id)->first();
                    $lastStage = Stage::where('route_id', $allRoute->id)->orderby('stage_order', 'DESC')->first();
                    $route_name_in = $allRoute->route_number . ' ' . $firstStage->stage_name . ' - ' . $lastStage->stage_name;
                    $route_name_out = $allRoute->route_number . ' ' . $lastStage->stage_name . ' - ' . $firstStage->stage_name;

                    $routeTripPlanned = 0;
                    $routeTripMade = 0;
                    $routeServicePlanned = 0;
                    $routeServiceServed = 0;
                    $routeClaim = 0;
                    $routeTravelGPS = 0;
                    $routeClaimGPS = 0;

                    foreach ($all_dates as $all_date) {
                        $out->writeln("YOU ARE IN HERE ClaimSummary all route for specific company all_date loop");
                        $tripPlanned = 0;
                        $tripMadeIn = 0;
                        $tripMadeOut = 0;
                        $servicePlannedIn = 0;
                        $servicePlannedOut = 0;
                        $serviceServedIn = 0;
                        $serviceServedOut = 0;
                        $travelGPSIn = 0;
                        $travelGPSOut = 0;
                        $existInTrip = false;
                        $existOutTrip = false;

                        $firstDate = new Carbon($all_date);
                        $lastDate = new Carbon($all_date . '11:59:59');

                        //Trip Planned
                        $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate, $lastDate])->get();
                        foreach ($schedules as $schedule) {
                            if ($schedule->RouteScheduleMSTR->route_id == $allRoute->id) {
                                $tripPlanned++;
                                $servicePlannedIn += $schedule->RouteScheduleMSTR->inbound_distance;
                                $servicePlannedOut += $schedule->RouteScheduleMSTR->outbound_distance;
                            }
                        }

                        //Inbound
                        $allTripInbounds = TripDetail::where('route_id', $allRoute->id)
                            ->whereBetween('start_trip', [$firstDate, $lastDate])
                            ->where('trip_code', 1)
                            ->get();

                        if (count($allTripInbounds) > 0) {
                            $out->writeln("YOU ARE IN HERE certain route allTripInbounds()");
                            $existInTrip = true;

                            foreach ($allTripInbounds as $allTripInbound) {
                                $tripMadeIn++;
                                $mileage = $allTripInbound->total_mileage;
                                $serviceServedIn += $mileage;
                                /**Need to recalculate totalTravelGPSIn*/
                                $travelGPSIn += $mileage;
                            }

                            /**Need to recalculate totalClaim, totalClaimGPS */
                            $claimIn = $serviceServedIn * 2.95;
                            $claimGPSIn = $travelGPSIn * 2.95;

                            $inbound['route_name'] = $route_name_in;
                            $inbound['trip_planned_in'] = $tripPlanned;
                            $inbound['service_planned_in'] = $servicePlannedIn;
                            $inbound['trip_made_in'] = $tripMadeIn;
                            $inbound['service_served_in'] = $serviceServedIn;
                            $inbound['travel_gps_in'] = $serviceServedIn;
                            $inbound['claim_in'] = $claimIn;
                            $inbound['claim_gps_in'] = $claimGPSIn;
                        }

                        //Outbound
                        $allTripOutbounds = TripDetail::where('route_id', $allRoute->id)
                            ->whereBetween('start_trip', [$firstDate, $lastDate])
                            ->where('trip_code', 0)
                            ->get();

                        if (count($allTripOutbounds) > 0) {
                            $out->writeln("YOU ARE IN HERE certain route allTripOutbounds");
                            $existOutTrip = true;

                            foreach ($allTripOutbounds as $allTripOutbound) {
                                $tripMadeOut++;
                                $mileage = $allTripOutbound->total_mileage;
                                $serviceServedOut += $mileage;
                                /**Need to recalculate totalTravelGPSOut*/
                                $travelGPSOut += $mileage;
                            }

                            /**Need to recalculate totalClaim, totalClaimGPS */
                            $claimOut = $serviceServedOut * 2.95;
                            $claimGPSOut = $travelGPSOut * 2.95;

                            $outbound['route_name'] = $route_name_out;
                            $outbound['trip_planned_out'] = $tripPlanned;
                            $outbound['service_planned_out'] = $servicePlannedOut;
                            $outbound['trip_made_out'] = $tripMadeOut;
                            $outbound['service_served_out'] = $serviceServedOut;
                            $outbound['travel_gps_out'] = $serviceServedOut;
                            $outbound['claim_out'] = $claimOut;
                            $outbound['claim_gps_out'] = $claimGPSOut;
                        }

                        if ($existInTrip == true && $existOutTrip == true) {
                            $totalTripPlanned = $inbound['trip_planned_in'] + $outbound['trip_planned_out'];
                            $totalTripMade = $inbound['trip_made_in'] + $outbound['trip_made_out'];
                            $totalServicePlanned = $inbound['service_planned_in'] + $outbound['service_planned_out'];
                            $totalServiceServed = $inbound['service_served_in'] + $outbound['service_served_out'];
                            $totalClaim = $inbound['claim_in'] + $outbound['claim_out'];
                            $totalTravelGPS = $inbound['travel_gps_in'] + $outbound['travel_gps_out'];
                            $totalClaimGPS = $inbound['claim_gps_in'] + $outbound['claim_gps_out'];

                            $perDate['total_trip_planned'] = $totalTripPlanned;
                            $perDate['total_trip_made'] = $totalTripMade;
                            $perDate['total_service_planned'] = $totalServicePlanned;
                            $perDate['total_service_served'] = $totalServiceServed;
                            $perDate['total_claim'] = $totalClaim;
                            $perDate['total_travel_gps'] = $totalTravelGPS;
                            $perDate['total_claim_gps'] = $totalClaimGPS;

                            $data_perDate['inbound_data'] = $inbound;
                            $data_perDate['outbound_data'] = $outbound;
                            $data_perDate['total_per_date'] = $perDate;

                            $allDate[$all_date] = $data_perDate;
                            /*$allData['data'] = $allDate;
                            $allData['route_name'] = $selectedRoute->route_name;*/
                            $route[$allRoute->route_number] = $allDate;

                        } elseif ($existInTrip == true && $existOutTrip == false) {
                            $totalTripPlanned = $inbound['trip_planned_in'];
                            $totalTripMade = $inbound['trip_made_in'];
                            $totalServicePlanned = $inbound['service_planned_in'];
                            $totalServiceServed = $inbound['service_served_in'];
                            $totalClaim = $inbound['claim_in'];
                            $totalTravelGPS = $inbound['travel_gps_in'];
                            $totalClaimGPS = $inbound['claim_gps_in'];

                            $perDate['total_trip_planned'] = $totalTripPlanned;
                            $perDate['total_trip_made'] = $totalTripMade;
                            $perDate['total_service_planned'] = $totalServicePlanned;
                            $perDate['total_service_served'] = $totalServiceServed;
                            $perDate['total_claim'] = $totalClaim;
                            $perDate['total_travel_gps'] = $totalTravelGPS;
                            $perDate['total_claim_gps'] = $totalClaimGPS;

                            $data_perDate['inbound_data'] = $inbound;
                            $data_perDate['outbound_data'] = [];
                            $data_perDate['total_per_date'] = $perDate;

                            $allDate[$all_date] = $data_perDate;
                            /*$allData['data'] = $allDate;
                            $allData['route_name'] = $selectedRoute->route_name;*/
                            $route[$allRoute->route_number] = $allDate;

                        } elseif ($existInTrip == false && $existOutTrip == true) {
                            $totalTripPlanned = $outbound['trip_planned_out'];
                            $totalTripMade = $outbound['trip_made_out'];
                            $totalServicePlanned = $outbound['service_planned_out'];
                            $totalServiceServed = $outbound['service_served_out'];
                            $totalClaim = $outbound['claim_out'];
                            $totalTravelGPS = $outbound['travel_gps_out'];
                            $totalClaimGPS = $outbound['claim_gps_out'];

                            $perDate['total_trip_planned'] = $totalTripPlanned;
                            $perDate['total_trip_made'] = $totalTripMade;
                            $perDate['total_service_planned'] = $totalServicePlanned;
                            $perDate['total_service_served'] = $totalServiceServed;
                            $perDate['total_claim'] = $totalClaim;
                            $perDate['total_travel_gps'] = $totalTravelGPS;
                            $perDate['total_claim_gps'] = $totalClaimGPS;

                            $data_perDate['inbound_data'] = [];
                            $data_perDate['outbound_data'] = $outbound;
                            $data_perDate['total_per_date'] = $perDate;

                            $allDate[$all_date] = $data_perDate;
                            /*$allData['data'] = $allDate;
                            $allData['route_name'] = $selectedRoute->route_name;*/
                            $route[$allRoute->route_number] = $allDate;
                        } else {
                            $totalTripPlanned = 0;
                            $totalTripMade = 0;
                            $totalServicePlanned = 0;
                            $totalServiceServed = 0;
                            $totalClaim = 0;
                            $totalTravelGPS = 0;
                            $totalClaimGPS = 0;

                            $data_perDate['inbound_data'] = [];
                            $data_perDate['outbound_data'] = [];
                            $data_perDate['total_per_date'] = [];

                            $allDate[$all_date] = $data_perDate;
                            /*$allData['data'] = $allDate;
                            $allData['route_name'] = $selectedRoute->route_name;*/
                            $route[$allRoute->route_number] = $allDate;
                        }
                        $routeTripPlanned += $totalTripPlanned;
                        $routeTripMade += $totalTripMade;
                        $routeServicePlanned += $totalServicePlanned;
                        $routeServiceServed += $totalServiceServed;
                        $routeClaim += $totalClaim;
                        $routeTravelGPS += $totalTravelGPS;
                        $routeClaimGPS += $totalClaimGPS;
                    }
                    $perRoute['route_trip_planned'] = $routeTripPlanned;
                    $perRoute['route_trip_made'] = $routeTripMade;
                    $perRoute['route_service_planned'] = $routeServicePlanned;
                    $perRoute['route_service_served'] = $routeServiceServed;
                    $perRoute['route_claim'] = $routeClaim;
                    $perRoute['route_travel_gps'] = $routeTravelGPS;
                    $perRoute['route_claim_gps'] = $routeClaimGPS;

                    $route['total_per_route'] = $perRoute;
                    $data['allRoute'] = $route;

                    $grandTripPlanned += $routeTripPlanned;
                    $grandTripMade += $routeTripMade;
                    $grandServicePlanned += $routeServicePlanned;
                    $grandServiceServed += $routeServiceServed;
                    $grandClaim += $routeClaim;
                    $grandTravelGPS += $routeTravelGPS;
                    $grandClaimGPS += $routeClaimGPS;

                }
                $grand['grand_trip_planned'] = $grandTripPlanned;
                $grand['grand_trip_made'] = $grandTripMade;
                $grand['grand_service_planned'] = $grandServicePlanned;
                $grand['grand_service_served'] = $grandServiceServed;
                $grand['grand_claim'] = $grandClaim;
                $grand['grand_travel_gps'] = $grandTravelGPS;
                $grand['grand_claim_gps'] = $grandClaimGPS;

                $data['grand'] = $grand;
                $claimSummary->add($data);
            }
        }
        //ClaimDetails all routes for all company
        else{
            $out->writeln("YOU ARE IN HERE ClaimSummaryall routes for all company");
            $grandTripPlanned= 0;
            $grandTripMade = 0;
            $grandServicePlanned = 0;
            $grandServiceServed = 0;
            $grandClaim = 0;
            $grandTravelGPS = 0;
            $grandClaimGPS = 0;

            $allRoutes = Route::all();

            foreach($allRoutes as $allRoute) {
                $firstStage = Stage::where('route_id', $allRoute->id)->first();
                $lastStage = Stage::where('route_id', $allRoute->id)->orderby('stage_order', 'DESC')->first();
                $route_name_in = $allRoute->route_number . ' ' . $firstStage->stage_name . ' - ' . $lastStage->stage_name;
                $route_name_out = $allRoute->route_number . ' ' . $lastStage->stage_name . ' - ' . $firstStage->stage_name;

                $routeTripPlanned = 0;
                $routeTripMade = 0;
                $routeServicePlanned = 0;
                $routeServiceServed = 0;
                $routeClaim = 0;
                $routeTravelGPS = 0;
                $routeClaimGPS = 0;

                foreach ($all_dates as $all_date) {
                    $out->writeln("YOU ARE IN HERE ClaimSummary all routes for all company all_date loop");
                    $tripPlanned = 0;
                    $tripMadeIn = 0;
                    $tripMadeOut = 0;
                    $servicePlannedIn = 0;
                    $servicePlannedOut = 0;
                    $serviceServedIn = 0;
                    $serviceServedOut = 0;
                    $travelGPSIn = 0;
                    $travelGPSOut = 0;
                    $existInTrip = false;
                    $existOutTrip = false;

                    $firstDate = new Carbon($all_date);
                    $lastDate = new Carbon($all_date . '11:59:59');

                    //Trip Planned
                    $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate, $lastDate])->get();
                    foreach ($schedules as $schedule) {
                        if ($schedule->RouteScheduleMSTR->route_id == $allRoute->id) {
                            $tripPlanned++;
                            $servicePlannedIn += $schedule->RouteScheduleMSTR->inbound_distance;
                            $servicePlannedOut += $schedule->RouteScheduleMSTR->outbound_distance;
                        }
                    }

                    //Inbound
                    $allTripInbounds = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$firstDate, $lastDate])
                        ->where('trip_code', 1)
                        ->get();

                    if (count($allTripInbounds) > 0) {
                        $out->writeln("YOU ARE IN HERE certain route allTripInbounds()");
                        $existInTrip = true;

                        foreach ($allTripInbounds as $allTripInbound) {
                            $tripMadeIn++;
                            $mileage = $allTripInbound->total_mileage;
                            $serviceServedIn += $mileage;
                            /**Need to recalculate totalTravelGPSIn*/
                            $travelGPSIn += $mileage;
                        }

                        /**Need to recalculate claimIn, claimGPSIn */
                        $claimIn = $serviceServedIn * 2.95;
                        $claimGPSIn = $travelGPSIn * 2.95;

                        $inbound['route_name'] = $route_name_in;
                        $inbound['trip_planned_in'] = $tripPlanned;
                        $inbound['service_planned_in'] = $servicePlannedIn;
                        $inbound['trip_made_in'] = $tripMadeIn;
                        $inbound['service_served_in'] = $serviceServedIn;
                        $inbound['travel_gps_in'] = $serviceServedIn;
                        $inbound['claim_in'] = $claimIn;
                        $inbound['claim_gps_in'] = $claimGPSIn;
                    }

                    //Outbound
                    $allTripOutbounds = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$firstDate, $lastDate])
                        ->where('trip_code', 0)
                        ->get();

                    if (count($allTripOutbounds) > 0) {
                        $out->writeln("YOU ARE IN HERE certain route allTripOutbounds");
                        $existOutTrip = true;

                        foreach ($allTripOutbounds as $allTripOutbound) {
                            $tripMadeOut++;
                            $mileage = $allTripOutbound->total_mileage;
                            $serviceServedOut += $mileage;
                            /**Need to recalculate totalTravelGPSOut*/
                            $travelGPSOut += $mileage;
                        }

                        /**Need to recalculate claimOut, claimGPSOut */
                        $claimOut = $serviceServedOut * 2.95;
                        $claimGPSOut = $travelGPSOut * 2.95;

                        $outbound['route_name'] = $route_name_out;
                        $outbound['trip_planned_out'] = $tripPlanned;
                        $outbound['service_planned_out'] = $servicePlannedOut;
                        $outbound['trip_made_out'] = $tripMadeOut;
                        $outbound['service_served_out'] = $serviceServedOut;
                        $outbound['travel_gps_out'] = $serviceServedOut;
                        $outbound['claim_out'] = $claimOut;
                        $outbound['claim_gps_out'] = $claimGPSOut;
                    }

                    if ($existInTrip == true && $existOutTrip == true) {
                        $totalTripPlanned = $inbound['trip_planned_in'] + $outbound['trip_planned_out'];
                        $totalTripMade = $inbound['trip_made_in'] + $outbound['trip_made_out'];
                        $totalServicePlanned = $inbound['service_planned_in'] + $outbound['service_planned_out'];
                        $totalServiceServed = $inbound['service_served_in'] + $outbound['service_served_out'];
                        $totalClaim = $inbound['claim_in'] + $outbound['claim_out'];
                        $totalTravelGPS = $inbound['travel_gps_in'] + $outbound['travel_gps_out'];
                        $totalClaimGPS = $inbound['claim_gps_in'] + $outbound['claim_gps_out'];

                        $perDate['total_trip_planned'] = $totalTripPlanned;
                        $perDate['total_trip_made'] = $totalTripMade;
                        $perDate['total_service_planned'] = $totalServicePlanned;
                        $perDate['total_service_served'] = $totalServiceServed;
                        $perDate['total_claim'] = $totalClaim;
                        $perDate['total_travel_gps'] = $totalTravelGPS;
                        $perDate['total_claim_gps'] = $totalClaimGPS;

                        $data_perDate['inbound_data'] = $inbound;
                        $data_perDate['outbound_data'] = $outbound;
                        $data_perDate['total_per_date'] = $perDate;

                        $allDate[$all_date] = $data_perDate;
                        /*$allData['data'] = $allDate;
                        $allData['route_name'] = $selectedRoute->route_name;*/
                        $route[$allRoute->route_number] = $allDate;

                    } elseif ($existInTrip == true && $existOutTrip == false) {
                        $totalTripPlanned = $inbound['trip_planned_in'];
                        $totalTripMade = $inbound['trip_made_in'];
                        $totalServicePlanned = $inbound['service_planned_in'];
                        $totalServiceServed = $inbound['service_served_in'];
                        $totalClaim = $inbound['claim_in'];
                        $totalTravelGPS = $inbound['travel_gps_in'];
                        $totalClaimGPS = $inbound['claim_gps_in'];

                        $perDate['total_trip_planned'] = $totalTripPlanned;
                        $perDate['total_trip_made'] = $totalTripMade;
                        $perDate['total_service_planned'] = $totalServicePlanned;
                        $perDate['total_service_served'] = $totalServiceServed;
                        $perDate['total_claim'] = $totalClaim;
                        $perDate['total_travel_gps'] = $totalTravelGPS;
                        $perDate['total_claim_gps'] = $totalClaimGPS;

                        $data_perDate['inbound_data'] = $inbound;
                        $data_perDate['outbound_data'] = [];
                        $data_perDate['total_per_date'] = $perDate;

                        $allDate[$all_date] = $data_perDate;
                        /*$allData['data'] = $allDate;
                        $allData['route_name'] = $selectedRoute->route_name;*/
                        $route[$allRoute->route_number] = $allDate;

                    } elseif ($existInTrip == false && $existOutTrip == true) {
                        $totalTripPlanned = $outbound['trip_planned_out'];
                        $totalTripMade = $outbound['trip_made_out'];
                        $totalServicePlanned = $outbound['service_planned_out'];
                        $totalServiceServed = $outbound['service_served_out'];
                        $totalClaim = $outbound['claim_out'];
                        $totalTravelGPS = $outbound['travel_gps_out'];
                        $totalClaimGPS = $outbound['claim_gps_out'];

                        $perDate['total_trip_planned'] = $totalTripPlanned;
                        $perDate['total_trip_made'] = $totalTripMade;
                        $perDate['total_service_planned'] = $totalServicePlanned;
                        $perDate['total_service_served'] = $totalServiceServed;
                        $perDate['total_claim'] = $totalClaim;
                        $perDate['total_travel_gps'] = $totalTravelGPS;
                        $perDate['total_claim_gps'] = $totalClaimGPS;

                        $data_perDate['inbound_data'] = [];
                        $data_perDate['outbound_data'] = $outbound;
                        $data_perDate['total_per_date'] = $perDate;

                        $allDate[$all_date] = $data_perDate;
                        /*$allData['data'] = $allDate;
                        $allData['route_name'] = $selectedRoute->route_name;*/
                        $route[$allRoute->route_number] = $allDate;
                    } else {
                        $totalTripPlanned = 0;
                        $totalTripMade = 0;
                        $totalServicePlanned = 0;
                        $totalServiceServed = 0;
                        $totalClaim = 0;
                        $totalTravelGPS = 0;
                        $totalClaimGPS = 0;

                        $data_perDate['inbound_data'] = [];
                        $data_perDate['outbound_data'] = [];
                        $data_perDate['total_per_date'] = [];

                        $allDate[$all_date] = $data_perDate;
                        $route[$allRoute->route_number] = $allDate;
                    }
                    $routeTripPlanned += $totalTripPlanned;
                    $routeTripMade += $totalTripMade;
                    $routeServicePlanned += $totalServicePlanned;
                    $routeServiceServed += $totalServiceServed;
                    $routeClaim += $totalClaim;
                    $routeTravelGPS += $totalTravelGPS;
                    $routeClaimGPS += $totalClaimGPS;
                }
                $perRoute['route_trip_planned'] = $routeTripPlanned;
                $perRoute['route_trip_made'] = $routeTripMade;
                $perRoute['route_service_planned'] = $routeServicePlanned;
                $perRoute['route_service_served'] = $routeServiceServed;
                $perRoute['route_claim'] = $routeClaim;
                $perRoute['route_travel_gps'] = $routeTravelGPS;
                $perRoute['route_claim_gps'] = $routeClaimGPS;

                $allDate['total_per_route'] = $perRoute;
                $route[$allRoute->route_number] = $allDate;
                $data['allRoute'] = $route;

                $grandTripPlanned += $routeTripPlanned;
                $grandTripMade += $routeTripMade;
                $grandServicePlanned += $routeServicePlanned;
                $grandServiceServed += $routeServiceServed;
                $grandClaim += $routeClaim;
                $grandTravelGPS += $routeTravelGPS;
                $grandClaimGPS += $routeClaimGPS;

            }
            $grand['grand_trip_planned'] = $grandTripPlanned;
            $grand['grand_trip_made'] = $grandTripMade;
            $grand['grand_service_planned'] = $grandServicePlanned;
            $grand['grand_service_served'] = $grandServiceServed;
            $grand['grand_claim'] = $grandClaim;
            $grand['grand_travel_gps'] = $grandTravelGPS;
            $grand['grand_claim_gps'] = $grandClaimGPS;

            $data['grand'] = $grand;
            $claimSummary->add($data);
        }

        return Excel::download(new SPADClaimSummary($all_dates, $claimSummary, $validatedData['dateFrom'], $validatedData['dateTo']), 'ClaimSummary_Report_SPAD.xlsx');
    }

    public function printSummaryRoute()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printSummaryRoute()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required','int'],
        ])->validate();

        $out->writeln("YOU ARE IN printSummaryRoute() after validation");

        if($this->selectedCompany) {
            $out->writeln("YOU ARE IN printSummaryRoute() this->selectedCompany");
            $companyDetails = Company::where('id', $this->selectedCompany)->first();
            $networkArea = $companyDetails->company_name;

            $out->writeln("YOU ARE IN printSummaryRoute() route not empty");

            $startDate = new Carbon($validatedData['dateFrom']);
            $endDate = new Carbon($validatedData['dateTo']);
            $all_dates = array();

            while ($startDate->lte($endDate)) {
                $all_dates[] = $startDate->toDateString();

                $startDate->addDay();
            }

            $summaryByRoute = collect();

            $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
            $grandKMPlanned = 0;
            $grandKMServed = 0;
            $grandTripServed = 0;
            $grandMissedTrip = 0;
            $grandEarlyLate = 0;
            $grandBreakdown = 0;
            $grandAccidents = 0;
            $grandRidershipCount = 0;
            $grandRidershipTicket = 0;
            $grandFarebox = 0;
            foreach ($all_dates as $all_date) {
                $out->writeln("YOU ARE IN SummaryByRoute all_date loop");
                $totalKMServed = 0;
                $totalFarebox = 0;
                $totalRidershipCount = 0;
                $totalRidershipTicket = 0;
                $earlyLateCount = 0;
                $totalTripPlanned = 0;

                $firstDate = new Carbon($all_date);
                $lastDate = new Carbon($all_date . '11:59:59');

                $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate, $lastDate])->get();

                if (count($schedules) > 0) {
                    foreach ($schedules as $schedule) {
                        if ($schedule->RouteScheduleMSTR->route_id == $selectedRoute->id) {
                            $totalTripPlanned++;
                        }
                    }
                }

                $kmInbound = $selectedRoute->inbound_distance * $totalTripPlanned;
                $kmOutbound = $selectedRoute->outbound_distance * $totalTripPlanned;
                $totalKMPlanned = $kmInbound + $kmOutbound;

                $allTrips = TripDetail::where('route_id', $selectedRoute->id)
                    ->whereBetween('start_trip', [$firstDate, $lastDate])
                    ->get();

                if (count($allTrips) > 0) {
                    $out->writeln("YOU ARE IN HERE certain route allTrips()");

                    foreach ($allTrips as $allTrip) {
                        //Total KM Service Served
                        $kmServed = $allTrip->total_mileage;
                        $totalKMServed += $kmServed;

                        //Early-Late
                        foreach ($schedules as $schedule) {
                            if ($schedule->RouteScheduleMSTR->route_id == $selectedRoute->id) {
                                if ($schedule->RouteScheduleMSTR->id == $allTrip->route_schedule_mstr_id) {
                                    $diff = strtotime($schedule->schedule_start_time) - strtotime($allTrip->start_trip);
                                    if ($diff > 10 || $diff < -10) {
                                        $earlyLateCount++;
                                    }
                                }
                            }
                        }

                        //Ridership Based On Count
                        $ridershipCount = $allTrip->total_adult + $allTrip->total_concession;
                        $totalRidershipCount += $ridershipCount;

                        //Ridership Based On Ticket Sales
                        $ridershipTicket = TicketSalesTransaction::where('trip_id', $allTrip->id)->count();
                        $totalRidershipTicket += $ridershipTicket;

                        //Farebox Collection
                        $farebox = $allTrip->total_adult_amount + $allTrip->total_concession_amount;
                        $totalFarebox += $farebox;
                    }
                }

                //Total Trip Served
                $totalTripServed = count($allTrips);

                //Total Missed Trip
                $totalMissedTrip = $totalTripPlanned - count($allTrips);

                /**Total Breakdown & Total Accidents need to revise**/
                $totalBreakdown = 0;
                $totalAccidents = 0;

                if ($firstDate->isWeekDay()) {
                    $trip_perDate['day'] = "WEEKDAY";
                } elseif ($firstDate->isWeekend()) {
                    $trip_perDate['day'] = "WEEKEND";
                }

                $trip_perDate['total_km_planned'] = $totalKMPlanned;
                $trip_perDate['total_km_served'] = $totalKMServed;
                $trip_perDate['total_trip_served'] = $totalTripServed;
                $trip_perDate['total_missed_trip'] = $totalMissedTrip;
                $trip_perDate['total_early_late'] = $earlyLateCount;
                $trip_perDate['total_breakdown'] = $totalBreakdown;
                $trip_perDate['total_accidents'] = $totalAccidents;
                $trip_perDate['total_ridership_count'] = $totalRidershipCount;
                $trip_perDate['total_ridership_tickets'] = $totalRidershipTicket;
                $trip_perDate['total_farebox'] = $totalFarebox;

                $data[$all_date] = $trip_perDate;

                $grandKMPlanned += $trip_perDate['total_km_planned'];
                $grandKMServed += $trip_perDate['total_km_served'];
                $grandTripServed += $trip_perDate['total_trip_served'];
                $grandMissedTrip += $trip_perDate['total_missed_trip'];
                $grandEarlyLate += $trip_perDate['total_early_late'];
                $grandBreakdown += $trip_perDate['total_breakdown'];
                $grandAccidents += $trip_perDate['total_accidents'];
                $grandRidershipCount += $trip_perDate['total_ridership_count'];
                $grandRidershipTicket += $trip_perDate['total_ridership_tickets'];
                $grandFarebox += $trip_perDate['total_farebox'];
            }

            $grand['grand_km_planned'] = $grandKMPlanned;
            $grand['grand_km_served'] = $grandKMServed;
            $grand['grand_trip_served'] = $grandTripServed;
            $grand['grand_missed_trip'] = $grandMissedTrip;
            $grand['grand_early_late'] = $grandEarlyLate;
            $grand['grand_breakdown'] = $grandBreakdown;
            $grand['grand_accidents'] = $grandAccidents;
            $grand['grand_ridership_count'] = $grandRidershipCount;
            $grand['grand_ridership_tickets'] = $grandRidershipTicket;
            $grand['grand_farebox'] = $grandFarebox;

            $data['grand'] = $grand;
            $summaryByRoute->add($data);
            return Excel::download(new SPADSummaryByRoute($summaryByRoute, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea, $selectedRoute->route_number), 'Summary_By_Route_Report_SPAD.xlsx');
        }else{
            $this->dispatchBrowserEvent('company_required');
        }
    }

    public function printSummaryNetwork()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE INprintSummaryNetwork()");

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

        $summaryByNetwork = collect();

        $grandKMPlanned = 0;
        $grandKMServed = 0;
        $grandClaim = 0;
        $grandTripPlanned = 0;
        $grandTripServed = 0;
        $grandBusDeployed = 0;
        $grandMissedTrip = 0;
        $grandEarlyLate = 0;
        $grandBreakdown = 0;
        $grandAccidents = 0;
        $grandRidership = 0;
        $grandFarebox = 0;
        if($this->selectedCompany) {
            $companyDetails = Company::where('id', $this->selectedCompany)->first();
            $networkArea = $companyDetails->company_name;

            if (!empty($this->state['route_id'])) {
                //SummaryByNetwork certain route for specific company
                $out->writeln("YOU ARE IN SummaryByNetwork this->selectedCompany");
                $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                $totalKMServed = 0;
                $totalFarebox = 0;
                $totalRidership = 0;
                $earlyLateCount = 0;
                $totalTripPlanned = 0;

                $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->get();
                if (count($schedules) > 0) {
                    foreach ($schedules as $schedule) {
                        if ($schedule->RouteScheduleMSTR->route_id == $selectedRoute->id) {
                            $totalTripPlanned++;
                        }
                    }
                }

                $kmInbound = $selectedRoute->inbound_distance * $totalTripPlanned;
                $kmOutbound = $selectedRoute->outbound_distance * $totalTripPlanned;
                $totalKMPlanned = $kmInbound + $kmOutbound;

                $allTrips = TripDetail::where('route_id', $selectedRoute->id)
                    ->whereBetween('start_trip', [$dateFrom, $dateTo])
                    ->get();
                if (count($allTrips) > 0) {
                    $out->writeln("YOU ARE IN HERE certain route allTrips()");

                    foreach ($allTrips as $allTrip) {
                        //Total KM Service Served
                        $kmServed = $allTrip->total_mileage;
                        $totalKMServed += $kmServed;

                        //Early-Late
                        foreach ($schedules as $schedule) {
                            if ($schedule->RouteScheduleMSTR->route_id == $selectedRoute->id) {
                                if ($schedule->RouteScheduleMSTR->id == $allTrip->route_schedule_mstr_id) {
                                    $diff = strtotime($schedule->schedule_start_time) - strtotime($allTrip->start_trip);
                                    if ($diff > 10 || $diff < -10) {
                                        $earlyLateCount++;
                                    }
                                }
                            }
                        }
                        //Ridership
                        $ridership = $ridership = $allTrip->total_adult + $allTrip->total_concession;
                        $totalRidership += $ridership;

                        //Farebox Collection
                        $farebox = $allTrip->total_adult_amount + $allTrip->total_concession_amount;
                        $totalFarebox += $farebox;
                    }
                }

                //Total No Bus Deployed
                $totalBusDeployed = TripDetail::where('route_id', $selectedRoute->id)
                    ->whereBetween('start_trip', [$dateFrom, $dateTo])
                    ->distinct('bus_id')
                    ->count();

                //Total Trip Served
                $totalTripServed = count($allTrips);

                //Total Missed Trip
                $totalMissedTrip = $totalTripPlanned - count($allTrips);

                /**Need to revise on Total Claim (RM)*/
                $charge = 0;
                $totalClaim = $charge * $totalKMServed;

                /**Total Breakdown & Total Accidents need to revise**/
                $totalBreakdown = 0;
                $totalAccidents = 0;

                $perRoute['total_km_planned'] = $totalKMPlanned;
                $perRoute['total_km_served'] = $totalKMServed;
                $perRoute['total_claim'] = $totalClaim;
                $perRoute['total_trip_planned'] = $totalTripPlanned;
                $perRoute['total_trip_served'] = $totalTripServed;
                $perRoute['total_bus_deployed'] = $totalBusDeployed;
                $perRoute['total_missed_trip'] = $totalMissedTrip;
                $perRoute['total_early_late'] = $earlyLateCount;
                $perRoute['total_breakdown'] = $totalBreakdown;
                $perRoute['total_accidents'] = $totalAccidents;
                $perRoute['total_ridership'] = $totalRidership;
                $perRoute['total_farebox'] = $totalFarebox;

                $data[$selectedRoute->route_number] = $perRoute;

                $grandKMPlanned += $perRoute['total_km_planned'];
                $grandKMServed += $perRoute['total_km_served'];
                $grandClaim += $perRoute['total_claim'];
                $grandTripPlanned += $perRoute['total_trip_planned'];
                $grandTripServed += $perRoute['total_trip_served'];
                $grandBusDeployed += $perRoute['total_bus_deployed'];
                $grandMissedTrip += $perRoute['total_missed_trip'];
                $grandEarlyLate += $perRoute['total_early_late'];
                $grandBreakdown += $perRoute['total_breakdown'];
                $grandAccidents += $perRoute['total_accidents'];
                $grandRidership += $perRoute['total_ridership'];
                $grandFarebox += $perRoute['total_farebox'];

                $grand['grand_km_planned'] = $grandKMPlanned;
                $grand['grand_km_served'] = $grandKMServed;
                $grand['grand_claim'] = $grandClaim;
                $grand['grand_trip_planned'] = $grandTripPlanned;
                $grand['grand_trip_served'] = $grandTripServed;
                $grand['grand_bus_deployed'] = $grandBusDeployed;
                $grand['grand_missed_trip'] = $grandMissedTrip;
                $grand['grand_early_late'] = $grandEarlyLate;
                $grand['grand_breakdown'] = $grandBreakdown;
                $grand['grand_accidents'] = $grandAccidents;
                $grand['grand_ridership'] = $grandRidership;
                $grand['grand_farebox'] = $grandFarebox;

                $data['grand'] = $grand;
                $summaryByNetwork->add($data);
            }
            else {
                $routeByCompanies = Route::where('company_id', $companyDetails->id)->get();

                foreach($routeByCompanies as $routeByCompany) {
                    $totalKMServed = 0;
                    $totalFarebox = 0;
                    $totalRidership = 0;
                    $earlyLateCount = 0;
                    $totalTripPlanned = 0;

                    $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->get();
                    if (count($schedules) > 0) {
                        foreach ($schedules as $schedule) {
                            if ($schedule->RouteScheduleMSTR->route_id == $routeByCompany->id) {
                                $totalTripPlanned++;
                            }
                        }
                    }

                    $kmInbound = $routeByCompany->inbound_distance * $totalTripPlanned;
                    $kmOutbound = $routeByCompany->outbound_distance * $totalTripPlanned;
                    $totalKMPlanned = $kmInbound + $kmOutbound;

                    $allTrips = TripDetail::where('route_id', $routeByCompany->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->get();
                    if (count($allTrips) > 0) {
                        $out->writeln("YOU ARE IN HERE certain route allTrips()");

                        foreach ($allTrips as $allTrip) {
                            //Total KM Service Served
                            $kmServed = $allTrip->total_mileage;
                            $totalKMServed += $kmServed;

                            //Early-Late
                            foreach ($schedules as $schedule) {
                                if ($schedule->RouteScheduleMSTR->route_id == $routeByCompany->id) {
                                    if ($schedule->RouteScheduleMSTR->id == $allTrip->route_schedule_mstr_id) {
                                        $diff = strtotime($schedule->schedule_start_time) - strtotime($allTrip->start_trip);
                                        if ($diff > 10 || $diff < -10) {
                                            $earlyLateCount++;
                                        }
                                    }
                                }
                            }
                            //Ridership
                            $ridership = $allTrip->total_adult + $allTrip->total_concession;
                            $totalRidership += $ridership;

                            //Farebox Collection
                            $farebox = $allTrip->total_adult_amount + $allTrip->total_concession_amount;
                            $totalFarebox += $farebox;
                        }
                    }

                    //Total No Bus Deployed
                    $totalBusDeployed = TripDetail::where('route_id', $routeByCompany->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->distinct('bus_id')
                        ->count();

                    //Total Trip Served
                    $totalTripServed = count($allTrips);

                    //Total Missed Trip
                    $totalMissedTrip = $totalTripPlanned - count($allTrips);

                    /**Need to revise on Total Claim (RM)*/
                    $charge = 0;
                    $totalClaim = $charge * $totalKMServed;

                    /**Total Breakdown & Total Accidents need to revise**/
                    $totalBreakdown = 0;
                    $totalAccidents = 0;

                    $perRoute['total_km_planned'] = $totalKMPlanned;
                    $perRoute['total_km_served'] = $totalKMServed;
                    $perRoute['total_claim'] = $totalClaim;
                    $perRoute['total_trip_planned'] = $totalTripPlanned;
                    $perRoute['total_trip_served'] = $totalTripServed;
                    $perRoute['total_bus_deployed'] = $totalBusDeployed;
                    $perRoute['total_missed_trip'] = $totalMissedTrip;
                    $perRoute['total_early_late'] = $earlyLateCount;
                    $perRoute['total_breakdown'] = $totalBreakdown;
                    $perRoute['total_accidents'] = $totalAccidents;
                    $perRoute['total_ridership'] = $totalRidership;
                    $perRoute['total_farebox'] = $totalFarebox;

                    $data[$routeByCompany->route_number] = $perRoute;

                    $grandKMPlanned += $perRoute['total_km_planned'];
                    $grandKMServed += $perRoute['total_km_served'];
                    $grandClaim += $perRoute['total_claim'];
                    $grandTripPlanned += $perRoute['total_trip_planned'];
                    $grandTripServed += $perRoute['total_trip_served'];
                    $grandBusDeployed += $perRoute['total_bus_deployed'];
                    $grandMissedTrip += $perRoute['total_missed_trip'];
                    $grandEarlyLate += $perRoute['total_early_late'];
                    $grandBreakdown += $perRoute['total_breakdown'];
                    $grandAccidents += $perRoute['total_accidents'];
                    $grandRidership += $perRoute['total_ridership'];
                    $grandFarebox += $perRoute['total_farebox'];
                }
                $grand['grand_km_planned'] = $grandKMPlanned;
                $grand['grand_km_served'] = $grandKMServed;
                $grand['grand_claim'] = $grandClaim;
                $grand['grand_trip_planned'] = $grandTripPlanned;
                $grand['grand_trip_served'] = $grandTripServed;
                $grand['grand_bus_deployed'] = $grandBusDeployed;
                $grand['grand_missed_trip'] = $grandMissedTrip;
                $grand['grand_early_late'] = $grandEarlyLate;
                $grand['grand_breakdown'] = $grandBreakdown;
                $grand['grand_accidents'] = $grandAccidents;
                $grand['grand_ridership'] = $grandRidership;
                $grand['grand_farebox'] = $grandFarebox;

                $data['grand'] = $grand;
                $summaryByNetwork->add($data);
            }
        }
        else{
            $networkArea = "ALL";
            $allRoutes = Route::all();

            foreach($allRoutes as $allRoute) {
                $totalKMServed = 0;
                $totalFarebox = 0;
                $totalRidership = 0;
                $earlyLateCount = 0;
                $totalTripPlanned = 0;
                $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->get();
                if (count($schedules) > 0) {
                    foreach ($schedules as $schedule) {
                        if ($schedule->RouteScheduleMSTR->route_id == $allRoute->id) {
                            $totalTripPlanned++;
                        }
                    }
                }

                $kmInbound = $allRoute->inbound_distance * $totalTripPlanned;
                $kmOutbound = $allRoute->outbound_distance * $totalTripPlanned;
                $totalKMPlanned = $kmInbound + $kmOutbound;

                $allTrips = TripDetail::where('route_id', $allRoute->id)
                    ->whereBetween('start_trip', [$dateFrom, $dateTo])
                    ->get();
                if (count($allTrips) > 0) {
                    $out->writeln("YOU ARE IN HERE certain route allTrips()");

                    foreach ($allTrips as $allTrip) {
                        //Total KM Service Served
                        $kmServed = $allTrip->total_mileage;
                        $totalKMServed += $kmServed;

                        //Early-Late
                        foreach ($schedules as $schedule) {
                            if ($schedule->RouteScheduleMSTR->route_id == $allRoute->id) {
                                if ($schedule->RouteScheduleMSTR->id == $allTrip->route_schedule_mstr_id) {
                                    $diff = strtotime($schedule->schedule_start_time) - strtotime($allTrip->start_trip);
                                    if ($diff > 10 || $diff < -10) {
                                        $earlyLateCount++;
                                    }
                                }
                            }
                        }
                        //Ridership
                        $ridership = $allTrip->total_adult + $allTrip->total_concession;
                        $totalRidership += $ridership;

                        //Farebox Collection
                        $farebox = $allTrip->total_adult_amount + $allTrip->total_concession_amount;
                        $totalFarebox += $farebox;
                    }
                }

                //Total No Bus Deployed
                $totalBusDeployed = TripDetail::where('route_id', $allRoute->id)
                    ->whereBetween('start_trip', [$dateFrom, $dateTo])
                    ->distinct('bus_id')
                    ->count();

                //Total Trip Served
                $totalTripServed = count($allTrips);

                //Total Missed Trip
                $totalMissedTrip = $totalTripPlanned - count($allTrips);

                /**Need to revise on Total Claim (RM)*/
                $charge = 0;
                $totalClaim = $charge * $totalKMServed;

                /**Total Breakdown & Total Accidents need to revise**/
                $totalBreakdown = 0;
                $totalAccidents = 0;

                $perRoute['total_km_planned'] = $totalKMPlanned;
                $perRoute['total_km_served'] = $totalKMServed;
                $perRoute['total_claim'] = $totalClaim;
                $perRoute['total_trip_planned'] = $totalTripPlanned;
                $perRoute['total_trip_served'] = $totalTripServed;
                $perRoute['total_bus_deployed'] = $totalBusDeployed;
                $perRoute['total_missed_trip'] = $totalMissedTrip;
                $perRoute['total_early_late'] = $earlyLateCount;
                $perRoute['total_breakdown'] = $totalBreakdown;
                $perRoute['total_accidents'] = $totalAccidents;
                $perRoute['total_ridership'] = $totalRidership;
                $perRoute['total_farebox'] = $totalFarebox;

                $data[$allRoute->route_number] = $perRoute;

                $grandKMPlanned += $perRoute['total_km_planned'];
                $grandKMServed += $perRoute['total_km_served'];
                $grandClaim += $perRoute['total_claim'];
                $grandTripPlanned += $perRoute['total_trip_planned'];
                $grandTripServed += $perRoute['total_trip_served'];
                $grandBusDeployed += $perRoute['total_bus_deployed'];
                $grandMissedTrip += $perRoute['total_missed_trip'];
                $grandEarlyLate += $perRoute['total_early_late'];
                $grandBreakdown += $perRoute['total_breakdown'];
                $grandAccidents += $perRoute['total_accidents'];
                $grandRidership += $perRoute['total_ridership'];
                $grandFarebox += $perRoute['total_farebox'];
            }
            $grand['grand_km_planned'] = $grandKMPlanned;
            $grand['grand_km_served'] = $grandKMServed;
            $grand['grand_claim'] = $grandClaim;
            $grand['grand_trip_planned'] = $grandTripPlanned;
            $grand['grand_trip_served'] = $grandTripServed;
            $grand['grand_bus_deployed'] = $grandBusDeployed;
            $grand['grand_missed_trip'] = $grandMissedTrip;
            $grand['grand_early_late'] = $grandEarlyLate;
            $grand['grand_breakdown'] = $grandBreakdown;
            $grand['grand_accidents'] = $grandAccidents;
            $grand['grand_ridership'] = $grandRidership;
            $grand['grand_farebox'] = $grandFarebox;

            $data['grand'] = $grand;
            $summaryByNetwork->add($data);
        }
        return Excel::download(new SPADSummaryByNetwork($summaryByNetwork, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Summary_By_Network_Report_SPAD.xlsx');
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
        $finalOffRoute = 0;
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
        $days = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();
            $days[] = $startDate->day;

            $startDate->addDay();
        }

        $isbsfSPAD = collect();
        $colspan = count($all_dates) + 3;
        $monthName = $dateFrom->format('M Y');

        if($this->selectedCompany) {
            if(!empty($this->state['route_id'])){
                //Data of selected route based on selectedCompany
                $out->writeln("route:" . $validatedData['route_id']);
                $finalPlannedTrip=0;
                $finalCompletedIn=0;
                $finalCompletedOut=0;
                $finalTotalCompletedTrip=0;
                $finalOffRoute=0;
                $finalInboundDistance=0;
                $finalOutboundDistance=0;
                $finalTotalDistanceIn=0;
                $finalTotalDistanceOut=0;
                $finalTotalDistance=0;
                $finalTripOnTime=0;
                $finalTotalTripBreakdown=0;
                $finalNumBus=0;
                $finalFarebox=0;
                $finalRidership=0;
                $sumTripCompliance=0;
                $sumRouteCompliance=0;
                $sumPunctuality=0;
                $sumRealibility=0;

                $validatedRoute = Route::where('id', $validatedData['route_id'])->first();
                $routeName = $validatedRoute->route_number . ' ' . $validatedRoute->route_name;

                foreach ($all_dates as $all_date) {
                    $firstDate = new Carbon($all_date);
                    $lastDate = new Carbon($all_date . '11:59:59');

                    //Planned Trip
                    //Total Trip On Time
                    $plannedTrip = 0;
                    $ontimeCount = 0;
                    $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate,$lastDate])->get();
                    foreach ($schedules as $schedule) {
                        if ($validatedData['route_id'] == $schedule->RouteScheduleMSTR->route_id) {
                            $plannedTrip++;
                        }
                        if ($schedule->RouteScheduleMSTR->route_id == $validatedRoute->id) {
                            $tripPerTime = TripDetail::where('route_schedule_mstr_id', $schedule->RouteScheduleMSTR->id)->first();
                            $diff = strtotime($schedule->schedule_start_time) - strtotime($tripPerTime->start_trip);
                            if ($diff < 10 || $diff > -10) {
                                $ontimeCount++;
                            }
                        }
                    }
                    $totalTripOnTimeArr[$all_date] = $ontimeCount;
                    $finalTripOnTime += $ontimeCount;
                    $plannedTripArr[$all_date] = $plannedTrip;
                    $finalPlannedTrip += $plannedTrip;

                    //Completed Trip Out
                    $completedOut = TripDetail::whereBetween('start_trip', [$firstDate,$lastDate])->where('route_id', $validatedRoute->id)->count();
                    $completedOutArr[$all_date] = $completedOut;
                    $finalCompletedOut += $completedOut;

                    //Completed Trip In
                    $completedIn = TripDetail::whereBetween('start_trip', [$firstDate,$lastDate])->where('route_id', $validatedRoute->id)->count();
                    $completedInArr[$all_date] = $completedIn;
                    $finalCompletedIn += $completedIn;

                    //Total Completed Trip
                    $totalCompletedTrip = $completedOut + $completedIn;
                    $totalCompletedTripArr[$all_date] = $totalCompletedTrip;
                    $finalTotalCompletedTrip += $totalCompletedTrip;

                    //Trip Compliance
                    if($plannedTrip==0){
                        $tripCompliance = 0;
                    }else{
                        $tripCompliance = ($totalCompletedTrip/$plannedTrip) * 100;
                    }
                    $tripComplianceArr[$all_date] = $tripCompliance;
                    $sumTripCompliance += $tripCompliance;

                    /**Need to recalculate Off Route**/
                    $offRoute = 0;
                    $totalOffRouteArr[$all_date] = $offRoute;
                    $finalOffRoute += $offRoute;

                    //KM 1 Way Outbound
                    $outboundDistance = $validatedRoute->outbound_distance;
                    $outboundDistanceArr[$all_date] = $outboundDistance;
                    $finalOutboundDistance += $outboundDistance;

                    //KM 1 Way Inbound
                    $inboundDistance = $validatedRoute->inbound_distance;
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

                    /**Need to recalculate Total Trip Breakdown**/
                    $totalTripBreakdown = 0;
                    $totalTripBreakdownArr[$all_date] = $totalTripBreakdown;
                    $finalTotalTripBreakdown += $totalTripBreakdown;

                    //Punctuality Adherence
                    //Realibility Breakdown
                    //Route Compliance
                    if($totalCompletedTrip==0){
                        $punctuality = 0;
                        $realibility = 0;
                        $routeCompliance = 0;
                    }else{
                        $punctuality = $ontimeCount / $totalCompletedTrip * 100;
                        $realibility = (($totalCompletedTrip - $totalTripBreakdown) / $totalCompletedTrip) * 100;
                        $routeCompliance = ($offRoute / $totalCompletedTrip) * 100;

                    }
                    $punctualityArr[$all_date] = $punctuality;
                    $sumPunctuality += $punctuality;
                    $realibilityArr[$all_date] = $realibility;
                    $sumRealibility += $realibility;
                    $routeComplianceArr[$all_date] = $routeCompliance;
                    $sumRouteCompliance += $routeCompliance;

                    //Number of Bus
                    $numBus = TripDetail::distinct()
                        ->get(['bus_id'])
                        ->whereBetween('start_trip',[$firstDate,$lastDate])
                        ->where('route_id', $validatedRoute->id)
                        ->count();
                    $numBusArr[$all_date] = $numBus;
                    $finalNumBus += $numBus;

                    $allTrips = TripDetail::where('route_id',  $validatedRoute->id)
                        ->whereBetween('start_trip', [$firstDate,$lastDate])
                        ->get();

                    $farebox =0;
                    $ridership =0;
                    if(count($allTrips)>0){
                        foreach ($allTrips as $allTrip){
                            //Farebox
                            $adultFarebox = $allTrip->total_adult_amount;
                            $concessionFarebox = $allTrip->total_concession_amount;
                            $sumFarebox = $adultFarebox + $concessionFarebox;
                            $farebox += $sumFarebox;
                            //Ridership
                            $adultRidership = $allTrip->total_adult;
                            $concessionRidership = $allTrip->total_concession;
                            $sumRidership = $adultRidership + $concessionRidership;
                            $ridership += $sumRidership;
                        }
                    }
                    $fareboxArr[$all_date] = $farebox;
                    $finalFarebox += $farebox;
                    $ridershipArr[$all_date] = $ridership;
                    $finalRidership += $ridership;
                }
                $finalTripCompliance = ($sumTripCompliance/(count($all_dates)*100))*100;
                $finalRouteCompliance = ($sumRouteCompliance/(count($all_dates)*100))*100;
                $finalPunctuality = ($sumPunctuality/(count($all_dates)*100))*100;
                $finalRealibility = ($sumRealibility/(count($all_dates)*100))*100;

                $tripComplianceFormat = number_format((float)$finalTripCompliance, 2, '.', '');
                $routeComplianceFormat = number_format((float)$finalRouteCompliance, 2, '.', '');
                $punctualityFormat = number_format((float)$finalPunctuality, 2, '.', '');
                $realibilityFormat = number_format((float)$finalRealibility, 2, '.', '');

                $plannedTripArr['final_total'] = $finalPlannedTrip;
                $completedInArr['final_total'] = $finalCompletedIn;
                $completedOutArr['final_total'] = $finalCompletedOut;
                $totalCompletedTripArr['final_total'] = $finalTotalCompletedTrip;
                $tripComplianceArr['final_total'] = $tripComplianceFormat;
                $totalOffRouteArr['final_total'] = $finalOffRoute;
                $routeComplianceArr['final_total'] = $routeComplianceFormat;
                $inboundDistanceArr['final_total'] = $finalInboundDistance;
                $outboundDistanceArr['final_total'] = $finalOutboundDistance;
                $totalDistanceInArr['final_total'] = $finalTotalDistanceIn;
                $totalDistanceOutArr['final_total'] = $finalTotalDistanceOut;
                $totalDistanceArr['final_total'] = $finalTotalDistance;
                $totalTripOnTimeArr['final_total'] = $finalTripOnTime;
                $punctualityArr['final_total'] = $punctualityFormat;
                $totalTripBreakdownArr['final_total'] = $finalTotalTripBreakdown;
                $realibilityArr['final_total'] = $realibilityFormat;
                $numBusArr['final_total'] = $finalNumBus;
                $fareboxArr['final_total'] = $finalFarebox;
                $ridershipArr['final_total'] = $finalRidership;

                $content['planned_trip'] = $plannedTripArr;
                $content['completed_trip_in'] = $completedInArr;
                $content['completed_trip_out'] = $completedOutArr;
                $content['total_completed_trip'] = $totalCompletedTripArr;
                $content['trip_compliance'] = $tripComplianceArr;
                $content['total_off_route'] = $totalOffRouteArr;
                $content['route_compliance'] = $routeComplianceArr;
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

                $data[$routeName] = $content;
                //$data[$i++] = $route;

                $isbsfSPAD->add($data);
            } else {
                //Data of all route based on selectedCompany
                $routeByCompanies = Route::where('company_id', $this->selectedCompany)->get();

                foreach ($routeByCompanies as $routeByCompany) {
                    $routeName = $routeByCompany->route_number . ' ' . $routeByCompany->route_name;
                    $finalPlannedTrip=0;
                    $finalCompletedIn=0;
                    $finalCompletedOut=0;
                    $finalTotalCompletedTrip=0;
                    $finalOffRoute=0;
                    $finalInboundDistance=0;
                    $finalOutboundDistance=0;
                    $finalTotalDistanceIn=0;
                    $finalTotalDistanceOut=0;
                    $finalTotalDistance=0;
                    $finalTripOnTime=0;
                    $finalTotalTripBreakdown=0;
                    $finalNumBus=0;
                    $finalFarebox=0;
                    $finalRidership=0;
                    $sumTripCompliance=0;
                    $sumRouteCompliance=0;
                    $sumPunctuality=0;
                    $sumRealibility=0;

                    foreach ($all_dates as $all_date) {
                        $firstDate = new Carbon($all_date);
                        $lastDate = new Carbon($all_date . '11:59:59');

                        //Planned Trip
                        //Total Trip On Time
                        $plannedTrip = 0;
                        $ontimeCount = 0;
                        $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate,$lastDate])->get();
                        foreach ($schedules as $schedule) {
                            if ($routeByCompany->id == $schedule->RouteScheduleMSTR->route_id) {
                                $plannedTrip++;
                            }
                            if ($schedule->RouteScheduleMSTR->route_id == $routeByCompany->id) {
                                $tripPerTime = TripDetail::where('route_schedule_mstr_id', $schedule->RouteScheduleMSTR->id)->first();
                                $diff = strtotime($schedule->schedule_start_time) - strtotime($tripPerTime->start_trip);
                                if ($diff < 10 || $diff > -10) {
                                    $ontimeCount++;
                                }
                            }
                        }
                        $totalTripOnTimeArr[$all_date] = $ontimeCount;
                        $finalTripOnTime += $ontimeCount;
                        $plannedTripArr[$all_date] = $plannedTrip;
                        $finalPlannedTrip += $plannedTrip;

                        //Completed Trip Out
                        $completedOut = TripDetail::whereBetween('start_trip', [$firstDate,$lastDate])->where('route_id', $routeByCompany->id)->count();
                        $completedOutArr[$all_date] = $completedOut;
                        $finalCompletedOut += $completedOut;

                        //Completed Trip In
                        $completedIn = TripDetail::whereBetween('start_trip', [$firstDate,$lastDate])->where('route_id', $routeByCompany->id)->count();
                        $completedInArr[$all_date] = $completedIn;
                        $finalCompletedIn += $completedIn;

                        //Total Completed Trip
                        $totalCompletedTrip = $completedOut + $completedIn;
                        $totalCompletedTripArr[$all_date] = $totalCompletedTrip;
                        $finalTotalCompletedTrip += $totalCompletedTrip;

                        //Trip Compliance
                        if($plannedTrip==0){
                            $tripCompliance = 0;
                        }else{
                            $tripCompliance = ($totalCompletedTrip/$plannedTrip) * 100;
                        }
                        $tripComplianceArr[$all_date] = $tripCompliance;
                        $sumTripCompliance += $tripCompliance;

                        /**Need to recalculate Off Route**/
                        $offRoute = 0;
                        $totalOffRouteArr[$all_date] = $offRoute;
                        $finalOffRoute += $offRoute;

                        //KM 1 Way Outbound
                        $outboundDistance = $routeByCompany->outbound_distance;
                        $outboundDistanceArr[$all_date] = $outboundDistance;
                        $finalOutboundDistance += $outboundDistance;

                        //KM 1 Way Inbound
                        $inboundDistance = $routeByCompany->inbound_distance;
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

                        /**Need to recalculate Total Trip Breakdown**/
                        $totalTripBreakdown = 0;
                        $totalTripBreakdownArr[$all_date] = $totalTripBreakdown;
                        $finalTotalTripBreakdown += $totalTripBreakdown;

                        //Punctuality Adherence
                        //Realibility Breakdown
                        //Route Compliance
                        if($totalCompletedTrip==0){
                            $punctuality = 0;
                            $realibility = 0;
                            $routeCompliance = 0;
                        }else{
                            $punctuality = $ontimeCount / $totalCompletedTrip * 100;
                            $realibility = (($totalCompletedTrip - $totalTripBreakdown) / $totalCompletedTrip) * 100;
                            $routeCompliance = ($offRoute / $totalCompletedTrip) * 100;
                        }
                        $punctualityArr[$all_date] = $punctuality;
                        $sumPunctuality += $punctuality;
                        $realibilityArr[$all_date] = $realibility;
                        $sumRealibility += $realibility;
                        $routeComplianceArr[$all_date] = $routeCompliance;
                        $sumRouteCompliance += $routeCompliance;

                        //Number of Bus
                        $numBus = TripDetail::distinct()
                            ->get(['bus_id'])
                            ->whereBetween('start_trip',[$firstDate,$lastDate])
                            ->where('route_id', $routeByCompany->id)
                            ->count();
                        $numBusArr[$all_date] = $numBus;
                        $finalNumBus += $numBus;

                        $allTrips = TripDetail::where('route_id',  $routeByCompany->id)
                            ->whereBetween('start_trip',[$firstDate,$lastDate])
                            ->get();

                        $farebox =0;
                        $ridership =0;
                        if(count($allTrips)>0) {
                            foreach ($allTrips as $allTrip) {
                                //Farebox
                                $adultFarebox = $allTrip->total_adult_amount;
                                $concessionFarebox = $allTrip->total_concession_amount;
                                $sumFarebox = $adultFarebox + $concessionFarebox;
                                $farebox += $sumFarebox;
                                //Ridership
                                $adultRidership = $allTrip->total_adult;
                                $concessionRidership = $allTrip->total_concession;
                                $sumRidership = $adultRidership + $concessionRidership;
                                $ridership += $sumRidership;
                            }
                        }
                        $fareboxArr[$all_date] = $farebox;
                        $finalFarebox += $farebox;
                        $ridershipArr[$all_date] = $ridership;
                        $finalRidership += $ridership;
                    }
                    $finalTripCompliance = ($sumTripCompliance/(count($all_dates)*100))*100;
                    $finalRouteCompliance = ($sumRouteCompliance/(count($all_dates)*100))*100;
                    $finalPunctuality = ($sumPunctuality/(count($all_dates)*100))*100;
                    $finalRealibility = ($sumRealibility/(count($all_dates)*100))*100;

                    $tripComplianceFormat = number_format((float)$finalTripCompliance, 2, '.', '');
                    $routeComplianceFormat = number_format((float)$finalRouteCompliance, 2, '.', '');
                    $punctualityFormat = number_format((float)$finalPunctuality, 2, '.', '');
                    $realibilityFormat = number_format((float)$finalRealibility, 2, '.', '');

                    $plannedTripArr['final_total'] = $finalPlannedTrip;
                    $completedInArr['final_total'] = $finalCompletedIn;
                    $completedOutArr['final_total'] = $finalCompletedOut;
                    $totalCompletedTripArr['final_total'] = $finalTotalCompletedTrip;
                    $tripComplianceArr['final_total'] = $finalTripCompliance;
                    $totalOffRouteArr['final_total'] = $finalOffRoute;
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
                    $content['total_off_route'] = $totalOffRouteArr;
                    $content['route_compliance'] = $routeComplianceArr;
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

                    $data[$routeName] = $content;
                }
                $isbsfSPAD->add($data);
            }
        }
        else{
            //Data for all route of all company
            $allRoutes = Route::all();
            $i = 0;

            foreach ($allRoutes as $allRoute) {
                $finalPlannedTrip=0;
                $finalCompletedIn=0;
                $finalCompletedOut=0;
                $finalTotalCompletedTrip=0;
                $finalOffRoute=0;
                $finalInboundDistance=0;
                $finalOutboundDistance=0;
                $finalTotalDistanceIn=0;
                $finalTotalDistanceOut=0;
                $finalTotalDistance=0;
                $finalTripOnTime=0;
                $finalTotalTripBreakdown=0;
                $finalNumBus=0;
                $finalFarebox=0;
                $finalRidership=0;
                $sumTripCompliance=0;
                $sumRouteCompliance=0;
                $sumPunctuality=0;
                $sumRealibility=0;

                $routeName = $allRoute->route_number . ' ' . $allRoute->route_name;

                foreach ($all_dates as $all_date) {
                    $firstDate = new Carbon($all_date);
                    $lastDate = new Carbon($all_date . '11:59:59');

                    //Planned Trip
                    //Total Trip On Time
                    $plannedTrip = 0;
                    $ontimeCount = 0;
                    $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate,$lastDate])->get();
                    foreach ($schedules as $schedule) {
                        if ($validatedData['route_id'] == $schedule->RouteScheduleMSTR->route_id) {
                            $plannedTrip++;
                        }
                        if ($schedule->RouteScheduleMSTR->route_id == $allRoute->id) {
                            $tripPerTime = TripDetail::where('route_schedule_mstr_id', $schedule->RouteScheduleMSTR->id)->first();
                            $diff = strtotime($schedule->schedule_start_time) - strtotime($tripPerTime->start_trip);
                            if ($diff < 10 || $diff > -10) {
                                $ontimeCount++;
                            }
                        }
                    }
                    $totalTripOnTimeArr[$all_date] = $ontimeCount;
                    $finalTripOnTime += $ontimeCount;
                    $plannedTripArr[$all_date] = $plannedTrip;
                    $finalPlannedTrip += $plannedTrip;

                    //Completed Trip Out
                    $completedOut = TripDetail::whereBetween('start_trip', [$firstDate,$lastDate])->where('route_id', $allRoute->id)->count();
                    $completedOutArr[$all_date] = $completedOut;
                    $finalCompletedOut += $completedOut;

                    //Completed Trip In
                    $completedIn = TripDetail::whereBetween('start_trip', [$firstDate,$lastDate])->where('route_id', $allRoute->id)->count();
                    $completedInArr[$all_date] = $completedIn;
                    $finalCompletedIn += $completedIn;

                    //Total Completed Trip
                    $totalCompletedTrip = $completedOut + $completedIn;
                    $totalCompletedTripArr[$all_date] = $totalCompletedTrip;
                    $finalTotalCompletedTrip += $totalCompletedTrip;

                    //Trip Compliance
                    if($plannedTrip==0){
                        $tripCompliance = 0;
                    }else{
                        $tripCompliance = ($totalCompletedTrip/$plannedTrip) * 100;
                    }
                    $tripComplianceArr[$all_date] = $tripCompliance;
                    $sumTripCompliance += $tripCompliance;

                    /**Need to recalculate Off Route**/
                    $offRoute = 0;
                    $totalOffRouteArr[$all_date] = $offRoute;
                    $finalOffRoute += $offRoute;

                    //KM 1 Way Outbound
                    $outboundDistance = $allRoute->outbound_distance;
                    $outboundDistanceArr[$all_date] = $outboundDistance;
                    $finalOutboundDistance += $outboundDistance;

                    //KM 1 Way Inbound
                    $inboundDistance = $allRoute->inbound_distance;
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

                    /**Need to recalculate Total Trip Breakdown**/
                    $totalTripBreakdown = 0;
                    $totalTripBreakdownArr[$all_date] = $totalTripBreakdown;
                    $finalTotalTripBreakdown += $totalTripBreakdown;

                    //Punctuality Adherence
                    //Realibility Breakdown
                    //Route Compliance
                    if($totalCompletedTrip==0){
                        $punctuality = 0;
                        $realibility = 0;
                        $routeCompliance = 0;
                    }else{
                        $punctuality = $ontimeCount / $totalCompletedTrip * 100;
                        $realibility = (($totalCompletedTrip - $totalTripBreakdown) / $totalCompletedTrip) * 100;
                        $routeCompliance = ($offRoute / $totalCompletedTrip) * 100;
                    }
                    $punctualityArr[$all_date] = $punctuality;
                    $sumPunctuality += $punctuality;
                    $realibilityArr[$all_date] = $realibility;
                    $sumRealibility += $realibility;
                    $routeComplianceArr[$all_date] = $routeCompliance;
                    $sumRouteCompliance += $routeCompliance;

                    //Number of Bus
                    $numBus = TripDetail::distinct()
                        ->get(['bus_id'])
                        ->whereBetween('start_trip',[$firstDate,$lastDate])
                        ->where('route_id', $allRoute->id)
                        ->count();
                    $numBusArr[$all_date] = $numBus;
                    $finalNumBus += $numBus;

                    $allTrips = TripDetail::where('route_id',  $allRoute->id)
                        ->whereBetween('start_trip', [$firstDate,$lastDate])
                        ->get();

                    $farebox =0;
                    $ridership =0;
                    if(count($allTrips)>0) {
                        foreach ($allTrips as $allTrip) {
                            //Farebox
                            $adultFarebox = $allTrip->total_adult_amount;
                            $concessionFarebox = $allTrip->total_concession_amount;
                            $sumFarebox = $adultFarebox + $concessionFarebox;
                            $farebox += $sumFarebox;
                            //Ridership
                            $adultRidership = $allTrip->total_adult;
                            $concessionRidership = $allTrip->total_concession;
                            $sumRidership = $adultRidership + $concessionRidership;
                            $ridership += $sumRidership;
                        }
                    }
                    $fareboxArr[$all_date] = $farebox;
                    $finalFarebox += $farebox;
                    $ridershipArr[$all_date] = $ridership;
                    $finalRidership += $ridership;
                }
                $finalTripCompliance = ($sumTripCompliance/(count($all_dates)*100))*100;
                $finalRouteCompliance = ($sumRouteCompliance/(count($all_dates)*100))*100;
                $finalPunctuality = ($sumPunctuality/(count($all_dates)*100))*100;
                $finalRealibility = ($sumRealibility/(count($all_dates)*100))*100;

                $tripComplianceFormat = number_format((float)$finalTripCompliance, 2, '.', '');
                $routeComplianceFormat = number_format((float)$finalRouteCompliance, 2, '.', '');
                $punctualityFormat = number_format((float)$finalPunctuality, 2, '.', '');
                $realibilityFormat = number_format((float)$finalRealibility, 2, '.', '');

                $plannedTripArr['final_total'] = $finalPlannedTrip;
                $completedInArr['final_total'] = $finalCompletedIn;
                $completedOutArr['final_total'] = $finalCompletedOut;
                $totalCompletedTripArr['final_total'] = $finalTotalCompletedTrip;
                $tripComplianceArr['final_total'] = $tripComplianceFormat;
                $totalOffRouteArr['final_total'] = $finalOffRoute;
                $routeComplianceArr['final_total'] = $routeComplianceFormat;
                $inboundDistanceArr['final_total'] = $finalInboundDistance;
                $outboundDistanceArr['final_total'] = $finalOutboundDistance;
                $totalDistanceInArr['final_total'] = $finalTotalDistanceIn;
                $totalDistanceOutArr['final_total'] = $finalTotalDistanceOut;
                $totalDistanceArr['final_total'] = $finalTotalDistance;
                $totalTripOnTimeArr['final_total'] = $finalTripOnTime;
                $punctualityArr['final_total'] = $punctualityFormat;
                $totalTripBreakdownArr['final_total'] = $finalTotalTripBreakdown;
                $realibilityArr['final_total'] = $realibilityFormat;
                $numBusArr['final_total'] = $finalNumBus;
                $fareboxArr['final_total'] = $finalFarebox;
                $ridershipArr['final_total'] = $finalRidership;

                $content['planned_trip'] = $plannedTripArr;
                $content['completed_trip_in'] = $completedInArr;
                $content['completed_trip_out'] = $completedOutArr;
                $content['total_completed_trip'] = $totalCompletedTripArr;
                $content['trip_compliance'] = $tripComplianceArr;
                $content['total_off_route'] = $totalOffRouteArr;
                $content['route_compliance'] = $routeComplianceArr;
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

                $data[$routeName] = $content;
            }
            $isbsfSPAD->add($data);
        }

        return Excel::download(new SPADIsbsf($isbsfSPAD, $validatedData['dateFrom'], $validatedData['dateTo'], $colspan, $all_dates,$monthName, $days), 'ISBSF_Report_SPAD.xlsx');
    }

    public function printTripMissed()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN  printTripMissed()");

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

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        $tripMissed = collect();

        $grandTripMissedIn = 0;
        $grandTripMissedOut  = 0;
        if($this->selectedCompany) {
            $companyDetails = Company::where('id', $this->selectedCompany)->first();
            $networkArea = $companyDetails->company_name;

            //TripMissed certain route for specific company
            if (!empty($this->state['route_id'])) {
                $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                $routeNameIn = $selectedRoute->route_name;
                $routeNameOut = implode(" - ",array_reverse(explode(" - ", $routeNameIn)));
                $totalTripMissedIn = 0;
                $totalTripMissedOut = 0;
                $trip_data = [];

                foreach($all_dates as $all_date){
                    $firstDate = new Carbon($all_date);
                    $lastDate = new Carbon($all_date . '11:59:59');
                    $countTripMissedIn = 0;
                    $countTripMissedOut = 0;

                    //Trip Planned
                    $tripPlanned = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate,$lastDate])->get();

                    //Inbound
                    $allInboundTrips = TripDetail::where('route_id',$selectedRoute->id)
                        ->whereBetween('start_trip', [$firstDate,$lastDate])
                        ->where('trip_code',1)
                        ->get();

                    //Outbound
                    $allOutboundTrips = TripDetail::where('route_id',$selectedRoute->id)
                        ->whereBetween('start_trip', [$firstDate,$lastDate])
                        ->where('trip_code',0)
                        ->get();

                    if(count($tripPlanned)>0) {
                        foreach ($tripPlanned as $tripPlan) {
                            $out->writeln("YOU ARE IN triPlanned loop" . $validatedData['dateFrom']);
                            if ($tripPlan->RouteScheduleMSTR->route_id == $selectedRoute->id) {
                                $tripServedIn = false;
                                $tripServedOut = false;

                                foreach ($allInboundTrips as $allInboundTrip) {
                                    if ($tripPlan->RouteScheduleMSTR->id == $allInboundTrip->route_schedule_mstr_id) {
                                        $tripServedIn = true;
                                    }
                                }
                                if ($tripServedIn == false) {
                                    $countTripMissedIn++;
                                    $notServedIn['route_name'] = $routeNameIn;
                                    $notServedIn['trip_time'] = $tripPlan->RouteScheduleMSTR->schedule_start_time;
                                    $notServedIn['trip_no'] = 'T' . $countTripMissedIn;
                                    $notServedIn['bus_reg_no'] = $tripPlan->RouteScheduleMSTR->inbus->bus_registration_number;
                                    $notServedIn['bus_age'] = $tripPlan->RouteScheduleMSTR->inbus->bus_age;
                                    $notServedIn['km_rate'] = $tripPlan->RouteScheduleMSTR->inbus->BusType->voc_per_km;

                                    $tripIn[$countTripMissedIn] = $notServedIn;
                                }
                                foreach ($allOutboundTrips as $allOutboundTrip) {
                                    if ($tripPlan->RouteScheduleMSTR->id == $allOutboundTrip->route_schedule_mstr_id) {
                                        $tripServedOut = true;
                                    }
                                }
                                if ($tripServedOut == false) {
                                    $countTripMissedOut++;
                                    $notServedOut['route_name'] = $routeNameOut;
                                    $notServedOut['trip_time'] = $tripPlan->RouteScheduleMSTR->schedule_start_time;
                                    $notServedOut['trip_no'] = 'T' . $countTripMissedOut;
                                    $notServedOut['bus_reg_no'] = $tripPlan->RouteScheduleMSTR->outbus->bus_registration_number;
                                    $notServedOut['bus_age'] = $tripPlan->RouteScheduleMSTR->outbus->bus_age;
                                    $notServedOut['km_rate'] = $tripPlan->RouteScheduleMSTR->outbus->BusType->voc_per_km;

                                    $tripOut[$countTripMissedOut] = $notServedOut;
                                }
                            }
                        }
                        $inbound['allTripIn'] = $tripIn;
                        $inbound['total_per_inbound'] = $countTripMissedIn;

                        $outbound['allTripOut'] = $tripOut;
                        $outbound['total_per_outbound'] = $countTripMissedOut;
                    }

                    if($countTripMissedIn!=0 && $countTripMissedOut!=0){
                        $trip_data[$routeNameIn] = $inbound;
                        $trip_data[$routeNameOut] = $outbound;
                        $trip_data['total_per_date'] = $countTripMissedIn + $countTripMissedOut;
                    }elseif($countTripMissedIn>0 && $countTripMissedOut==0){
                        $trip_data[$routeNameIn] = $inbound;
                        $trip_data[$routeNameOut] = [];
                        $trip_data['total_per_date'] = $countTripMissedIn;
                    }elseif($countTripMissedIn==0 && $countTripMissedOut>0){
                        $trip_data[$routeNameIn] = [];
                        $trip_data[$routeNameOut] = $outbound;
                        $trip_data['total_per_date'] = $countTripMissedOut;
                    }else {
                        $trip_data[$routeNameIn] = [];
                        $trip_data[$routeNameOut] = [];
                        $trip_data['total_per_date'] = 0;
                    }

                    $tripPerDate[$all_date] = $trip_data;

                    $totalTripMissedIn += $countTripMissedIn;
                    $totalTripMissedOut += $countTripMissedOut;
                }
                $sumTotal = $totalTripMissedIn + $totalTripMissedOut;
                if($sumTotal==0){
                    $perDate = [];
                    $sumGrand = 0;
                }else{
                    $perDate['allDate'] = $tripPerDate;
                    $perDate['total_per_route'] = $sumTotal;
                    $sumGrand = $sumTotal;

                }
                $route[$selectedRoute->route_number] = $perDate;
                $grand['allRoute'] = $route;
                $grand['grand'] = $sumGrand;

                $tripMissed->add($grand);

            }
            //TripMissed all route for specific company
            else{
                $routeByCompanies = Route::where('company_id', $companyDetails->id)->get();

                foreach($routeByCompanies as $routeByCompany){
                    $routeNameIn = $routeByCompany->route_name;
                    $routeNameOut = implode(" - ",array_reverse(explode(" - ", $routeNameIn)));
                    $totalTripMissedIn = 0;
                    $totalTripMissedOut = 0;
                    $trip_data = [];

                    foreach($all_dates as $all_date){
                        $firstDate = new Carbon($all_date);
                        $lastDate = new Carbon($all_date . '11:59:59');
                        $countTripMissedIn = 0;
                        $countTripMissedOut = 0;

                        //Trip Planned
                        $tripPlanned = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate,$lastDate])->get();

                        //Inbound
                        $allInboundTrips = TripDetail::where('route_id',$routeByCompany->id)
                            ->whereBetween('start_trip', [$firstDate,$lastDate])
                            ->where('trip_code',1)
                            ->get();

                        //Outbound
                        $allOutboundTrips = TripDetail::where('route_id',$routeByCompany->id)
                            ->whereBetween('start_trip', [$firstDate,$lastDate])
                            ->where('trip_code',0)
                            ->get();

                        if(count($tripPlanned)>0) {
                            foreach ($tripPlanned as $tripPlan) {
                                $out->writeln("YOU ARE IN triPlanned loop" . $validatedData['dateFrom']);
                                if ($tripPlan->RouteScheduleMSTR->route_id == $routeByCompany->id) {
                                    $tripServedIn = false;
                                    $tripServedOut = false;

                                    foreach ($allInboundTrips as $allInboundTrip) {
                                        if ($tripPlan->RouteScheduleMSTR->id == $allInboundTrip->route_schedule_mstr_id) {
                                            $tripServedIn = true;
                                        }
                                    }
                                    if ($tripServedIn == false) {
                                        $countTripMissedIn++;
                                        $notServedIn['route_name'] = $routeNameIn;
                                        $notServedIn['trip_time'] = $tripPlan->RouteScheduleMSTR->schedule_start_time;
                                        $notServedIn['trip_no'] = 'T' . $countTripMissedIn;
                                        $notServedIn['bus_reg_no'] = $tripPlan->RouteScheduleMSTR->inbus->bus_registration_number;
                                        $notServedIn['bus_age'] = $tripPlan->RouteScheduleMSTR->inbus->bus_age;
                                        $notServedIn['km_rate'] = $tripPlan->RouteScheduleMSTR->inbus->BusType->voc_per_km;

                                        $tripIn[$countTripMissedIn] = $notServedIn;
                                    }
                                    foreach ($allOutboundTrips as $allOutboundTrip) {
                                        if ($tripPlan->RouteScheduleMSTR->id == $allOutboundTrip->route_schedule_mstr_id) {
                                            $tripServedOut = true;
                                        }
                                    }
                                    if ($tripServedOut == false) {
                                        $countTripMissedOut++;
                                        $notServedOut['route_name'] = $routeNameOut;
                                        $notServedOut['trip_time'] = $tripPlan->RouteScheduleMSTR->schedule_start_time;
                                        $notServedOut['trip_no'] = 'T' . $countTripMissedOut;
                                        $notServedOut['bus_reg_no'] = $tripPlan->RouteScheduleMSTR->outbus->bus_registration_number;
                                        $notServedOut['bus_age'] = $tripPlan->RouteScheduleMSTR->outbus->bus_age;
                                        $notServedOut['km_rate'] = $tripPlan->RouteScheduleMSTR->outbus->BusType->voc_per_km;

                                        $tripOut[$countTripMissedOut] = $notServedOut;
                                    }
                                }
                            }

                            $inbound['allTripIn'] = $tripIn;
                            $inbound['total_per_inbound'] = $countTripMissedIn;

                            $outbound['allTripOut'] = $tripOut;
                            $outbound['total_per_outbound'] = $countTripMissedOut;
                        }

                        if($countTripMissedIn!=0 && $countTripMissedOut!=0){
                            $trip_data[$routeNameIn] = $inbound;
                            $trip_data[$routeNameOut] = $outbound;
                            $trip_data['total_per_date'] = $countTripMissedIn + $countTripMissedOut;
                        }elseif($countTripMissedIn>0 && $countTripMissedOut==0){
                            $trip_data[$routeNameIn] = $inbound;
                            $trip_data[$routeNameOut] = [];
                            $trip_data['total_per_date'] = $countTripMissedIn;
                        }elseif($countTripMissedIn==0 && $countTripMissedOut>0){
                            $trip_data[$routeNameIn] = [];
                            $trip_data[$routeNameOut] = $outbound;
                            $trip_data['total_per_date'] = $countTripMissedOut;
                        }else {
                            $trip_data[$routeNameIn] = [];
                            $trip_data[$routeNameOut] = [];
                            $trip_data['total_per_date'] = 0;
                        }

                        $tripPerDate[$all_date] = $trip_data;

                        $totalTripMissedIn += $countTripMissedIn;
                        $totalTripMissedOut += $countTripMissedOut;
                    }
                    $sumTotal = $totalTripMissedIn + $totalTripMissedOut;
                    if($sumTotal==0){
                        $perDate = [];
                    }else{
                        $perDate['allDate'] = $tripPerDate;
                        $perDate['total_per_route'] = $sumTotal;
                    }

                    $route[$routeByCompany->route_number] = $perDate;

                    $grandTripMissedIn += $totalTripMissedIn;
                    $grandTripMissedOut += $totalTripMissedOut;
                }
                $sumGrand = $grandTripMissedIn + $grandTripMissedOut;
                if($sumGrand==0){
                    $grand = 0;
                }else{
                    $grand['allRoute'] = $route;
                    $grand['grand'] = $sumGrand;
                }

                $tripMissed->add($grand);
            }
        }
        //TripMissed all route for all company
        else{
            $networkArea = "ALL";
            $allRoutes = Route::all();

            foreach($allRoutes as $allRoute){
                $routeNameIn = $allRoute->route_name;
                $routeNameOut = implode(" - ",array_reverse(explode(" - ", $routeNameIn)));
                $totalTripMissedIn = 0;
                $totalTripMissedOut = 0;
                $trip_data = [];

                foreach($all_dates as $all_date) {
                    $firstDate = new Carbon($all_date);
                    $lastDate = new Carbon($all_date . '11:59:59');
                    $countTripMissedIn = 0;
                    $countTripMissedOut = 0;

                    //Trip Planned
                    $tripPlanned = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate, $lastDate])->get();

                    //Inbound
                    $allInboundTrips = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$firstDate, $lastDate])
                        ->where('trip_code', 1)
                        ->get();

                    //Outbound
                    $allOutboundTrips = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$firstDate, $lastDate])
                        ->where('trip_code', 0)
                        ->get();

                    if(count($tripPlanned) > 0){
                        foreach ($tripPlanned as $tripPlan) {
                            $out->writeln("YOU ARE IN triPlanned loop" . $validatedData['dateFrom']);
                            if ($tripPlan->RouteScheduleMSTR->route_id == $allRoute->id) {
                                $tripServedIn = false;
                                $tripServedOut = false;

                                foreach ($allInboundTrips as $allInboundTrip) {
                                    if ($tripPlan->RouteScheduleMSTR->id == $allInboundTrip->route_schedule_mstr_id) {
                                        $tripServedIn = true;
                                    }
                                }
                                if ($tripServedIn == false) {
                                    $countTripMissedIn++;
                                    $notServedIn['route_name'] = $routeNameIn;
                                    $notServedIn['trip_time'] = $tripPlan->RouteScheduleMSTR->schedule_start_time;
                                    $notServedIn['trip_no'] = 'T' . $countTripMissedIn;
                                    $notServedIn['bus_reg_no'] = $tripPlan->RouteScheduleMSTR->inbus->bus_registration_number;
                                    $notServedIn['bus_age'] = $tripPlan->RouteScheduleMSTR->inbus->bus_age;
                                    $notServedIn['km_rate'] = $tripPlan->RouteScheduleMSTR->inbus->BusType->voc_per_km;

                                    $tripIn[$countTripMissedIn] = $notServedIn;
                                }
                                foreach ($allOutboundTrips as $allOutboundTrip) {
                                    if ($tripPlan->RouteScheduleMSTR->id == $allOutboundTrip->route_schedule_mstr_id) {
                                        $tripServedOut = true;
                                    }
                                }
                                if ($tripServedOut == false) {
                                    $countTripMissedOut++;
                                    $notServedOut['route_name'] = $routeNameOut;
                                    $notServedOut['trip_time'] = $tripPlan->RouteScheduleMSTR->schedule_start_time;
                                    $notServedOut['trip_no'] = 'T' . $countTripMissedOut;
                                    $notServedOut['bus_reg_no'] = $tripPlan->RouteScheduleMSTR->outbus->bus_registration_number;
                                    $notServedOut['bus_age'] = $tripPlan->RouteScheduleMSTR->outbus->bus_age;
                                    $notServedOut['km_rate'] = $tripPlan->RouteScheduleMSTR->outbus->BusType->voc_per_km;

                                    $tripOut[$countTripMissedOut] = $notServedOut;
                                }
                            }
                        }
                        $inbound['allTripIn'] = $tripIn;
                        $inbound['total_per_inbound'] = $countTripMissedIn;

                        $outbound['allTripOut'] = $tripOut;
                        $outbound['total_per_outbound'] = $countTripMissedOut;
                    }

                    if($countTripMissedIn!=0 && $countTripMissedOut!=0){
                        $trip_data[$routeNameIn] = $inbound;
                        $trip_data[$routeNameOut] = $outbound;
                        $trip_data['total_per_date'] = $countTripMissedIn + $countTripMissedOut;
                    }elseif($countTripMissedIn>0 && $countTripMissedOut==0){
                        $trip_data[$routeNameIn] = $inbound;
                        $trip_data[$routeNameOut] = [];
                        $trip_data['total_per_date'] = $countTripMissedIn;
                    }elseif($countTripMissedIn==0 && $countTripMissedOut>0){
                        $trip_data[$routeNameIn] = [];
                        $trip_data[$routeNameOut] = $outbound;
                        $trip_data['total_per_date'] = $countTripMissedOut;
                    }else {
                        $trip_data[$routeNameIn] = [];
                        $trip_data[$routeNameOut] = [];
                        $trip_data['total_per_date'] = 0;
                    }

                    $tripPerDate[$all_date] = $trip_data;

                    $totalTripMissedIn += $countTripMissedIn;
                    $totalTripMissedOut += $countTripMissedOut;
                }
                $sumTotal = $totalTripMissedIn + $totalTripMissedOut;
                if($sumTotal==0){
                    $perDate = [];
                }else{
                    $perDate['allDate'] = $tripPerDate;
                    $perDate['total_per_route'] = $sumTotal;
                }

                $route[$allRoute->route_number] = $perDate;

                $grandTripMissedIn += $totalTripMissedIn;
                $grandTripMissedOut += $totalTripMissedOut;
            }
            $sumGrand = $grandTripMissedIn + $grandTripMissedOut;
            if($sumGrand==0){
                $grand = 0;
            }else{
                $grand['allRoute'] = $route;
                $grand['grand'] = $sumGrand;
            }

            $tripMissed->add($grand);
        }
        return Excel::download(new SPADTripMissed($tripMissed, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Trip_Missed_Report_SPAD.xlsx');

    }

    public function printTripPlanned()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printTripPlanned()");

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

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        $tripPlanned = collect();

        $grandTripPlannedIn = 0;
        $grandTripPlannedOut = 0;
        if($this->selectedCompany) {
            $companyDetails = Company::where('id', $this->selectedCompany)->first();
            $networkArea = $companyDetails->company_name;

            //TripPlanned certain route for specific company
            if (!empty($this->state['route_id'])) {
                $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                $routeNameIn = $selectedRoute->route_name;
                $routeNameOut = implode(" - ",array_reverse(explode(" - ", $routeNameIn)));
                $totalTripPlannedIn = 0;
                $totalTripPlannedOut = 0;
                $trip_data = [];

                foreach($all_dates as $all_date) {
                    $firstDate = new Carbon($all_date);
                    $lastDate = new Carbon($all_date . '11:59:59');
                    $countTripPlannedIn = 0;
                    $countTripPlannedOut = 0;

                    //Trip Planned
                    $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate, $lastDate])->get();

                    //Inbound
                    $allInboundTrips = TripDetail::where('route_id', $selectedRoute->id)
                        ->whereBetween('start_trip', [$firstDate, $lastDate])
                        ->where('trip_code', 1)
                        ->get();

                    //Outbound
                    $allOutboundTrips = TripDetail::where('route_id', $selectedRoute->id)
                        ->whereBetween('start_trip', [$firstDate, $lastDate])
                        ->where('trip_code', 0)
                        ->get();

                    if (count($schedules) > 0) {
                        foreach ($schedules as $schedule) {
                            $out->writeln("YOU ARE IN triPlanned loop" . $validatedData['dateFrom']);
                            if ($schedule->RouteScheduleMSTR->route_id == $selectedRoute->id) {
                                $countTripPlannedIn++;
                                $countTripPlannedOut++;
                                $tripServedIn = false;
                                $tripServedOut = false;

                                $plannedIn['route_name'] = $routeNameIn;
                                $plannedIn['trip_date'] = $schedule->schedule_date;
                                $plannedIn['service_time'] = $schedule->RouteScheduleMSTR->schedule_start_time;
                                $plannedIn['trip_no'] = 'T' . $countTripPlannedIn;
                                $plannedIn['bus_reg_no'] = $schedule->RouteScheduleMSTR->inbus->bus_registration_number;
                                $plannedIn['bus_age'] = $schedule->RouteScheduleMSTR->inbus->bus_age;
                                $plannedIn['km_rate'] = $schedule->RouteScheduleMSTR->inbus->BusType->voc_per_km;

                                foreach ($allInboundTrips as $allInboundTrip) {
                                    if ($schedule->RouteScheduleMSTR->id == $allInboundTrip->route_schedule_mstr_id) {
                                        $tripServedIn = true;
                                    }
                                }
                                if ($tripServedIn == false) {
                                    $plannedIn['status'] = "MISSED TRIP";
                                } else {
                                    $plannedIn['status'] = "TRIP SERVED";
                                }
                                $tripIn[$countTripPlannedIn] = $plannedIn;

                                //Outbound
                                $plannedOut['route_name'] = $routeNameOut;
                                $plannedOut['trip_date'] = $all_date;
                                $plannedOut['service_time'] = $schedule->RouteScheduleMSTR->schedule_start_time;
                                $plannedOut['trip_no'] = 'T' . $countTripPlannedOut;
                                $plannedOut['bus_reg_no'] = $schedule->RouteScheduleMSTR->inbus->bus_registration_number;
                                $plannedOut['bus_age'] = $schedule->RouteScheduleMSTR->inbus->bus_age;
                                $plannedOut['km_rate'] = $schedule->RouteScheduleMSTR->inbus->BusType->voc_per_km;

                                foreach ($allOutboundTrips as $allOutboundTrip) {
                                    if ($schedule->RouteScheduleMSTR->id == $allOutboundTrip->route_schedule_mstr_id) {
                                        $tripServedOut = true;
                                    }
                                }
                                if ($tripServedOut == false) {
                                    $plannedOut['status'] = "MISSED TRIP";
                                } else {
                                    $plannedOut['status'] = "TRIP SERVED";
                                }
                                $tripOut[$countTripPlannedOut] = $plannedOut;
                            }
                        }
                        $inbound['allTripIn'] = $tripIn;
                        $inbound['total_per_inbound'] = $countTripPlannedIn;
                        $outbound['allTripOut'] = $tripOut;
                        $outbound['total_per_outbound'] = $countTripPlannedOut;
                    }

                    if ($countTripPlannedIn != 0 && $countTripPlannedOut != 0) {
                        $trip_data[$routeNameIn] = $inbound;
                        $trip_data[$routeNameOut] = $outbound;
                        $trip_data['total_per_date'] = $countTripPlannedIn + $countTripPlannedOut;
                    } elseif ($countTripPlannedIn > 0 && $countTripPlannedOut == 0) {
                        $trip_data[$routeNameIn] = $inbound;
                        $trip_data[$routeNameOut] = [];
                        $trip_data['total_per_date'] = $countTripPlannedIn;
                    } elseif ($countTripPlannedIn == 0 && $countTripPlannedOut > 0) {
                        $trip_data[$routeNameIn] = [];
                        $trip_data[$routeNameOut] = $outbound;
                        $trip_data['total_per_date'] = $countTripPlannedOut;
                    } else {
                        $trip_data[$routeNameIn] = [];
                        $trip_data[$routeNameOut] = [];
                        $trip_data['total_per_date'] = 0;
                    }

                    $tripPerDate[$all_date] = $trip_data;

                    $totalTripPlannedIn += $countTripPlannedIn;
                    $totalTripPlannedOut += $countTripPlannedOut;
                }
                $sumTotal = $totalTripPlannedIn + $totalTripPlannedOut;
                if($sumTotal==0){
                    $perDate = [];
                    $sumGrand = 0;
                }else{
                    $perDate['allDate'] = $tripPerDate;
                    $perDate['total_per_route'] = $sumTotal;
                    $sumGrand = $sumTotal;

                }
                $route[$selectedRoute->route_number] = $perDate;
                $grand['allRoute'] = $route;
                $grand['grand'] = $sumGrand;

                $tripPlanned->add($grand);
            }
            //TripPlanned all route for specific company
            else{
                $routeByCompanies = Route::where('company_id', $companyDetails->id)->get();

                foreach($routeByCompanies as $routeByCompany){
                    $routeNameIn = $routeByCompany->route_name;
                    $routeNameOut = implode(" - ",array_reverse(explode(" - ", $routeNameIn)));
                    $totalTripPlannedIn = 0;
                    $totalTripPlannedOut = 0;
                    $trip_data = [];

                    foreach($all_dates as $all_date) {
                        $firstDate = new Carbon($all_date);
                        $lastDate = new Carbon($all_date . '11:59:59');
                        $countTripPlannedIn = 0;
                        $countTripPlannedOut = 0;

                        //Trip Planned
                        $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate, $lastDate])->get();

                        //Inbound
                        $allInboundTrips = TripDetail::where('route_id', $routeByCompany->id)
                            ->whereBetween('start_trip', [$firstDate, $lastDate])
                            ->where('trip_code', 1)
                            ->get();

                        //Outbound
                        $allOutboundTrips = TripDetail::where('route_id', $routeByCompany->id)
                            ->whereBetween('start_trip', [$firstDate, $lastDate])
                            ->where('trip_code', 0)
                            ->get();

                        if (count($schedules) > 0) {
                            foreach ($schedules as $schedule) {
                                $out->writeln("YOU ARE IN triPlanned loop" . $validatedData['dateFrom']);
                                if ($schedule->RouteScheduleMSTR->route_id == $routeByCompany->id) {
                                    $countTripPlannedIn++;
                                    $countTripPlannedOut++;
                                    $tripServedIn = false;
                                    $tripServedOut = false;

                                    $plannedIn['route_name'] = $routeNameIn;
                                    $plannedIn['trip_date'] = $schedule->schedule_date;
                                    $plannedIn['service_time'] = $schedule->RouteScheduleMSTR->schedule_start_time;
                                    $plannedIn['trip_no'] = 'T' . $countTripPlannedIn;
                                    $plannedIn['bus_reg_no'] = $schedule->RouteScheduleMSTR->inbus->bus_registration_number;
                                    $plannedIn['bus_age'] = $schedule->RouteScheduleMSTR->inbus->bus_age;
                                    $plannedIn['km_rate'] = $schedule->RouteScheduleMSTR->inbus->BusType->voc_per_km;

                                    foreach ($allInboundTrips as $allInboundTrip) {
                                        if ($schedule->RouteScheduleMSTR->id == $allInboundTrip->route_schedule_mstr_id) {
                                            $tripServedIn = true;
                                        }
                                    }
                                    if ($tripServedIn == false) {
                                        $plannedIn['status'] = "MISSED TRIP";
                                    } else {
                                        $plannedIn['status'] = "TRIP SERVED";
                                    }
                                    $tripIn[$countTripPlannedIn] = $plannedIn;

                                    //Outbound
                                    $plannedOut['route_name'] = $routeNameOut;
                                    $plannedOut['trip_date'] = $all_date;
                                    $plannedOut['service_time'] = $schedule->RouteScheduleMSTR->schedule_start_time;
                                    $plannedOut['trip_no'] = 'T' . $countTripPlannedOut;
                                    $plannedOut['bus_reg_no'] = $schedule->RouteScheduleMSTR->inbus->bus_registration_number;
                                    $plannedOut['bus_age'] = $schedule->RouteScheduleMSTR->inbus->bus_age;
                                    $plannedOut['km_rate'] = $schedule->RouteScheduleMSTR->inbus->BusType->voc_per_km;

                                    foreach ($allOutboundTrips as $allOutboundTrip) {
                                        if ($schedule->RouteScheduleMSTR->id == $allOutboundTrip->route_schedule_mstr_id) {
                                            $tripServedOut = true;
                                        }
                                    }
                                    if ($tripServedOut == false) {
                                        $plannedOut['status'] = "MISSED TRIP";
                                    } else {
                                        $plannedOut['status'] = "TRIP SERVED";
                                    }
                                    $tripOut[$countTripPlannedOut] = $plannedOut;
                                }
                            }
                            $inbound['allTripIn'] = $tripIn;
                            $inbound['total_per_inbound'] = $countTripPlannedIn;
                            $outbound['allTripOut'] = $tripOut;
                            $outbound['total_per_outbound'] = $countTripPlannedOut;
                        }

                        if ($countTripPlannedIn != 0 && $countTripPlannedOut != 0) {
                            $trip_data[$routeNameIn] = $inbound;
                            $trip_data[$routeNameOut] = $outbound;
                            $trip_data['total_per_date'] = $countTripPlannedIn + $countTripPlannedOut;
                        } elseif ($countTripPlannedIn > 0 && $countTripPlannedOut == 0) {
                            $trip_data[$routeNameIn] = $inbound;
                            $trip_data[$routeNameOut] = [];
                            $trip_data['total_per_date'] = $countTripPlannedIn;
                        } elseif ($countTripPlannedIn == 0 && $countTripPlannedOut > 0) {
                            $trip_data[$routeNameIn] = [];
                            $trip_data[$routeNameOut] = $outbound;
                            $trip_data['total_per_date'] = $countTripPlannedOut;
                        } else {
                            $trip_data[$routeNameIn] = [];
                            $trip_data[$routeNameOut] = [];
                            $trip_data['total_per_date'] = 0;
                        }

                        $tripPerDate[$all_date] = $trip_data;

                        $totalTripPlannedIn += $countTripPlannedIn;
                        $totalTripPlannedOut += $countTripPlannedOut;
                    }
                    $sumTotal = $totalTripPlannedIn + $totalTripPlannedOut;
                    if($sumTotal==0){
                        $perDate = [];
                    }else{
                        $perDate['allDate'] = $tripPerDate;
                        $perDate['total_per_route'] = $sumTotal;
                    }

                    $route[$routeByCompany->route_number] = $perDate;

                    $grandTripPlannedIn += $totalTripPlannedIn;
                    $grandTripPlannedOut += $totalTripPlannedOut;
                }
                $sumGrand = $grandTripPlannedIn + $grandTripPlannedOut ;
                if($sumGrand==0){
                    $grand = 0;
                }else{
                    $grand['allRoute'] = $route;
                    $grand['grand'] = $sumGrand;
                }

                $tripPlanned->add($grand);
            }
        }
        //TripPlanned all route for all company
        else{
            $networkArea = "ALL";
            $allRoutes = Route::all();

            foreach($allRoutes as $allRoute){
                $routeNameIn = $allRoute->route_name;
                $routeNameOut = implode(" - ",array_reverse(explode(" - ", $routeNameIn)));
                $totalTripPlannedIn = 0;
                $totalTripPlannedOut = 0;
                $trip_data = [];

                foreach($all_dates as $all_date) {
                    $firstDate = new Carbon($all_date);
                    $lastDate = new Carbon($all_date . '11:59:59');
                    $countTripPlannedIn = 0;
                    $countTripPlannedOut = 0;

                    //Trip Planned
                    $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate, $lastDate])->get();

                    //Inbound
                    $allInboundTrips = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$firstDate, $lastDate])
                        ->where('trip_code', 1)
                        ->get();

                    //Outbound
                    $allOutboundTrips = TripDetail::where('route_id', $allRoute->id)
                        ->whereBetween('start_trip', [$firstDate, $lastDate])
                        ->where('trip_code', 0)
                        ->get();

                    if (count($schedules) > 0) {
                        foreach ($schedules as $schedule) {
                            $out->writeln("YOU ARE IN triPlanned loop" . $validatedData['dateFrom']);
                            if ($schedule->RouteScheduleMSTR->route_id == $allRoute->id) {
                                $countTripPlannedIn++;
                                $countTripPlannedOut++;
                                $tripServedIn = false;
                                $tripServedOut = false;

                                $plannedIn['route_name'] = $routeNameIn;
                                $plannedIn['trip_date'] = $schedule->schedule_date;
                                $plannedIn['service_time'] = $schedule->RouteScheduleMSTR->schedule_start_time;
                                $plannedIn['trip_no'] = 'T' . $countTripPlannedIn;
                                $plannedIn['bus_reg_no'] = $schedule->RouteScheduleMSTR->inbus->bus_registration_number;
                                $plannedIn['bus_age'] = $schedule->RouteScheduleMSTR->inbus->bus_age;
                                $plannedIn['km_rate'] = $schedule->RouteScheduleMSTR->inbus->BusType->voc_per_km;

                                foreach ($allInboundTrips as $allInboundTrip) {
                                    if ($schedule->RouteScheduleMSTR->id == $allInboundTrip->route_schedule_mstr_id) {
                                        $tripServedIn = true;
                                    }
                                }
                                if ($tripServedIn == false) {
                                    $plannedIn['status'] = "MISSED TRIP";
                                } else {
                                    $plannedIn['status'] = "TRIP SERVED";
                                }
                                $tripIn[$countTripPlannedIn] = $plannedIn;

                                //Outbound
                                $plannedOut['route_name'] = $routeNameOut;
                                $plannedOut['trip_date'] = $all_date;
                                $plannedOut['service_time'] = $schedule->RouteScheduleMSTR->schedule_start_time;
                                $plannedOut['trip_no'] = 'T' . $countTripPlannedOut;
                                $plannedOut['bus_reg_no'] = $schedule->RouteScheduleMSTR->inbus->bus_registration_number;
                                $plannedOut['bus_age'] = $schedule->RouteScheduleMSTR->inbus->bus_age;
                                $plannedOut['km_rate'] = $schedule->RouteScheduleMSTR->inbus->BusType->voc_per_km;

                                foreach ($allOutboundTrips as $allOutboundTrip) {
                                    if ($schedule->RouteScheduleMSTR->id == $allOutboundTrip->route_schedule_mstr_id) {
                                        $tripServedOut = true;
                                    }
                                }
                                if ($tripServedOut == false) {
                                    $plannedOut['status'] = "MISSED TRIP";
                                } else {
                                    $plannedOut['status'] = "TRIP SERVED";
                                }
                                $tripOut[$countTripPlannedOut] = $plannedOut;
                            }
                        }
                        $inbound['allTripIn'] = $tripIn;
                        $inbound['total_per_inbound'] = $countTripPlannedIn;
                        $outbound['allTripOut'] = $tripOut;
                        $outbound['total_per_outbound'] = $countTripPlannedOut;
                    }

                    if ($countTripPlannedIn != 0 && $countTripPlannedOut != 0) {
                        $trip_data[$routeNameIn] = $inbound;
                        $trip_data[$routeNameOut] = $outbound;
                        $trip_data['total_per_date'] = $countTripPlannedIn + $countTripPlannedOut;
                    } elseif ($countTripPlannedIn > 0 && $countTripPlannedOut == 0) {
                        $trip_data[$routeNameIn] = $inbound;
                        $trip_data[$routeNameOut] = [];
                        $trip_data['total_per_date'] = $countTripPlannedIn;
                    } elseif ($countTripPlannedIn == 0 && $countTripPlannedOut > 0) {
                        $trip_data[$routeNameIn] = [];
                        $trip_data[$routeNameOut] = $outbound;
                        $trip_data['total_per_date'] = $countTripPlannedOut;
                    } else {
                        $trip_data[$routeNameIn] = [];
                        $trip_data[$routeNameOut] = [];
                        $trip_data['total_per_date'] = 0;
                    }

                    $tripPerDate[$all_date] = $trip_data;

                    $totalTripPlannedIn += $countTripPlannedIn;
                    $totalTripPlannedOut += $countTripPlannedOut;
                }
                $sumTotal = $totalTripPlannedIn + $totalTripPlannedOut;
                if($sumTotal==0){
                    $perDate = [];
                }else{
                    $perDate['allDate'] = $tripPerDate;
                    $perDate['total_per_route'] = $sumTotal;
                }

                $route[$allRoute->route_number] = $perDate;

                $grandTripPlannedIn += $totalTripPlannedIn;
                $grandTripPlannedOut += $totalTripPlannedOut;
            }
            $sumGrand = $grandTripPlannedIn + $grandTripPlannedOut ;
            if($sumGrand==0){
                $grand = 0;
            }else{
                $grand['allRoute'] = $route;
                $grand['grand'] = $sumGrand;
            }

            $tripPlanned->add($grand);
        }
        return Excel::download(new SPADTripPlanned($tripPlanned, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Trip_Planned_Report_SPAD.xlsx');

    }


}
