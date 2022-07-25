<?php

namespace App\Http\Livewire;

use App\Exports\SalesByBus;
use App\Exports\SPADClaimDetailsGPS;
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
use App\Exports\SPADBusTransfer;
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
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Support\Facades\DB;

class ReportSpad extends Component
{
    public $companies;
    public $routes;
    public $selectedCompany = NULL;
    public $state = [];

    public function render()
    {
        $this->companies = Company::orderBy('company_name')->get();
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
            'route_id' => ['required'],
        ])->validate();

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo'] . '23:59:59');

        $prevStart = Carbon::create($validatedData['dateFrom'])->startOfMonth()->subMonthsNoOverflow()->toDateString();
        $prevEnd = Carbon::create($validatedData['dateTo'])->subMonthsNoOverflow()->endOfMonth()->toDateString();

        $previousStartMonth = new Carbon($prevStart);
        $previousEndMonth = new Carbon($prevEnd . '23:59:59');

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
        if($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                if(!empty($validatedData['route_id'])) {
                     //Summary all routes for all company
                    if($validatedData['route_id']=='All'){
                        $allRoutes = Route::orderBy('route_number')->get();
            
                        foreach($allRoutes as $allRoute) {
                            $existInTrip = false;
                            $existOutTrip = false;
                            $totalFareboxIn = 0;
                            $totalRidershipIn = 0;
                            $totalKMPlannedIn = 0;
                            $totalKMServedIn = 0;
                            $totalKMGPSIn = 0;
                            $earlyDepartureIn = 0;
                            $lateDepartureIn = 0;
                            $earlyEndIn = 0;
                            $lateEndIn = 0;
                            $totalTripIn = 0;
                            $inbound = [];
            
                            //Inbound
                            $allTripInbounds = TripDetail::where('route_id', $allRoute->id)
                                ->whereBetween('start_trip', [$dateFrom, $dateTo])
                                ->where('trip_code', 1)
                                ->get();
            
                            if (count($allTripInbounds)>0) {
                                foreach ($allTripInbounds as $allTripInbound) {
                                    $existInTrip = true;
            
                                    //Ridership
                                    $ridership = $allTripInbound->total_adult + $allTripInbound->total_concession;
                                    //Check tickets Ridership
                                    if($ridership==0){
                                        $ridership = TicketSalesTransaction::where('trip_id', $allTripInbound->id)->count();
                                    }
                                    $totalRidershipIn += $ridership;
            
                                    //Farebox Collection
                                    $farebox = $allTripInbound->total_adult_amount + $allTripInbound->total_concession_amount;
                                    //Check tickets Farebox
                                    if($farebox==0){
                                        $allTicketPerTrips = TicketSalesTransaction::where('trip_id', $allTripInbound->id)->get();
                                        if(count($allTicketPerTrips)>0){
                                            foreach($allTicketPerTrips as $allTicketPerTrip){
                                                $farebox += $allTicketPerTrip->actual_amount;
                                            }
                                        }
                                    }
                                    $totalFareboxIn += $farebox;
            
                                    //Total KM Service Served
                                    $kmServed = $allTripInbound->Route->inbound_distance;
                                    $totalKMServedIn += $kmServed;
            
                                    //Total KM Service Served by GPS
                                    $kmGPS = $allTripInbound->total_mileage;
                                    $totalKMGPSIn += $kmGPS;
            
                                    if ($allTripInbound->route_schedule_mstr_id != NULL) {
                                        //Total Early Departure
                                        $diffDepart = strtotime($allTripInbound->routeScheduleMSTR->schedule_start_time) - strtotime($allTripInbound->start_trip);
                                        if ($diffDepart > 5) {
                                            $earlyDepartureIn++;
                                        }elseif($diffDepart < -5){
                                            $lateDepartureIn++;
                                        }
                                        //Total Early End
                                        $diffEnd = strtotime($allTripInbound->routeScheduleMSTR->schedule_end_time) - strtotime($allTripInbound->end_trip);
                                        if ($diffEnd > 5) {
                                            $earlyEndIn++;
                                        }elseif($diffEnd < -5){
                                            $lateEndIn++;
                                        }
                                    }
                                    $totalTripIn++;
                                }
                            }
                            $prevRidershipIn = 0;
                            $prevFareboxIn = 0;
            
                            //Previous Month Inbound Trip
                            $prevTripInbounds = TripDetail::where('route_id', $allRoute->id)
                            ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                            ->where('trip_code', 1)
                            ->get();
            
                            if(count($prevTripInbounds)>0){
                                foreach($prevTripInbounds as $prevTripInbound){
                                    //Previous Month Inbound Ridership collection
                                    $prevRidership = $prevTripInbound->total_adult + $prevTripInbound->total_concession;
                                    //Check tickets Ridership
                                    if($prevRidership==0){
                                        $prevRidership = TicketSalesTransaction::where('trip_id', $prevTripInbound->id)->count();
                                    }
                                    $prevRidershipIn += $prevRidership;
            
                                    //Previous Month Inbound Farebox collection
                                    $prevFarebox = $prevTripInbound->total_adult_amount + $prevTripInbound->total_concession_amount;
                                    //Check tickets Farebox
                                    if($prevFarebox==0){
                                        $allTicketPerTrips = TicketSalesTransaction::where('trip_id', $prevTripInbound->id)->get();
                                        if(count($allTicketPerTrips)>0){
                                            foreach($allTicketPerTrips as $allTicketPerTrip){
                                                $prevFarebox += $allTicketPerTrip->actual_amount;
                                            }
                                        }
                                    }
                                    $prevFareboxIn += $prevFarebox;
                                }
                            }
                            //Increment ridership collection (%)
                            if($prevRidershipIn==0){
                                $increaseRidershipIn = 100;
                                $increaseRidershipFormatIn = 100;
                            }else{
                            if($totalRidershipIn==0){
                                $increaseRidershipIn = -100;
                                $increaseRidershipFormatIn = -100;
                            }else{
                                $increaseRidershipIn = (($totalRidershipIn - $prevRidershipIn) / $prevRidershipIn) * 100;
                                $increaseRidershipFormatIn = number_format((float)$increaseRidershipIn, 2, '.', '');
                            }
                            }
            
                            //Incremeant farebox collection (%)
                            if($prevFareboxIn==0){
                                $increaseFareboxIn = 100;
                                $increaseFareboxFormatIn = 100;
                            }else{
                            if($totalFareboxIn==0){
                                $increaseFareboxIn  = -100;
                                $increaseFareboxFormatIn  = -100;
                            }else{
                                $increaseFareboxIn = (($totalFareboxIn - $prevFareboxIn) / $prevFareboxIn) * 100;
                                $increaseFareboxFormatIn = number_format((float)$increaseFareboxIn, 2, '.', '');
                            }
                            }
            
                            //Average Fare per pax (RM)
                            if($totalRidershipIn==0) {
                                $averageIn = 0;
                                $averageFormatIn = 0;
                            }
                            else{
                                $averageIn = $totalFareboxIn / $totalRidershipIn;
                                $averageFormatIn = number_format((float)$averageIn, 2, '.', '');
                            }
            
                            $countScheduleIn = 0;
                            $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->get();
                            if(count($schedules))
                            foreach($schedules as $schedule){
                                if($schedule->RouteScheduleMSTR->route_id == $allRoute->id){
                                    if($schedule->RouteScheduleMSTR->trip_code == 1){
                                        $countScheduleIn++;
                                    }
                                }
                            }
                            //Total KM Service Planned
                            $totalKMPlannedIn = $countScheduleIn * $allRoute->inbound_distance;
            
                            //Number of Trips missed
                            $tripMissedIn = $countScheduleIn - $totalTripIn;
                            if($tripMissedIn<0){
                                $tripMissedIn=0;
                            }
            
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
                            $inbound['trip_planned_in'] = $countScheduleIn;
                            $inbound['trip_made_in'] = $totalTripIn;
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
            
                            $totalFareboxOut = 0;
                            $totalRidershipOut = 0;
                            $totalKMPlannedOut = 0;
                            $totalKMServedOut = 0;
                            $totalKMGPSOut = 0;
                            $earlyDepartureOut = 0;
                            $lateDepartureOut = 0;
                            $earlyEndOut = 0;
                            $lateEndOut = 0;
                            $totalTripOut = 0;
                            $outbound = [];
            
                            //Outbound
                            $allTripOutbounds = TripDetail::where('route_id',$allRoute->id)
                                ->whereBetween('start_trip', [$dateFrom, $dateTo])
                                ->where('trip_code', 0)
                                ->get();
            
                            if (count($allTripOutbounds) > 0) {
                                foreach ($allTripOutbounds as $allTripOutbound) {
                                    $existOutTrip = true;
            
                                    //Ridership
                                    $ridership = $allTripOutbound->total_adult + $allTripOutbound->total_concession;
                                    //Check tickets Ridership
                                    if($ridership==0){
                                        $ridership = TicketSalesTransaction::where('trip_id', $allTripOutbound->id)->count();
                                    }
                                    $totalRidershipOut += $ridership;
            
                                    //Farebox Collection
                                    $farebox = $allTripOutbound->total_adult_amount + $allTripOutbound->total_concession_amount;
                                    //Check tickets Farebox
                                    if($farebox==0){
                                        $allTicketPerTrips = TicketSalesTransaction::where('trip_id', $allTripOutbound->id)->get();
                                        if(count($allTicketPerTrips)>0){
                                            foreach($allTicketPerTrips as $allTicketPerTrip){
                                                $farebox += $allTicketPerTrip->actual_amount;
                                            }
                                        }
                                    }
                                    $totalFareboxOut += $farebox;
            
                                    //Total KM Service Served
                                    $kmServed = $allTripOutbound->Route->outbound_distance;
                                    $totalKMServedOut += $kmServed;
            
                                    //Total KM Service Served by GPS
                                    $kmGPS = $allTripOutbound->total_mileage;
                                    $totalKMGPSOut += $kmGPS;
            
                                    if ($allTripOutbound->route_schedule_mstr_id != NULL) {
                                        //Total Early Departure
                                        $diffDepart = strtotime($allTripOutbound->routeScheduleMSTR->schedule_start_time) - strtotime($allTripOutbound->start_trip);
                                        if ($diffDepart > 5) {
                                            $earlyDepartureOut++;
                                        }elseif($diffDepart < -5){
                                            $lateDepartureOut++;
                                        }
                                        //Total Early End
                                        $diffEnd = strtotime($allTripOutbound->routeScheduleMSTR->schedule_end_time) - strtotime($allTripOutbound->end_trip);
                                        if ($diffEnd > 5) {
                                            $earlyEndOut++;
                                        }elseif($diffEnd < -5){
                                            $lateEndOut++;
                                        }
                                    }
                                    $totalTripOut++;
                                }
                            }
                            $prevRidershipOut = 0;
                            $prevFareboxOut = 0;
            
                            //Previous Month Outbound Trip
                            $prevTripOutbounds = TripDetail::where('route_id', $allRoute->id)
                            ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                            ->where('trip_code', 0)
                            ->get();
            
                            if(count($prevTripOutbounds)>0){
                                foreach($prevTripOutbounds as $prevTripOutbound){
                                    //Previous Month Inbound Ridership collection
                                    $prevRidership = $prevTripOutbound->total_adult + $prevTripOutbound->total_concession;
                                    //Check tickets Ridership
                                    if($prevRidership==0){
                                        $prevRidership = TicketSalesTransaction::where('trip_id', $prevTripOutbound->id)->count();
                                    }
                                    $prevRidershipOut += $prevRidership;
            
                                    //Previous Month Inbound Farebox collection
                                    $prevFarebox = $prevTripOutbound->total_adult_amount + $prevTripOutbound->total_concession_amount;
                                    //Check tickets Farebox
                                    if($prevFarebox==0){
                                        $allTicketPerTrips = TicketSalesTransaction::where('trip_id', $prevTripOutbound->id)->get();
                                        if(count($allTicketPerTrips)>0){
                                            foreach($allTicketPerTrips as $allTicketPerTrip){
                                                $prevFarebox += $allTicketPerTrip->actual_amount;
                                            }
                                        }
                                    }
                                    $prevFareboxOut += $prevFarebox;
                                }
                            }
                            //Increment ridership collection (%)
                            if($prevRidershipOut==0){
                                $increaseRidershipOut = 100;
                                $increaseRidershipFormatOut = 100;
                            }else{
                                if($totalRidershipOut==0){
                                    $increaseRidershipOut = -100;
                                    $increaseRidershipFormatOut = -100;
                                }else{
                                    $increaseRidershipOut = (($totalRidershipOut - $prevRidershipOut) / $prevRidershipOut) * 100/100;
                                    $increaseRidershipFormatOut = number_format((float)$increaseRidershipOut, 2, '.', '');
                                }
                            }
                            //Increment farebox collection (%)
                            if($prevFareboxOut==0){
                                $increaseFareboxOut = 100;
                                $increaseFareboxFormatOut = 100;
                            }else{
                                if($totalRidershipOut==0){
                                    $increaseFareboxOut = -100;
                                    $increaseFareboxFormatOut = -100;
                                }else{
                                    $increaseFareboxOut = (($totalFareboxOut - $prevFareboxOut) / $prevFareboxOut) * 100;
                                    $increaseFareboxFormatOut = number_format((float)$increaseFareboxOut, 2, '.', '');
                                }
                            }
            
                            //Average Fare per pax (RM)
                            if($totalRidershipOut==0) {
                                $averageOut = 0;
                                $averageFormatOut = 0;
                            }
                            else{
                                $averageOut = $totalFareboxOut / $totalRidershipOut;
                                $averageFormatOut = number_format((float)$averageOut, 2, '.', '');
                            }
            
                            $countScheduleOut = 0;
                            $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->get();
                            foreach($schedules as $schedule){
                                if($schedule->RouteScheduleMSTR->route_id == $allRoute->id){
                                    if($schedule->RouteScheduleMSTR->trip_code == 0){
                                        $countScheduleOut++;
                                    } 
                                }
                            }
                            //Total KM Service Planned
                            $totalKMPlannedOut = $countScheduleOut * $allRoute->outbound_distance;
            
                            //Number of Trips missed
                            $tripMissedOut = $countScheduleOut - $totalTripOut;
                            if($tripMissedOut<0) {
                                $tripMissedOut=0;
                            }
            
                            /**Total Breakdown During Operation*/
                            $breakdownOut = 0;
            
                            //Total Bus In Used
                            $busUsedOut = TripDetail::where('route_id', $allRoute->id)
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
                            $outbound['trip_planned_out'] = $countScheduleOut;
                            $outbound['trip_made_out'] = $totalTripOut;
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
            
                            $inbound_data=[];
                            $outbound_data=[];
                            $content = [];
                            $route_name_in = $allRoute->route_name;
                            $route_name_out = implode(" - ", array_reverse(explode("-", $route_name_in)));
                            if ($existInTrip == 1 && $existOutTrip == 1) {
                                $inbound_data[$route_name_in] = $inbound;
                                $outbound_data[$route_name_out] = $outbound;
            
                                $total['total_ridership'] = $totalRidershipOut + $totalRidershipIn;
                                $total['total_prev_ridership'] = $prevRidershipOut + $prevRidershipIn;
            
                                if($increaseRidershipIn==100 && $increaseRidershipOut==100){
                                    $sumIncreaseRidership = 100;
                                    $sumIncreaseRidershipFormat = 100;
                                }else{
                                    $sumIncreaseRidership = (($increaseRidershipIn + $increaseRidershipOut) / 200) * 100;
                                    $sumIncreaseRidershipFormat = number_format((float)$sumIncreaseRidership, 2, '.', '');
                                }
                                $total['total_increase_ridership'] = $sumIncreaseRidershipFormat;
            
                                $total['total_farebox'] = $totalFareboxOut + $totalFareboxIn;
                                $total['total_prev_farebox'] = $prevFareboxOut + $prevFareboxIn;
            
                                if($increaseFareboxIn==100 && $increaseFareboxOut==100){
                                    $sumIncreaseFarebox = 100;
                                    $sumIncreaseFareboxFormat = 100;
                                }else{
                                    $sumIncreaseFarebox = (($increaseFareboxIn + $increaseFareboxOut) / 200) * 100;
                                    $sumIncreaseFareboxFormat = number_format((float)$sumIncreaseFarebox, 2, '.', '');
                                }
                                $total['total_increase_farebox'] = $sumIncreaseFareboxFormat;
            
                                if($total['total_ridership']==0) {
                                    $sumAverageFormat = 0;
                                }
                                else{
                                    $sumAverage = $total['total_farebox'] / $total['total_ridership'];
                                    $sumAverageFormat = number_format((float)$sumAverage, 2, '.', '');
                                }
                                $total['total_average_fare'] = $sumAverageFormat;
            
                                $total['total_trip_planned'] = $countScheduleOut + $countScheduleIn;
                                $total['total_trip_made'] = $totalTripOut + $totalTripIn;
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
                                $data[$allRoute->route_number] = $content;
            
                                $grandRidership += $total['total_ridership'];
                                $grandPrevRidership += $total['total_prev_ridership'];
                                $grandIncreaseRidership += $sumIncreaseRidership;
                                $grandFarebox += $total['total_farebox'];
                                $grandPrevFarebox += $total['total_prev_farebox'];
                                $grandIncreaseFarebox += $sumIncreaseFarebox;
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
            
                                $inbound_data[$route_name_in] = $inbound;
                                $outbound_data[$route_name_out] = $outbound;
            
                                $total['total_ridership'] = $totalRidershipOut;
                                $total['total_prev_ridership'] = $prevRidershipOut;
            
                                $totIncreaseRidership = ($increaseRidershipOut/ 200) * 100;
                                $totIncreaseRidershipFormat = number_format((float)$totIncreaseRidership, 2, '.', '');
                                $total['total_increase_ridership'] = $totIncreaseRidershipFormat;
            
                                $total['total_farebox'] = $totalFareboxOut;
                                $total['total_prev_farebox'] = $prevFareboxOut;
            
                                $totIncreaseFarebox = ($increaseFareboxOut/ 200) * 100;
                                $totIncreaseFareboxFormat = number_format((float)$totIncreaseFarebox, 2, '.', '');
                                $total['total_increase_farebox'] = $totIncreaseFareboxFormat;
            
                                $total['total_average_fare'] = $averageFormatOut;
                                $total['total_trip_planned'] = $countScheduleOut;
                                $total['total_trip_made'] = $totalTripOut;
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
            
                                $content['inbound_data'] = $inbound_data;
                                $content['outbound_data'] = $outbound_data;
                                $content['total'] = $total;
                                $data[$allRoute->route_number] = $content;
            
                                $grandRidership += $total['total_ridership'];
                                $grandPrevRidership += $total['total_prev_ridership'];
                                $grandIncreaseRidership += $increaseRidershipOut;
                                $grandFarebox += $total['total_farebox'];
                                $grandPrevFarebox += $total['total_prev_farebox'];
                                $grandIncreaseFarebox += $increaseFareboxOut;
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
            
                                $outbound_data[$route_name_out] = $outbound;
                                $inbound_data[$route_name_in] = $inbound;
            
                                $total['total_ridership'] = $totalRidershipIn;
                                $total['total_prev_ridership'] = $prevRidershipIn;
            
                                $totIncreaseRidership = ($increaseRidershipIn/ 200) * 100;
                                $totIncreaseRidershipFormat = number_format((float)$totIncreaseRidership, 2, '.', '');
                                $total['total_increase_ridership'] = $totIncreaseRidershipFormat;
            
                                $total['total_farebox'] = $totalFareboxOut;
                                $total['total_prev_farebox'] = $prevFareboxOut;
            
                                $totIncreaseFarebox = ($increaseFareboxIn/ 200) * 100;
                                $totIncreaseFareboxFormat = number_format((float)$totIncreaseFarebox, 2, '.', '');
                                $total['total_increase_farebox'] = $totIncreaseFareboxFormat;
                            
                                $total['total_average_fare'] = $averageFormatIn;
                                $total['total_trip_planned'] = $countScheduleIn;
                                $total['total_trip_made'] = $totalTripIn;
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
            
                                $content['outbound_data'] = $outbound_data;
                                $content['inbound_data'] = $inbound_data;
                                $content['total'] = $total;
                                $data[$allRoute->route_number] = $content;
            
                                $grandRidership += $total['total_ridership'];
                                $grandPrevRidership += $total['total_prev_ridership'];
                                $grandIncreaseRidership += $increaseRidershipIn;
                                $grandFarebox += $total['total_farebox'];
                                $grandPrevFarebox += $total['total_prev_farebox'];
                                $grandIncreaseFarebox += $increaseFareboxIn;
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
                                $data[$allRoute->route_number] = $content;
            
                                $grandRidership += 0;
                                $grandPrevRidership += 0;
                                $grandIncreaseRidership += 0;
                                $grandFarebox += 0;
                                $grandPrevFarebox += 0;
                                $grandIncreaseFarebox += 0;
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
            
                        $percentAllRoutes = count($allRoutes) * 100;
                        if($percentAllRoutes==$grandIncreaseRidership){
                            $grandIncreaseRidershipFormat = 100;
                        }else{
                            $increaseRidershipFormat = ($grandIncreaseRidership / $percentAllRoutes) * 100;
                            $grandIncreaseRidershipFormat = number_format((float)$increaseRidershipFormat, 2, '.', '');
                        }
                        $grand['grand_increase_ridership'] = $grandIncreaseRidershipFormat;
            
                        $grand['grand_farebox'] = $grandFarebox;
                        $grand['grand_prev_farebox'] = $grandPrevFarebox;
            
                        if($percentAllRoutes==$grandIncreaseFarebox){
                            $grandIncreaseFareboxFormat = 100;
                        }else{
                            $increaseFareboxFormat = ($grandIncreaseFarebox / $percentAllRoutes) * 100;
                            $grandIncreaseFareboxFormat = number_format((float)$increaseFareboxFormat, 2, '.', '');
                        }
                        $grand['grand_increase_farebox'] = $grandIncreaseFareboxFormat;
            
                        if($grandRidership!=0){
                            $averageFareGrand =  $grandFarebox / $grandRidership;
                            $grandAverageFare = number_format((float)$averageFareGrand, 2, '.', '');
                        }
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
            }
            else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $companyDetails->company_name;

                if(!empty($validatedData['route_id'])){
                    //Summary all routes for specific company
                    if($validatedData['route_id']=='All'){
                        //Get all route per company
                        $allRoutes = Route::where('company_id', $this->selectedCompany)->orderBy('route_number')->get();
            
                        foreach($allRoutes as $allRoute) {
                            $existInTrip = false;
                            $existOutTrip = false;
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
                            $totalTripIn = 0;

                            //Inbound
                            $allTripInbounds = TripDetail::where('route_id', $allRoute->id)
                                ->whereBetween('start_trip', [$dateFrom, $dateTo])
                                ->where('trip_code', 1)
                                ->get();
            
                            if (count($allTripInbounds)>0) {
                                foreach ($allTripInbounds as $allTripInbound) {
                                    $existInTrip = true;
            
                                    //Ridership
                                    $ridership = $allTripInbound->total_adult + $allTripInbound->total_concession;
                                    //Check tickets Ridership
                                    if($ridership==0){
                                        $ridership = TicketSalesTransaction::where('trip_id', $allTripInbound->id)->count();
                                    }
                                    $totalRidershipIn += $ridership;
            
                                    //Farebox Collection
                                    $farebox = $allTripInbound->total_adult_amount + $allTripInbound->total_concession_amount;
                                    //Check tickets Farebox
                                    if($farebox==0){
                                        $allTicketPerTrips = TicketSalesTransaction::where('trip_id', $allTripInbound->id)->get();
                                        if(count($allTicketPerTrips)>0){
                                            foreach($allTicketPerTrips as $allTicketPerTrip){
                                                $farebox += $allTicketPerTrip->actual_amount;
                                            }
                                        }
                                    }
                                    $totalFareboxIn += $farebox;
            
                                    //Total KM Service Served
                                    $kmServed = $allTripInbound->Route->inbound_distance;
                                    $totalKMServedIn += $kmServed;
            
                                    //Total KM Service Served by GPS
                                    $kmGPS = $allTripInbound->Route->inbound_distance;
                                    $totalKMGPSIn += $kmGPS;
            
                                    if ($allTripInbound->route_schedule_mstr_id != NULL) {
                                        //Total Early Departure
                                        $diffDepart = strtotime($allTripInbound->routeScheduleMSTR->schedule_start_time) - strtotime($allTripInbound->start_trip);
                                        if ($diffDepart > 5) {
                                            $earlyDepartureIn++;
                                        }elseif($diffDepart < -5){
                                            $lateDepartureIn++;
                                        }
                                        //Total Early End
                                        $diffEnd = strtotime($allTripInbound->routeScheduleMSTR->schedule_end_time) - strtotime($allTripInbound->end_trip);
                                        if ($diffEnd > 5) {
                                            $earlyEndIn++;
                                        }elseif($diffEnd < -5){
                                            $lateEndIn++;
                                        }
                                    }
                                    $totalTripIn++;
                                }
                            }
                            $prevRidershipIn = 0;
                            $prevFareboxIn = 0;
            
                            //Previous Month Inbound Trip
                            $prevTripInbounds = TripDetail::where('route_id', $allRoute->id)
                                ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                                ->where('trip_code', 1)
                                ->get();
            
                            if(count($prevTripInbounds)>0){
                                foreach($prevTripInbounds as $prevTripInbound){
                                    //Previous Month Inbound Ridership collection
                                    $prevRidership = $prevTripInbound->total_adult + $prevTripInbound->total_concession;
                                    //Check tickets Ridership
                                    if($prevRidership==0){
                                        $prevRidership = TicketSalesTransaction::where('trip_id', $prevTripInbound->id)->count();
                                    }
                                    $prevRidershipIn += $prevRidership;
            
                                    //Previous Month Inbound Farebox collection
                                    $prevFarebox = $prevTripInbound->total_adult_amount + $prevTripInbound->total_concession_amount;
                                    //Check tickets Farebox
                                    if($prevFarebox==0){
                                        $allTicketPerTrips = TicketSalesTransaction::where('trip_id', $prevTripInbound->id)->get();
                                        if(count($allTicketPerTrips)>0){
                                            foreach($allTicketPerTrips as $allTicketPerTrip){
                                                $prevFarebox += $allTicketPerTrip->actual_amount;
                                            }
                                        }
                                    }
                                    $prevFareboxIn += $prevFarebox;
                                }
                            }
                            //Increment ridership collection (%)
                            if($prevRidershipIn==0){
                                $increaseRidershipIn = 100;
                                $increaseRidershipFormatIn = 100;
                            }else{
                            if($totalRidershipIn==0){
                                $increaseRidershipIn = -100;
                                $increaseRidershipFormatIn = -100;
                            }else{
                                $increaseRidershipIn = (($totalRidershipIn - $prevRidershipIn) / $prevRidershipIn) * 100;
                                $increaseRidershipFormatIn = number_format((float)$increaseRidershipIn, 2, '.', '');
                            }
                            }

                            //Incremeant farebox collection (%)
                            if($prevFareboxIn==0){
                                $increaseFareboxIn = 100;
                                $increaseFareboxFormatIn = 100;
                            }else{
                            if($totalFareboxIn==0){
                                $increaseFareboxIn = -100;
                                $increaseFareboxFormatIn = -100;
                            }else{
                                $increaseFareboxIn = (($totalFareboxIn - $prevFareboxIn) / $prevFareboxIn) * 100;
                                $increaseFareboxFormatIn = number_format((float)$increaseFareboxIn, 2, '.', '');
                            }
                            }

                            //Average Fare per pax (RM)
                            if($totalRidershipIn==0) {
                                $averageIn = 0;
                                $averageFormatIn = 0;
                            }
                            else{
                                $averageIn = $totalFareboxIn / $totalRidershipIn;
                                $averageFormatIn = number_format((float)$averageIn, 2, '.', '');
                            }

                            $countScheduleIn = 0;
                            $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->get();
                            foreach($schedules as $schedule){
                                if($schedule->RouteScheduleMSTR->route_id == $allRoute->id){
                                    if($schedule->RouteScheduleMSTR->trip_code == 1){
                                        $countScheduleIn++;
                                    }
                                }
                            }
                            //Total KM Service Planned
                            $totalKMPlannedIn = $countScheduleIn * $allRoute->inbound_distance;

                            /**Number of Trips missed*/
                            //$tripMissed = MissedTrip::whereBetween('service_date', [$dateFrom, $dateTo])->count();
                            $tripMissedIn = $countScheduleIn - $totalTripIn;
                            if($tripMissedIn<0){
                                $tripMissedIn=0;
                            }

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
                            $inbound['trip_planned_in'] = $countScheduleIn;
                            $inbound['trip_made_in'] = $totalTripIn;
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
                            $totalTripOut = 0;

                            //Outbound
                            $allTripOutbounds = TripDetail::where('route_id',$allRoute->id)
                                ->whereBetween('start_trip', [$dateFrom, $dateTo])
                                ->where('trip_code', 0)
                                ->get();
            
                            if (count($allTripOutbounds) > 0) {
                                foreach ($allTripOutbounds as $allTripOutbound) {
                                    $existOutTrip = true;

                                    //Ridership
                                    $ridership = $allTripOutbound->total_adult + $allTripOutbound->total_concession;
                                    //Check tickets Ridership
                                    if($ridership==0){
                                        $ridership = TicketSalesTransaction::where('trip_id', $allTripOutbound->id)->count();
                                    }
                                    $totalRidershipOut += $ridership;
            
                                    //Farebox Collection
                                    $farebox = $allTripOutbound->total_adult_amount + $allTripOutbound->total_concession_amount;
                                    //Check tickets Farebox
                                    if($farebox==0){
                                        $allTicketPerTrips = TicketSalesTransaction::where('trip_id', $allTripOutbound->id)->get();
                                        if(count($allTicketPerTrips)>0){
                                            foreach($allTicketPerTrips as $allTicketPerTrip){
                                                $farebox += $allTicketPerTrip->actual_amount;
                                            }
                                        }
                                    }
                                    $totalFareboxOut += $farebox;
            
                                    //Total KM Service Served
                                    $kmServed = $allTripOutbound->Route->outbound_distance;
                                    $totalKMServedOut += $kmServed;
            
                                    //Total KM Service Served by GPS
                                    $kmGPS = $allTripOutbound->Route->outbound_distance;
                                    $totalKMGPSOut += $kmGPS;
            
                                    if ($allTripOutbound->route_schedule_mstr_id != NULL) {
                                        //Total Early Departure
                                        $diffDepart = strtotime($allTripOutbound->routeScheduleMSTR->schedule_start_time) - strtotime($allTripOutbound->start_trip);
                                        if ($diffDepart > 5) {
                                            $earlyDepartureOut++;
                                        }elseif($diffDepart < -5){
                                            $lateDepartureOut++;
                                        }
                                        //Total Early End
                                        $diffEnd = strtotime($allTripOutbound->routeScheduleMSTR->schedule_end_time) - strtotime($allTripOutbound->end_trip);
                                        if ($diffEnd > 5) {
                                            $earlyEndOut++;
                                        }elseif($diffEnd < -5){
                                            $lateEndOut++;
                                        }
                                    }
                                    $totalTripOut++;
                                }
                            }
                            $prevRidershipOut = 0;
                            $prevFareboxOut = 0;
            
                            //Previous Month Outbound Trip
                            $prevTripOutbounds = TripDetail::where('route_id', $allRoute->id)
                            ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                            ->where('trip_code', 0)
                            ->get();
            
                            if(count($prevTripOutbounds)>0){
                                foreach($prevTripOutbounds as $prevTripOutbound){
                                    //Previous Month Inbound Ridership collection
                                    $prevRidership = $prevTripOutbound->total_adult + $prevTripOutbound->total_concession;
                                    //Check tickets Ridership
                                    if($prevRidership==0){
                                        $prevRidership = TicketSalesTransaction::where('trip_id', $prevTripOutbound->id)->count();
                                    }
                                    $prevRidershipOut += $prevRidership;
            
                                    //Previous Month Inbound Farebox collection
                                    $prevFarebox = $prevTripOutbound->total_adult_amount + $prevTripOutbound->total_concession_amount;
                                    //Check tickets Farebox
                                    if($prevFarebox==0){
                                        $allTicketPerTrips = TicketSalesTransaction::where('trip_id', $prevTripOutbound->id)->get();
                                        if(count($allTicketPerTrips)>0){
                                            foreach($allTicketPerTrips as $allTicketPerTrip){
                                                $prevFarebox += $allTicketPerTrip->actual_amount;
                                            }
                                        }
                                    }
                                    $prevFareboxOut += $prevFarebox;
                                }
                            }
                            //Increment ridership collection (%)
                            if($prevRidershipOut==0){
                                $increaseRidershipOut = 100;
                                $increaseRidershipFormatOut = 100;
                            }else{
                                if($totalRidershipOut==0){
                                    $increaseRidershipOut = -100;
                                    $increaseRidershipFormatOut = -100;
                                }else{
                                    $increaseRidershipOut = (($totalRidershipOut - $prevRidershipOut) / $prevRidershipOut) * 100/100;
                                    $increaseRidershipFormatOut = number_format((float)$increaseRidershipOut, 2, '.', '');
                                }
                            }
                            //Increment farebox collection (%)
                            if($prevFareboxOut==0){
                                $increaseFareboxOut = 100;
                                $increaseFareboxFormatOut = 100;
                            }else{
                                if($totalRidershipOut==0){
                                    $increaseFareboxOut = -100;
                                    $increaseFareboxFormatOut = -100;
                                }else{
                                    $increaseFareboxOut = (($totalFareboxOut - $prevFareboxOut) / $prevFareboxOut) * 100;
                                    $increaseFareboxFormatOut = number_format((float)$increaseFareboxOut, 2, '.', '');
                                }
                            }
            
                            //Average Fare per pax (RM)
                            if($totalRidershipOut==0) {
                                $averageOut = 0;
                                $averageFormatOut = 0;
                            }
                            else{
                                $averageOut = $totalFareboxOut / $totalRidershipOut;
                                $averageFormatOut = number_format((float)$averageOut, 2, '.', '');
                            }
            
                            $countScheduleOut = 0;
                            $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->get();
                            foreach($schedules as $schedule){
                                if($schedule->RouteScheduleMSTR->route_id == $allRoute->id){
                                    if($schedule->RouteScheduleMSTR->trip_code == 0){
                                        $countScheduleOut++;
                                    }
                                }
                            }
                            //Total KM Service Planned
                            $totalKMPlannedOut = $countScheduleOut * $allRoute->outbound_distance;
            
                            /**Number of Trips missed*/
                            //$tripMissed = MissedTrip::whereBetween('service_date', [$dateFrom, $dateTo])->count();
                            $tripMissedOut =  $countScheduleOut - $totalTripOut;
                            if($tripMissedOut<0) {
                                $tripMissedOut=0;
                            }
            
                            /**Total Breakdown During Operation*/
                            $breakdownOut = 0;
            
                            //Total Bus In Used
                            $busUsedOut = TripDetail::where('route_id', $allRoute->id)
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
                            $outbound['trip_planned_out'] = $countScheduleOut;
                            $outbound['trip_made_out'] = $totalTripOut;
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
            
                            $inbound_data=[];
                            $outbound_data=[];
                            $content = [];
                            $route_name_in = $allRoute->route_name;
                            $route_name_out = implode(" - ", array_reverse(explode("-", $route_name_in)));
                            if ($existInTrip == 1 && $existOutTrip == 1) {
                                $out = new ConsoleOutput();
                                $out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == true");
            
                                $inbound_data[$route_name_in] = $inbound;
                                $outbound_data[$route_name_out] = $outbound;
            
                                $total['total_ridership'] = $totalRidershipOut + $totalRidershipIn;
                                $total['total_prev_ridership'] = $prevRidershipOut + $prevRidershipIn;
            
                                if($increaseRidershipIn==100 && $increaseRidershipOut==100){
                                    $sumIncreaseRidership = 100;
                                    $sumIncreaseRidershipFormat = 100;
                                }else{
                                    $sumIncreaseRidership = (($increaseRidershipIn + $increaseRidershipOut) / 200) * 100;
                                    $sumIncreaseRidershipFormat = number_format((float)$sumIncreaseRidership, 2, '.', '');
                                }
                                $total['total_increase_ridership'] = $sumIncreaseRidershipFormat;
            
                                $total['total_farebox'] = $totalFareboxOut + $totalFareboxIn;
                                $total['total_prev_farebox'] = $prevFareboxOut + $prevFareboxIn;
            
                                if($increaseFareboxIn==100 && $increaseFareboxOut==100){
                                    $sumIncreaseFarebox = 100;
                                    $sumIncreaseFareboxFormat = 100;
                                }else{
                                    $sumIncreaseFarebox = (($increaseFareboxIn + $increaseFareboxOut) / 200) * 100;
                                    $sumIncreaseFareboxFormat = number_format((float)$sumIncreaseFarebox, 2, '.', '');
                                }
                                $total['total_increase_farebox'] = $sumIncreaseFareboxFormat;
            
                                if($total['total_ridership']==0) {
                                    $sumAverageFormat = 0;
                                }
                                else{
                                    $sumAverage = $total['total_farebox'] / $total['total_ridership'];
                                    $sumAverageFormat = number_format((float)$sumAverage, 2, '.', '');
                                }
                                $total['total_average_fare'] = $sumAverageFormat;
            
                                $total['total_trip_planned'] = $countScheduleOut +  $countScheduleIn;
                                $total['total_trip_made'] = $totalTripOut + $totalTripIn;
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
                                $data[$allRoute->route_number] = $content;
            
                                $grandRidership += $total['total_ridership'];
                                $grandPrevRidership += $total['total_prev_ridership'];
                                $grandIncreaseRidership += $sumIncreaseRidership;
                                $grandFarebox += $total['total_farebox'];
                                $grandPrevFarebox += $total['total_prev_farebox'];
                                $grandIncreaseFarebox += $sumIncreaseFarebox;
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
            
                                $inbound_data[$route_name_in] = $inbound;
                                $outbound_data[$route_name_out] = $outbound;
            
                                $total['total_ridership'] = $totalRidershipOut;
                                $total['total_prev_ridership'] = $prevRidershipOut;
            
                                $totIncreaseRidership = ($increaseRidershipOut/ 200) * 100;
                                $totIncreaseRidershipFormat = number_format((float)$totIncreaseRidership, 2, '.', '');
                                $total['total_increase_ridership'] = $totIncreaseRidershipFormat;
            
                                $total['total_farebox'] = $totalFareboxOut;
                                $total['total_prev_farebox'] = $prevFareboxOut;
            
                                $totIncreaseFarebox = ($increaseFareboxOut/ 200) * 100;
                                $totIncreaseFareboxFormat = number_format((float)$totIncreaseFarebox, 2, '.', '');
                                $total['total_increase_farebox'] = $totIncreaseFareboxFormat;
            
                                $total['total_average_fare'] = $averageFormatOut;
                                $total['total_trip_planned'] =  $countScheduleOut;
                                $total['total_trip_made'] = $totalTripOut;
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
            
                                $content['inbound_data'] = $inbound_data;
                                $content['outbound_data'] = $outbound_data;
                                $content['total'] = $total;
                                $data[$allRoute->route_number] = $content;
            
                                $grandRidership += $total['total_ridership'];
                                $grandPrevRidership += $total['total_prev_ridership'];
                                $grandIncreaseRidership += $increaseRidershipOut;
                                $grandFarebox += $total['total_farebox'];
                                $grandPrevFarebox += $total['total_prev_farebox'];
                                $grandIncreaseFarebox += $increaseFareboxOut;
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
            
                                $outbound_data[$route_name_out] = $outbound;
                                $inbound_data[$route_name_in] = $inbound;
            
                                $total['total_ridership'] = $totalRidershipIn;
                                $total['total_prev_ridership'] = $prevRidershipIn;
            
                                $totIncreaseRidership = ($increaseRidershipIn/ 200) * 100;
                                $totIncreaseRidershipFormat = number_format((float)$totIncreaseRidership, 2, '.', '');
                                $total['total_increase_ridership'] = $totIncreaseRidershipFormat;
            
                                $total['total_farebox'] = $totalFareboxOut;
                                $total['total_prev_farebox'] = $prevFareboxOut;
            
                                $totIncreaseFarebox = ($increaseFareboxIn/ 200) * 100;
                                $totIncreaseFareboxFormat = number_format((float)$totIncreaseFarebox, 2, '.', '');
                                $total['total_increase_farebox'] = $totIncreaseFareboxFormat;

                                $total['total_average_fare'] = $averageFormatIn;
                                $total['total_trip_planned'] =  $countScheduleIn;
                                $total['total_trip_made'] = $totalTripIn;
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
            
                                $content['outbound_data'] = $outbound_data;
                                $content['inbound_data'] = $inbound_data;
                                $content['total'] = $total;
                                $data[$allRoute->route_number] = $content;
            
                                $grandRidership += $total['total_ridership'];
                                $grandPrevRidership += $total['total_prev_ridership'];
                                $grandIncreaseRidership += $increaseRidershipIn;
                                $grandFarebox += $total['total_farebox'];
                                $grandPrevFarebox += $total['total_prev_farebox'];
                                $grandIncreaseFarebox += $increaseFareboxIn;
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
                                $data[$allRoute->route_number] = $content;
            
                                $grandRidership += 0;
                                $grandPrevRidership += 0;
                                $grandIncreaseRidership += 0;
                                $grandFarebox += 0;
                                $grandPrevFarebox += 0;
                                $grandIncreaseFarebox += 0;
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

                        $percentAllRoutes = count($allRoutes) * 100;
                        if($percentAllRoutes==$grandIncreaseRidership){
                            $grandIncreaseRidershipFormat = 100;
                        }else{
                            $increaseRidershipFormat = ($grandIncreaseRidership / $percentAllRoutes) * 100;
                            $grandIncreaseRidershipFormat = number_format((float)$increaseRidershipFormat, 2, '.', '');
                        }
                        $grand['grand_increase_ridership'] = $grandIncreaseRidershipFormat;

                        $grand['grand_farebox'] = $grandFarebox;
                        $grand['grand_prev_farebox'] = $grandPrevFarebox;

                        if($percentAllRoutes==$grandIncreaseFarebox){
                            $grandIncreaseFareboxFormat = 100;
                        }else{
                            $increaseFareboxFormat = ($grandIncreaseFarebox / $percentAllRoutes) * 100;
                            $grandIncreaseFareboxFormat = number_format((float)$increaseFareboxFormat, 2, '.', '');
                        }
                        $grand['grand_increase_farebox'] = $grandIncreaseFareboxFormat;

                        if($grandRidership!=0){
                            $averageFareGrand =  $grandFarebox / $grandRidership;
                            $grandAverageFare = number_format((float)$averageFareGrand, 2, '.', '');
                        }
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
                    //Summary specific routes for specific company
                    else{
                        //Get all route per company
                        $chosenRoute = Route::where('id', $this->state['route_id'])->first();
                        $existInTrip = false;
                        $existOutTrip = false;
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
                        $totalTripIn = 0;

                        //Inbound
                        $allTripInbounds = TripDetail::where('route_id', $chosenRoute->id)
                            ->whereBetween('start_trip', [$dateFrom, $dateTo])
                            ->where('trip_code', 1)
                            ->get();

                        if (count($allTripInbounds)>0) {
                            foreach ($allTripInbounds as $allTripInbound) {
                                $existInTrip = true;

                                //Ridership
                                $ridership = $allTripInbound->total_adult + $allTripInbound->total_concession;
                                //Check tickets Ridership
                                if($ridership==0){
                                    $ridership = TicketSalesTransaction::where('trip_id', $allTripInbound->id)->count();
                                }
                                $totalRidershipIn += $ridership;

                                //Farebox Collection
                                $farebox = $allTripInbound->total_adult_amount + $allTripInbound->total_concession_amount;
                                //Check tickets Farebox
                                if($farebox==0){
                                    $allTicketPerTrips = TicketSalesTransaction::where('trip_id', $allTripInbound->id)->get();
                                    if(count($allTicketPerTrips)>0){
                                        foreach($allTicketPerTrips as $allTicketPerTrip){
                                            $farebox += $allTicketPerTrip->actual_amount;
                                        }
                                    }
                                }
                                $totalFareboxIn += $farebox;

                                //Total KM Service Served
                                $kmServed = $allTripInbound->Route->inbound_distance;
                                $totalKMServedIn += $kmServed;

                                //Total KM Service Served by GPS
                                $kmGPS = $allTripInbound->Route->inbound_distance;
                                $totalKMGPSIn += $kmGPS;

                                if ($allTripInbound->route_schedule_mstr_id != NULL) {
                                    //Total Early Departure
                                    $diffDepart = strtotime($allTripInbound->routeScheduleMSTR->schedule_start_time) - strtotime($allTripInbound->start_trip);
                                    if ($diffDepart > 5) {
                                        $earlyDepartureIn++;
                                    }elseif($diffDepart < -5){
                                        $lateDepartureIn++;
                                    }
                                    //Total Early End
                                    $diffEnd = strtotime($allTripInbound->routeScheduleMSTR->schedule_end_time) - strtotime($allTripInbound->end_trip);
                                    if ($diffEnd > 5) {
                                        $earlyEndIn++;
                                    }elseif($diffEnd < -5){
                                        $lateEndIn++;
                                    }
                                }
                                $totalTripIn++;
                            }
                        }
                        $prevRidershipIn = 0;
                        $prevFareboxIn = 0;

                        //Previous Month Inbound Trip
                        $prevTripInbounds = TripDetail::where('route_id', $chosenRoute->id)
                        ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                        ->where('trip_code', 1)
                        ->get();

                        if(count($prevTripInbounds)>0){
                            foreach($prevTripInbounds as $prevTripInbound){
                                //Previous Month Inbound Ridership collection
                                $prevRidership = $prevTripInbound->total_adult + $prevTripInbound->total_concession;
                                //Check tickets Ridership
                                if($prevRidership==0){
                                    $prevRidership = TicketSalesTransaction::where('trip_id', $prevTripInbound->id)->count();
                                }
                                $prevRidershipIn += $prevRidership;
            
                                //Previous Month Inbound Farebox collection
                                $prevFarebox = $prevTripInbound->total_adult_amount + $prevTripInbound->total_concession_amount;
                                //Check tickets Farebox
                                if($prevFarebox==0){
                                    $allTicketPerTrips = TicketSalesTransaction::where('trip_id', $prevTripInbound->id)->get();
                                    if(count($allTicketPerTrips)>0){
                                        foreach($allTicketPerTrips as $allTicketPerTrip){
                                        $prevFarebox += $allTicketPerTrip->actual_amount;
                                        }
                                    }
                                }
                                $prevFareboxIn += $prevFarebox;
                            }
                        }
                        //Increment ridership collection (%)
                        if($prevRidershipIn==0){
                            $increaseRidershipIn = 100;
                            $increaseRidershipFormatIn = 100;
                        }else{
                            if($totalRidershipIn==0){
                                $increaseRidershipIn = -100;
                                $increaseRidershipFormatIn = -100;
                            }else{
                                $increaseRidershipIn = (($totalRidershipIn - $prevRidershipIn) / $prevRidershipIn) * 100/100;
                                $increaseRidershipFormatIn = number_format((float)$increaseRidershipIn, 2, '.', '');
                            }
                        }

                        //Incremeant farebox collection (%)
                        if($prevFareboxIn==0){
                            $increaseFareboxIn = 100;
                            $increaseFareboxFormatIn = 100;
                        }else{
                            if($totalFareboxIn==0){
                                $increaseFareboxIn = -100;
                                $increaseFareboxFormatIn = -100;
                            }else{
                                $increaseFareboxIn = (($totalFareboxIn - $prevFareboxIn) / $prevFareboxIn) * 100/100;
                                $increaseFareboxFormatIn = number_format((float)$increaseFareboxIn, 2, '.', '');
                            }
                        }

                        //Average Fare per pax (RM)
                        if($totalRidershipIn==0) {
                            $averageIn = 0;
                            $averageFormatIn = 0;
                        }
                        else{
                            $averageIn = $totalFareboxIn / $totalRidershipIn;
                            $averageFormatIn = number_format((float)$averageIn, 2, '.', '');
                        }

                        $countScheduleIn = 0;
                        $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->get();
                        foreach($schedules as $schedule){
                            if($schedule->RouteScheduleMSTR->route_id == $chosenRoute->id){
                                if($schedule->RouteScheduleMSTR->trip_code == 1){
                                    $countScheduleIn++;
                                }
                            }
                        }
                        //Total KM Service Planned
                        $totalKMPlannedIn = $countScheduleIn * $chosenRoute->inbound_distance;

                        /**Number of Trips missed*/
                        $tripMissedIn = $countScheduleIn - $totalTripIn;
                        if($tripMissedIn<0){
                            $tripMissedIn=0;
                        }

                        /**Total Breakdown During Operation*/
                        $breakdownIn = 0;

                        //Total Bus In Used
                        $busUsedIn = TripDetail::where('route_id', $chosenRoute->id)
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
                        $inbound['trip_planned_in'] = $countScheduleIn;
                        $inbound['trip_made_in'] = $totalTripIn;
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
                        $totalTripOut = 0;

                        //Outbound
                        $allTripOutbounds = TripDetail::where('route_id', $chosenRoute->id)
                            ->whereBetween('start_trip', [$dateFrom, $dateTo])
                            ->where('trip_code', 0)
                            ->get();

                        if (count($allTripOutbounds) > 0) {
                            foreach ($allTripOutbounds as $allTripOutbound) {
                                $existOutTrip = true;

                                //Ridership
                                $ridership = $allTripOutbound->total_adult + $allTripOutbound->total_concession;
                                //Check tickets Ridership
                                if($ridership==0){
                                    $ridership = TicketSalesTransaction::where('trip_id', $allTripOutbound->id)->count();
                                }
                                $totalRidershipOut += $ridership;

                                //Farebox Collection
                                $farebox = $allTripOutbound->total_adult_amount + $allTripOutbound->total_concession_amount;
                                //Check tickets Farebox
                                if($farebox==0){
                                    $allTicketPerTrips = TicketSalesTransaction::where('trip_id', $allTripOutbound->id)->get();
                                    if(count($allTicketPerTrips)>0){
                                        foreach($allTicketPerTrips as $allTicketPerTrip){
                                            $farebox += $allTicketPerTrip->actual_amount;
                                        }
                                    }
                                }
                                $totalFareboxOut += $farebox;

                                //Total KM Service Served
                                $kmServed = $allTripOutbound->Route->outbound_distance;
                                $totalKMServedOut += $kmServed;

                                //Total KM Service Served by GPS
                                $kmGPS = $allTripOutbound->Route->outbound_distance;
                                $totalKMGPSOut += $kmGPS;

                                if ($allTripOutbound->route_schedule_mstr_id != NULL) {
                                    //Total Early Departure
                                    $diffDepart = strtotime($allTripOutbound->routeScheduleMSTR->schedule_start_time) - strtotime($allTripOutbound->start_trip);
                                    if ($diffDepart > 5) {
                                        $earlyDepartureOut++;
                                    }elseif($diffDepart < -5){
                                        $lateDepartureOut++;
                                    }
                                    //Total Early End
                                    $diffEnd = strtotime($allTripOutbound->routeScheduleMSTR->schedule_end_time) - strtotime($allTripOutbound->end_trip);
                                    if ($diffEnd > 5) {
                                        $earlyEndOut++;
                                    }elseif($diffEnd < -5){
                                        $lateEndOut++;
                                    }
                                }
                                $totalTripOut++;
                            }
                        }
                        $prevRidershipOut = 0;
                        $prevFareboxOut = 0;

                        //Previous Month Outbound Trip
                        $prevTripOutbounds = TripDetail::where('route_id', $chosenRoute->id)
                            ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                            ->where('trip_code', 0)
                            ->get();

                        if(count($prevTripOutbounds)>0){
                            foreach($prevTripOutbounds as $prevTripOutbound){
                                //Previous Month Inbound Ridership collection
                                $prevRidership = $prevTripOutbound->total_adult + $prevTripOutbound->total_concession;
                                //Check tickets Ridership
                                if($prevRidership==0){
                                    $prevRidership = TicketSalesTransaction::where('trip_id', $prevTripOutbound->id)->count();
                                }
                                $prevRidershipOut += $prevRidership;

                                //Previous Month Inbound Farebox collection
                                $prevFarebox = $prevTripOutbound->total_adult_amount + $prevTripOutbound->total_concession_amount;
                                //Check tickets Farebox
                                if($prevFarebox==0){
                                    $allTicketPerTrips = TicketSalesTransaction::where('trip_id', $prevTripOutbound->id)->get();
                                    if(count($allTicketPerTrips)>0){
                                        foreach($allTicketPerTrips as $allTicketPerTrip){
                                        $prevFarebox += $allTicketPerTrip->actual_amount;
                                        }
                                    }
                                }
                                $prevFareboxOut += $prevFarebox;
                            }
                        }
                        //Increment ridership collection (%)
                        if($prevRidershipOut==0){
                            $increaseRidershipOut = 100;
                            $increaseRidershipFormatOut = 100;
                        }else{
                            if($totalRidershipOut==0){
                                $increaseRidershipOut = -100;
                                $increaseRidershipFormatOut = -100;
                            }else{
                                $increaseRidershipOut = (($totalRidershipOut - $prevRidershipOut) / $prevRidershipOut) * 100;
                                $increaseRidershipFormatOut = number_format((float)$increaseRidershipOut, 2, '.', '');
                            }
                        }
                        //Increment farebox collection (%)
                        if($prevFareboxOut==0){
                            $increaseFareboxOut = 100;
                            $increaseFareboxFormatOut = 100;
                        }else{
                            if($totalRidershipOut==0){
                                $increaseFareboxOut = -100;
                                $increaseFareboxFormatOut = -100;
                            }else{
                                $increaseFareboxOut = (($totalFareboxOut - $prevFareboxOut) / $prevFareboxOut) * 100;
                                $increaseFareboxFormatOut = number_format((float)$increaseFareboxOut, 2, '.', '');
                            }
                        }

                        //Average Fare per pax (RM)
                        if($totalRidershipOut==0) {
                            $averageOut = 0;
                            $averageFormatOut = 0;
                        }
                        else{
                            $averageOut = $totalFareboxOut / $totalRidershipOut;
                            $averageFormatOut = number_format((float)$averageOut, 2, '.', '');
                        }

                        $countScheduleOut = 0;
                        $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->get();
                        foreach($schedules as $schedule){
                            if($schedule->RouteScheduleMSTR->route_id == $chosenRoute->id){
                                if($schedule->RouteScheduleMSTR->trip_code == 0){
                                    $countScheduleOut++;
                                }
                            }
                        }
                        //Total KM Service Planned
                        $totalKMPlannedOut = $countScheduleOut * $chosenRoute->outbound_distance;

                        /**Number of Trips missed*/
                        //$tripMissed = MissedTrip::whereBetween('service_date', [$dateFrom, $dateTo])->count();
                        $tripMissedOut = $countScheduleOut - $totalTripOut;
                        if($tripMissedOut<0) {
                            $tripMissedOut=0;
                        }

                        /**Total Breakdown During Operation*/
                        $breakdownOut = 0;

                        //Total Bus In Used
                        $busUsedOut = TripDetail::where('route_id', $chosenRoute->id)
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
                        $outbound['trip_planned_out'] = $countScheduleOut;
                        $outbound['trip_made_out'] = $totalTripOut;
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

                        $inbound_data=[];
                        $outbound_data=[];
                        $route_name_in = $chosenRoute->route_name;
                        $route_name_out = implode(" - ", array_reverse(explode("-", $route_name_in)));
                        if ($existInTrip == 1 && $existOutTrip == 1) {
                            //$out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == true");

                            $inbound_data[$route_name_in] = $inbound;
                            $outbound_data[$route_name_out] = $outbound;

                            $total['total_ridership'] = $totalRidershipOut + $totalRidershipIn;
                            $total['total_prev_ridership'] = $prevRidershipOut + $prevRidershipIn;

                            if($increaseRidershipIn==100 && $increaseRidershipOut==100){
                                $sumIncreaseRidership = 100;
                                $sumIncreaseRidershipFormat = 100;
                            }else{
                                $sumIncreaseRidership = (($increaseRidershipIn + $increaseRidershipOut) / 200) * 100;
                                $sumIncreaseRidershipFormat = number_format((float)$sumIncreaseRidership, 2, '.', '');
                            }
                            $total['total_increase_ridership'] = $sumIncreaseRidershipFormat;

                            $total['total_farebox'] = $totalFareboxOut + $totalFareboxIn;
                            $total['total_prev_farebox'] = $prevFareboxOut + $prevFareboxIn;

                            if($increaseFareboxIn==100 && $increaseFareboxOut==100){
                                $sumIncreaseFarebox = 100;
                                $sumIncreaseFareboxFormat = 100;
                            }else{
                                $sumIncreaseFarebox = (($increaseFareboxIn + $increaseFareboxOut) / 200) * 100;
                                $sumIncreaseFareboxFormat = number_format((float)$sumIncreaseFarebox, 2, '.', '');
                            }
                            $total['total_increase_farebox'] = $sumIncreaseFareboxFormat;

                            if($total['total_ridership']==0) {
                                $sumAverageFormat = 0;
                            }
                            else{
                                $sumAverage = $total['total_farebox'] / $total['total_ridership'];
                                $sumAverageFormat = number_format((float)$sumAverage, 2, '.', '');
                            }
                            $total['total_average_fare'] = $sumAverageFormat;

                            $total['total_trip_planned'] = $countScheduleIn + $totalTripIn;
                            $total['total_trip_made'] = $countScheduleOut + $totalTripOut;
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
                            $data[$chosenRoute->route_number] = $content;

                            $grandRidership += $total['total_ridership'];
                            $grandPrevRidership += $total['total_prev_ridership'];
                            $grandIncreaseRidership += $sumIncreaseRidership;
                            $grandFarebox += $total['total_farebox'];
                            $grandPrevFarebox += $total['total_prev_farebox'];
                            $grandIncreaseFarebox += $sumIncreaseFarebox;;
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
                            //$out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == true");

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

                            $inbound_data[$route_name_in] = $inbound;
                            $outbound_data[$route_name_out] = $outbound;

                            $total['total_ridership'] = $totalRidershipOut;
                            $total['total_prev_ridership'] = $prevRidershipOut;

                            $totIncreaseRidership = ($increaseRidershipOut/ 200) * 100;
                            $totIncreaseRidershipFormat = number_format((float)$totIncreaseRidership, 2, '.', '');
                            $total['total_increase_ridership'] = $totIncreaseRidershipFormat;

                            $total['total_farebox'] = $totalFareboxOut;
                            $total['total_prev_farebox'] = $prevFareboxOut;

                            $totIncreaseFarebox = ($increaseFareboxOut/ 200) * 100;
                            $totIncreaseFareboxFormat = number_format((float)$totIncreaseFarebox, 2, '.', '');
                            $total['total_increase_farebox'] = $totIncreaseFareboxFormat;

                            $total['total_average_fare'] = $averageFormatOut;
                            $total['total_trip_planned'] = $countScheduleOut;
                            $total['total_trip_made'] = $totalTripOut;
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

                            $content['inbound_data'] = $inbound_data;
                            $content['outbound_data'] = $outbound_data;
                            $content['total'] = $total;
                            $data[$chosenRoute->route_number] = $content;

                            $grandRidership += $total['total_ridership'];
                            $grandPrevRidership += $total['total_prev_ridership'];
                            $grandIncreaseRidership += $increaseRidershipOut;
                            $grandFarebox += $total['total_farebox'];
                            $grandPrevFarebox += $total['total_prev_farebox'];
                            $grandIncreaseFarebox += $increaseFareboxOut;
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
                            //$out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == false");

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

                            $outbound_data[$route_name_out] = $outbound;
                            $inbound_data[$route_name_in] = $inbound;

                            $total['total_ridership'] = $totalRidershipIn;
                            $total['total_prev_ridership'] = $prevRidershipIn;

                            $totIncreaseRidership = ($increaseRidershipIn/ 200) * 100;
                            $totIncreaseRidershipFormat = number_format((float)$totIncreaseRidership, 2, '.', '');
                            $total['total_increase_ridership'] = $totIncreaseRidershipFormat;

                            $total['total_farebox'] = $totalFareboxOut;
                            $total['total_prev_farebox'] = $prevFareboxOut;

                            $totIncreaseFarebox = ($increaseFareboxIn/ 200) * 100;
                            $totIncreaseFareboxFormat = number_format((float)$totIncreaseFarebox, 2, '.', '');
                            $total['total_increase_farebox'] = $totIncreaseFareboxFormat;

                            $total['total_average_fare'] = $averageFormatIn;
                            $total['total_trip_planned'] = $countScheduleIn;
                            $total['total_trip_made'] = $totalTripIn;
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

                            $content['outbound_data'] = $outbound_data;
                            $content['inbound_data'] = $inbound_data;
                            $content['total'] = $total;
                            $data[$chosenRoute->route_number] = $content;

                            $grandRidership += $total['total_ridership'];
                            $grandPrevRidership += $total['total_prev_ridership'];
                            $grandIncreaseRidership += $increaseRidershipIn;
                            $grandFarebox += $total['total_farebox'];
                            $grandPrevFarebox += $total['total_prev_farebox'];
                            $grandIncreaseFarebox += $increaseFareboxIn;
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
                            //$out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == false");

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
                            $data[$chosenRoute->route_number] = $content;

                            $grandRidership += 0;
                            $grandPrevRidership += 0;
                            $grandIncreaseRidership += 0;
                            $grandFarebox += 0;
                            $grandPrevFarebox += 0;
                            $grandIncreaseFarebox += 0;
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
                        $grand['grand_ridership'] = $grandRidership;
                        $grand['grand_prev_ridership'] =  $grandPrevRidership;

                        $percentAllRoutes = 100;
                        if($percentAllRoutes==$grandIncreaseRidership){
                            $grandIncreaseRidershipFormat = 100;
                        }else{
                            $increaseRidershipFormat = ($grandIncreaseRidership / $percentAllRoutes) * 100;
                            $grandIncreaseRidershipFormat = number_format((float)$increaseRidershipFormat, 2, '.', '');
                        }
                        $grand['grand_increase_ridership'] = $grandIncreaseRidershipFormat;

                        $grand['grand_farebox'] = $grandFarebox;
                        $grand['grand_prev_farebox'] = $grandPrevFarebox;

                        if($percentAllRoutes==$grandIncreaseFarebox){
                            $grandIncreaseFareboxFormat = 100;
                        }else{
                            $increaseFareboxFormat = ($grandIncreaseFarebox / $percentAllRoutes) * 100;
                            $grandIncreaseFareboxFormat = number_format((float)$increaseFareboxFormat, 2, '.', '');
                        }
                        $grand['grand_increase_farebox'] = $grandIncreaseFareboxFormat;

                        if($grandRidership!=0){
                            $averageFareGrand =  $grandFarebox / $grandRidership;
                            $grandAverageFare = number_format((float)$averageFareGrand, 2, '.', '');
                        }
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
            }
            return Excel::download(new SPADSummary($summary, $all_dates, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Summary_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printServiceGroup()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printServiceGroup()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();
            $startDate->addDay();
        }

        $serviceGroup = collect();
        $totalScheduledTrip=0;
        $totalTripMade=0;
        $totalSumPassenger=0;
        $totalAdult=0;
        $totalConcession=0;
        if($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'ALL';
                if(!empty($validatedData['route_id'])) {
                    //ServiceGroup all routes for all company
                    if($validatedData['route_id']=='All'){
                        $allRoutes = Route::orderBy('route_number')->get();
            
                        foreach($allRoutes as $allRoute){
                            foreach ($all_dates as $all_date) {
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date . '23:59:59');
            
                                //Number of Scheduled Trips
                                $scheduledTrip = 0;
                                $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate,$lastDate])->get();
                                foreach($schedules as $schedule){
                                    if($schedule->RouteScheduleMSTR->route_id == $allRoute->id){
                                        $scheduledTrip++;
                                    }
                                }
                                $totalScheduledTrip += $scheduledTrip;
            
                                $allTrips = TripDetail::where('route_id', $allRoute->id)
                                    ->whereBetween('start_trip', [$firstDate,$lastDate])
                                    ->get();
            
                                $tripMade = 0;
                                foreach ($allTrips as $allTrip){
                                    //Number of Trips Made
                                    $tripMade++;
            
                                    //Passengers Boarding Count
                                    $adult = $allTrip->total_adult;
                                    $totalAdult += $adult;
            
                                    $concession = $allTrip->total_concession;
                                    $totalConcession += $concession;
            
                                    $sumPassenger = $adult + $concession;
                                    $totalSumPassenger += $sumPassenger;
                                }
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
            }else{
                $selectedCompany = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $selectedCompany->company_name;

                if(!empty($validatedData['route_id'])) {
                    //ServiceGroup all route for specific company
                    if($validatedData['route_id']=='All'){
                        $routeByCompanies = Route::where('company_id', $this->selectedCompany)->orderBy('route_number')->get();
        
                        foreach($routeByCompanies as $routeByCompany){
                            foreach ($all_dates as $all_date) {
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date . '23:59:59');
        
                                //Number of Scheduled Trips
                                $scheduledTrip = 0;
                                $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate,$lastDate])->get();
                                foreach($schedules as $schedule){
                                    if($schedule->RouteScheduleMSTR->route_id == $routeByCompany->id){
                                        $scheduledTrip++;
                                    }
                                }
                                $totalScheduledTrip += $scheduledTrip;
        
                                $allTrips = TripDetail::where('route_id', $routeByCompany->id)
                                    ->whereBetween('start_trip', [$firstDate,$lastDate])
                                    ->get();
        
                                $tripMade = 0;
                                foreach ($allTrips as $allTrip){
                                    //Number of Trips Made
                                    $tripMade++;
        
                                    //Passengers Boarding Count
                                    $adult = $allTrip->total_adult;
                                    $totalAdult += $adult;
                                    
                                    $concession = $allTrip->total_concession;
                                    $totalConcession += $concession;
        
                                    $sumPassenger = $adult + $concession;
                                    $totalSumPassenger += $sumPassenger;
                                }
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
                    //ServiceGroup certain route for specific company
                    else{
                        $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
        
                        foreach ($all_dates as $all_date) {
                            $firstDate = new Carbon($all_date);
                            $lastDate = new Carbon($all_date . '23:59:59');
        
                            //Number of Scheduled Trips
                            $scheduledTrip = 0;
                            $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate,$lastDate])->get();
                            foreach($schedules as $schedule){
                                if($schedule->RouteScheduleMSTR->route_id == $selectedRoute->id){
                                    $scheduledTrip++;
                                }
                            }
                            $totalScheduledTrip += $scheduledTrip;
        
                            $allTrips = TripDetail::where('route_id', $selectedRoute->id)
                                ->whereBetween('start_trip', [$firstDate,$lastDate])
                                ->get();
        
                            $tripMade = 0;
                            foreach ($allTrips as $allTrip){
                                //Number of Trips Made
                                $tripMade++;
        
                                //Passengers Boarding Count
                                $adult = $allTrip->total_adult;
                                $totalAdult += $adult;
        
                                $concession = $allTrip->total_concession;
                                $totalConcession += $concession;
        
                                $sumPassenger = $adult + $concession;
                                $totalSumPassenger += $sumPassenger;
                            }
                            $totalTripMade += $tripMade;
                        }
        
                        $data['num_scheduled_trip'] = $totalScheduledTrip;
                        $data['num_trip_made'] = $totalTripMade;
                        $data['count_passenger_board'] = $totalSumPassenger;
                        $data['num_adult'] = $totalAdult;
                        $data['num_concession'] = $totalConcession;
        
                        $serviceGroup->add($data);
                    }
                }
            }
            return Excel::download(new SPADServiceGroup($networkArea,$serviceGroup, $validatedData['dateFrom'], $validatedData['dateTo']), 'Service_Group_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printRoute()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printRoute()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo'] . '23:59:59');

        $prevStart = Carbon::create($validatedData['dateFrom'])->startOfMonth()->subMonthsNoOverflow()->toDateString();
        $prevEnd = Carbon::create($validatedData['dateTo'])->subMonthsNoOverflow()->endOfMonth()->toDateString();

        $previousStartMonth = new Carbon($prevStart);
        $previousEndMonth = new Carbon($prevEnd . '23:59:59');

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();
            $startDate->addDay();
        }

        $routeSPAD = collect();
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
        if($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'ALL';
                if(!empty($validatedData['route_id'])) {
                    //Route all route all company
                    if($validatedData['route_id']=='All') {
                        $allRoutes = Route::orderBy('route_number')->get();
            
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
                                    $servicePlannedIn += $schedule->RouteScheduleMSTR->Route->inbound_distance;
                                    $servicePlannedOut += $schedule->RouteScheduleMSTR->Route->outbound_distance;
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
                            $routeNameIn = $allRoute->route_name;
            
                            if (count($allInboundTrips) > 0) {
                                foreach ($allInboundTrips as $allInboundTrip) {
                                    $tripMadeIn++;
                                    $kmServedIn += $allInboundTrip->Route->inbound_distance;
                                    $kmServedGPSIn += $allInboundTrip->Route->inbound_distance;
            
                                    $adult = $allInboundTrip->total_adult;
                                    $concession = $allInboundTrip->total_concession;
                                    $sum = $adult + $concession;
            
                                    $adultSales = $allInboundTrip->total_adult_amount;
                                    $concessionSales = $allInboundTrip->total_concession_amount;
                                    $sumSales = $adultSales + $concessionSales;
            
                                    //Check tickets if sumRidership==0 || sumSales==0 
                                    $checkTickets = TicketSalesTransaction::where('trip_number', $allInboundTrip->trip_number)->get();
                                    if($sum==0 || $sumSales==0){
                                        if(count($checkTickets)>0){
                                            $adult = 0;
                                            $concession = 0;
                                            $sum = 0;
                                            $sumSales = 0;
                                            foreach($checkTickets as $checkTicket){
                                                if($checkTicket->passenger_type==0){
                                                    $adult++;
                                                }
                                                elseif($checkTicket->passenger_type==1){
                                                    $concession++;
                                                }
                                                $sumSales += $checkTicket->actual_amount;
                                            }
                                            $sum = $adult + $concession;
                                        }
                                    }
            
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
                                if($prevRidershipIn==0){
                                    if($totalRidershipIn==0){
                                        $increaseRidershipFormatIn = 0;
                                    }else{
                                        $increaseRidershipFormatIn = 100;
                                    }
                                }else{
                                    if($totalRidershipIn==0){
                                        $increaseRidershipFormatIn = -100;
                                    }else{
                                        $increaseRidershipIn = (($totalRidershipIn - $prevRidershipIn) / $prevRidershipIn) * 100;
                                        $increaseRidershipFormatIn = number_format((float)$increaseRidershipIn, 2, '.', '');
                                    }
                                }
            
                                //Increment farebox inbound collection (%)
                                if($prevSalesIn==0){
                                    if($totalSalesIn==0){
                                        $increaseSalesFormatIn = 0;
                                    }else{
                                        $increaseSalesFormatIn = 100;
                                    }
                                }else{
                                    if($totalSalesIn==0){
                                        $increaseSalesFormatIn = -100;
                                    }else{
                                        $increaseSalesIn = (($totalSalesIn - $prevSalesIn) / $prevSalesIn) * 100;
                                        $increaseSalesFormatIn = number_format((float)$increaseSalesIn, 2, '.', '');
                                    }
                                }
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
                            $routeNameOut = implode(" - ", array_reverse(explode(" - ", $routeNameIn)));
            
                            if (count($allOutboundTrips) > 0) {
                                foreach ($allOutboundTrips as $allOutboundTrip) {
                                    $tripMadeOut++;
                                    $kmServedOut += $allOutboundTrip->Route->outbound_distance;
                                    $kmServedGPSOut += $allOutboundTrip->Route->outbound_distance;
            
                                    $adult = $allOutboundTrip->total_adult;
                                    $concession = $allOutboundTrip->total_concession;
                                    $sumRidership = $adult + $concession;
            
                                    $adultSales = $allOutboundTrip->total_adult_amount;
                                    $concessionSales = $allOutboundTrip->total_concession_amount;
                                    $sumSales = $adultSales + $concessionSales;
            
                                    //Check tickets if sumRidership==0 || sumSales==0 
                                    $checkTickets = TicketSalesTransaction::where('trip_number', $allOutboundTrip->trip_number)->get();
                                    if($sumRidership==0 || $sumSales==0){
                                        if(count($checkTickets)>0){
                                            $adult = 0;
                                            $concession = 0;
                                            $sumRidership = 0;
                                            $sumSales = 0;
                                            foreach($checkTickets as $checkTicket){
                                                if($checkTicket->passenger_type==0){
                                                    $adult++;
                                                }
                                                elseif($checkTicket->passenger_type==1){
                                                    $concession++;
                                                }
                                                $sumSales += $checkTicket->actual_amount;
                                            }
                                            $sumRidership = $adult + $concession;
                                        }
                                    }
            
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
                                if($prevRidershipOut==0){
                                    if($totalRidershipOut==0){
                                        $increaseRidershipFormatOut = 0;
                                    }else{
                                        $increaseRidershipFormatOut  = 100;
                                    }
                                }else{
                                    if($totalRidershipOut==0){
                                        $increaseRidershipFormatOut = -100;
                                    }else{
                                        $increaseRidershipOut = (($totalRidershipOut - $prevRidershipOut) / $prevRidershipOut) * 100;
                                        $increaseRidershipFormatOut = number_format((float)$increaseRidershipOut, 2, '.', '');
                                    }
                                }
            
                                //Increment farebox outbound collection (%)
                                if($prevSalesOut==0){
                                    if($totalSalesOut==0){
                                        $increaseSalesFormatOut = 0;
                                    }else{
                                        $increaseSalesFormatOut = 100;
                                    }
                                }else{
                                    if($totalSalesOut==0){
                                        $increaseSalesFormatOut = -100;
                                    }else{
                                        $increaseSalesOut = (($totalSalesOut - $prevSalesOut) / $prevSalesOut) * 100;
                                        $increaseSalesFormatOut = number_format((float)$increaseSalesOut, 2, '.', '');
                                    }
                                }
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
                            $total['tot_num_km_served'] = $inbound['num_km_served'] + $outbound['num_km_served'];
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
                            $calcPaxIncreaseFormat = round($calcPaxIncrease, 2);
                            $total['tot_total_pax_increase'] = $calcPaxIncreaseFormat;
            
                            $total['tot_total_sales'] = $inbound['total_sales'] + $outbound['total_sales'];
            
                            //total_sales_increase
                            $sumtotalSalesIncrease =  $inbound['total_sales_increase'] + $outbound['total_sales_increase'];
                            $calcSalesIncrease = ($sumtotalSalesIncrease/200)*100;
                            $calcSalesIncreaseFormat = round($calcSalesIncrease, 2);
                            $total['tot_total_sales_increase'] = $calcSalesIncreaseFormat;
            
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
                }
            }else{
                $selectedCompany = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $selectedCompany->company_name;

                if(!empty($validatedData['route_id'])) {
                    //Route all route specific company
                    if($validatedData['route_id']=='All'){
                        $routeByCompanies = Route::where('company_id', $this->selectedCompany)->orderBy('route_number')->get();
        
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
                                    $servicePlannedIn += $schedule->RouteScheduleMSTR->Route->inbound_distance;
                                    $servicePlannedOut += $schedule->RouteScheduleMSTR->Route->outbound_distance;
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
                            $routeNameIn = $routeByCompany->route_name;
                            if (count($allInboundTrips) > 0) {
                                foreach ($allInboundTrips as $allInboundTrip) {
                                    $tripMadeIn++;
                                    $kmServedIn += $allInboundTrip->Route->inbound_distance;
                                    $kmServedGPSIn += $allInboundTrip->Route->inbound_distance;
        
                                    $adult = $allInboundTrip->total_adult;
                                    $concession = $allInboundTrip->total_concession;
                                    $sum = $adult + $concession;
        
                                    $adultSales = $allInboundTrip->total_adult_amount;
                                    $concessionSales = $allInboundTrip->total_concession_amount;
                                    $sumSales = $adultSales + $concessionSales;
        
                                    //Check tickets if sumRidership==0 || sumSales==0 
                                    $checkTickets = TicketSalesTransaction::where('trip_number', $allInboundTrip->trip_number)->get();
                                    if($sum==0 || $sumSales==0){
                                        if(count($checkTickets)>0){
                                            $adult = 0;
                                            $concession = 0;
                                            $sum = 0;
                                            $sumSales = 0;
                                            foreach($checkTickets as $checkTicket){
                                                if($checkTicket->passenger_type==0){
                                                    $adult++;
                                                }
                                                elseif($checkTicket->passenger_type==1){
                                                    $concession++;
                                                }
                                                $sumSales += $checkTicket->actual_amount;
                                            }
                                            $sum = $adult + $concession;
                                        }
                                    }
        
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
                                if($prevRidershipIn==0){
                                    if($totalRidershipIn==0){
                                        $increaseRidershipFormatIn = 0;
                                    }else{
                                        $increaseRidershipFormatIn = 100;
                                    }
                                }else{
                                    if($totalRidershipIn==0){
                                        $increaseRidershipFormatIn = -100;
                                    }else{
                                        $increaseRidershipIn = (($totalRidershipIn - $prevRidershipIn) / $prevRidershipIn) * 100;
                                        $increaseRidershipFormatIn = number_format((float)$increaseRidershipIn, 2, '.', '');
                                    }
                                }
        
                                //Increment farebox inbound collection (%)
                                if($prevSalesIn==0){
                                    if($totalSalesIn==0){
                                        $increaseSalesFormatIn = 0;
                                    }else{
                                        $increaseSalesFormatIn = 100;
                                    }
                                }else{
                                    if($totalSalesIn==0){
                                        $increaseSalesFormatIn = -100;
                                    }else{
                                        $increaseSalesIn = (($totalSalesIn - $prevSalesIn) / $prevSalesIn) * 100;
                                        $increaseSalesFormatIn = number_format((float)$increaseSalesIn, 2, '.', '');
                                    }
                                }
                            }else{
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
                            $routeNameOut = implode(" - ", array_reverse(explode(" - ", $routeNameIn)));
                            if (count($allOutboundTrips) > 0) {
                                foreach ($allOutboundTrips as $allOutboundTrip) {
                                    $tripMadeOut++;
                                    $kmServedOut += $allOutboundTrip->Route->outbound_distance;
                                    $kmServedGPSOut += $allOutboundTrip->Route->outbound_distance;
        
                                    $adult = $allOutboundTrip->total_adult;
                                    $concession = $allOutboundTrip->total_concession;
                                    $sumRidership = $adult + $concession;
        
                                    $adultSales = $allOutboundTrip->total_adult_amount;
                                    $concessionSales = $allOutboundTrip->total_concession_amount;
                                    $sumSales = $adultSales + $concessionSales;
        
                                    //Check tickets if sumRidership==0 || sumSales==0 
                                    $checkTickets = TicketSalesTransaction::where('trip_number', $allOutboundTrip->trip_number)->get();
                                    if($sumRidership==0 || $sumSales==0){
                                        if(count($checkTickets)>0){
                                            $adult = 0;
                                            $concession = 0;
                                            $sumRidership = 0;
                                            $sumSales = 0;
                                            foreach($checkTickets as $checkTicket){
                                                if($checkTicket->passenger_type==0){
                                                    $adult++;
                                                }
                                                elseif($checkTicket->passenger_type==1){
                                                    $concession++;
                                                }
                                                $sumSales += $checkTicket->actual_amount;
                                            }
                                            $sumRidership = $adult + $concession;
                                        }
                                    }
        
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
                                if($prevRidershipOut==0){
                                    if($totalRidershipOut==0){
                                        $increaseRidershipFormatOut = 0;
                                    }else{
                                        $increaseRidershipFormatOut = 100;
                                    }
                                }else{
                                    if($totalRidershipOut==0){
                                        $increaseRidershipFormatOut = -100;
                                    }else{
                                        $increaseRidershipOut = (($totalRidershipOut - $prevRidershipOut) / $prevRidershipOut) * 100;
                                        $increaseRidershipFormatOut = number_format((float)$increaseRidershipOut, 2, '.', '');
                                    }
                                }
        
                                //Increment farebox outbound collection (%)
                                if($prevSalesOut==0){
                                    if($totalSalesOut==0){
                                        $increaseSalesFormatOut = 0;
                                    }else{
                                        $increaseSalesFormatOut = 100;
                                    }
                                }else{
                                    if($totalSalesOut==0){
                                        $increaseSalesFormatOut = -100;
                                    }else{
                                        $increaseSalesOut = (($totalSalesOut - $prevSalesOut) / $prevSalesOut) * 100;
                                        $increaseSalesFormatOut = number_format((float)$increaseSalesOut, 2, '.', '');
                                    }
                                }
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
                            $total['tot_num_km_served'] = $inbound['num_km_served'] + $outbound['num_km_served'];
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
                            $calcPaxIncreaseFormat = round($calcPaxIncrease, 2);
                            $total['tot_total_pax_increase'] = $calcPaxIncreaseFormat;
        
                            $total['tot_total_sales'] = $inbound['total_sales'] + $outbound['total_sales'];
        
                            //total_sales_increase
                            $sumtotalSalesIncrease =  $inbound['total_sales_increase'] + $outbound['total_sales_increase'];
                            $calcSalesIncrease = ($sumtotalSalesIncrease/200)*100;
                            $calcSalesIncreaseFormat = round($calcSalesIncrease, 2);
                            $total['tot_total_sales_increase'] = $calcSalesIncreaseFormat;
        
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
                    //Route specific route specific company
                    else{
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
                                $servicePlannedIn += $schedule->RouteScheduleMSTR->Route->inbound_distance;
                                $servicePlannedOut += $schedule->RouteScheduleMSTR->Route->outbound_distance;
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
                        $routeNameIn = $selectedRoute->route_name;
                        if(count($allInboundTrips)>0) {
                            foreach ($allInboundTrips as $allInboundTrip) {
                                $tripMadeIn++;
                                $kmServedIn += $allInboundTrip->Route->inbound_distance;
                                $kmServedGPSIn += $allInboundTrip->Route->inbound_distance;
        
                                $adult = $allInboundTrip->total_adult;
                                $concession = $allInboundTrip->total_concession;
                                $sum = $adult + $concession;
        
                                $adultSales = $allInboundTrip->total_adult_amount;
                                $concessionSales = $allInboundTrip->total_concession_amount;
                                $sumSales = $adultSales + $concessionSales;
        
                                //Check tickets if sumRidership==0 || sumSales==0 
                                $checkTickets = TicketSalesTransaction::where('trip_number', $allInboundTrip->trip_number)->get();
                                if($sum==0 || $sumSales==0){
                                    if(count($checkTickets)>0){
                                        $adult = 0;
                                        $concession = 0;
                                        $sum = 0;
                                        $sumSales = 0;
                                        foreach($checkTickets as $checkTicket){
                                            if($checkTicket->passenger_type==0){
                                                $adult++;
                                            }
                                            elseif($checkTicket->passenger_type==1){
                                                $concession++;
                                            }
                                            $sumSales += $checkTicket->actual_amount;
                                        }
                                        $sum = $adult + $concession;
                                    }
                                }
        
                                $totalAdultIn += $adult;
                                $totalConcessionIn += $concession;
                                $totalRidershipIn += $sum;
                                $totalSalesIn += $sumSales;
                            }
                        }
        
                        //Previous Month Ridership Farebox Inbound collection
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
                            //Increment ridership outbound collection (%)
                            if($prevRidershipIn==0){
                                if($totalRidershipIn==0){
                                    $increaseRidershipFormatIn = 0;
                                }else{
                                    $increaseRidershipFormatIn = 100;
                                }
                            }else{
                                if($totalRidershipIn==0){
                                    $increaseRidershipFormatIn = -100;
                                }else{
                                    $increaseRidershipIn = (($totalRidershipIn - $prevRidershipIn) / $prevRidershipIn) * 100;
                                    $increaseRidershipFormatIn = number_format((float)$increaseRidershipIn, 2, '.', '');
                                }
                            }
        
                            //Increment farebox inbound collection (%)
                            if($prevSalesIn==0){
                                if($totalSalesIn==0){
                                    $increaseSalesFormatIn = 0;
                                }else{
                                    $increaseSalesFormatIn = 100;
                                }
                            }else{
                                if($totalSalesIn==0){
                                    $increaseSalesFormatIn = -100;
                                }else{
                                    $increaseSalesIn = (($totalSalesIn - $prevSalesIn) / $prevSalesIn) * 100;
                                    $increaseSalesFormatIn = number_format((float)$increaseSalesIn, 2, '.', '');
                                }
                            }
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
                        $routeNameOut = implode(" - ", array_reverse(explode(" - ", $routeNameIn)));
                        if(count($allOutboundTrips)>0) {
                            foreach ($allOutboundTrips as $allOutboundTrip) {
                                $tripMadeOut++;
                                $kmServedOut += $allOutboundTrip->Route->outbound_distance;
                                $kmServedGPSOut += $allOutboundTrip->Route->outbound_distance;
        
                                $adult = $allOutboundTrip->total_adult;
                                $concession = $allOutboundTrip->total_concession;
                                $sumRidership = $adult + $concession;
        
                                $adultSales = $allOutboundTrip->total_adult_amount;
                                $concessionSales = $allOutboundTrip->total_concession_amount;
                                $sumSales = $adultSales + $concessionSales;
        
                                //Check tickets if sumRidership==0 || sumSales==0 
                                $checkTickets = TicketSalesTransaction::where('trip_number', $allOutboundTrip->trip_number)->get();
                                if($sumRidership==0 || $sumSales==0){
                                    if(count($checkTickets)>0){
                                        $adult = 0;
                                        $concession = 0;
                                        $sumRidership = 0;
                                        $sumSales = 0;
                                        foreach($checkTickets as $checkTicket){
                                            if($checkTicket->passenger_type==0){
                                                $adult++;
                                            }
                                            elseif($checkTicket->passenger_type==1){
                                                $concession++;
                                            }
                                            $sumSales += $checkTicket->actual_amount;
                                        }
                                        $sumRidership = $adult + $concession;
                                    }
                                }
        
                                $totalAdultOut += $adult;
                                $totalConcessionOut += $concession;
                                $totalRidershipOut += $sumRidership;
                                $totalSalesOut += $sumSales;
                            }
                        }
        
                        //Previous Month Ridership Farebox Outbound collection
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
                            if($prevRidershipOut==0){
                                if($totalRidershipOut==0){
                                    $increaseRidershipFormatOut = 0;
                                }else{
                                    $increaseRidershipFormatOut = 100;
                                }
                            }else{
                                if($totalRidershipOut==0){
                                    $increaseRidershipFormatOut = -100;
                                }else{
                                    $increaseRidershipOut = (($totalRidershipOut - $prevRidershipOut) / $prevRidershipOut) * 100;
                                    $increaseRidershipFormatOut = number_format((float)$increaseRidershipOut, 2, '.', '');
                                }
                            }
        
                            //Increment farebox outbound collection (%)
                            if($prevSalesOut==0){
                                if($totalSalesOut==0){
                                    $increaseSalesFormatOut = 0;
                                }else{
                                    $increaseSalesFormatOut = 100;
                                }
                            }else{
                                if($totalSalesOut==0){
                                    $increaseSalesFormatOut = -100;
                                }else{
                                    $increaseSalesOut = (($totalSalesOut - $prevSalesOut) / $prevSalesOut) * 100;
                                    $increaseSalesFormatOut = number_format((float)$increaseSalesOut, 2, '.', '');
                                }
                            }
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
                        $total['tot_num_km_served'] = $inbound['num_km_served'] + $outbound['num_km_served'];
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
                        $calcPaxIncreaseFormat = round($calcPaxIncrease, 2);
                        $total['tot_total_pax_increase'] = $calcPaxIncreaseFormat;
        
                        $total['tot_total_sales'] = $inbound['total_sales'] + $outbound['total_sales'];
        
                        //total_sales_increase
                        $sumtotalSalesIncrease =  $inbound['total_sales_increase'] + $outbound['total_sales_increase'];
                        $calcSalesIncrease = ($sumtotalSalesIncrease/200)*100;
                        $calcSalesIncreaseFormat = round($calcSalesIncrease, 2);
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
                }
            }
            return Excel::download(new SPADRoute($networkArea, $routeSPAD, $validatedData['dateFrom'], $validatedData['dateTo']), 'Route_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printTrip()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printTrip()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();
        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();
            $startDate->addDay();
        }

        $tripSPAD = collect();
        $grandPassengerCount = 0;
        $grandSalesAmount  = 0;
        $grandAdult  = 0;
        $grandConcession  = 0;
        $perRoute = [];
        if($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                if(!empty($validatedData['route_id'])) {
                    //Trip all route all company
                    if($validatedData['route_id']=='All'){
                        $allRoutes = Route::orderBy('route_number')->get();

                        foreach ($allRoutes as $allRoute){
                            $perRoutePassengerCount = 0;
                            $perRouteSalesAmount = 0;
                            $perRouteAdult = 0;
                            $perRouteConcession = 0;
                            $perDate = [];
            
                            foreach ($all_dates as $all_date) {
                                $existOutTrip = false;
                                $existInTrip = false;
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date . '23:59:59');
            
                                //Inbound Trip
                                $allInboundTrips = TripDetail::where('route_id', $allRoute->id)
                                    ->whereBetween('start_trip', [$firstDate, $lastDate])
                                    ->where('trip_code', 1)
                                    ->get();
            
                                $routeNameIn = $allRoute->route_name;
            
                                $inbound = [];
                                $inboundPassenger = 0;
                                $inboundSales = 0;
                                $inboundAdult = 0;
                                $inboundConcession = 0;
                                if (count($allInboundTrips) > 0) {
                                    $existInTrip = true;
                                    foreach ($allInboundTrips as $allInboundTrip) {
                                        $tripIn['trip_no'] = 'T' . $allInboundTrip->id;
                                        $tripIn['bus_no'] = $allInboundTrip->Bus->bus_registration_number;
            
                                        //Check driver_id
                                        if($allInboundTrip->driver_id != NULL) {
                                            $tripIn['driver_id'] = $allInboundTrip->BusDriver->id_number;
                                        }else{
                                            $tripIn['driver_id'] = "No Data";
                                        }
            
                                        $tripIn['service_date'] = $all_date;
            
                                        $firstStageIn = Stage::where('route_id', $allRoute->id)->first();
                                        $tripIn['start_point'] = $firstStageIn->stage_name;
            
                                        //Check route_schedule_mstr_id
                                        if($allInboundTrip->route_schedule_mstr_id != NULL) {
                                            $tripIn['service_start'] = Carbon::create($allInboundTrip->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                        }else{
                                            $tripIn['service_start'] = "No Data";
                                        }
            
                                        $tripIn['actual_start'] = date("H:i", strtotime($allInboundTrip->start_trip));
            
                                        //Check sales
                                        $firstTicket = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                            ->orderby('sales_date')
                                            ->first();
                                        if($firstTicket){
                                            $tripIn['sales_start'] = date("H:i", strtotime($firstTicket->sales_date));
                                        }else{
                                            $tripIn['sales_start'] = "No Sales";
                                        }
            
                                        //Check route_schedule_mstr_id
                                        if($allInboundTrip->route_schedule_mstr_id != NULL) {
                                            $tripIn['service_end'] = Carbon::create($allInboundTrip->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                        }else{
                                            $tripIn['service_end'] = "No Data";
                                        }
            
                                        $tripIn['actual_end'] = date("H:i", strtotime($allInboundTrip->end_trip));
            
                                        //Check sales
                                        $lastTicket = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                            ->orderby('sales_date', 'DESC')
                                            ->first();
                                        if($lastTicket){
                                            $tripIn['sales_end'] = date("H:i", strtotime($lastTicket->sales_date));
                                        }else{
                                            $tripIn['sales_end'] = "No Sales";
                                        }
            
                                        $adult = $allInboundTrip->total_adult;
                                        $concession = $allInboundTrip->total_concession;
                                        $passengerIn = $adult + $concession;
            
                                        $adultFarebox = $allInboundTrip->total_adult_amount;
                                        $concessionFarebox = $allInboundTrip->total_concession_amount;
                                        $fareboxIn = $adultFarebox + $concessionFarebox;
            
                                        //Check tickets
                                        if($fareboxIn==0 || $passengerIn==0){
                                            $allTicketPerTrips = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)->get();
                                            if(count($allTicketPerTrips)>0){
                                                $adult = 0;
                                                $concession = 0;
                                                $passengerIn = 0;
                                                $fareboxIn = 0;
                                                foreach($allTicketPerTrips as $allTicketPerTrip){
                                                    $fareboxIn += $allTicketPerTrip->actual_amount;
                                                    if($allTicketPerTrip->passenger_type==0){
                                                        $adult++;
                                                    }else{
                                                        $concession++;
                                                    }
                                                }
                                                $passengerIn = $adult + $concession;
                                            }
                                        }
                                        $tripIn['passenger_count'] = $passengerIn;
                                        $tripIn['sales_amount'] = $fareboxIn;
                                        $tripIn['total_on'] = $passengerIn;
                                        $tripIn['adult'] = $adult;
                                        $tripIn['concession'] = $concession;
            
                                        $inbound[$allInboundTrip->id] = $tripIn;
            
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
            
                                $routeNameOut = implode(" - ", array_reverse(explode(" - ", $routeNameIn)));
            
                                $outbound = [];
                                $outboundPassenger = 0;
                                $outboundSales  = 0;
                                $outboundAdult  = 0;
                                $outboundConcession  = 0;
                                if (count($allOutboundTrips) > 0) {
                                    $existOutTrip = true;
                                    foreach ($allOutboundTrips as $allOutboundTrip) {
                                        $tripOut['trip_no'] = 'T' . $allOutboundTrip->id;
                                        $tripOut['bus_no'] = $allOutboundTrip->Bus->bus_registration_number;
            
                                        //Check driver_id
                                        if($allOutboundTrip->driver_id!=NULL) {
                                            $tripOut['driver_id'] = $allOutboundTrip->BusDriver->driver_number;
                                        }else{
                                            $tripOut['driver_id'] = "No Data";
                                        }
            
                                        $tripOut['service_date'] = $all_date;
                                        $firstStageOut = Stage::where('route_id',$allRoute->id)->orderBy('stage_order', 'DESC')->first();
                                        $tripOut['start_point'] = $firstStageOut->stage_name;
            
                                        //Check route_schedule_mstr_id
                                        if($allOutboundTrip->route_schedule_mstr_id!=NULL){
                                            $tripOut['service_start'] = Carbon::create($allOutboundTrip->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                        }else{
                                            $tripOut['service_start'] = "No Data";
                                        }
            
                                        $tripOut['actual_start'] = date("H:i", strtotime($allOutboundTrip->start_trip));
            
                                        //Check sales
                                        $firstTicket = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                            ->orderby('sales_date')
                                            ->first();
                                        if($firstTicket){
                                            $tripOut['sales_start'] = date("H:i", strtotime($firstTicket->sales_date));
                                        }else{
                                            $tripOut['sales_start'] = "No Sales";
                                        }
            
                                        //Check route_schedule_mstr_id
                                        if($allOutboundTrip->route_schedule_mstr_id!=NULL){
                                            $tripOut['service_end'] = Carbon::create($allOutboundTrip->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                        }else{
                                            $tripOut['service_end'] = "No Data";
                                        }
            
                                        $tripOut['actual_end'] = date("H:i", strtotime($allOutboundTrip->end_trip));
            
                                        //Check sales
                                        $lastTicket = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                            ->orderby('sales_date', 'DESC')
                                            ->first();
                                        if($lastTicket){
                                            $tripOut['sales_end'] = date("H:i", strtotime($lastTicket->sales_date));
                                        }else{
                                            $tripOut['sales_end'] = "No Sales";
                                        }
            
                                        $adult = $allOutboundTrip->total_adult;
                                        $concession = $allOutboundTrip->total_concession;
                                        $passengerOut = $adult + $concession;
            
                                        $adultFarebox = $allOutboundTrip->total_adult_amount;
                                        $concessionFarebox = $allOutboundTrip->total_concession_amount;
                                        $fareboxOut = $adultFarebox + $concessionFarebox;
            
                                        //Check tickets
                                        if($fareboxOut==0 || $passengerOut==0){
                                            $allTicketPerTrips = TicketSalesTransaction::where('trip_id',  $allOutboundTrip->id)->get();
                                            if(count($allTicketPerTrips)>0){
                                                foreach($allTicketPerTrips as $allTicketPerTrip){
                                                    $fareboxOut += $allTicketPerTrip->actual_amount;
                                                    if($allTicketPerTrip->passenger_type==0){
                                                        $adult++;
                                                    }else{
                                                        $concession++;
                                                    }
                                                }
                                                $passengerOut = count($allTicketPerTrips);
                                            }
                                        }
                                        $tripOut['passenger_count'] = $passengerOut;
                                        $tripOut['sales_amount'] = $fareboxOut;
                                        $tripOut['total_on'] = $passengerOut;
                                        $tripOut['adult'] = $adult;
                                        $tripOut['concession'] = $concession;
                                        $outbound[$allOutboundTrip->id] = $tripOut;
            
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
            
                                //$out->writeln("existInTrip: " . $existInTrip . " existOutTrip: " . $existOutTrip);
                                $route_data = [];
                                $sumPassengerCount = 0;
                                $sumSalesAmount = 0;
                                $sumAdult = 0;
                                $sumConcession = 0;
                                if ($existInTrip == true && $existOutTrip == true) {
                                    //$out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == true");
                                    $sumPassengerCount = $outboundPassenger + $inboundPassenger;
                                    $sumSalesAmount = $outboundSales + $inboundSales;
                                    $sumAdult = $outboundAdult + $inboundAdult;
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
                                    //$out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == true");
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
                                    //$out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == false");
                                    $sumPassengerCount =  $inboundPassenger;
                                    $sumSalesAmount = $inboundSales;
                                    $sumAdult = $inboundAdult;
                                    $sumConcession = $inboundConcession;
            
                                    $totalPerDate['passenger_count'] = $sumPassengerCount;
                                    $totalPerDate['sales_amount'] = $sumSalesAmount;
                                    $totalPerDate['total_on'] = $sumPassengerCount;
                                    $totalPerDate['adult'] = $sumAdult;
                                    $totalPerDate['concession'] = $sumConcession;
            
                                    $route_data[$routeNameIn] = $inbound;
                                    $route_data[$routeNameOut] = [];
                                    $route_data['total_per_date'] = $totalPerDate;
                                    $perDate[$all_date] = $route_data;
                                }else{
                                    //$out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == false");
                                    $perDate[$all_date] = [];
                                }
                                $perRoutePassengerCount += $sumPassengerCount;
                                $perRouteSalesAmount += $sumSalesAmount;
                                $perRouteAdult += $sumAdult;
                                $perRouteConcession += $sumConcession;
                            }
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
                }
            }
            else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $companyDetails->company_name;

                if(!empty($validatedData['route_id'])) {
                    //Trip all route specific company
                    if($validatedData['route_id']=='All'){
                        $routeByCompanies = Route::where('company_id', $this->selectedCompany)->orderBy('route_number')->get();
        
                        foreach ($routeByCompanies as $routeByCompany){
                            $perRoutePassengerCount = 0;
                            $perRouteSalesAmount = 0;
                            $perRouteAdult = 0;
                            $perRouteConcession = 0;
                            $perDate = [];
        
                            foreach ($all_dates as $all_date) {
                                $existOutTrip = false;
                                $existInTrip = false;
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date . '23:59:59');
        
                                //Inbound Trip
                                $allInboundTrips = TripDetail::where('route_id', $routeByCompany->id)
                                    ->whereBetween('start_trip', [$firstDate, $lastDate])
                                    ->where('trip_code', 1)
                                    ->get();
        
                                $routeNameIn = $routeByCompany->route_name;
        
                                $inbound = [];
                                $inboundPassenger = 0;
                                $inboundSales = 0;
                                $inboundAdult = 0;
                                $inboundConcession = 0;
                                if (count($allInboundTrips) > 0) {
                                    $existInTrip = true;
                                    foreach ($allInboundTrips as $allInboundTrip) {
        
                                        $tripIn['trip_no'] = 'T' . $allInboundTrip->id;
                                        $tripIn['bus_no'] = $allInboundTrip->Bus->bus_registration_number;
        
                                        //Check driver_id
                                        if($allInboundTrip->driver_id != NULL) {
                                            $tripIn['driver_id'] = $allInboundTrip->BusDriver->driver_number;
                                        }else{
                                            $tripIn['driver_id'] = "No Data";
                                        }
        
                                        $tripIn['service_date'] = $all_date;
        
                                        $firstStageIn = Stage::where('route_id', $routeByCompany->id)->first();
                                        $tripIn['start_point'] = $firstStageIn->stage_name;
        
                                        //Check route_schedule_mstr_id
                                        if($allInboundTrip->route_schedule_mstr_id != NULL) {
                                            $tripIn['service_start'] = Carbon::create($allInboundTrip->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                        }else{
                                            $tripIn['service_start'] = "No Data";
                                        }
        
                                        $tripIn['actual_start'] = date("H:i", strtotime($allInboundTrip->start_trip));
        
                                        //Check sales
                                        $firstTicket = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                            ->orderby('sales_date')
                                            ->first();
                                        if($firstTicket){
                                            $tripIn['sales_start'] = date("H:i", strtotime($firstTicket->sales_date));
                                        }else{
                                            $tripIn['sales_start'] = "No Sales";
                                        }
        
                                        //Check route_schedule_mstr_id
                                        if($allInboundTrip->route_schedule_mstr_id != NULL) {
                                            $tripIn['service_end'] = Carbon::create($allInboundTrip->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                        }else{
                                            $tripIn['service_end'] = "No Data";
                                        }
        
                                        $tripIn['actual_end'] = date("H:i", strtotime($allInboundTrip->end_trip));
        
                                        //Check sales
                                        $lastTicket = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                            ->orderby('sales_date', 'DESC')
                                            ->first();
                                        if($lastTicket){
                                            $tripIn['sales_end'] = date("H:i", strtotime($lastTicket->sales_date));
                                        }else{
                                            $tripIn['sales_end'] = "No Sales";
                                        }
        
                                        $adult = $allInboundTrip->total_adult;
                                        $concession = $allInboundTrip->total_concession;
                                        $passengerIn = $adult + $concession;
        
                                        $adultFarebox = $allInboundTrip->total_adult_amount;
                                        $concessionFarebox = $allInboundTrip->total_concession_amount;
                                        $fareboxIn = $adultFarebox + $concessionFarebox;
        
                                        $out->writeln("fareboxIn before: " . $fareboxIn);
                                        //Check tickets
                                        if($fareboxIn==0 || $passengerIn==0){
                                            $allTicketPerTrips = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)->get();
                                            if(count($allTicketPerTrips)>0){
                                                $adult = 0;
                                                $concession = 0;
                                                $passengerIn = 0;
                                                $fareboxIn = 0;
                                                foreach($allTicketPerTrips as $allTicketPerTrip){
                                                    $fareboxIn += $allTicketPerTrip->actual_amount;
                                                    if($allTicketPerTrip->passenger_type==0){
                                                        $adult++;
                                                    }else{
                                                        $concession++;
                                                    }
                                                }
                                                $passengerIn = $adult + $concession;
                                            }
                                        }
                                        $tripIn['passenger_count'] = $passengerIn;
                                        $tripIn['sales_amount'] = $fareboxIn;
                                        $tripIn['total_on'] = $passengerIn;
                                        $tripIn['adult'] = $adult;
                                        $tripIn['concession'] = $concession;
        
                                        $inbound[$allInboundTrip->id] = $tripIn;
        
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
        
                                $routeNameOut = implode(" - ", array_reverse(explode(" - ", $routeNameIn)));
        
                                $outbound = [];
                                $outboundPassenger = 0;
                                $outboundSales  = 0;
                                $outboundAdult  = 0;
                                $outboundConcession  = 0;
                                if (count($allOutboundTrips) > 0) {
                                    $existOutTrip = true;
                                    foreach ($allOutboundTrips as $allOutboundTrip) {
                                        $tripOut['trip_no'] = 'T' .  $allOutboundTrip->id;
                                        $tripOut['bus_no'] = $allOutboundTrip->Bus->bus_registration_number;
        
                                        //Check driver_id
                                        if($allOutboundTrip->driver_id!=NULL) {
                                            $tripOut['driver_id'] = $allOutboundTrip->BusDriver->driver_number;
                                        }else{
                                            $tripOut['driver_id'] = "No Data";
                                        }
        
                                        $tripOut['service_date'] = $all_date;
                                        $firstStageOut = Stage::where('route_id',$routeByCompany->id)->orderBy('stage_order', 'DESC')->first();
                                        $tripOut['start_point'] = $firstStageOut->stage_name;
        
                                        //Check route_schedule_mstr_id
                                        if($allOutboundTrip->route_schedule_mstr_id!=NULL){
                                            $tripOut['service_start'] = Carbon::create($allOutboundTrip->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                        }else{
                                            $tripOut['service_start'] = "No Data";
                                        }
        
                                        $tripOut['actual_start'] = date("H:i", strtotime($allOutboundTrip->start_trip));
        
                                        //Check sales
                                        $firstTicket = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                            ->orderby('sales_date')
                                            ->first();
                                        if($firstTicket){
                                            $tripOut['sales_start'] = date("H:i", strtotime($firstTicket->sales_date));
                                        }else{
                                            $tripOut['sales_start'] = "No Sales";
                                        }
        
                                        //Check route_schedule_mstr_id
                                        if($allOutboundTrip->route_schedule_mstr_id!=NULL){
                                            $tripOut['service_end'] = Carbon::create($allOutboundTrip->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                        }else{
                                            $tripOut['service_end'] = "No Data";
                                        }
        
                                        $tripOut['actual_end'] = date("H:i", strtotime($allOutboundTrip->end_trip));
        
                                        //Check sales
                                        $lastTicket = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                            ->orderby('sales_date', 'DESC')
                                            ->first();
                                        if($lastTicket){
                                            $tripOut['sales_end'] = date("H:i", strtotime($lastTicket->sales_date));
                                        }else{
                                            $tripOut['sales_end'] = "No Sales";
                                        }
        
                                        $adult = $allOutboundTrip->total_adult;
                                        $concession = $allOutboundTrip->total_concession;
                                        $passengerOut = $adult + $concession;
        
                                        $adultFarebox = $allOutboundTrip->total_adult_amount;
                                        $concessionFarebox = $allOutboundTrip->total_concession_amount;
                                        $fareboxOut = $adultFarebox + $concessionFarebox;
        
                                        //Check tickets
                                        if($fareboxOut==0 || $passengerOut==0){
                                            $allTicketPerTrips = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)->get();
                                            if(count($allTicketPerTrips)>0){
                                                $adult = 0;
                                                $concession = 0;
                                                $passengerOut = 0;
                                                $fareboxOut = 0;
                                                foreach($allTicketPerTrips as $allTicketPerTrip){
                                                    $fareboxOut += $allTicketPerTrip->actual_amount;
                                                    if($allTicketPerTrip->passenger_type==0){
                                                        $adult++;
                                                    }else{
                                                        $concession++;
                                                    }
                                                }
                                                $passengerOut = $adult + $concession;
                                            }
                                        }
        
                                        $tripOut['passenger_count'] = $passengerOut;
                                        $tripOut['sales_amount'] = $fareboxOut;
                                        $tripOut['total_on'] = $passengerOut;
                                        $tripOut['adult'] = $adult;
                                        $tripOut['concession'] = $concession;
                                        $outbound[$allOutboundTrip->id] = $tripOut;
        
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
        
                                $route_data = [];
                                $sumPassengerCount = 0;
                                $sumSalesAmount = 0;
                                $sumAdult = 0;
                                $sumConcession = 0;
                                if ($existInTrip == true && $existOutTrip == true) {
                                    //$out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == true");
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
                                   // $out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == true");
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
                                    //$out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == false");
                                    $sumPassengerCount =  $inboundPassenger;
                                    $sumSalesAmount = $inboundSales;
                                    $sumAdult = $inboundAdult;
                                    $sumConcession = $inboundConcession;
        
                                    $totalPerDate['passenger_count'] = $sumPassengerCount;
                                    $totalPerDate['sales_amount'] = $sumSalesAmount;
                                    $totalPerDate['total_on'] = $sumPassengerCount;
                                    $totalPerDate['adult'] = $sumAdult;
                                    $totalPerDate['concession'] = $sumConcession;
        
                                    $route_data[$routeNameIn] = $inbound;
                                    $route_data[$routeNameOut] = [];
                                    $route_data['total_per_date'] = $totalPerDate;
                                    $perDate[$all_date] = $route_data;
                                }else{
                                    //$out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == false");
                                    $perDate[$all_date] = [];
                                }
                                $perRoutePassengerCount += $sumPassengerCount;
                                $perRouteSalesAmount += $sumSalesAmount;
                                $perRouteAdult += $sumAdult;
                                $perRouteConcession += $sumConcession;
                            }
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
                    //Trip specific route specific company
                    else{
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
                            $lastDate = new Carbon($all_date . '23:59:59');

                            //Inbound Trip
                            $allInboundTrips = TripDetail::where('route_id', $selectedRoute->id)
                                ->whereBetween('start_trip', [$firstDate, $lastDate])
                                ->where('trip_code', 1)
                                ->get();

                            $routeNameIn = $selectedRoute->route_name;

                            $inbound = [];
                            $inboundPassenger = 0;
                            $inboundSales = 0;
                            $inboundAdult = 0;
                            $inboundConcession = 0;
                            if (count($allInboundTrips) > 0) {
                                $existInTrip = true;
                                foreach ($allInboundTrips as $allInboundTrip) {

                                    $tripIn['trip_no'] = 'T' . $allInboundTrip->id;
                                    $tripIn['bus_no'] = $allInboundTrip->Bus->bus_registration_number;

                                    //Check driver_id
                                    if($allInboundTrip->driver_id != NULL) {
                                        $tripIn['driver_id'] = $allInboundTrip->BusDriver->driver_number;
                                    }else{
                                        $tripIn['driver_id'] = "No Data";
                                    }

                                    $tripIn['service_date'] = $all_date;

                                    $firstStageIn = Stage::where('route_id', $selectedRoute->id)->first();
                                    $tripIn['start_point'] = $firstStageIn->stage_name;

                                    //Check route_schedule_mstr_id
                                    if($allInboundTrip->route_schedule_mstr_id != NULL) {
                                        $tripIn['service_start'] = Carbon::create($allInboundTrip->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                    }else{
                                        $tripIn['service_start'] = "No Data";
                                    }

                                    $tripIn['actual_start'] = date("H:i", strtotime($allInboundTrip->start_trip));

                                    //Check sales
                                    $firstTicket = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                        ->orderby('sales_date')
                                        ->first();
                                    if($firstTicket){
                                        $tripIn['sales_start'] = date("H:i", strtotime($firstTicket->sales_date));
                                    }else{
                                        $tripIn['sales_start'] = "No Sales";
                                    }

                                    //Check route_schedule_mstr_id
                                    if($allInboundTrip->route_schedule_mstr_id != NULL) {
                                        $tripIn['service_end'] = Carbon::create($allInboundTrip->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                    }else{
                                        $tripIn['service_end'] = "No Data";
                                    }

                                    $tripIn['actual_end'] = date("H:i", strtotime($allInboundTrip->end_trip));

                                    //Check sales
                                    $lastTicket = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                        ->orderby('sales_date', 'DESC')
                                        ->first();
                                    if($lastTicket){
                                        $tripIn['sales_end'] = date("H:i", strtotime($lastTicket->sales_date));
                                    }else{
                                        $tripIn['sales_end'] = "No Sales";
                                    }

                                    $adult = $allInboundTrip->total_adult;
                                    $concession = $allInboundTrip->total_concession;
                                    $passengerIn = $adult + $concession;

                                    $adultFarebox = $allInboundTrip->total_adult_amount;
                                    $concessionFarebox = $allInboundTrip->total_concession_amount;
                                    $fareboxIn = $adultFarebox + $concessionFarebox;

                                    //Check tickets
                                    if($fareboxIn==0 || $passengerIn==0){
                                        $allTicketPerTrips = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)->get();
                                        if(count($allTicketPerTrips)>0){
                                            $adult = 0;
                                            $concession = 0;
                                            $passengerIn = 0;
                                            $fareboxIn = 0;
                                            foreach($allTicketPerTrips as $allTicketPerTrip){
                                                $fareboxIn += $allTicketPerTrip->actual_amount;
                                                if($allTicketPerTrip->passenger_type==0){
                                                    $adult++;
                                                }else{
                                                    $concession++;
                                                }
                                            }
                                            $passengerIn = $adult + $concession;
                                        }
                                    }

                                    $tripIn['passenger_count'] = $passengerIn;
                                    $tripIn['sales_amount'] = $fareboxIn;
                                    $tripIn['total_on'] = $passengerIn;
                                    $tripIn['adult'] = $adult;
                                    $tripIn['concession'] =$concession;

                                    $inbound[$allInboundTrip->id] = $tripIn;

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

                            $routeNameOut = implode(" - ", array_reverse(explode(" - ", $routeNameIn)));

                            $outbound = [];
                            $outboundPassenger = 0;
                            $outboundSales  = 0;
                            $outboundAdult  = 0;
                            $outboundConcession  = 0;
                            if (count($allOutboundTrips) > 0) {
                                $existOutTrip = true;
                                foreach ($allOutboundTrips as $allOutboundTrip) {
                                    $tripOut['trip_no'] = 'T' . $allOutboundTrip->id;
                                    $tripOut['bus_no'] = $allOutboundTrip->Bus->bus_registration_number;

                                    //Check driver_id
                                    if($allOutboundTrip->driver_id!=NULL) {
                                        $tripOut['driver_id'] = $allOutboundTrip->BusDriver->driver_number;
                                    }else{
                                        $tripOut['driver_id'] = "No Data";
                                    }

                                    $tripOut['service_date'] = $all_date;
                                    $firstStageOut = Stage::where('route_id',$selectedRoute->id)->orderBy('stage_order', 'DESC')->first();
                                    $tripOut['start_point'] = $firstStageOut->stage_name;

                                    //Check route_schedule_mstr_id
                                    if($allOutboundTrip->route_schedule_mstr_id!=NULL){
                                        $tripOut['service_start'] = Carbon::create($allOutboundTrip->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                    }else{
                                        $tripOut['service_start'] = "No Data";
                                    }

                                    $tripOut['actual_start'] = date("H:i", strtotime($allOutboundTrip->start_trip));

                                    //Check sales
                                    $firstTicket = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                        ->orderby('sales_date')
                                        ->first();
                                    if($firstTicket){
                                        $tripOut['sales_start'] = date("H:i", strtotime($firstTicket->sales_date));
                                    }else{
                                        $tripOut['sales_start'] = "No Sales";
                                    }

                                    //Check route_schedule_mstr_id
                                    if($allOutboundTrip->route_schedule_mstr_id!=NULL){
                                        $tripOut['service_end'] = Carbon::create($allOutboundTrip->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                    }else{
                                        $tripOut['service_end'] = "No Data";
                                    }

                                    $tripOut['actual_end'] = date("H:i", strtotime($allOutboundTrip->end_trip));

                                    //Check sales
                                    $lastTicket = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)
                                        ->orderby('sales_date', 'DESC')
                                        ->first();
                                    if($lastTicket){
                                        $tripOut['sales_end'] = date("H:i", strtotime($lastTicket->sales_date));
                                    }else{
                                        $tripOut['sales_end'] = "No Sales";
                                    }

                                    $adult = $allOutboundTrip->total_adult;
                                    $concession = $allOutboundTrip->total_concession;
                                    $passengerOut = $adult + $concession;

                                    $adultFarebox = $allOutboundTrip->total_adult_amount;
                                    $concessionFarebox = $allOutboundTrip->total_concession_amount;
                                    $fareboxOut = $adultFarebox + $concessionFarebox;

                                    //Check tickets
                                    if($fareboxOut==0 || $passengerOut==0){
                                        $allTicketPerTrips = TicketSalesTransaction::where('trip_id', $allOutboundTrip->id)->get();
                                        if(count($allTicketPerTrips)>0){
                                            $adult = 0;
                                            $concession = 0;
                                            $passengerOut = 0;
                                            $fareboxOut = 0;
                                            foreach($allTicketPerTrips as $allTicketPerTrip){
                                                $fareboxOut += $allTicketPerTrip->actual_amount;
                                                if($allTicketPerTrip->passenger_type==0){
                                                    $adult++;
                                                }else{
                                                    $concession++;
                                                }
                                            }
                                            $passengerOut = $adult + $concession;
                                        }
                                    }
                                    $tripOut['passenger_count'] = $passengerOut;
                                    $tripOut['sales_amount'] = $fareboxOut;
                                    $tripOut['total_on'] = $passengerOut;
                                    $tripOut['adult'] = $adult;
                                    $tripOut['concession'] = $concession;

                                    $outbound[$allOutboundTrip->id] = $tripOut;

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

                            $route_data = [];
                            $sumPassengerCount = 0;
                            $sumSalesAmount = 0;
                            $sumAdult = 0;
                            $sumConcession = 0;
                            if ($existInTrip == true && $existOutTrip == true) {
                                //$out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == true");
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
                                //$out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == true");
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
                                //$out->writeln("YOU ARE IN HERE existInTrip == true && existOutTrip == false");
                                $sumPassengerCount =  $inboundPassenger;
                                $sumSalesAmount = $inboundSales;
                                $sumAdult = $inboundAdult;
                                $sumConcession = $inboundConcession;

                                $totalPerDate['passenger_count'] = $sumPassengerCount;
                                $totalPerDate['sales_amount'] = $sumSalesAmount;
                                $totalPerDate['total_on'] = $sumPassengerCount;
                                $totalPerDate['adult'] = $sumAdult;
                                $totalPerDate['concession'] = $sumConcession;

                                $route_data[$routeNameIn] = $inbound;
                                $route_data[$routeNameOut] = [];
                                $route_data['total_per_date'] = $totalPerDate;
                                $perDate[$all_date] = $route_data;
                            }else{
                                //$out->writeln("YOU ARE IN HERE existInTrip == false && existOutTrip == false");
                                $perDate[$all_date] = [];
                            }
                            $perRoutePassengerCount += $sumPassengerCount;
                            $perRouteSalesAmount += $sumSalesAmount;
                            $perRouteAdult += $sumAdult;
                            $perRouteConcession += $sumConcession;
                        }
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
                }
            }
            return Excel::download(new SPADTrip($tripSPAD, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Trip_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printTopBoardings()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printTopBoarding()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo'] . '23:59:59');

        $topBoardingSPAD = collect();
        $grandTotalOnIn = 0;
        $grandAdultIn = 0;
        $grandConcessionIn = 0;
        $grandTotalOnOut = 0;
        $grandAdultOut = 0;
        $grandConcessionOut = 0;
        $perRoute = [];
        if($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                if(!empty($validatedData['route_id'])) {
                    //Top Boarding all route all company
                    if($validatedData['route_id']=='All'){
                        $allRoutes = Route::orderBy('route_number')->get();
                        
                        foreach($allRoutes as $allRoute) {
                            $allStages = Stage::where('route_id', $allRoute->id)->get();
                            $totalTotalOnIn = 0;
                            $totalAdultIn = 0;
                            $totalConcessionIn = 0;
                            $totalTotalOnOut = 0;
                            $totalAdultOut = 0;
                            $totalConcessionOut = 0;
                            $perStage = [];
            
                            if (count($allStages) > 0) {
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
                                            $ticketInTrips = TicketSalesTransaction::where('trip_id', $allInboundTrip->id)
                                                ->where('fromstage_stage_id', $allStage->id)
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
                                                ->where('fromstage_stage_id', $allStage->id)
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
            
                                    $perStage[$allStage->stage_order . '. ' . $allStage->stage_name] = $trip_data;
            
                                    $totalTotalOnIn += $countIn;
                                    $totalAdultIn += $adultIn;
                                    $totalConcessionIn += $concessionIn;
                                    $totalTotalOnOut += $countOut;
                                    $totalAdultOut += $adultOut;
                                    $totalConcessionOut += $concessionOut;
                                }
                            }
            
                            if ($totalTotalOnIn == 0 && $totalAdultIn == 0 && $totalConcessionIn == 0 && $totalTotalOnOut == 0 && $totalAdultOut == 0 && $totalConcessionOut == 0) {
                                $perRoute[$allRoute->route_number] = [];
                            } else{
                                $total_in = [];
                                foreach ($perStage as $key => $row) {
                                    //$out->writeln("You are in loop array multisort");
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
                                $perRoute[$allRoute->route_number] = $perStage;
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
                }
            }else{
                $selectedCompany = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $selectedCompany->company_name;
                if(!empty($validatedData['route_id'])) {
                    //Top Boarding all route specific company
                    if($validatedData['route_id']=='All'){
                        $routeByCompanies = Route::where('company_id', $this->selectedCompany)->orderBy('route_number')->get();
        
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
        
                            if (count($allStages) > 0) {
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
                                                ->where('fromstage_stage_id', $allStage->id)
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
                                                ->where('fromstage_stage_id', $allStage->id)
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
        
                                    $perStage[$allStage->stage_order . '. ' . $allStage->stage_name] = $trip_data;
        
                                    $totalTotalOnIn += $countIn;
                                    $totalAdultIn += $adultIn;
                                    $totalConcessionIn += $concessionIn;
                                    $totalTotalOnOut += $countOut;
                                    $totalAdultOut += $adultOut;
                                    $totalConcessionOut += $concessionOut;
                                }
                            }
        
                            if ($totalTotalOnIn == 0 && $totalAdultIn == 0 && $totalConcessionIn == 0 && $totalTotalOnOut == 0 && $totalAdultOut == 0 && $totalConcessionOut == 0) {
                                $perRoute[$routeByCompany->route_number] = [];
                            } else {
                                $total_in = [];
                                foreach ($perStage as $key => $row) {
                                    //$out->writeln("You are in loop array multisort");
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
                        $topBoardingSPAD->add($data);
                    }
                    //Top Boarding specific route specific company
                    else{
                        $validatedRoute = Route::where('id', $validatedData['route_id'])->first();
                        $allStages = Stage::where('route_id', $validatedRoute->id)->get();
                        $totalTotalOnIn = 0;
                        $totalAdultIn = 0;
                        $totalConcessionIn = 0;
                        $totalTotalOnOut = 0;
                        $totalAdultOut = 0;
                        $totalConcessionOut = 0;
                        $perStage = [];
        
                        if (count($allStages) > 0) {
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
                                            ->where('fromstage_stage_id', $allStage->id)
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
                                            ->where('fromstage_stage_id', $allStage->id)
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
        
                                $perStage[$allStage->stage_order . '. ' . $allStage->stage_name] = $trip_data;
        
                                $totalTotalOnIn += $countIn;
                                $totalAdultIn += $adultIn;
                                $totalConcessionIn += $concessionIn;
                                $totalTotalOnOut += $countOut;
                                $totalAdultOut += $adultOut;
                                $totalConcessionOut += $concessionOut;
                            }
                        }
        
                        if ($totalTotalOnIn == 0 && $totalAdultIn == 0 && $totalConcessionIn == 0 && $totalTotalOnOut == 0 && $totalAdultOut == 0 && $totalConcessionOut == 0) {
                            $perRoute[$validatedRoute->route_number] = [];
                        } else {
                            $total_in = [];
                            foreach ($perStage as $key => $row) {
                                //$out->writeln("You are in loop array multisort");
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
                        $topBoardingSPAD->add($data);
                    }
                }
            }
            return Excel::download(new SPADTopBoarding($topBoardingSPAD, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Top_Boarding_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printTopAlighting()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printTopAlighting()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo'] . '23:59:59');

        $topAlightingSPAD = collect();
        $grandTotalOffIn = 0;
        $grandAdultIn = 0;
        $grandConcessionIn = 0;
        $grandTotalOffOut = 0;
        $grandAdultOut = 0;
        $grandConcessionOut = 0;
        $perRoute = [];
        if($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                if(!empty($validatedData['route_id'])) {
                    //Top Alighting all route all company
                    if($validatedData['route_id']=='All'){
                        $allRoutes = Route::orderBy('route_number')->get();
            
                        foreach($allRoutes as $allRoute) {
                            $allStages = Stage::where('route_id', $allRoute->id)->get();
                            $totalTotalOffIn = 0;
                            $totalAdultIn = 0;
                            $totalConcessionIn = 0;
                            $totalTotalOffOut = 0;
                            $totalAdultOut = 0;
                            $totalConcessionOut = 0;
                            $perStage = [];
            
                            if (count($allStages) > 0) {
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
                                    $trip_data['total_off_in'] = $countIn;
                                    $trip_data['adult_in'] = $adultIn;
                                    $trip_data['concession_in'] = $concessionIn;
                                    $trip_data['total_off_out'] = $countOut;
                                    $trip_data['adult_out'] = $adultOut;
                                    $trip_data['concession_out'] = $concessionOut;
            
                                    $perStage[$allStage->stage_order . '. ' . $allStage->stage_name] = $trip_data;
            
                                    $totalTotalOffIn += $countIn;
                                    $totalAdultIn += $adultIn;
                                    $totalConcessionIn += $concessionIn;
                                    $totalTotalOffOut += $countOut;
                                    $totalAdultOut += $adultOut;
                                    $totalConcessionOut += $concessionOut;
                                }
                            }
            
                            if ($totalTotalOffIn == 0 && $totalAdultIn == 0 && $totalConcessionIn == 0 && $totalTotalOffOut == 0 && $totalAdultOut == 0 && $totalConcessionOut == 0) {
                                $perRoute[$allRoute->route_number] = [];
                            } else{
                                $total_in = [];
                                foreach ($perStage as $key => $row) {
                                    //$out->writeln("You are in loop array multisort");
                                    $total_in[$key] = $row['total_off_in'];
                                }
                                array_multisort($total_in, SORT_DESC, $perStage);
            
                                $total['total_off_in'] = $totalTotalOffIn;
                                $total['adult_in'] = $totalAdultIn;
                                $total['concession_in'] = $totalConcessionIn;
                                $total['total_off_out'] = $totalTotalOffOut;
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
                        $grand['total_off_in'] = $grandTotalOffIn;
                        $grand['adult_in'] = $grandAdultIn;
                        $grand['concession_in'] = $grandConcessionIn;
                        $grand['total_off_out'] = $grandTotalOffOut;
                        $grand['adult_out'] = $grandAdultOut;
                        $grand['concession_out'] = $grandConcessionOut;
            
            
                        $data['allRoute'] = $perRoute;
                        $data['grand'] = $grand;
                        $topAlightingSPAD->add($data);
                    }
                }
            }else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $companyDetails->company_name;

                if(!empty($validatedData['route_id'])) {
                    //Top Alighting all route specific company
                    if($validatedData['route_id']=='All'){
                        $routeByCompanies = Route::where('company_id', $this->selectedCompany)->orderBy('route_number')->get();
        
                        foreach ($routeByCompanies as $routeByCompany) {
                            $allStages = Stage::where('route_id', $routeByCompany->id)->get();
                            $totalTotalOffIn = 0;
                            $totalAdultIn = 0;
                            $totalConcessionIn = 0;
                            $totalTotalOffOut = 0;
                            $totalAdultOut = 0;
                            $totalConcessionOut = 0;
                            $perStage = [];
        
                            if (count($allStages) > 0) {
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
                                    $trip_data['total_off_in'] = $countIn;
                                    $trip_data['adult_in'] = $adultIn;
                                    $trip_data['concession_in'] = $concessionIn;
                                    $trip_data['total_off_out'] = $countOut;
                                    $trip_data['adult_out'] = $adultOut;
                                    $trip_data['concession_out'] = $concessionOut;
        
                                    $perStage[$allStage->stage_order . '. ' . $allStage->stage_name] = $trip_data;
        
                                    $totalTotalOffIn += $countIn;
                                    $totalAdultIn += $adultIn;
                                    $totalConcessionIn += $concessionIn;
                                    $totalTotalOffOut += $countOut;
                                    $totalAdultOut += $adultOut;
                                    $totalConcessionOut += $concessionOut;
                                }
                            }
        
                            if ($totalTotalOffIn == 0 && $totalAdultIn == 0 && $totalConcessionIn == 0 && $totalTotalOffOut == 0 && $totalAdultOut == 0 && $totalConcessionOut == 0) {
                                $perRoute[$routeByCompany->route_number] = [];
                            } else {
                                $total_in = [];
                                foreach ($perStage as $key => $row) {
                                    $total_in[$key] = $row['total_off_in'];
                                }
                                array_multisort($total_in, SORT_DESC, $perStage);
        
                                $total['total_off_in'] = $totalTotalOffIn;
                                $total['adult_in'] = $totalAdultIn;
                                $total['concession_in'] = $totalConcessionIn;
                                $total['total_off_out'] = $totalTotalOffOut;
                                $total['adult_out'] = $totalAdultOut;
                                $total['concession_out'] = $totalConcessionOut;
        
                                $perStage['total_per_route'] = $total;
                                $perRoute[$routeByCompany->route_number] = $perStage;
                            }
        
                            $grandTotalOffIn += $totalTotalOffIn;
                            $grandAdultIn += $totalAdultIn;
                            $grandConcessionIn += $totalConcessionIn;
                            $grandTotalOffOut += $totalTotalOffOut;
                            $grandAdultOut += $totalAdultOut;
                            $grandConcessionOut += $totalConcessionOut;
                        }
                        $grand['total_off_in'] = $grandTotalOffIn;
                        $grand['adult_in'] = $grandAdultIn;
                        $grand['concession_in'] = $grandConcessionIn;
                        $grand['total_off_out'] = $grandTotalOffOut;
                        $grand['adult_out'] = $grandAdultOut;
                        $grand['concession_out'] = $grandConcessionOut;
        
                        $data['allRoute'] = $perRoute;
                        $data['grand'] = $grand;
                        $topAlightingSPAD->add($data);
                    }
                    //Top Alighting specific route specific company
                    else{
                        $validatedRoute = Route::where('id', $validatedData['route_id'])->first();
                        $allStages = Stage::where('route_id', $validatedRoute->id)->get();
                        $totalTotalOffIn = 0;
                        $totalAdultIn = 0;
                        $totalConcessionIn = 0;
                        $totalTotalOffOut = 0;
                        $totalAdultOut = 0;
                        $totalConcessionOut = 0;
                        $perStage = [];
        
                        if (count($allStages) > 0) {
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
                                $trip_data['total_off_in'] = $countIn;
                                $trip_data['adult_in'] = $adultIn;
                                $trip_data['concession_in'] = $concessionIn;
                                $trip_data['total_off_out'] = $countOut;
                                $trip_data['adult_out'] = $adultOut;
                                $trip_data['concession_out'] = $concessionOut;
        
                                $perStage[$allStage->stage_order . '. ' . $allStage->stage_name] = $trip_data;
        
                                $totalTotalOffIn += $countIn;
                                $totalAdultIn += $adultIn;
                                $totalConcessionIn += $concessionIn;
                                $totalTotalOffOut += $countOut;
                                $totalAdultOut += $adultOut;
                                $totalConcessionOut += $concessionOut;
                            }
                        }
        
                        if ($totalTotalOffIn == 0 && $totalAdultIn == 0 && $totalConcessionIn == 0 && $totalTotalOffOut == 0 && $totalAdultOut == 0 && $totalConcessionOut == 0) {
                            $perRoute[$validatedRoute->route_number] = [];
                        } else {
                            $total_in = [];
                            foreach ($perStage as $key => $row) {
                                $total_in[$key] = $row['total_off_in'];
                            }
                            array_multisort($total_in, SORT_DESC, $perStage);
        
                            $total['total_off_in'] = $totalTotalOffIn;
                            $total['adult_in'] = $totalAdultIn;
                            $total['concession_in'] = $totalConcessionIn;
                            $total['total_off_out'] = $totalTotalOffOut;
                            $total['adult_out'] = $totalAdultOut;
                            $total['concession_out'] = $totalConcessionOut;
        
                            $perStage['total_per_route'] = $total;
                            $perRoute[$validatedRoute->route_number] = $perStage;
                        }
        
                        $grandTotalOffIn = $totalTotalOffIn;
                        $grandAdultIn = $totalAdultIn;
                        $grandConcessionIn = $totalConcessionIn;
                        $grandTotalOffOut = $totalTotalOffOut;
                        $grandAdultOut = $totalAdultOut;
                        $grandConcessionOut = $totalConcessionOut;
        
                        $grand['total_off_in'] = $grandTotalOffIn;
                        $grand['adult_in'] = $grandAdultIn;
                        $grand['concession_in'] = $grandConcessionIn;
                        $grand['total_off_out'] = $grandTotalOffOut;
                        $grand['adult_out'] = $grandAdultOut;
                        $grand['concession_out'] = $grandConcessionOut;
        
                        $data['allRoute'] = $perRoute;
                        $data['grand'] = $grand;
                        $topAlightingSPAD->add($data);
                    }
                }
            }
            return Excel::download(new SPADTopAlighting($topAlightingSPAD, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Top_Alighting_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printBusTransfer()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printBusTransfer()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();
        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();
            $startDate->addDay();
        }

        $SPADBusTransfer = collect();
        if($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                if(!empty($validatedData['route_id'])) {
                    //BusTransfer all route all company
                    if($validatedData['route_id']=='All'){

                    }
                }
            }else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $companyDetails->company_name;

                if(!empty($validatedData['route_id'])) {
                    //BusTransfer all route all company
                    if($validatedData['route_id']=='All'){

                    }else{

                    }
                }
            }
            return Excel::download(new SPADBusTransfer($SPADBusTransfer, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Bus_Transfer_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
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
            'route_id' => ['required'],
        ])->validate();

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo'] . '23:59:59');

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)) {
            $all_dates[] = $startDate->toDateString();
            $startDate->addDay();
        }

        $salesDetails = collect();
        $countTicket = 0;
        $sales = [];
        if ($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                if(!empty($validatedData['route_id'])) {
                    //printSalesDetails() all route for all company
                    if($validatedData['route_id']=='All'){
                        $allTickets = TicketSalesTransaction::whereBetween('sales_date', [$dateFrom , $dateTo])
                            ->orderBy('sales_date')
                            ->get();

                        if (count($allTickets) > 0) {
                            foreach ($allTickets as $allTicket) {
                                $countTicket++;
            
                                $salesTime = new Carbon($allTicket->sales_date);
                                $perTicket['sales_date'] = $salesTime->toDateString();
            
                                $perTicket['sales_time'] = $salesTime->toTimeString();
            
                                $perTicket['ticket_no'] = $allTicket->ticket_number;
            
                                if ($allTicket->fromstage_stage_id != NULL) {
                                    $perTicket['from'] = $allTicket->fromstage->stage_name;
                                } else {
                                    $perTicket['from'] = "NO DATA";
                                }
                                if ($allTicket->tostage_stage_id != NULL) {
                                    $perTicket['to'] = $allTicket->tostage->stage_name;
                                } else {
                                    $perTicket['to'] = "NO DATA";
                                }
            
                                if ($allTicket->pasenger_type == 0) {
                                    $passType = 'ADULT';
                                } elseif ($allTicket->pasenger_type == 1) {
                                    $passType = 'CONCESSION';
                                }
                                $perTicket['passenger_type'] = $passType;
            
                                $perTicket['price'] = $allTicket->actual_amount;
            
                                if($allTicket->TripDetail->bus_id!=NULL){
                                    $perTicket['bus_no'] = $allTicket->TripDetail->Bus->bus_registration_number;
                                }else{
                                    $perTicket['bus_no'] = "NO DATA";
                                }
            
                                if ($allTicket->TripDetail->trip_code == 1) {
                                    $IBOB = 'IB';
                                } elseif ($allTicket->TripDetail->trip_code == 0) {
                                    $IBOB = 'OB';
                                }
                                $perTicket['IBOB'] = $IBOB;
            
                                if($allTicket->TripDetail->route_id!=NULL){
                                    $perTicket['route_no'] = $allTicket->TripDetail->Route->route_number;
            
                                    $lastStage = Stage::where('route_id', $allTicket->TripDetail->route_id)
                                        ->orderBy('stage_order', 'DESC')
                                        ->first();
                                    if($lastStage){
                                        $perTicket['route_destination'] = $lastStage->stage_name;
                                    }else{
                                        $perTicket['route_destination'] =  "NO DATA";
                                    }
                                    
                                    $perTicket['route_name'] = $allTicket->TripDetail->Route->route_name;
                                }else{
                                    $perTicket['route_no'] =  "NO DATA";
                                    $perTicket['route_destination'] =  "NO DATA";
                                    $perTicket['route_name'] =  "NO DATA";
                                }
                            
                                $tripTime = new Carbon($allTicket->TripDetail->start_trip);
                                $perTicket['trip_time'] = $tripTime->toTimeString();
            
                                $perTicket['trip_no'] = 'T'. $allTicket->trip_id;
            
                                if ($allTicket->TripDetail->driver_id != NULL) {
                                    $perTicket['driver_id'] = $allTicket->TripDetail->BusDriver->driver_number;
                                    $perTicket['driver_name'] = $allTicket->TripDetail->BusDriver->driver_name;
                                }
                                else{
                                    $perTicket['driver_id'] = "NO DATA";
                                    $perTicket['driver_name'] = "NO DATA";
                                }
            
                                if($allTicket->fare_type == 1) {
                                    $payment = 'CARD';
                                }elseif($allTicket->fare_type == 2){
                                    $payment = 'TOUCH N GO';
                                }else{
                                    $payment = 'CASH';
                                }
                                $perTicket['payment'] = $payment;
            
                                $sales[$countTicket] = $perTicket;
                            }
                        }
                        $salesDetails->add($sales);
                    }
                }
            }else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $companyDetails->company_name;
                if(!empty($validatedData['route_id'])) {
                    //printSalesDetails() all route for specific company
                    if($validatedData['route_id']=='All'){
                        $routeByCompanies = Route::where('company_id', $companyDetails->id)->orderBy('route_number')->get();
        
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
                                            $countTicket++;
        
                                            $salesTime = new Carbon($allTicket->sales_date);
                                            $perTicket['sales_date'] = $salesTime->toDateString();
        
                                            $perTicket['sales_time'] = $salesTime->toTimeString();
        
                                            $perTicket['ticket_no'] = $allTicket->ticket_number;
        
                                            if($allTicket->fromstage_stage_id != NULL) {
                                                $perTicket['from'] = $allTicket->fromstage->stage_name;
                                            }
                                            else{
                                                $perTicket['from'] = "NO DATA";
                                            }
                                            if($allTicket->tostage_stage_id != NULL) {
                                                $perTicket['to'] = $allTicket->tostage->stage_name;
                                            }else{
                                                $perTicket['to'] = "NO DATA";
                                            }
        
                                            if ($allTicket->pasenger_type == 0) {
                                                $passType = 'ADULT';
                                            } elseif ($allTicket->pasenger_type == 1) {
                                                $passType = 'CONCESSION';
                                            }
                                            $perTicket['passenger_type'] = $passType;
        
                                            $perTicket['price'] = $allTicket->actual_amount;
        
                                            if($allTrip->bus_id != NULL) {
                                                $perTicket['bus_no'] = $allTrip->Bus->bus_registration_number;
                                            }else{
                                                $perTicket['bus_no'] = "NO DATA";
                                            }
        
                                            if ($allTrip->trip_code == 1) {
                                                $IBOB = 'IB';
                                            } elseif ($allTrip->trip_code == 0) {
                                                $IBOB = 'OB';
                                            }
                                            $perTicket['IBOB'] = $IBOB;
        
                                            $perTicket['route_no'] = $routeByCompany->route_number;
        
                                            $lastStage = Stage::where('route_id', $allTrip->route_id)
                                                ->orderBy('stage_order', 'DESC')
                                                ->first();
                                            if($lastStage){
                                                $perTicket['route_destination'] = $lastStage->stage_name;
                                            }else{
                                                $perTicket['route_destination'] = "NO DATA";
                                            }
        
                                            $perTicket['route_name'] = $routeByCompany->route_name;
        
                                            $tripTime = new Carbon($allTrip->start_trip);
                                            $perTicket['trip_time'] = $tripTime->toTimeString();
        
                                            $perTicket['trip_no'] = 'T'. $allTrip->id;
        
                                            if ($allTrip->driver_id != NULL) {
                                                $perTicket['driver_id'] = $allTrip->BusDriver->driver_number;
                                                $perTicket['driver_name'] = $allTrip->BusDriver->driver_name;
                                            }
                                            else{
                                                $perTicket['driver_id'] = "NO DATA";
                                                $perTicket['driver_name'] = "NO DATA";
                                            }
        
                                            if ($allTicket->fare_type == 1) {
                                                $payment = 'CARD';
                                            } elseif ($allTicket->fare_type == 2) {
                                                $payment = 'TOUCH N GO';
                                            }else{
                                                $payment = 'CASH';
                                            }
                                            $perTicket['payment'] = $payment;
        
                                            $sales[$countTicket] = $perTicket;
                                        }
                                    }
                                }
                            }
                        }
                        $salesDetails->add($sales);
                    }
                    //printSalesDetails() certain route for specific company
                    else{
                        $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
        
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
                                        $countTicket++;
        
                                        $salesTime = new Carbon($allTicket->sales_date);
                                        $perTicket['sales_date'] = $salesTime->toDateString();
        
                                        $perTicket['sales_time'] = $salesTime->toTimeString();
        
                                        $perTicket['ticket_no'] = $allTicket->ticket_number;
        
                                        if($allTicket->fromstage_stage_id != NULL) {
                                            $perTicket['from'] = $allTicket->fromstage->stage_name;
                                        }
                                        else{
                                            $perTicket['from'] = "NO DATA";
                                        }
                                        if($allTicket->tostage_stage_id != NULL) {
                                            $perTicket['to'] = $allTicket->tostage->stage_name;
                                        }else{
                                            $perTicket['to'] = "NO DATA";
                                        }
        
                                        if ($allTicket->pasenger_type == 0) {
                                            $passType = 'ADULT';
                                        } elseif ($allTicket->pasenger_type == 1) {
                                            $passType = 'CONCESSION';
                                        }
                                        $perTicket['passenger_type'] = $passType;
        
                                        $perTicket['price'] = $allTicket->actual_amount;
        
                                        if($allTrip->bus_id != NULL) {
                                            $perTicket['bus_no'] = $allTrip->Bus->bus_registration_number;
                                        }else{
                                            $perTicket['bus_no'] = "NO DATA";
                                        }
        
                                        if ($allTrip->trip_code == 1) {
                                            $IBOB = 'IB';
                                        } elseif ($allTrip->trip_code == 0) {
                                            $IBOB = 'OB';
                                        }
                                        $perTicket['IBOB'] = $IBOB;
        
                                        $perTicket['route_no'] = $selectedRoute->route_number;
        
                                        $lastStage = Stage::where('route_id', $allTrip->route_id)
                                            ->orderBy('stage_order', 'DESC')
                                            ->first();
                                        if($lastStage){
                                            $perTicket['route_destination'] = $lastStage->stage_name;
                                        }else{
                                            $perTicket['route_destination'] = "NO DATA";
                                        }
        
                                        $perTicket['route_name'] = $selectedRoute->route_name;
        
                                        $tripTime = new Carbon($allTrip->start_trip);
                                        $perTicket['trip_time'] = $tripTime->toTimeString();
        
                                        $perTicket['trip_no'] ='T'.$allTrip->id;
        
                                        if ($allTrip->driver_id != NULL) {
                                            $perTicket['driver_id'] = $allTrip->BusDriver->driver_number;
                                            $perTicket['driver_name'] = $allTrip->BusDriver->driver_name;
                                        }
                                        else{
                                            $perTicket['driver_id'] = "NO DATA";
                                            $perTicket['driver_name'] = "NO DATA";
                                        }
        
                                        if ($allTicket->fare_type == 1) {
                                            $payment = 'CARD';
                                        } elseif ($allTicket->fare_type == 2) {
                                            $payment = 'TOUCH N GO';
                                        }else{
                                            $payment = 'CASH';
                                        }
                                        $perTicket['payment'] = $payment;
        
                                        $sales[$countTicket] = $perTicket;
                                    }
                                }
                            }
                        }
                        $salesDetails->add($sales);
                    }
                }
            }
            return Excel::download(new SPADSalesDetails($salesDetails, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Sales_Details_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printClaimDetails()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printClaimDetails())");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();
            $startDate->addDay();
        }

        $claimDetails = collect();
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
        if ($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                if(!empty($validatedData['route_id'])) {
                    //ClaimDetails all routes for all company
                    if($validatedData['route_id']=='All'){
                        $allRoutes = Route::orderBy('route_number')->get();
            
                        foreach($allRoutes as $allRoute) {
                            foreach($all_dates as $all_date) {
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
                                $allTripIn = [];
                                $allInbound = [];
                                $existInTrip = false;
                                $existOutTrip = false;
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date . '23:59:59');
            
                                //Inbound
                                $allTripInbounds = TripDetail::where('route_id', $allRoute->id)
                                    ->whereBetween('start_trip', [$firstDate,$lastDate])
                                    ->where('trip_code', 1)
                                    ->get();
            
                                $routeNameIn = implode(" - ", array_reverse(explode(" - ", $allRoute->route_name)));
                                if (count($allTripInbounds) > 0) {
                                    $existInTrip = true;
                                    $countIn = 0;
            
                                    foreach ($allTripInbounds as $allTripInbound) {
                                        $inbound['trip_type'] = "IB";
                                        $firstStage = Stage::where('route_id', $allTripInbound->route_id)->orderby('stage_order', 'DESC')->first();
                                        $inbound['start_point'] = $firstStage->stage_name;
                                        $inbound['trip_no'] = "T" . $allTripInbound->id;
                                        $inbound['rph_no'] = $allTripInbound->trip_number;
                                        
                                        if($allTripInbound->bus_id != NULL){
                                            $inbound['bus_plate_no'] = $allTripInbound->Bus->bus_registration_number;
                                            $inbound['bus_age'] = $allTripInbound->Bus->bus_age;
                                            //$inbound['bus_age'] = Carbon::parse($allTripInbound->Bus->bus_manufacturing_date)->diff(Carbon::now())->y;
                                        }else{
                                            $inbound['bus_plate_no'] = "No Data";
                                            $inbound['bus_age'] = "No Data";
                                        }
            
                                        $charge = 1.33;
                                        $inbound['charge_km'] = $charge;
            
                                        if($allTripInbound->driver_id != NULL){
                                            $inbound['driver_id'] = $allTripInbound->BusDriver->driver_number;
                                        }else{
                                            $inbound['driver_id'] = "No Data";
                                        }
            
                                        //$busStop = BusStand::where('route_id', $allTripInbound->route_id)->count();
                                        $busStop = Stage::where('route_id', $allTripInbound->route_id)->count();
                                        $inbound['bus_stop_travel'] = $busStop;
                                        $totalBusStopIn += $busStop;
            
                                        $travel = $allTripInbound->Route->inbound_distance;
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
                                        $inbound['travel_BOP'] = $travel;
                                        $inbound['claim_BOP'] = $claim;
            
                                        if($allTripInbound->route_schedule_mstr_id!=NULL){
                                            $inbound['service_start'] = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                            $inbound['service_end'] = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                        }else{
                                            $inbound['service_start'] = "No Data";
                                            $inbound['service_end'] = "No Data";
                                        }
                                        $inbound['start_point_time'] = $inbound['service_start'];
            
                                        $inbound['actual_start'] = date("H:i", strtotime($allTripInbound->start_trip));
                                        $inbound['actual_end'] = date("H:i", strtotime($allTripInbound->end_trip));
            
                                        //Check 1st sales
                                        $firstSales = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)
                                            ->orderby('sales_date')
                                            ->first();
                                        if($firstSales){
                                            $inbound['sales_start'] = date("H:i", strtotime($firstSales->sales_date));
                                        }else{
                                            $inbound['sales_start'] = "No Sales";
                                        }
            
                                        //Check last sales
                                        $lastSales = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)
                                            ->orderby('sales_date', 'DESC')
                                            ->first();
                                        if($lastSales){
                                            $inbound['sales_end'] = date("H:i", strtotime($lastSales->sales_date));
                                        }else{
                                            $inbound['sales_end'] = "No Sales";
                                        }
            
                                        if($inbound['service_start']!="No Data"){
                                            $diff = strtotime($inbound['service_start']) - strtotime($inbound['actual_start']);
                                            if ($diff > 5 || $diff < -5) {
                                                $inbound['punctuality'] = "NOT PUNCTUAL";
                                            }else {
                                                $inbound['punctuality'] = "ONTIME";
                                            }
                                        }else{
                                            $inbound['punctuality'] = "No Data";
                                        }
            
                                        $adult = $allTripInbound->total_adult;
                                        $concession = $allTripInbound->total_concession;
                                        $countPassenger = $adult + $concession;
                                        $sales = $allTripInbound->total_adult_amount + $allTripInbound->total_concession_amount;
                                        //Check tickets
                                        if($countPassenger==0 || $sales==0){
                                            $allTicketPerTrips = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)->get();
                                            if(count($allTicketPerTrips)>0){
                                                $adult = 0;
                                                $concession = 0;
                                                $countPassenger = 0;
                                                $sales = 0;
                                                foreach($allTicketPerTrips as $allTicketPerTrip){
                                                    $sales += $allTicketPerTrip->actual_amount;
                                                    if($allTicketPerTrip->passenger_type==0){
                                                        $adult++;
                                                    }else{
                                                        $concession++;
                                                    }
                                                }
                                                $countPassenger = $adult + $concession;
                                            }
                                        }
                                        $inbound['pass_count'] = $countPassenger;
                                        $totalCountPassengerIn += $countPassenger;
            
                                        $inbound['total_sales'] = $sales;
                                        $totalSalesIn += $sales;
            
                                        $inbound['total_on'] = $countPassenger;
                                        $totalTotalIn += $countPassenger;
            
                                        $inbound['adult'] = $adult;
                                        $totalAdultIn += $adult;
            
                                        $inbound['concession'] = $concession;
                                        $totalConcessionIn += $concession;
            
                                        $allInbound[$countIn] = $inbound;
                                        $countIn++;
                                    }
                                    $allTripIn[$routeNameIn] = $allInbound;
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
                                $allTripOut = [];
                                $allOutbound = [];
                                //Outbound
                                $allTripOutbounds = TripDetail::where('route_id', $allRoute->id)
                                    ->whereBetween('start_trip', [$firstDate,$lastDate])
                                    ->where('trip_code', 0)
                                    ->get();
            
                                $routeNameOut = $allRoute->route_name;
                                if (count($allTripOutbounds) > 0) {
                                    $existOutTrip = true;
                                    $countOut = 0;
            
                                    foreach ($allTripOutbounds as $allTripOutbound) {
                                        $outbound['trip_type'] = "OB";
                                        $firstStage = Stage::where('route_id', $allTripOutbound->route_id)->orderby('stage_order')->first();
                                        $outbound['start_point'] = $firstStage->stage_name;
                                        $outbound['trip_no'] = "T" . $allTripOutbound->id;
                                        $outbound['rph_no'] = $allTripOutbound->trip_number;
            
                                        if($allTripOutbound->bus_id != NULL){
                                            $outbound['bus_plate_no'] = $allTripOutbound->Bus->bus_registration_number;
                                            $outbound['bus_age'] = $allTripOutbound->Bus->bus_age;
                                            //$inbound['bus_age'] = Carbon::parse($allTripOutbound->Bus->bus_manufacturing_date)->diff(Carbon::now())->y;
                                        }else{
                                            $outbound['bus_plate_no']  = "No Data";
                                            $outbound['bus_age'] = "No Data";
                                        }
            
                                        $charge = 1.33;
                                        $outbound['charge_km'] = $charge;
            
                                        if($allTripOutbound->driver_id != NULL){
                                            $outbound['driver_id'] = $allTripOutbound->BusDriver->driver_number;
                                        }else{
                                            $outbound['driver_id'] = "No Data";
                                        }
            
                                        //$busStop = BusStand::where('route_id', $allTripOutbound->route_id)->count();
                                        $busStop = Stage::where('route_id', $allTripOutbound->route_id)->count();
                                        $outbound['bus_stop_travel'] = $busStop;
                                        $totalBusStopOut += $busStop;
            
                                        $travel = $allTripOutbound->Route->inbound_distance;
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
                                        $outbound['travel_BOP'] = $travel;
                                        $outbound['claim_BOP'] = $claim;
            
                                        if($allTripOutbound->route_schedule_mstr_id!=NULL){
                                            $outbound['service_start'] = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                            $outbound['service_end'] = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                        }else{
                                            $outbound['service_start'] = "No Data";
                                            $outbound['service_end'] = "No Data";
                                        }
                                        $outbound['start_point_time'] = $outbound['service_start'];
            
                                        $outbound['actual_start'] = date("H:i", strtotime($allTripOutbound->start_trip));
                                        $outbound['actual_end'] = date("H:i", strtotime($allTripOutbound->end_trip));
            
                                        //Check 1st sales
                                        $firstSales = TicketSalesTransaction::where('trip_number', $allTripOutbound->trip_number)
                                            ->orderby('sales_date')
                                            ->first();
                                        if($firstSales){
                                            $outbound['sales_start'] = date("H:i", strtotime($firstSales->sales_date));
                                        }else{
                                            $outbound['sales_start'] = "No Sales";
                                        }
            
                                        //Check last sales
                                        $lastSales = TicketSalesTransaction::where('trip_number', $allTripOutbound->trip_number)
                                            ->orderby('sales_date', 'DESC')
                                            ->first();
                                        if($lastSales){
                                            $outbound['sales_end'] = date("H:i", strtotime($lastSales->sales_date));
                                        }else{
                                            $outbound['sales_end'] = "No Sales";
                                        }
            
                                        if($outbound['service_start']!="No Data"){
                                            $diff = strtotime($outbound['service_start']) - strtotime($outbound['actual_start']);
                                            if ($diff > 5 || $diff < -5) {
                                                $outbound['punctuality'] = "NOT PUNCTUAL";
                                            } else {
                                                $outbound['punctuality'] = "ONTIME";
                                            }
                                        }else{
                                            $outbound['punctuality'] = "No Data";
                                        }
            
                                        $adult = $allTripOutbound->total_adult;
                                        $concession = $allTripOutbound->total_concession;
                                        $countPassenger = $adult + $concession;
                                        $sales = $allTripOutbound->total_adult_amount + $allTripOutbound->total_concession_amount;
                                        //Check tickets
                                        if($countPassenger==0 || $sales==0){
                                            $allTicketPerTrips = TicketSalesTransaction::where('trip_number', $allTripOutbound->trip_number)->get();
                                            if(count($allTicketPerTrips)>0){
                                                $adult = 0;
                                                $concession = 0;
                                                $countPassenger = 0;
                                                $sales = 0;
                                                foreach($allTicketPerTrips as $allTicketPerTrip){
                                                    $sales += $allTicketPerTrip->actual_amount;
                                                    if($allTicketPerTrip->passenger_type==0){
                                                        $adult++;
                                                    }else{
                                                        $concession++;
                                                    }
                                                }
                                                $countPassenger = $adult + $concession;
                                            }
                                        }
                                        $outbound['pass_count'] = $countPassenger;
                                        $totalCountPassengerOut += $countPassenger;
            
                                        $outbound['total_sales'] = $sales;
                                        $totalSalesOut += $sales;
            
                                        $outbound['total_on'] = $countPassenger;
                                        $totalTotalOut += $countPassenger;
                                        
                                        $outbound['adult'] = $adult;
                                        $totalAdultOut += $adult;
                                        
                                        $outbound['concession'] = $concession;
                                        $totalConcessionOut += $concession;
            
                                        $allOutbound[$countOut] = $outbound;
                                        $countOut++;
                                    }
                                    $allTripOut[$routeNameOut] = $allOutbound;
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
                }
            }else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $companyDetails->company_name;
                if(!empty($validatedData['route_id'])) {
                    //ClaimDetails all routes for specific company
                    if($validatedData['route_id']=='All'){
                        $allRouteCompanies = Route::where('company_id',$companyDetails->id)->orderBy('route_number')->get();
            
                        foreach($allRouteCompanies as $allRouteCompany) {
                            foreach($all_dates as $all_date) {
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
                                $allTripIn = [];
                                $allInbound = [];
                                $existInTrip = false;
                                $existOutTrip = false;
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date . '23:59:59');
            
                                //Inbound
                                $allTripInbounds = TripDetail::where('route_id', $allRouteCompany->id)
                                    ->whereBetween('start_trip', [$firstDate,$lastDate])
                                    ->where('trip_code', 1)
                                    ->get();
                                $routeNameIn = implode(" - ", array_reverse(explode(" - ", $allRouteCompany->route_name)));
                                if (count($allTripInbounds) > 0) {
                                    $existInTrip = true;
                                    $countIn = 0;
            
                                    foreach ($allTripInbounds as $allTripInbound) {
                                        $inbound['trip_type'] = "IB";
                                        $firstStage = Stage::where('route_id', $allTripInbound->route_id)->orderby('stage_order','DESC')->first();
                                        $inbound['start_point'] = $firstStage->stage_name;
                                        $inbound['trip_no'] = "T" . $allTripInbound->id;
                                        $inbound['rph_no'] = $allTripInbound->trip_number;
                                        
                                        if($allTripInbound->bus_id != NULL){
                                            $inbound['bus_plate_no'] = $allTripInbound->Bus->bus_registration_number;
                                            $inbound['bus_age'] = $allTripInbound->Bus->bus_age;
                                            //$inbound['bus_age'] = Carbon::parse($allTripInbound->Bus->bus_manufacturing_date)->diff(Carbon::now())->y;
                                        }else{
                                            $inbound['bus_plate_no'] = "No Data";
                                            $inbound['bus_age'] = "No Data";
                                        }
            
                                        $charge = 1.33;
                                        $inbound['charge_km'] = $charge;
            
                                        if($allTripInbound->driver_id != NULL){
                                            $inbound['driver_id'] = $allTripInbound->BusDriver->driver_number;
                                        }else{
                                            $inbound['driver_id'] = "No Data";
                                        }
            
                                        //$busStop = BusStand::where('route_id', $allTripInbound->route_id)->count();
                                        $busStop = Stage::where('route_id', $allTripInbound->route_id)->count();
                                        $inbound['bus_stop_travel'] = $busStop;
                                        $totalBusStopIn += $busStop;
            
                                        $travel = $allTripInbound->Route->inbound_distance;
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
                                        $inbound['travel_BOP'] = $travel;
                                        $inbound['claim_BOP'] = $claim;
            
                                        if($allTripInbound->route_schedule_mstr_id!=NULL){
                                            $inbound['service_start'] = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                            $inbound['service_end'] = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                        }else{
                                            $inbound['service_start'] = "No Data";
                                            $inbound['service_end'] = "No Data";
                                        }
                                        $inbound['start_point_time'] = $inbound['service_start'];
            
                                        $inbound['actual_start'] = date("H:i", strtotime($allTripInbound->start_trip));
                                        $inbound['actual_end'] = date("H:i", strtotime($allTripInbound->end_trip));
            
                                        //Check 1st sales
                                        $firstSales = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)
                                            ->orderby('sales_date')
                                            ->first();
                                        if($firstSales){
                                            $inbound['sales_start'] = date("H:i", strtotime($firstSales->sales_date));
                                        }else{
                                            $inbound['sales_start'] = "No Sales";
                                        }
            
                                        //Check last sales
                                        $lastSales = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)
                                            ->orderby('sales_date', 'DESC')
                                            ->first();
                                        if($lastSales){
                                            $inbound['sales_end'] = date("H:i", strtotime($lastSales->sales_date));
                                        }else{
                                            $inbound['sales_end'] = "No Sales";
                                        }
            
                                        if($inbound['service_start']!="No Data"){
                                            $diff = strtotime($inbound['service_start']) - strtotime($inbound['actual_start']);
                                            if ($diff > 5 || $diff < -5) {
                                                $inbound['punctuality'] = "NOT PUNCTUAL";
                                            }else {
                                                $inbound['punctuality'] = "ONTIME";
                                            }
                                        }else{
                                            $inbound['punctuality'] = "No Data";
                                        }
        
                                        $adult = $allTripInbound->total_adult;
                                        $concession = $allTripInbound->total_concession;
                                        $countPassenger = $adult + $concession;
                                        $sales = $allTripInbound->total_adult_amount + $allTripInbound->total_concession_amount;
                                        //Check tickets
                                        if($countPassenger==0 || $sales==0){
                                            $allTicketPerTrips = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)->get();
                                            if(count($allTicketPerTrips)>0){
                                                $adult = 0;
                                                $concession = 0;
                                                $countPassenger = 0;
                                                $sales = 0;
                                                foreach($allTicketPerTrips as $allTicketPerTrip){
                                                    $sales += $allTicketPerTrip->actual_amount;
                                                    if($allTicketPerTrip->passenger_type==0){
                                                        $adult++;
                                                    }else{
                                                        $concession++;
                                                    }
                                                }
                                                $countPassenger = $adult + $concession;
                                            }
                                        }
                                        $inbound['pass_count'] = $countPassenger;
                                        $totalCountPassengerIn += $countPassenger;
            
                                        $inbound['total_sales'] = $sales;
                                        $totalSalesIn += $sales;
            
                                        $inbound['total_on'] = $countPassenger;
                                        $totalTotalIn += $countPassenger;
            
                                        $inbound['adult'] = $adult;
                                        $totalAdultIn += $adult;
            
                                        $inbound['concession'] = $concession;
                                        $totalConcessionIn += $concession;
            
                                        $allInbound[$countIn] = $inbound;
                                        $countIn++;
                                    }
                                    $allTripIn[$routeNameIn] = $allInbound;
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
                                $allTripOut = [];
                                $allOutbound = [];
                                //Outbound
                                $allTripOutbounds = TripDetail::where('route_id', $allRouteCompany->id)
                                    ->whereBetween('start_trip', [$firstDate,$lastDate])
                                    ->where('trip_code', 0)
                                    ->get();
        
                                $routeNameOut = $allRouteCompany->route_name;
                                if (count($allTripOutbounds) > 0) {
                                    $existOutTrip = true;
                                    $countOut = 0;
            
                                    foreach ($allTripOutbounds as $allTripOutbound) {
                                        $outbound['trip_type'] = "OB";
                                        $firstStage = Stage::where('route_id', $allTripOutbound->route_id)->orderby('stage_order')->first();
                                        $outbound['start_point'] = $firstStage->stage_name;
                                        $outbound['trip_no'] = "T" . $allTripOutbound->id;
                                        $outbound['rph_no'] = $allTripOutbound->trip_number;
            
                                        if($allTripOutbound->bus_id != NULL){
                                            $outbound['bus_plate_no'] = $allTripOutbound->Bus->bus_registration_number;
                                            $outbound['bus_age'] = $allTripOutbound->Bus->bus_age;
                                            //$inbound['bus_age'] = Carbon::parse($allTripOutbound->Bus->bus_manufacturing_date)->diff(Carbon::now())->y;
                                        }else{
                                            $outbound['bus_plate_no']  = "No Data";
                                            $outbound['bus_age'] = "No Data";
                                        }
            
                                        $charge = 1.33;
                                        $outbound['charge_km'] = $charge;
            
                                        if($allTripOutbound->driver_id != NULL){
                                            $outbound['driver_id'] = $allTripOutbound->BusDriver->driver_number;
                                        }else{
                                            $outbound['driver_id'] = "No Data";
                                        }
            
                                        //$busStop = BusStand::where('route_id', $allTripOutbound->route_id)->count();
                                        $busStop = Stage::where('route_id', $allTripOutbound->route_id)->count();
                                        $outbound['bus_stop_travel'] = $busStop;
                                        $totalBusStopOut += $busStop;
            
                                        $travel = $allTripOutbound->Route->inbound_distance;
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
                                        $outbound['travel_BOP'] = $travel;
                                        $outbound['claim_BOP'] = $claim;
            
                                        if($allTripOutbound->route_schedule_mstr_id!=NULL){
                                            $outbound['service_start'] = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                            $outbound['service_end'] = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                        }else{
                                            $outbound['service_start'] = "No Data";
                                            $outbound['service_end'] = "No Data";
                                        }
                                        $outbound['start_point_time'] = $outbound['service_start'];
            
                                        $outbound['actual_start'] = date("H:i", strtotime($allTripOutbound->start_trip));
                                        $outbound['actual_end'] = date("H:i", strtotime($allTripOutbound->end_trip));
            
                                        //Check 1st sales
                                        $firstSales = TicketSalesTransaction::where('trip_number', $allTripOutbound->trip_number)
                                            ->orderby('sales_date')
                                            ->first();
                                        if($firstSales){
                                            $outbound['sales_start'] = date("H:i", strtotime($firstSales->sales_date));
                                        }else{
                                            $outbound['sales_start'] = "No Sales";
                                        }
            
                                        //Check last sales
                                        $lastSales = TicketSalesTransaction::where('trip_number', $allTripOutbound->trip_number)
                                            ->orderby('sales_date', 'DESC')
                                            ->first();
                                        if($lastSales){
                                            $outbound['sales_end'] = date("H:i", strtotime($lastSales->sales_date));
                                        }else{
                                            $outbound['sales_end'] = "No Sales";
                                        }
            
                                        if($outbound['service_start']!="No Data"){
                                            $diff = strtotime($outbound['service_start']) - strtotime($outbound['actual_start']);
                                            if ($diff > 5 || $diff < -5) {
                                                $outbound['punctuality'] = "NOT PUNCTUAL";
                                            } else {
                                                $outbound['punctuality'] = "ONTIME";
                                            }
                                        }else{
                                            $outbound['punctuality'] = "No Data";
                                        }
        
                                        $adult = $allTripOutbound->total_adult;
                                        $concession = $allTripOutbound->total_concession;
                                        $countPassenger = $adult + $concession;
                                        $sales = $allTripOutbound->total_adult_amount + $allTripOutbound->total_concession_amount;
                                        //Check tickets
                                        if($countPassenger==0 || $sales==0){
                                            $allTicketPerTrips = TicketSalesTransaction::where('trip_number', $allTripOutbound->trip_number)->get();
                                            if(count($allTicketPerTrips)>0){
                                                $adult = 0;
                                                $concession = 0;
                                                $countPassenger = 0;
                                                $sales = 0;
                                                foreach($allTicketPerTrips as $allTicketPerTrip){
                                                    $sales += $allTicketPerTrip->actual_amount;
                                                    if($allTicketPerTrip->passenger_type==0){
                                                        $adult++;
                                                    }else{
                                                        $concession++;
                                                    }
                                                }
                                                $countPassenger = $adult + $concession;
                                            }
                                        }
                                        $outbound['pass_count'] = $countPassenger;
                                        $totalCountPassengerOut += $countPassenger;
            
                                        $outbound['total_sales'] = $sales;
                                        $totalSalesOut += $sales;
            
                                        $outbound['total_on'] = $countPassenger;
                                        $totalTotalOut += $countPassenger;
                                        
                                        $outbound['adult'] = $adult;
                                        $totalAdultOut += $adult;
                                        
                                        $outbound['concession'] = $concession;
                                        $totalConcessionOut += $concession;
            
                                        $allOutbound[$countOut] = $outbound;
                                        $countOut++;
                                    }
                                    $allTripOut[$routeNameOut] = $allOutbound;
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
                    //ClaimDetails specific routes for specific company
                    else{
                        $selectedRoute = Route::where('id', $this->state['route_id'])->first();
        
                        if($selectedRoute){
                            foreach($all_dates as $all_date) {
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
                                $allTripIn = [];
                                $allInbound = [];
                                $existInTrip = false;
                                $existOutTrip = false;
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date . '23:59:59');
            
                                //Inbound
                                $allTripInbounds = TripDetail::where('route_id', $selectedRoute->id)
                                    ->whereBetween('start_trip', [$firstDate,$lastDate])
                                    ->where('trip_code', 1)
                                    ->get();
                                $routeNameIn = implode(" - ", array_reverse(explode(" - ", $selectedRoute->route_name)));
                                if (count($allTripInbounds) > 0) {
                                    $existInTrip = true;
                                    $countIn = 0;
            
                                    foreach ($allTripInbounds as $allTripInbound) {
                                        $inbound['trip_type'] = "IB";
                                        $firstStage = Stage::where('route_id', $allTripInbound->route_id)->orderby('stage_order', 'DESC')->first();
                                        $inbound['start_point'] = $firstStage->stage_name;
                                        $inbound['trip_no'] = "T" . $allTripInbound->id;
                                        $inbound['rph_no'] = $allTripInbound->trip_number;
                                        
                                        if($allTripInbound->bus_id != NULL){
                                            $inbound['bus_plate_no'] = $allTripInbound->Bus->bus_registration_number;
                                            $inbound['bus_age'] = $allTripInbound->Bus->bus_age;
                                            //$inbound['bus_age'] = Carbon::parse($allTripInbound->Bus->bus_manufacturing_date)->diff(Carbon::now())->y;
                                        }else{
                                            $inbound['bus_plate_no'] = "No Data";
                                            $inbound['bus_age'] = "No Data";
                                        }
            
                                        $charge = 1.33;
                                        $inbound['charge_km'] = $charge;
            
                                        if($allTripInbound->driver_id != NULL){
                                            $inbound['driver_id'] = $allTripInbound->BusDriver->driver_number;
                                        }else{
                                            $inbound['driver_id'] = "No Data";
                                        }
            
                                        //$busStop = BusStand::where('route_id', $allTripInbound->route_id)->count();
                                        $busStop = Stage::where('route_id', $allTripInbound->route_id)->count();
                                        $inbound['bus_stop_travel'] = $busStop;
                                        $totalBusStopIn += $busStop;
            
                                        $travel = $allTripInbound->Route->inbound_distance;
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
                                        $inbound['travel_BOP'] = $travel;
                                        $inbound['claim_BOP'] = $claim;
            
                                        if($allTripInbound->route_schedule_mstr_id!=NULL){
                                            $inbound['service_start'] = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                            $inbound['service_end'] = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                        }else{
                                            $inbound['service_start'] = "No Data";
                                            $inbound['service_end'] = "No Data";
                                        }
                                        $inbound['start_point_time'] = $inbound['service_start'];
            
                                        $inbound['actual_start'] = date("H:i", strtotime($allTripInbound->start_trip));
                                        $inbound['actual_end'] = date("H:i", strtotime($allTripInbound->end_trip));
            
                                        //Check 1st sales
                                        $firstSales = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)
                                            ->orderby('sales_date')
                                            ->first();
                                        if($firstSales){
                                            $inbound['sales_start'] = date("H:i", strtotime($firstSales->sales_date));
                                        }else{
                                            $inbound['sales_start'] = "No Sales";
                                        }
            
                                        //Check last sales
                                        $lastSales = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)
                                            ->orderby('sales_date', 'DESC')
                                            ->first();
                                        if($lastSales){
                                            $inbound['sales_end'] = date("H:i", strtotime($lastSales->sales_date));
                                        }else{
                                            $inbound['sales_end'] = "No Sales";
                                        }
            
                                        if($inbound['service_start']!="No Data"){
                                            $diff = strtotime($inbound['service_start']) - strtotime($inbound['actual_start']);
                                            if ($diff > 5 || $diff < -5) {
                                                $inbound['punctuality'] = "NOT PUNCTUAL";
                                            }else {
                                                $inbound['punctuality'] = "ONTIME";
                                            }
                                        }else{
                                            $inbound['punctuality'] = "No Data";
                                        }
        
                                        $adult = $allTripInbound->total_adult;
                                        $concession = $allTripInbound->total_concession;
                                        $countPassenger = $adult + $concession;
                                        $sales = $allTripInbound->total_adult_amount + $allTripInbound->total_concession_amount;
                                        //Check tickets
                                        if($countPassenger==0 || $sales==0){
                                            $allTicketPerTrips = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)->get();
                                            if(count($allTicketPerTrips)>0){
                                                $adult = 0;
                                                $concession = 0;
                                                $countPassenger = 0;
                                                $sales = 0;
                                                foreach($allTicketPerTrips as $allTicketPerTrip){
                                                    $sales += $allTicketPerTrip->actual_amount;
                                                    if($allTicketPerTrip->passenger_type==0){
                                                        $adult++;
                                                    }else{
                                                        $concession++;
                                                    }
                                                }
                                                $countPassenger = $adult + $concession;
                                            }
                                        }
                                        $inbound['pass_count'] = $countPassenger;
                                        $totalCountPassengerIn += $countPassenger;
            
                                        $inbound['total_sales'] = $sales;
                                        $totalSalesIn += $sales;
            
                                        $inbound['total_on'] = $countPassenger;
                                        $totalTotalIn += $countPassenger;
            
                                        $inbound['adult'] = $adult;
                                        $totalAdultIn += $adult;
            
                                        $inbound['concession'] = $concession;
                                        $totalConcessionIn += $concession;
            
                                        $allInbound[$countIn] = $inbound;
                                        $countIn++;
                                    }
                                    $allTripIn[$routeNameIn] = $allInbound;
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
                                $allTripOut = [];
                                $allOutbound = [];
                                //Outbound
                                $allTripOutbounds = TripDetail::where('route_id', $selectedRoute->id)
                                    ->whereBetween('start_trip', [$firstDate,$lastDate])
                                    ->where('trip_code', 0)
                                    ->get();
                                $routeNameOut = $selectedRoute->route_name;
                                if (count($allTripOutbounds) > 0) {
                                    $existOutTrip = true;
                                    $countOut = 0;
            
                                    foreach ($allTripOutbounds as $allTripOutbound) {
                                        $outbound['trip_type'] = "OB";
                                        $firstStage = Stage::where('route_id', $allTripOutbound->route_id)->orderby('stage_order')->first();
                                        $outbound['start_point'] = $firstStage->stage_name;
                                        $outbound['trip_no'] = "T" . $allTripOutbound->id;
                                        $outbound['rph_no'] = $allTripOutbound->trip_number;
            
                                        if($allTripOutbound->bus_id != NULL){
                                            $outbound['bus_plate_no'] = $allTripOutbound->Bus->bus_registration_number;
                                            $outbound['bus_age'] = $allTripOutbound->Bus->bus_age;
                                            //$inbound['bus_age'] = Carbon::parse($allTripOutbound->Bus->bus_manufacturing_date)->diff(Carbon::now())->y;
                                        }else{
                                            $outbound['bus_plate_no']  = "No Data";
                                            $outbound['bus_age'] = "No Data";
                                        }
            
                                        $charge = 1.33;
                                        $outbound['charge_km'] = $charge;
            
                                        if($allTripOutbound->driver_id != NULL){
                                            $outbound['driver_id'] = $allTripOutbound->BusDriver->driver_number;
                                        }else{
                                            $outbound['driver_id'] = "No Data";
                                        }
            
                                        //$busStop = BusStand::where('route_id', $allTripOutbound->route_id)->count();
                                        $busStop = Stage::where('route_id', $allTripOutbound->route_id)->count();
                                        $outbound['bus_stop_travel'] = $busStop;
                                        $totalBusStopOut += $busStop;
            
                                        $travel = $allTripOutbound->Route->inbound_distance;
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
                                        $outbound['travel_BOP'] = $travel;
                                        $outbound['claim_BOP'] = $claim;
            
                                        if($allTripOutbound->route_schedule_mstr_id!=NULL){
                                            $outbound['service_start'] = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                            $outbound['service_end'] = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                        }else{
                                            $outbound['service_start'] = "No Data";
                                            $outbound['service_end'] = "No Data";
                                        }
                                        $outbound['start_point_time'] = $outbound['service_start'];
            
                                        $outbound['actual_start'] = date("H:i", strtotime($allTripOutbound->start_trip));
                                        $outbound['actual_end'] = date("H:i", strtotime($allTripOutbound->end_trip));
            
                                        //Check 1st sales
                                        $firstSales = TicketSalesTransaction::where('trip_number',$allTripOutbound->trip_number)
                                            ->orderby('sales_date')
                                            ->first();
                                        if($firstSales){
                                            $outbound['sales_start'] = date("H:i", strtotime($firstSales->sales_date));
                                        }else{
                                            $outbound['sales_start'] = "No Sales";
                                        }
            
                                        //Check last sales
                                        $lastSales = TicketSalesTransaction::where('trip_number',$allTripOutbound->trip_number)
                                            ->orderby('sales_date', 'DESC')
                                            ->first();
                                        if($lastSales){
                                            $outbound['sales_end'] = date("H:i", strtotime($lastSales->sales_date));
                                        }else{
                                            $outbound['sales_end'] = "No Sales";
                                        }
            
                                        if($outbound['service_start']!="No Data"){
                                            $diff = strtotime($outbound['service_start']) - strtotime($outbound['actual_start']);
                                            if ($diff > 5 || $diff < -5) {
                                                $outbound['punctuality'] = "NOT PUNCTUAL";
                                            } else {
                                                $outbound['punctuality'] = "ONTIME";
                                            }
                                        }else{
                                            $outbound['punctuality'] = "No Data";
                                        }
        
                                        $adult = $allTripOutbound->total_adult;
                                        $concession = $allTripOutbound->total_concession;
                                        $countPassenger = $adult + $concession;
                                        $sales = $allTripOutbound->total_adult_amount + $allTripOutbound->total_concession_amount;
                                        //Check tickets
                                        if($countPassenger==0 || $sales==0){
                                            $allTicketPerTrips = TicketSalesTransaction::where('trip_number',$allTripOutbound->trip_number)->get();
                                            if(count($allTicketPerTrips)>0){
                                                $adult = 0;
                                                $concession = 0;
                                                $countPassenger = 0;
                                                $sales = 0;
                                                foreach($allTicketPerTrips as $allTicketPerTrip){
                                                    $sales += $allTicketPerTrip->actual_amount;
                                                    if($allTicketPerTrip->passenger_type==0){
                                                        $adult++;
                                                    }else{
                                                        $concession++;
                                                    }
                                                }
                                                $countPassenger = $adult + $concession;
                                            }
                                        }
                                        $outbound['pass_count'] = $countPassenger;
                                        $totalCountPassengerOut += $countPassenger;
            
                                        $outbound['total_sales'] = $sales;
                                        $totalSalesOut += $sales;
            
                                        $outbound['total_on'] = $countPassenger;
                                        $totalTotalOut += $countPassenger;
                                        
                                        $outbound['adult'] = $adult;
                                        $totalAdultOut += $adult;
                                        
                                        $outbound['concession'] = $concession;
                                        $totalConcessionOut += $concession;
            
                                        $allOutbound[$countOut] = $outbound;
                                        $countOut++;
                                    }
                                    $allTripOut[$routeNameOut] = $allOutbound;
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
            }
            return Excel::download(new SPADClaimDetails($all_dates, $claimDetails, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'ClaimDetails_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printClaimDetailGPS()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printClaimDetails())");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

        if ($this->selectedCompany){
            $this->dispatchBrowserEvent('new-tab-gps',['dateFrom' => $validatedData['dateFrom'] , 'dateTo' => $validatedData['dateTo'] , 'routeID' => $validatedData['route_id'], 'companyID' => $this->selectedCompany]);

            //Redirect::to(route('viewClaimDetails', ['dateFrom' => $validatedData['dateFrom'] , 'dateTo' => $validatedData['dateTo'] , 'routeID' => $validatedData['route_id'], 'companyID' => $this->selectedCompany]));
            //return redirect()->route('viewClaimDetails', ['dateFrom' => $validatedData['dateFrom'] , 'dateTo' => $validatedData['dateTo'] , 'routeID' => $validatedData['route_id'], 'companyID' => $this->selectedCompany]);
        }else{
            $this->dispatchBrowserEvent('company-required');
        }

        /*$startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();
            $startDate->addDay();
        }

        $claimDetails = collect();
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
        if ($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                if(!empty($validatedData['route_id'])) {
                    //ClaimDetails all routes for all company
                    if($validatedData['route_id']=='All'){
                        $allRoutes = Route::all();
            
                        foreach($allRoutes as $allRoute) {
                            foreach($all_dates as $all_date) {
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
                                $allTripIn = [];
                                $allInbound = [];
                                $existInTrip = false;
                                $existOutTrip = false;
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date . '23:59:59');
            
                                //Inbound
                                $allTripInbounds = TripDetail::where('route_id', $allRoute->id)
                                    ->whereBetween('start_trip', [$firstDate,$lastDate])
                                    ->where('trip_code', 1)
                                    ->get();
            
                                $routeNameIn = $allRoute->route_name;
                                if (count($allTripInbounds) > 0) {
                                    $existInTrip = true;
                                    $countIn = 0;
            
                                    foreach ($allTripInbounds as $allTripInbound) {
                                        $inbound['trip_type'] = "IB";
                                        $firstStage = Stage::where('route_id', $allTripInbound->route_id)->orderby('stage_order')->first();
                                        $inbound['start_point'] = $firstStage->stage_name;
                                        $inbound['trip_no'] = "T" . $allTripInbound->id;
                                        $inbound['rph_no'] = $allTripInbound->trip_number;
                                        
                                        if($allTripInbound->bus_id != NULL){
                                            $inbound['bus_plate_no'] = $allTripInbound->Bus->bus_registration_number;
                                            $inbound['bus_age'] = $allTripInbound->Bus->bus_age;
                                            //$inbound['bus_age'] = Carbon::parse($allTripInbound->Bus->bus_manufacturing_date)->diff(Carbon::now())->y;
                                        }else{
                                            $inbound['bus_plate_no'] = "No Data";
                                            $inbound['bus_age'] = "No Data";
                                        }
            
                                        $charge = 1.33;
                                        $inbound['charge_km'] = $charge;
            
                                        if($allTripInbound->driver_id != NULL){
                                            $inbound['driver_id'] = $allTripInbound->BusDriver->driver_number;
                                        }else{
                                            $inbound['driver_id'] = "No Data";
                                        }
            
                                        //$busStop = BusStand::where('route_id', $allTripInbound->route_id)->count();
                                        $busStop = Stage::where('route_id', $allTripInbound->route_id)->count();
                                        $inbound['bus_stop_travel'] = $busStop;
                                        $totalBusStopIn += $busStop;
            
                                        $travel = $allTripInbound->Route->inbound_distance;
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
                                        $inbound['travel_BOP'] = $travel;
                                        $inbound['claim_BOP'] = $claim;
            
                                        if($allTripInbound->route_schedule_mstr_id!=NULL){
                                            $inbound['service_start'] = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                            $inbound['service_end'] = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                        }else{
                                            $inbound['service_start'] = "No Data";
                                            $inbound['service_end'] = "No Data";
                                        }
                                        $inbound['start_point_time'] = $inbound['service_start'];
            
                                        $inbound['actual_start'] = date("H:i", strtotime($allTripInbound->start_trip));
                                        $inbound['actual_end'] = date("H:i", strtotime($allTripInbound->end_trip));
            
                                        //Check 1st sales
                                        $firstSales = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)
                                            ->orderby('sales_date')
                                            ->first();
                                        if($firstSales){
                                            $inbound['sales_start'] = date("H:i", strtotime($firstSales->sales_date));
                                        }else{
                                            $inbound['sales_start'] = "No Sales";
                                        }
            
                                        //Check last sales
                                        $lastSales = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)
                                            ->orderby('sales_date', 'DESC')
                                            ->first();
                                        if($lastSales){
                                            $inbound['sales_end'] = date("H:i", strtotime($lastSales->sales_date));
                                        }else{
                                            $inbound['sales_end'] = "No Sales";
                                        }
            
                                        if($inbound['service_start']!="No Data"){
                                            $diff = strtotime($inbound['service_start']) - strtotime($inbound['actual_start']);
                                            if ($diff > 5 || $diff < -5) {
                                                $inbound['punctuality'] = "NOT PUNCTUAL";
                                            }else {
                                                $inbound['punctuality'] = "ONTIME";
                                            }
                                        }else{
                                            $inbound['punctuality'] = "No Data";
                                        }
            
                                        $adult = $allTripInbound->total_adult;
                                        $concession = $allTripInbound->total_concession;
                                        $countPassenger = $adult + $concession;
                                        $sales = $allTripInbound->total_adult_amount + $allTripInbound->total_concession_amount;
                                        //Check tickets
                                        if($countPassenger==0 || $sales==0){
                                            $allTicketPerTrips = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)->get();
                                            if(count($allTicketPerTrips)>0){
                                                $adult = 0;
                                                $concession = 0;
                                                $countPassenger = 0;
                                                $sales = 0;
                                                foreach($allTicketPerTrips as $allTicketPerTrip){
                                                    $sales += $allTicketPerTrip->actual_amount;
                                                    if($allTicketPerTrip->passenger_type==0){
                                                        $adult++;
                                                    }else{
                                                        $concession++;
                                                    }
                                                }
                                                $countPassenger = $adult + $concession;
                                            }
                                        }
                                        $inbound['pass_count'] = $countPassenger;
                                        $totalCountPassengerIn += $countPassenger;
            
                                        $inbound['total_sales'] = $sales;
                                        $totalSalesIn += $sales;
            
                                        $inbound['total_on'] = $countPassenger;
                                        $totalTotalIn += $countPassenger;
            
                                        $inbound['adult'] = $adult;
                                        $totalAdultIn += $adult;
            
                                        $inbound['concession'] = $concession;
                                        $totalConcessionIn += $concession;
            
                                        $allInbound[$countIn] = $inbound;
                                        $countIn++;
                                    }
                                    $allTripIn[$routeNameIn] = $allInbound;
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
                                $allTripOut = [];
                                $allOutbound = [];
                                //Outbound
                                $allTripOutbounds = TripDetail::where('route_id', $allRoute->id)
                                    ->whereBetween('start_trip', [$firstDate,$lastDate])
                                    ->where('trip_code', 0)
                                    ->get();
            
                                $routeNameOut = implode(" - ", array_reverse(explode(" - ", $routeNameIn)));
                                if (count($allTripOutbounds) > 0) {
                                    $existOutTrip = true;
                                    $countOut = 0;
            
                                    foreach ($allTripOutbounds as $allTripOutbound) {
                                        $outbound['trip_type'] = "OB";
                                        $firstStage = Stage::where('route_id', $allTripOutbound->route_id)->orderby('stage_order', 'DESC')->first();
                                        $outbound['start_point'] = $firstStage->stage_name;
                                        $outbound['trip_no'] = "T" . $allTripOutbound->id;
                                        $outbound['rph_no'] = $allTripOutbound->trip_number;
            
                                        if($allTripOutbound->bus_id != NULL){
                                            $outbound['bus_plate_no'] = $allTripOutbound->Bus->bus_registration_number;
                                            $outbound['bus_age'] = $allTripOutbound->Bus->bus_age;
                                            //$inbound['bus_age'] = Carbon::parse($allTripOutbound->Bus->bus_manufacturing_date)->diff(Carbon::now())->y;
                                        }else{
                                            $outbound['bus_plate_no']  = "No Data";
                                            $outbound['bus_age'] = "No Data";
                                        }
            
                                        $charge = 1.33;
                                        $outbound['charge_km'] = $charge;
            
                                        if($allTripOutbound->driver_id != NULL){
                                            $outbound['driver_id'] = $allTripOutbound->BusDriver->driver_number;
                                        }else{
                                            $outbound['driver_id'] = "No Data";
                                        }
            
                                        //$busStop = BusStand::where('route_id', $allTripOutbound->route_id)->count();
                                        $busStop = Stage::where('route_id', $allTripOutbound->route_id)->count();
                                        $outbound['bus_stop_travel'] = $busStop;
                                        $totalBusStopOut += $busStop;
            
                                        $travel = $allTripOutbound->Route->inbound_distance;
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
                                        $outbound['travel_BOP'] = $travel;
                                        $outbound['claim_BOP'] = $claim;
            
                                        if($allTripOutbound->route_schedule_mstr_id!=NULL){
                                            $outbound['service_start'] = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                            $outbound['service_end'] = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                        }else{
                                            $outbound['service_start'] = "No Data";
                                            $outbound['service_end'] = "No Data";
                                        }
                                        $outbound['start_point_time'] = $outbound['service_start'];
            
                                        $outbound['actual_start'] = date("H:i", strtotime($allTripOutbound->start_trip));
                                        $outbound['actual_end'] = date("H:i", strtotime($allTripOutbound->end_trip));
            
                                        //Check 1st sales
                                        $firstSales = TicketSalesTransaction::where('trip_number', $allTripOutbound->trip_number)
                                            ->orderby('sales_date')
                                            ->first();
                                        if($firstSales){
                                            $outbound['sales_start'] = date("H:i", strtotime($firstSales->sales_date));
                                        }else{
                                            $outbound['sales_start'] = "No Sales";
                                        }
            
                                        //Check last sales
                                        $lastSales = TicketSalesTransaction::where('trip_number', $allTripOutbound->trip_number)
                                            ->orderby('sales_date', 'DESC')
                                            ->first();
                                        if($lastSales){
                                            $outbound['sales_end'] = date("H:i", strtotime($lastSales->sales_date));
                                        }else{
                                            $outbound['sales_end'] = "No Sales";
                                        }
            
                                        if($outbound['service_start']!="No Data"){
                                            $diff = strtotime($outbound['service_start']) - strtotime($outbound['actual_start']);
                                            if ($diff > 5 || $diff < -5) {
                                                $outbound['punctuality'] = "NOT PUNCTUAL";
                                            } else {
                                                $outbound['punctuality'] = "ONTIME";
                                            }
                                        }else{
                                            $outbound['punctuality'] = "No Data";
                                        }
            
                                        $adult = $allTripOutbound->total_adult;
                                        $concession = $allTripOutbound->total_concession;
                                        $countPassenger = $adult + $concession;
                                        $sales = $allTripOutbound->total_adult_amount + $allTripOutbound->total_concession_amount;
                                        //Check tickets
                                        if($countPassenger==0 || $sales==0){
                                            $allTicketPerTrips = TicketSalesTransaction::where('trip_number', $allTripOutbound->trip_number)->get();
                                            if(count($allTicketPerTrips)>0){
                                                $adult = 0;
                                                $concession = 0;
                                                $countPassenger = 0;
                                                $sales = 0;
                                                foreach($allTicketPerTrips as $allTicketPerTrip){
                                                    $sales += $allTicketPerTrip->actual_amount;
                                                    if($allTicketPerTrip->passenger_type==0){
                                                        $adult++;
                                                    }else{
                                                        $concession++;
                                                    }
                                                }
                                                $countPassenger = $adult + $concession;
                                            }
                                        }
                                        $outbound['pass_count'] = $countPassenger;
                                        $totalCountPassengerOut += $countPassenger;
            
                                        $outbound['total_sales'] = $sales;
                                        $totalSalesOut += $sales;
            
                                        $outbound['total_on'] = $countPassenger;
                                        $totalTotalOut += $countPassenger;
                                        
                                        $outbound['adult'] = $adult;
                                        $totalAdultOut += $adult;
                                        
                                        $outbound['concession'] = $concession;
                                        $totalConcessionOut += $concession;
            
                                        $allOutbound[$countOut] = $outbound;
                                        $countOut++;
                                    }
                                    $allTripOut[$routeNameOut] = $allOutbound;
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
                }
            }else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $companyDetails->company_name;
                if(!empty($validatedData['route_id'])) {
                    //ClaimDetails all routes for specific company
                    if($validatedData['route_id']=='All'){
                        $allRouteCompanies = Route::where('company_id',$companyDetails->id)->get();
            
                        foreach($allRouteCompanies as $allRouteCompany) {
                            foreach($all_dates as $all_date) {
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
                                $allTripIn = [];
                                $allInbound = [];
                                $existInTrip = false;
                                $existOutTrip = false;
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date . '23:59:59');
            
                                //Inbound
                                $allTripInbounds = TripDetail::where('route_id', $allRouteCompany->id)
                                    ->whereBetween('start_trip', [$firstDate,$lastDate])
                                    ->where('trip_code', 1)
                                    ->get();
                                $routeNameIn = $allRouteCompany->route_name;
                                if (count($allTripInbounds) > 0) {
                                    $existInTrip = true;
                                    $countIn = 0;
            
                                    foreach ($allTripInbounds as $allTripInbound) {
                                        $inbound['trip_type'] = "IB";
                                        $firstStage = Stage::where('route_id', $allTripInbound->route_id)->orderby('stage_order')->first();
                                        $inbound['start_point'] = $firstStage->stage_name;
                                        $inbound['trip_no'] = "T" . $allTripInbound->id;
                                        $inbound['rph_no'] = $allTripInbound->trip_number;
                                        
                                        if($allTripInbound->bus_id != NULL){
                                            $inbound['bus_plate_no'] = $allTripInbound->Bus->bus_registration_number;
                                            $inbound['bus_age'] = $allTripInbound->Bus->bus_age;
                                            //$inbound['bus_age'] = Carbon::parse($allTripInbound->Bus->bus_manufacturing_date)->diff(Carbon::now())->y;
                                        }else{
                                            $inbound['bus_plate_no'] = "No Data";
                                            $inbound['bus_age'] = "No Data";
                                        }
            
                                        $charge = 1.33;
                                        $inbound['charge_km'] = $charge;
            
                                        if($allTripInbound->driver_id != NULL){
                                            $inbound['driver_id'] = $allTripInbound->BusDriver->driver_number;
                                        }else{
                                            $inbound['driver_id'] = "No Data";
                                        }
            
                                        //$busStop = BusStand::where('route_id', $allTripInbound->route_id)->count();
                                        $busStop = Stage::where('route_id', $allTripInbound->route_id)->count();
                                        $inbound['bus_stop_travel'] = $busStop;
                                        $totalBusStopIn += $busStop;
            
                                        $travel = $allTripInbound->Route->inbound_distance;
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
                                        $inbound['travel_BOP'] = $travel;
                                        $inbound['claim_BOP'] = $claim;
            
                                        if($allTripInbound->route_schedule_mstr_id!=NULL){
                                            $inbound['service_start'] = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                            $inbound['service_end'] = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                        }else{
                                            $inbound['service_start'] = "No Data";
                                            $inbound['service_end'] = "No Data";
                                        }
                                        $inbound['start_point_time'] = $inbound['service_start'];
            
                                        $inbound['actual_start'] = date("H:i", strtotime($allTripInbound->start_trip));
                                        $inbound['actual_end'] = date("H:i", strtotime($allTripInbound->end_trip));
            
                                        //Check 1st sales
                                        $firstSales = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)
                                            ->orderby('sales_date')
                                            ->first();
                                        if($firstSales){
                                            $inbound['sales_start'] = date("H:i", strtotime($firstSales->sales_date));
                                        }else{
                                            $inbound['sales_start'] = "No Sales";
                                        }
            
                                        //Check last sales
                                        $lastSales = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)
                                            ->orderby('sales_date', 'DESC')
                                            ->first();
                                        if($lastSales){
                                            $inbound['sales_end'] = date("H:i", strtotime($lastSales->sales_date));
                                        }else{
                                            $inbound['sales_end'] = "No Sales";
                                        }
            
                                        if($inbound['service_start']!="No Data"){
                                            $diff = strtotime($inbound['service_start']) - strtotime($inbound['actual_start']);
                                            if ($diff > 5 || $diff < -5) {
                                                $inbound['punctuality'] = "NOT PUNCTUAL";
                                            }else {
                                                $inbound['punctuality'] = "ONTIME";
                                            }
                                        }else{
                                            $inbound['punctuality'] = "No Data";
                                        }
        
                                        $adult = $allTripInbound->total_adult;
                                        $concession = $allTripInbound->total_concession;
                                        $countPassenger = $adult + $concession;
                                        $sales = $allTripInbound->total_adult_amount + $allTripInbound->total_concession_amount;
                                        //Check tickets
                                        if($countPassenger==0 || $sales==0){
                                            $allTicketPerTrips = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)->get();
                                            if(count($allTicketPerTrips)>0){
                                                $adult = 0;
                                                $concession = 0;
                                                $countPassenger = 0;
                                                $sales = 0;
                                                foreach($allTicketPerTrips as $allTicketPerTrip){
                                                    $sales += $allTicketPerTrip->actual_amount;
                                                    if($allTicketPerTrip->passenger_type==0){
                                                        $adult++;
                                                    }else{
                                                        $concession++;
                                                    }
                                                }
                                                $countPassenger = $adult + $concession;
                                            }
                                        }
                                        $inbound['pass_count'] = $countPassenger;
                                        $totalCountPassengerIn += $countPassenger;
            
                                        $inbound['total_sales'] = $sales;
                                        $totalSalesIn += $sales;
            
                                        $inbound['total_on'] = $countPassenger;
                                        $totalTotalIn += $countPassenger;
            
                                        $inbound['adult'] = $adult;
                                        $totalAdultIn += $adult;
            
                                        $inbound['concession'] = $concession;
                                        $totalConcessionIn += $concession;
            
                                        $allInbound[$countIn] = $inbound;
                                        $countIn++;
                                    }
                                    $allTripIn[$routeNameIn] = $allInbound;
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
                                $allTripOut = [];
                                $allOutbound = [];
                                //Outbound
                                $allTripOutbounds = TripDetail::where('route_id', $allRouteCompany->id)
                                    ->whereBetween('start_trip', [$firstDate,$lastDate])
                                    ->where('trip_code', 0)
                                    ->get();
        
                                $routeNameOut = implode(" - ", array_reverse(explode(" - ", $routeNameIn)));
                                if (count($allTripOutbounds) > 0) {
                                    $existOutTrip = true;
                                    $countOut = 0;
            
                                    foreach ($allTripOutbounds as $allTripOutbound) {
                                        $outbound['trip_type'] = "OB";
                                        $firstStage = Stage::where('route_id', $allTripOutbound->route_id)->orderby('stage_order', 'DESC')->first();
                                        $outbound['start_point'] = $firstStage->stage_name;
                                        $outbound['trip_no'] = "T" . $allTripOutbound->id;
                                        $outbound['rph_no'] = $allTripOutbound->trip_number;
            
                                        if($allTripOutbound->bus_id != NULL){
                                            $outbound['bus_plate_no'] = $allTripOutbound->Bus->bus_registration_number;
                                            $outbound['bus_age'] = $allTripOutbound->Bus->bus_age;
                                            //$inbound['bus_age'] = Carbon::parse($allTripOutbound->Bus->bus_manufacturing_date)->diff(Carbon::now())->y;
                                        }else{
                                            $outbound['bus_plate_no']  = "No Data";
                                            $outbound['bus_age'] = "No Data";
                                        }
            
                                        $charge = 1.33;
                                        $outbound['charge_km'] = $charge;
            
                                        if($allTripOutbound->driver_id != NULL){
                                            $outbound['driver_id'] = $allTripOutbound->BusDriver->driver_number;
                                        }else{
                                            $outbound['driver_id'] = "No Data";
                                        }
            
                                        //$busStop = BusStand::where('route_id', $allTripOutbound->route_id)->count();
                                        $busStop = Stage::where('route_id', $allTripOutbound->route_id)->count();
                                        $outbound['bus_stop_travel'] = $busStop;
                                        $totalBusStopOut += $busStop;
            
                                        $travel = $allTripOutbound->Route->inbound_distance;
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
                                        $outbound['travel_BOP'] = $travel;
                                        $outbound['claim_BOP'] = $claim;
            
                                        if($allTripOutbound->route_schedule_mstr_id!=NULL){
                                            $outbound['service_start'] = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                            $outbound['service_end'] = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                        }else{
                                            $outbound['service_start'] = "No Data";
                                            $outbound['service_end'] = "No Data";
                                        }
                                        $outbound['start_point_time'] = $outbound['service_start'];
            
                                        $outbound['actual_start'] = date("H:i", strtotime($allTripOutbound->start_trip));
                                        $outbound['actual_end'] = date("H:i", strtotime($allTripOutbound->end_trip));
            
                                        //Check 1st sales
                                        $firstSales = TicketSalesTransaction::where('trip_number', $allTripOutbound->trip_number)
                                            ->orderby('sales_date')
                                            ->first();
                                        if($firstSales){
                                            $outbound['sales_start'] = date("H:i", strtotime($firstSales->sales_date));
                                        }else{
                                            $outbound['sales_start'] = "No Sales";
                                        }
            
                                        //Check last sales
                                        $lastSales = TicketSalesTransaction::where('trip_number', $allTripOutbound->trip_number)
                                            ->orderby('sales_date', 'DESC')
                                            ->first();
                                        if($lastSales){
                                            $outbound['sales_end'] = date("H:i", strtotime($lastSales->sales_date));
                                        }else{
                                            $outbound['sales_end'] = "No Sales";
                                        }
            
                                        if($outbound['service_start']!="No Data"){
                                            $diff = strtotime($outbound['service_start']) - strtotime($outbound['actual_start']);
                                            if ($diff > 5 || $diff < -5) {
                                                $outbound['punctuality'] = "NOT PUNCTUAL";
                                            } else {
                                                $outbound['punctuality'] = "ONTIME";
                                            }
                                        }else{
                                            $outbound['punctuality'] = "No Data";
                                        }
        
                                        $adult = $allTripOutbound->total_adult;
                                        $concession = $allTripOutbound->total_concession;
                                        $countPassenger = $adult + $concession;
                                        $sales = $allTripOutbound->total_adult_amount + $allTripOutbound->total_concession_amount;
                                        //Check tickets
                                        if($countPassenger==0 || $sales==0){
                                            $allTicketPerTrips = TicketSalesTransaction::where('trip_number', $allTripOutbound->trip_number)->get();
                                            if(count($allTicketPerTrips)>0){
                                                $adult = 0;
                                                $concession = 0;
                                                $countPassenger = 0;
                                                $sales = 0;
                                                foreach($allTicketPerTrips as $allTicketPerTrip){
                                                    $sales += $allTicketPerTrip->actual_amount;
                                                    if($allTicketPerTrip->passenger_type==0){
                                                        $adult++;
                                                    }else{
                                                        $concession++;
                                                    }
                                                }
                                                $countPassenger = $adult + $concession;
                                            }
                                        }
                                        $outbound['pass_count'] = $countPassenger;
                                        $totalCountPassengerOut += $countPassenger;
            
                                        $outbound['total_sales'] = $sales;
                                        $totalSalesOut += $sales;
            
                                        $outbound['total_on'] = $countPassenger;
                                        $totalTotalOut += $countPassenger;
                                        
                                        $outbound['adult'] = $adult;
                                        $totalAdultOut += $adult;
                                        
                                        $outbound['concession'] = $concession;
                                        $totalConcessionOut += $concession;
            
                                        $allOutbound[$countOut] = $outbound;
                                        $countOut++;
                                    }
                                    $allTripOut[$routeNameOut] = $allOutbound;
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
                    //ClaimDetails specific routes for specific company
                    else{
                        $selectedRoute = Route::where('id', $this->state['route_id'])->first();
        
                        if($selectedRoute){
                            foreach($all_dates as $all_date) {
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
                                $allTripIn = [];
                                $allInbound = [];
                                $existInTrip = false;
                                $existOutTrip = false;
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date . '23:59:59');
            
                                //Inbound
                                $allTripInbounds = TripDetail::where('route_id', $selectedRoute->id)
                                    ->whereBetween('start_trip', [$firstDate,$lastDate])
                                    ->where('trip_code', 1)
                                    ->get();
                                $routeNameIn = $selectedRoute->route_name;
                                if (count($allTripInbounds) > 0) {
                                    $existInTrip = true;
                                    $countIn = 0;
            
                                    foreach ($allTripInbounds as $allTripInbound) {
                                        $inbound['trip_type'] = "IB";
                                        $firstStage = Stage::where('route_id', $allTripInbound->route_id)->orderby('stage_order')->first();
                                        $inbound['start_point'] = $firstStage->stage_name;
                                        $inbound['trip_no'] = "T" . $allTripInbound->id;
                                        $inbound['rph_no'] = $allTripInbound->trip_number;
                                        
                                        if($allTripInbound->bus_id != NULL){
                                            $inbound['bus_plate_no'] = $allTripInbound->Bus->bus_registration_number;
                                            $inbound['bus_age'] = $allTripInbound->Bus->bus_age;
                                            //$inbound['bus_age'] = Carbon::parse($allTripInbound->Bus->bus_manufacturing_date)->diff(Carbon::now())->y;
                                        }else{
                                            $inbound['bus_plate_no'] = "No Data";
                                            $inbound['bus_age'] = "No Data";
                                        }
            
                                        $charge = 1.33;
                                        $inbound['charge_km'] = $charge;
            
                                        if($allTripInbound->driver_id != NULL){
                                            $inbound['driver_id'] = $allTripInbound->BusDriver->driver_number;
                                        }else{
                                            $inbound['driver_id'] = "No Data";
                                        }
            
                                        //$busStop = BusStand::where('route_id', $allTripInbound->route_id)->count();
                                        $busStop = Stage::where('route_id', $allTripInbound->route_id)->count();
                                        $inbound['bus_stop_travel'] = $busStop;
                                        $totalBusStopIn += $busStop;
            
                                        $travel = $allTripInbound->Route->inbound_distance;
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
                                        $inbound['travel_BOP'] = $travel;
                                        $inbound['claim_BOP'] = $claim;
            
                                        if($allTripInbound->route_schedule_mstr_id!=NULL){
                                            $inbound['service_start'] = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                            $inbound['service_end'] = Carbon::create($allTripInbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                        }else{
                                            $inbound['service_start'] = "No Data";
                                            $inbound['service_end'] = "No Data";
                                        }
                                        $inbound['start_point_time'] = $inbound['service_start'];
            
                                        $inbound['actual_start'] = date("H:i", strtotime($allTripInbound->start_trip));
                                        $inbound['actual_end'] = date("H:i", strtotime($allTripInbound->end_trip));
            
                                        //Check 1st sales
                                        $firstSales = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)
                                            ->orderby('sales_date')
                                            ->first();
                                        if($firstSales){
                                            $inbound['sales_start'] = date("H:i", strtotime($firstSales->sales_date));
                                        }else{
                                            $inbound['sales_start'] = "No Sales";
                                        }
            
                                        //Check last sales
                                        $lastSales = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)
                                            ->orderby('sales_date', 'DESC')
                                            ->first();
                                        if($lastSales){
                                            $inbound['sales_end'] = date("H:i", strtotime($lastSales->sales_date));
                                        }else{
                                            $inbound['sales_end'] = "No Sales";
                                        }
            
                                        if($inbound['service_start']!="No Data"){
                                            $diff = strtotime($inbound['service_start']) - strtotime($inbound['actual_start']);
                                            if ($diff > 5 || $diff < -5) {
                                                $inbound['punctuality'] = "NOT PUNCTUAL";
                                            }else {
                                                $inbound['punctuality'] = "ONTIME";
                                            }
                                        }else{
                                            $inbound['punctuality'] = "No Data";
                                        }
        
                                        $adult = $allTripInbound->total_adult;
                                        $concession = $allTripInbound->total_concession;
                                        $countPassenger = $adult + $concession;
                                        $sales = $allTripInbound->total_adult_amount + $allTripInbound->total_concession_amount;
                                        //Check tickets
                                        if($countPassenger==0 || $sales==0){
                                            $allTicketPerTrips = TicketSalesTransaction::where('trip_number', $allTripInbound->trip_number)->get();
                                            if(count($allTicketPerTrips)>0){
                                                $adult = 0;
                                                $concession = 0;
                                                $countPassenger = 0;
                                                $sales = 0;
                                                foreach($allTicketPerTrips as $allTicketPerTrip){
                                                    $sales += $allTicketPerTrip->actual_amount;
                                                    if($allTicketPerTrip->passenger_type==0){
                                                        $adult++;
                                                    }else{
                                                        $concession++;
                                                    }
                                                }
                                                $countPassenger = $adult + $concession;
                                            }
                                        }
                                        $inbound['pass_count'] = $countPassenger;
                                        $totalCountPassengerIn += $countPassenger;
            
                                        $inbound['total_sales'] = $sales;
                                        $totalSalesIn += $sales;
            
                                        $inbound['total_on'] = $countPassenger;
                                        $totalTotalIn += $countPassenger;
            
                                        $inbound['adult'] = $adult;
                                        $totalAdultIn += $adult;
            
                                        $inbound['concession'] = $concession;
                                        $totalConcessionIn += $concession;
            
                                        $allInbound[$countIn] = $inbound;
                                        $countIn++;
                                    }
                                    $allTripIn[$routeNameIn] = $allInbound;
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
                                $allTripOut = [];
                                $allOutbound = [];
                                //Outbound
                                $allTripOutbounds = TripDetail::where('route_id', $selectedRoute->id)
                                    ->whereBetween('start_trip', [$firstDate,$lastDate])
                                    ->where('trip_code', 0)
                                    ->get();
                                $routeNameOut = implode(" - ", array_reverse(explode(" - ", $routeNameIn)));
                                if (count($allTripOutbounds) > 0) {
                                    $existOutTrip = true;
                                    $countOut = 0;
            
                                    foreach ($allTripOutbounds as $allTripOutbound) {
                                        $outbound['trip_type'] = "OB";
                                        $firstStage = Stage::where('route_id', $allTripOutbound->route_id)->orderby('stage_order', 'DESC')->first();
                                        $outbound['start_point'] = $firstStage->stage_name;
                                        $outbound['trip_no'] = "T" . $allTripOutbound->id;
                                        $outbound['rph_no'] = $allTripOutbound->trip_number;
            
                                        if($allTripOutbound->bus_id != NULL){
                                            $outbound['bus_plate_no'] = $allTripOutbound->Bus->bus_registration_number;
                                            $outbound['bus_age'] = $allTripOutbound->Bus->bus_age;
                                            //$inbound['bus_age'] = Carbon::parse($allTripOutbound->Bus->bus_manufacturing_date)->diff(Carbon::now())->y;
                                        }else{
                                            $outbound['bus_plate_no']  = "No Data";
                                            $outbound['bus_age'] = "No Data";
                                        }
            
                                        $charge = 1.33;
                                        $outbound['charge_km'] = $charge;
            
                                        if($allTripOutbound->driver_id != NULL){
                                            $outbound['driver_id'] = $allTripOutbound->BusDriver->driver_number;
                                        }else{
                                            $outbound['driver_id'] = "No Data";
                                        }
            
                                        //$busStop = BusStand::where('route_id', $allTripOutbound->route_id)->count();
                                        $busStop = Stage::where('route_id', $allTripOutbound->route_id)->count();
                                        $outbound['bus_stop_travel'] = $busStop;
                                        $totalBusStopOut += $busStop;
            
                                        $travel = $allTripOutbound->Route->inbound_distance;
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
                                        $outbound['travel_BOP'] = $travel;
                                        $outbound['claim_BOP'] = $claim;
            
                                        if($allTripOutbound->route_schedule_mstr_id!=NULL){
                                            $outbound['service_start'] = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                                            $outbound['service_end'] = Carbon::create($allTripOutbound->RouteScheduleMSTR->schedule_end_time)->format('H:i');
                                        }else{
                                            $outbound['service_start'] = "No Data";
                                            $outbound['service_end'] = "No Data";
                                        }
                                        $outbound['start_point_time'] = $outbound['service_start'];
            
                                        $outbound['actual_start'] = date("H:i", strtotime($allTripOutbound->start_trip));
                                        $outbound['actual_end'] = date("H:i", strtotime($allTripOutbound->end_trip));
            
                                        //Check 1st sales
                                        $firstSales = TicketSalesTransaction::where('trip_number',$allTripOutbound->trip_number)
                                            ->orderby('sales_date')
                                            ->first();
                                        if($firstSales){
                                            $outbound['sales_start'] = date("H:i", strtotime($firstSales->sales_date));
                                        }else{
                                            $outbound['sales_start'] = "No Sales";
                                        }
            
                                        //Check last sales
                                        $lastSales = TicketSalesTransaction::where('trip_number',$allTripOutbound->trip_number)
                                            ->orderby('sales_date', 'DESC')
                                            ->first();
                                        if($lastSales){
                                            $outbound['sales_end'] = date("H:i", strtotime($lastSales->sales_date));
                                        }else{
                                            $outbound['sales_end'] = "No Sales";
                                        }
            
                                        if($outbound['service_start']!="No Data"){
                                            $diff = strtotime($outbound['service_start']) - strtotime($outbound['actual_start']);
                                            if ($diff > 5 || $diff < -5) {
                                                $outbound['punctuality'] = "NOT PUNCTUAL";
                                            } else {
                                                $outbound['punctuality'] = "ONTIME";
                                            }
                                        }else{
                                            $outbound['punctuality'] = "No Data";
                                        }
        
                                        $adult = $allTripOutbound->total_adult;
                                        $concession = $allTripOutbound->total_concession;
                                        $countPassenger = $adult + $concession;
                                        $sales = $allTripOutbound->total_adult_amount + $allTripOutbound->total_concession_amount;
                                        //Check tickets
                                        if($countPassenger==0 || $sales==0){
                                            $allTicketPerTrips = TicketSalesTransaction::where('trip_number',$allTripOutbound->trip_number)->get();
                                            if(count($allTicketPerTrips)>0){
                                                $adult = 0;
                                                $concession = 0;
                                                $countPassenger = 0;
                                                $sales = 0;
                                                foreach($allTicketPerTrips as $allTicketPerTrip){
                                                    $sales += $allTicketPerTrip->actual_amount;
                                                    if($allTicketPerTrip->passenger_type==0){
                                                        $adult++;
                                                    }else{
                                                        $concession++;
                                                    }
                                                }
                                                $countPassenger = $adult + $concession;
                                            }
                                        }
                                        $outbound['pass_count'] = $countPassenger;
                                        $totalCountPassengerOut += $countPassenger;
            
                                        $outbound['total_sales'] = $sales;
                                        $totalSalesOut += $sales;
            
                                        $outbound['total_on'] = $countPassenger;
                                        $totalTotalOut += $countPassenger;
                                        
                                        $outbound['adult'] = $adult;
                                        $totalAdultOut += $adult;
                                        
                                        $outbound['concession'] = $concession;
                                        $totalConcessionOut += $concession;
            
                                        $allOutbound[$countOut] = $outbound;
                                        $countOut++;
                                    }
                                    $allTripOut[$routeNameOut] = $allOutbound;
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
            }
            //return Excel::download(new SPADClaimDetails($all_dates, $claimDetails, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'ClaimDetails_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }*/
    }

    public function printClaimDetailGPSOld(){
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printClaimDetailGPS()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();
            $startDate->addDay();
        }

        $claimDetailGPSSPAD = collect();
        $companyArr = [];
        if ($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                if(!empty($validatedData['route_id'])) {
                    //Claim Details GPS all route all company
                    if($validatedData['route_id']=='All'){
                        $allCompanies = Company::all();
                        foreach($allCompanies as $allCompany){
                            $allRoutes = Route::where('company_id', $allCompany->id)->get();

                            $routeArr = [];
                            foreach ($allRoutes as $allRoute){
                                $routeNo = $allRoute->route_number;
                                $routeNameIn = $allRoute->route_name;

                                $allDate = [];
                                foreach ($all_dates as $all_date) {
                                    $firstDate = new Carbon($all_date);
                                    $lastDate = new Carbon($all_date . '23:59:59');

                                    $tripPerDates = TripDetail::where('route_id', $allRoute->id)
                                        ->whereBetween('start_trip', [$firstDate,$lastDate])
                                        ->get();

                                    $allTrip = [];
                                    if (count($tripPerDates) > 0) {
                                        foreach ($tripPerDates as $tripPerDate) {
                                            if ($tripPerDate->trip_code == 1) {
                                                $title = 'T' . $tripPerDate->id . ' - ' . $routeNo . '  ' . $routeNameIn . ' - IB - ' . $tripPerDate->start_trip;
                                            } else {
                                                $routeNameOut = implode(" - ", array_reverse(explode(" - ", $routeNameIn)));
                                                $title = 'T' . $tripPerDate->id . ' - ' . $routeNo . '  ' . $routeNameOut . ' - OB - ' . $tripPerDate->start_trip;
                                            }

                                            $vehiclePositions = VehiclePosition::where('trip_id', $tripPerDate->trip_number)->get();
                                            $i=1;
                                            $allGPS = [];
                                            $prevTime = 0;
                                            if (count($vehiclePositions) > 0) {
                                                foreach ($vehiclePositions as $vehiclePosition) {
                                                    $gps['bus_no'] = $vehiclePosition->Bus->bus_registration_number;
                                                    $gps['creation_date'] = $vehiclePosition->date_time;
                                                    $gps['speed'] = round($vehiclePosition->speed, 2);
                                                    $gps['longitude'] = $vehiclePosition->longitude;
                                                    $gps['latitude'] = $vehiclePosition->latitude;
                                                    $gps['pmhs_status'] = $vehiclePosition->phms_id;
                                                    $gps['pmhs_upload_date'] = $vehiclePosition->date_time;
                                                    if($i==1){
                                                        $gps['duration'] = 0;
                                                    }else{
                                                        $duration = strtotime($vehiclePosition->date_time) - $prevTime;
                                                        $gps['duration'] = $duration;
                                                    }
                                                    $prevTime = strtotime($vehiclePosition->date_time);

                                                    $allGPS[$i++] = $gps;
                                                }
                                            }
                                            $allTrip[$title] = $allGPS;
                                        }
                                    }
                                    $allDate[$all_date] = $allTrip;
                                }
                                $routeArr[$allRoute->route_name] = $allDate;
                            }
                            $companyArr[$allCompany->company_name] = $routeArr;
                        }
                        $claimDetailGPSSPAD->add($companyArr);
                    }
                }
            }else{
                $selectedCompany = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $selectedCompany->company_name;

                if(!empty($validatedData['route_id'])) {
                     //Claim Details GPS all route specific company
                    if($validatedData['route_id']=='All'){
                        $allRoutes = Route::where('company_id', $selectedCompany->id)->get();

                        $routeArr = [];
                        foreach ($allRoutes as $allRoute){
                            $routeNo = $allRoute->route_number;
                            $routeNameIn = $allRoute->route_name;
        
                            $allDate = [];
                            foreach ($all_dates as $all_date) {
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date . '23:59:59');
        
                                $tripPerDates = TripDetail::where('route_id', $allRoute->id)
                                    ->whereBetween('start_trip', [$firstDate,$lastDate])
                                    ->get();
        
                                $allTrip = [];
                                if (count($tripPerDates) > 0) {
                                    foreach ($tripPerDates as $tripPerDate) {
                                        if ($tripPerDate->trip_code == 1) {
                                            $title = 'T' . $tripPerDate->id . ' - ' . $routeNo . '  ' . $routeNameIn . ' - IB - ' . $tripPerDate->start_trip;
                                        } else {
                                            $routeNameOut = implode(" - ", array_reverse(explode(" - ", $routeNameIn)));
                                            $title = 'T' . $tripPerDate->id . ' - ' . $routeNo . '  ' . $routeNameOut . ' - OB - ' . $tripPerDate->start_trip;
                                        }
        
                                        $vehiclePositions = VehiclePosition::where('trip_id', $tripPerDate->trip_number)->get();
                                        $i=1;
                                        $allGPS = [];
                                        $prevTime = 0;
                                        if (count($vehiclePositions) > 0) {
                                            foreach ($vehiclePositions as $vehiclePosition) {
                                                $gps['bus_no'] = $vehiclePosition->Bus->bus_registration_number;
                                                $gps['creation_date'] = $vehiclePosition->date_time;
                                                $gps['speed'] = round($vehiclePosition->speed, 2);
                                                $gps['longitude'] = $vehiclePosition->longitude;
                                                $gps['latitude'] = $vehiclePosition->latitude;
                                                $gps['pmhs_status'] = $vehiclePosition->phms_id;
                                                $gps['pmhs_upload_date'] = $vehiclePosition->date_time;
                                                if($i==1){
                                                    $gps['duration'] = 0;
                                                }else{
                                                    $duration = strtotime($vehiclePosition->date_time) - $prevTime;
                                                    $gps['duration'] = $duration;
                                                }
                                                $prevTime = strtotime($vehiclePosition->date_time);
        
                                                $allGPS[$i++] = $gps;
                                            }
                                        }
                                        $allTrip[$title] = $allGPS;
                                    }
                                }
                                $allDate[$all_date] = $allTrip;
                            }
                            $routeArr[$allRoute->route_name] = $allDate;
                        }
                        $companyArr[$selectedCompany->company_name] = $routeArr;
                        $claimDetailGPSSPAD->add($companyArr);
                    }
                    //Claim Details GPS specific route specific company
                    else{
                        $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                        $routeNo = $selectedRoute->route_number;
                        $routeNameIn = $selectedRoute->route_name;

                        $routeArr = [];
                        $allDate = [];
                        foreach ($all_dates as $all_date) {
                            $firstDate = new Carbon($all_date);
                            $lastDate = new Carbon($all_date . '23:59:59');

                            $tripPerDates = TripDetail::where('route_id', $selectedRoute->id)
                                ->whereBetween('start_trip', [$firstDate,$lastDate])
                                ->get();

                            $allTrip = [];
                            if (count($tripPerDates) > 0) {
                                foreach ($tripPerDates as $tripPerDate) {
                                    if ($tripPerDate->trip_code == 1) {
                                        $title = 'T' . $tripPerDate->id . ' - ' . $routeNo . '  ' . $routeNameIn . ' - IB - ' . $tripPerDate->start_trip;
                                    } else {
                                        $routeNameOut = implode(" - ", array_reverse(explode(" - ", $routeNameIn)));
                                        $title = 'T' . $tripPerDate->id . ' - ' . $routeNo . '  ' . $routeNameOut . ' - OB - ' . $tripPerDate->start_trip;
                                    }
                                    
                                    $vehiclePositions = VehiclePosition::where('trip_id', $tripPerDate->trip_number)->get();
                                    $i=1;
                                    $allGPS = [];
                                    $prevTime = 0;
                                    if (count($vehiclePositions) > 0) {
                                        foreach ($vehiclePositions as $vehiclePosition) {
                                            $gps['bus_no'] = $vehiclePosition->Bus->bus_registration_number;
                                            $gps['creation_date'] = $vehiclePosition->date_time;
                                            $gps['speed'] = round($vehiclePosition->speed, 2);
                                            $gps['longitude'] = $vehiclePosition->longitude;
                                            $gps['latitude'] = $vehiclePosition->latitude;
                                            $gps['pmhs_status'] = $vehiclePosition->phms_id;
                                            $gps['pmhs_upload_date'] = $vehiclePosition->date_time;
                                            if($i==1){
                                                $gps['duration'] = 0;
                                            }else{
                                                $duration = strtotime($vehiclePosition->date_time) - $prevTime;
                                                $gps['duration'] = $duration;
                                            }
                                            $prevTime = strtotime($vehiclePosition->date_time);

                                            $allGPS[$i++] = $gps;
                                        }
                                    }
                                    $allTrip[$title] = $allGPS;
                                }
                            }
                            $allDate[$all_date] = $allTrip;
                        }
                        $routeArr[$selectedRoute->route_name] = $allDate;
                        $companyArr[$selectedCompany->company_name] = $routeArr;
                        $claimDetailGPSSPAD->add($companyArr);
                    }
                }
            }
            return Excel::download(new SPADClaimDetailsGPS($networkArea, $claimDetailGPSSPAD, $validatedData['dateFrom'], $validatedData['dateTo']), 'ClaimDetailsGPS_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printClaimSummary()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN  printClaimSummary()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();
            $startDate->addDay();
        }

        $claimSummary = collect();
        $grandTripPlanned= 0;
        $grandTripMade = 0;
        $grandServicePlanned = 0;
        $grandServiceServed = 0;
        $grandClaim = 0;
        $grandTravelGPS = 0;
        $grandClaimGPS = 0;
        if ($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                if(!empty($validatedData['route_id'])) {
                    //ClaimSummary all routes for all company
                    if($validatedData['route_id']=='All'){
                        $allRoutes = Route::orderBy('route_number')->get();
            
                        foreach($allRoutes as $allRoute) {
                            $route_name_in = $allRoute->route_name;
                            $route_name_out = implode(" - ", array_reverse(explode(" - ", $route_name_in)));
                            $routeTripPlanned = 0;
                            $routeTripMade = 0;
                            $routeServicePlanned = 0;
                            $routeServiceServed = 0;
                            $routeClaim = 0;
                            $routeTravelGPS = 0;
                            $routeClaimGPS = 0;
            
                            foreach ($all_dates as $all_date) {
                                $tripPlannedIn = 0;
                                $tripPlannedOut = 0;
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
                                $lastDate = new Carbon($all_date . '23:59:59');
            
                                //Trip Planned
                                $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate, $lastDate])->get();
                                if (count($schedules) > 0) {
                                    foreach ($schedules as $schedule) {
                                        if ($schedule->RouteScheduleMSTR->route_id == $allRoute->id) {
                                            if ($schedule->RouteScheduleMSTR->trip_code == 0){
                                                $tripPlannedOut++;
                                                $servicePlannedOut += $schedule->RouteScheduleMSTR->Route->outbound_distance;
                                            }elseif ($schedule->RouteScheduleMSTR->trip_code == 1){
                                                $tripPlannedIn++;
                                                $servicePlannedIn += $schedule->RouteScheduleMSTR->Route->inbound_distance;
                                            }
                                        }
                                    }
                                }
            
                                //Inbound
                                $allTripInbounds = TripDetail::where('route_id', $allRoute->id)
                                    ->whereBetween('start_trip', [$firstDate, $lastDate])
                                    ->where('trip_code', 1)
                                    ->get();
            
                                if (count($allTripInbounds) > 0) {
                                    $existInTrip = true;
            
                                    foreach ($allTripInbounds as $allTripInbound) {
                                        $tripMadeIn++;
            
                                        //Service Served (KM)
                                        $travelIn = $allTripInbound->Route->inbound_distance;
                                        $serviceServedIn += $travelIn;
            
                                        //Travel GPS
                                        $travelGPS = $allTripInbound->total_mileage;
                                        $travelGPSIn += $travelGPS;
                                    }
            
                                    //Total Claim In
                                    $claimIn = $serviceServedIn * 1.33;
                                    $claimInFormat = round($claimIn,1);
            
                                    //Total Claim GPS In
                                    $claimGPSIn = $travelGPSIn * 1.33;
                                    $claimGPSInFormat = round($claimGPSIn,1);
            
                                    $inbound['route_name'] = $route_name_in;
                                    $inbound['trip_planned_in'] = $tripPlannedIn;
                                    $inbound['trip_made_in'] = $tripMadeIn;
                                    $inbound['service_planned_in'] = $servicePlannedIn;
                                    $inbound['service_served_in'] = $serviceServedIn;
                                    $inbound['claim_in'] = $claimInFormat;
                                    $inbound['travel_gps_in'] = $travelGPSIn;
                                    $inbound['claim_gps_in'] = $claimGPSInFormat;
                                }
            
                                //Outbound
                                $allTripOutbounds = TripDetail::where('route_id', $allRoute->id)
                                    ->whereBetween('start_trip', [$firstDate, $lastDate])
                                    ->where('trip_code', 0)
                                    ->get();
            
                                if (count($allTripOutbounds) > 0) {
                                    $existOutTrip = true;
            
                                    foreach ($allTripOutbounds as $allTripOutbound) {
                                        $tripMadeOut++;
            
                                        //Service Served (KM)
                                        $travelOut = $allTripOutbound->Route->outbound_distance;
                                        $serviceServedOut += $travelOut;
            
                                        //Travel GPS
                                        $travelGPS = $allTripOutbound->total_mileage;
                                        $travelGPSOut += $travelGPS;
                                    }
            
                                    //Total Claim Out
                                    $claimOut = $serviceServedOut * 1.33;
                                    $claimOutFormat = round($claimOut,1);
            
                                    //Total Claim GPS Out
                                    $claimGPSOut = $travelGPSOut * 1.33;
                                    $claimGPSOutFormat = round($claimGPSOut,1);
            
                                    $outbound['route_name'] = $route_name_out;
                                    $outbound['trip_planned_out'] = $tripPlannedOut;
                                    $outbound['trip_made_out'] = $tripMadeOut;
                                    $outbound['service_planned_out'] = $servicePlannedOut;
                                    $outbound['service_served_out'] = $serviceServedOut;
                                    $outbound['claim_out'] = $claimOutFormat;
                                    $outbound['travel_gps_out'] = $travelGPSOut;
                                    $outbound['claim_gps_out'] = $claimGPSOutFormat;
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
                                }
                                $routeTripPlanned += $totalTripPlanned;
                                $routeTripMade += $totalTripMade;
                                $routeServicePlanned += $totalServicePlanned;
                                $routeServiceServed += $totalServiceServed;
                                $routeClaim += $totalClaim;
                                $routeTravelGPS += $totalTravelGPS;
                                $routeClaimGPS += $totalClaimGPS;
                            }
                            if($routeTripPlanned==0 && $routeTripMade == 0 & $routeServicePlanned ==0 && $routeServiceServed == 0 && 
                                $routeClaim==0 && $routeTravelGPS==0 && $routeClaimGPS == 0){
                                $allDate['total_per_route'] = [];
                            }else{
                                $perRoute['route_trip_planned'] = $routeTripPlanned;
                                $perRoute['route_trip_made'] = $routeTripMade;
                                $perRoute['route_service_planned'] = $routeServicePlanned;
                                $perRoute['route_service_served'] = $routeServiceServed;
                                $perRoute['route_claim'] = $routeClaim;
                                $perRoute['route_travel_gps'] = $routeTravelGPS;
                                $perRoute['route_claim_gps'] = $routeClaimGPS;
                
                                $allDate['total_per_route'] = $perRoute;
                            }
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
                }
            }else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $companyDetails->company_name;
                if(!empty($validatedData['route_id'])) {
                    //ClaimSummary all routes for specific company
                    if($validatedData['route_id']=='All'){
                        $allRoutes = Route::where('company_id',  $companyDetails->id)->orderBy('route_number')->get();
        
                        foreach($allRoutes as $allRoute) {
                            $route_name_in = $allRoute->route_name;
                            $route_name_out = implode(" - ", array_reverse(explode(" - ", $route_name_in)));
                            $routeTripPlanned = 0;
                            $routeTripMade = 0;
                            $routeServicePlanned = 0;
                            $routeServiceServed = 0;
                            $routeClaim = 0;
                            $routeTravelGPS = 0;
                            $routeClaimGPS = 0;
        
                            foreach ($all_dates as $all_date) {
                                $tripPlannedIn = 0;
                                $tripPlannedOut = 0;
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
                                $lastDate = new Carbon($all_date . '23:59:59');
        
                                //Trip Planned
                                $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate, $lastDate])->get();
                                if(count($schedules)>0) {
                                    foreach ($schedules as $schedule) {
                                        if ($schedule->RouteScheduleMSTR->route_id == $allRoute->id) {
                                            if ($schedule->RouteScheduleMSTR->trip_code == 0){
                                                $tripPlannedOut++;
                                                $servicePlannedOut += $schedule->RouteScheduleMSTR->Route->outbound_distance;
                                            }elseif ($schedule->RouteScheduleMSTR->trip_code == 1){
                                                $tripPlannedIn++;
                                                $servicePlannedIn += $schedule->RouteScheduleMSTR->Route->inbound_distance;
                                            }
                                        }
                                    }
                                }
        
                                //Inbound
                                $allTripInbounds = TripDetail::where('route_id', $allRoute->id)
                                    ->whereBetween('start_trip', [$firstDate, $lastDate])
                                    ->where('trip_code', 1)
                                    ->get();
        
                                if (count($allTripInbounds) > 0) {
                                    $existInTrip = true;
        
                                    foreach ($allTripInbounds as $allTripInbound) {
                                        $tripMadeIn++;
                                        //Service Served (KM)
                                        $travelIn = $allTripInbound->Route->inbound_distance;
                                        $serviceServedIn += $travelIn;
        
                                        //Travel GPS
                                        $travelGPS = $allTripInbound->total_mileage;
                                        $travelGPSIn += $travelGPS;
                                    }
        
                                    //Total Claim In
                                    $claimIn = $serviceServedIn * 1.33;
                                    $claimInFormat = round($claimIn,1);
        
                                    //Total Claim GPS In
                                    $claimGPSIn = $travelGPSIn * 1.33;
                                    $claimGPSInFormat = round($claimGPSIn,1);
        
                                    $inbound['route_name'] = $route_name_in;
                                    $inbound['trip_planned_in'] = $tripPlannedIn;
                                    $inbound['trip_made_in'] = $tripMadeIn;
                                    $inbound['service_planned_in'] = $servicePlannedIn;
                                    $inbound['service_served_in'] = $serviceServedIn;
                                    $inbound['claim_in'] = $claimInFormat;
                                    $inbound['travel_gps_in'] = $travelGPSIn;
                                    $inbound['claim_gps_in'] = $claimGPSInFormat;
                                }
        
                                //Outbound
                                $allTripOutbounds = TripDetail::where('route_id', $allRoute->id)
                                    ->whereBetween('start_trip', [$firstDate, $lastDate])
                                    ->where('trip_code', 0)
                                    ->get();
        
                                if (count($allTripOutbounds) > 0) {
                                    $existOutTrip = true;
        
                                    foreach ($allTripOutbounds as $allTripOutbound) {
                                        $tripMadeOut++;
                                        //Service Served (KM)
                                        $travelOut = $allTripOutbound->Route->outbound_distance;
                                        $serviceServedOut += $travelOut;
        
                                        //Travel GPS
                                        $travelGPS = $allTripOutbound->total_mileage;
                                        $travelGPSOut += $travelGPS;
                                    }
        
                                    //Total Claim Out
                                    $claimOut = $serviceServedOut * 1.33;
                                    $claimOutFormat = round($claimOut,1);
        
                                    //Total Claim GPS Out
                                    $claimGPSOut = $travelGPSOut * 1.33;
                                    $claimGPSOutFormat = round($claimGPSOut,1);
        
                                    $outbound['route_name'] = $route_name_out;
                                    $outbound['trip_planned_out'] = $tripPlannedOut;
                                    $outbound['trip_made_out'] = $tripMadeOut;
                                    $outbound['service_planned_out'] = $servicePlannedOut;
                                    $outbound['service_served_out'] = $serviceServedOut;
                                    $outbound['claim_out'] = $claimOutFormat;
                                    $outbound['travel_gps_out'] = $travelGPSOut;
                                    $outbound['claim_gps_out'] = $claimGPSOutFormat;
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
                                }
                                $routeTripPlanned += $totalTripPlanned;
                                $routeTripMade += $totalTripMade;
                                $routeServicePlanned += $totalServicePlanned;
                                $routeServiceServed += $totalServiceServed;
                                $routeClaim += $totalClaim;
                                $routeTravelGPS += $totalTravelGPS;
                                $routeClaimGPS += $totalClaimGPS;
                            }
        
                            if($routeTripPlanned==0 && $routeTripMade == 0 & $routeServicePlanned ==0 && $routeServiceServed == 0 && 
                                $routeClaim==0 && $routeTravelGPS==0 && $routeClaimGPS == 0){
                                $allDate['total_per_route'] = [];
                            }else{
                                $perRoute['route_trip_planned'] = $routeTripPlanned;
                                $perRoute['route_trip_made'] = $routeTripMade;
                                $perRoute['route_service_planned'] = $routeServicePlanned;
                                $perRoute['route_service_served'] = $routeServiceServed;
                                $perRoute['route_claim'] = $routeClaim;
                                $perRoute['route_travel_gps'] = $routeTravelGPS;
                                $perRoute['route_claim_gps'] = $routeClaimGPS;
                
                                $allDate['total_per_route'] = $perRoute;
                            }
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
                    //ClaimSummary specific routes for specific company
                    else{
                        $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                        $route_name_in = $selectedRoute->route_name;
                        $route_name_out = implode(" - ", array_reverse(explode(" - ", $route_name_in)));
        
                        foreach ($all_dates as $all_date) {
                            $tripPlannedIn = 0;
                            $tripPlannedOut = 0;
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
                            $lastDate = new Carbon($all_date .'23:59:59');
        
                            //Trip Planned
                            $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate,$lastDate])->get();
                            if(count($schedules)>0) {
                                foreach ($schedules as $schedule) {
                                    if ($schedule->RouteScheduleMSTR->route_id == $selectedRoute->id) {
                                        if ($schedule->RouteScheduleMSTR->trip_code == 0){
                                            $tripPlannedOut++;
                                            $servicePlannedOut += $schedule->RouteScheduleMSTR->Route->outbound_distance;
                                        }elseif ($schedule->RouteScheduleMSTR->trip_code == 1){
                                            $tripPlannedIn++;
                                            $servicePlannedIn += $schedule->RouteScheduleMSTR->Route->inbound_distance;
                                        }
                                    }
                                }
                            }
        
                            //Inbound
                            $allTripInbounds = TripDetail::where('route_id', $selectedRoute->id)
                                ->whereBetween('start_trip', [$firstDate,$lastDate])
                                ->where('trip_code', 1)
                                ->get();
        
                            if (count($allTripInbounds) > 0) {
                                $existInTrip = true;
        
                                foreach ($allTripInbounds as $allTripInbound) {
                                    $tripMadeIn++;
        
                                    //Service Served (KM)
                                    $travelIn = $allTripInbound->Route->inbound_distance;
                                    $serviceServedIn += $travelIn;
        
                                    //Travel GPS
                                    $travelGPS = $allTripInbound->total_mileage;
                                    $travelGPSIn += $travelGPS;
                                }
        
                                //Total Claim In
                                $claimIn = $serviceServedIn * 1.33;
                                $claimInFormat = round($claimIn,1);
        
                                //Total Claim GPS In
                                $claimGPSIn = $travelGPSIn * 1.33;
                                $claimGPSInFormat = round($claimGPSIn,1);
        
                                $inbound['route_name'] = $route_name_in;
                                $inbound['trip_planned_in'] = $tripPlannedIn;
                                $inbound['trip_made_in'] = $tripMadeIn;
                                $inbound['service_planned_in'] = $servicePlannedIn;
                                $inbound['service_served_in'] = $serviceServedIn;
                                $inbound['claim_in'] = $claimInFormat;
                                $inbound['travel_gps_in'] = $travelGPSIn;
                                $inbound['claim_gps_in'] = $claimGPSInFormat;
                            }
        
                            //Outbound
                            $allTripOutbounds = TripDetail::where('route_id', $selectedRoute->id)
                                ->whereBetween('start_trip', [$firstDate,$lastDate])
                                ->where('trip_code', 0)
                                ->get();
        
                            if (count($allTripOutbounds) > 0) {
                                $existOutTrip = true;
        
                                foreach ($allTripOutbounds as $allTripOutbound) {
                                    $tripMadeOut++;
        
                                    //Service Served (KM)
                                    $travelOut = $allTripOutbound->Route->outbound_distance;
                                    $serviceServedOut += $travelOut;
        
                                    //Travel GPS
                                    $travelGPS = $allTripOutbound->total_mileage;
                                    $travelGPSOut += $travelGPS;
                                }
        
                                //Total Claim Out
                                $claimOut = $serviceServedOut * 1.33;
                                $claimOutFormat = round($claimOut,1);
        
                                //Total Claim GPS Out
                                $claimGPSOut = $travelGPSOut * 1.33;
                                $claimGPSOutFormat = round($claimGPSOut,1);
        
                                $outbound['route_name'] = $route_name_out;
                                $outbound['trip_planned_out'] = $tripPlannedOut;
                                $outbound['trip_made_out'] = $tripMadeOut;
                                $outbound['service_planned_out'] = $servicePlannedOut;
                                $outbound['service_served_out'] = $serviceServedOut;
                                $outbound['claim_out'] = $claimOutFormat;
                                $outbound['travel_gps_out'] = $travelGPSOut;
                                $outbound['claim_gps_out'] = $claimGPSOutFormat;
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
                            }
        
                            $grandTripPlanned +=  $totalTripPlanned;
                            $grandTripMade += $totalTripMade;
                            $grandServicePlanned += $totalServicePlanned;
                            $grandServiceServed += $totalServiceServed;
                            $grandClaim += $totalClaim;
                            $grandTravelGPS += $totalTravelGPS;
                            $grandClaimGPS += $totalClaimGPS;
                        }
                        if($grandTripPlanned==0 && $grandTripMade == 0 & $grandServicePlanned ==0 && $grandServiceServed == 0 && 
                            $grandClaim==0 && $grandTravelGPS==0 && $grandClaimGPS == 0){
                            $allDate['total_per_route'] = [];
                        }else{
                            $perRoute['route_trip_planned'] = $grandTripPlanned;
                            $perRoute['route_trip_made'] = $grandTripMade;
                            $perRoute['route_service_planned'] = $grandServicePlanned;
                            $perRoute['route_service_served'] = $grandServiceServed;
                            $perRoute['route_claim'] = $grandClaim;;
                            $perRoute['route_travel_gps'] = $grandTravelGPS;
                            $perRoute['route_claim_gps'] = $grandClaimGPS;
            
                            $allDate['total_per_route'] = $perRoute;
                        }
                        $route[$selectedRoute->route_number] = $allDate;
                        $data['allRoute'] = $route;
        
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
            }
            return Excel::download(new SPADClaimSummary($all_dates, $claimSummary, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'ClaimSummary_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printSummaryRoute()
    {  
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printSummaryRoute()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)) {
            $all_dates[] = $startDate->toDateString();
            $startDate->addDay();
        }

        $summaryByRoute = collect();
        $grandTripPlanned = 0;
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
        $data_perCompany = [];
        if ($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                if(!empty($validatedData['route_id'])) {
                     //Summary By Route all route all company
                    if($validatedData['route_id']=='All'){
                        $allCompanies = Company::all();
            
                        foreach($allCompanies as $allCompany){
                            $routePerCompanies = Route::where('company_id', $allCompany->id)->orderBy('route_number')->get();
                            $companyTripPlanned = 0;
                            $companyKMPlanned = 0;
                            $companyKMServed = 0;
                            $companyTripServed = 0;
                            $companyMissedTrip = 0;
                            $companyEarlyLate = 0;
                            $companyBreakdown = 0;
                            $companyAccidents = 0;
                            $companyRidershipCount = 0;
                            $companyRidershipTicket = 0;
                            $companyFarebox = 0;
                            $data_perRoute = [];
            
                            if(count($routePerCompanies)>0){
                                foreach($routePerCompanies as $routePerCompany){
                                    $routeTripPlanned = 0;
                                    $routeKMPlanned = 0;
                                    $routeKMServed = 0;
                                    $routeTripServed = 0;
                                    $routeMissedTrip = 0;
                                    $routeEarlyLate = 0;
                                    $routeBreakdown = 0;
                                    $routeAccidents = 0;
                                    $routeRidershipCount = 0;
                                    $routeRidershipTicket = 0;
                                    $routeFarebox = 0;
                                    $data_perDate = [];
            
                                    foreach ($all_dates as $all_date) {
                                        $totalKMServed = 0;
                                        $totalFarebox = 0;
                                        $totalRidershipCount = 0;
                                        $totalRidershipTicket = 0;
                                        $earlyLateCount = 0;
                                        $totalTripPlanned = 0;
                                        $tripPlannedOut = 0; 
                                        $tripPlannedIn = 0;
                                        $kmInbound = 0;
                                        $kmOutbound = 0;
                                        $tripServedIn = 0;
                                        $tripServedOut = 0;
                                        $firstDate = new Carbon($all_date);
                                        $lastDate = new Carbon($all_date .'23:59:59');
                                        $isWeekday = false;
                                        $isWeekend = false;

                                        $isWeekday = $firstDate->isWeekday();
                                        $isWeekend =  $firstDate->isWeekend();

                                        if($isWeekday){
                                            $isFriday = $firstDate->format('l');
                                            if($isFriday=='Friday'){
                                                $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,7,12])
                                                ->where('status', 1)
                                                ->where('route_id', $routePerCompany->id)
                                                ->count();
                                            }else{
                                                $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,4,6,9,10])
                                                ->where('status', 1)
                                                ->where('route_id', $routePerCompany->id)
                                                ->count();
                                            }
                                        }
                                        if($isWeekend){
                                            $isSunday = $firstDate->format('l');
                                            if($isSunday=='Sunday'){
                                                $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11])
                                                ->where('route_id', $routePerCompany->id)
                                                ->where('status', 1)
                                                ->count();
                                            }else{
                                                $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12])
                                                ->where('status', 1)
                                                ->where('route_id', $routePerCompany->id)
                                                ->count();
                                            }
                                            
                                        }
                                        $totalTripPlanned = $copies;
                                        $totalKMPlanned = $routePerCompany->inbound_distance * $copies;

                                        $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate, $lastDate])->get();
                    
                                        // if (count($schedules) > 0) {
                                        //     foreach ($schedules as $schedule) {
                                        //         if ($schedule->RouteScheduleMSTR->route_id == $routePerCompany->id) {
                                        //             if ($schedule->RouteScheduleMSTR->trip_code == 0){
                                        //                 $tripPlannedOut++;
                                        //                 $kmInbound += $schedule->RouteScheduleMSTR->Route->outbound_distance;
                                        //             }elseif ($schedule->RouteScheduleMSTR->trip_code == 1){
                                        //                 $tripPlannedIn++;
                                        //                 $kmOutbound += $schedule->RouteScheduleMSTR->Route->inbound_distance;
                                        //             }
                                        //         }
                                        //     }
                                        // }
                                        // $totalTripPlanned = $tripPlannedOut + $tripPlannedIn;
                                        // $totalKMPlanned = $kmInbound + $kmOutbound;
                    
                                        $allTrips = TripDetail::where('route_id', $routePerCompany->id)
                                            ->whereBetween('start_trip', [$firstDate, $lastDate])
                                            ->get();
                    
                                        if (count($allTrips) > 0) {
                                            foreach ($allTrips as $allTrip) {
                                                //Total KM Service Served
                                                if($allTrip->trip_code==0){
                                                    $tripServedOut++;
                                                    $kmServed = $allTrip->Route->outbound_distance;
                                                }else{
                                                    $tripServedIn++;
                                                    $kmServed = $allTrip->Route->inbound_distance;
                                                }
                                                $totalKMServed += $kmServed;
                    
                                                //Early-Late
                                                if (count($schedules) > 0) {
                                                    foreach ($schedules as $schedule) {
                                                        if ($schedule->RouteScheduleMSTR->route_id == $routePerCompany->id) {
                                                            if ($schedule->RouteScheduleMSTR->id == $allTrip->route_schedule_mstr_id) {
                                                                $diff = strtotime($schedule->schedule_start_time) - strtotime($allTrip->start_trip);
                                                                if ($diff > 5 || $diff < -5) {
                                                                    $earlyLateCount++;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                    
                                                //Ridership Based On Count
                                                $ridershipCount = $allTrip->total_adult + $allTrip->total_concession;
                                                $totalRidershipCount += $ridershipCount;
                    
                                                //Ridership Based On Ticket Sales
                                                $ridershipTicket = TicketSalesTransaction::where('trip_number', $allTrip->trip_number)->count();
                                                $totalRidershipTicket += $ridershipTicket;
                    
                                                //Farebox Collection
                                                $farebox = $allTrip->total_adult_amount + $allTrip->total_concession_amount;
                                                if($farebox==0){
                                                    $checkTickets = TicketSalesTransaction::where('trip_number', $allTrip->trip_number)->get();
                                                    if(count($checkTickets)>0){
                                                        foreach($checkTickets as $checkTicket){
                                                            $farebox += $checkTicket->actual_amount;
                                                        }
                                                    }
                                                }
                                                $totalFarebox += $farebox;
                                            }
                                        }
                    
                                        //Total Trip Served
                                        $totalTripServed = count($allTrips);
                    
                                        //Total Missed Trip
                                        $diffIn = $tripPlannedIn - $tripServedIn;
                                        $diffOut = $tripPlannedOut - $tripServedOut;
                                        if($diffIn>0){
                                            if($diffOut>0){
                                                $totalMissedTrip = $diffIn + $diffOut;
                                            }else{
                                                $totalMissedTrip = $diffIn;
                                            }
                                        }else{
                                            if($diffOut>0){
                                                $totalMissedTrip = $diffOut;
                                            }else{
                                                $totalMissedTrip = 0;
                                            }
                                        }
                    
                                        /**Total Breakdown & Total Accidents need to revise**/
                                        $totalBreakdown = 0;
                                        $totalAccidents = 0;
                    
                                        if ($firstDate->isWeekDay()) {
                                            $trip_perDate['day'] = "WEEKDAY";
                                        } elseif ($firstDate->isWeekend()) {
                                            $trip_perDate['day'] = "WEEKEND";
                                        }
            
                                        //total per date
                                        $trip_perDate['total_trip_planned'] = $totalTripPlanned;
                                        $trip_perDate['total_km_planned'] = $totalKMPlanned;
                                        $trip_perDate['total_trip_served'] = $totalTripServed;
                                        $trip_perDate['total_km_served'] = $totalKMServed;
                                        $trip_perDate['total_missed_trip'] = $totalMissedTrip;
                                        $trip_perDate['total_early_late'] = $earlyLateCount;
                                        $trip_perDate['total_breakdown'] = $totalBreakdown;
                                        $trip_perDate['total_accidents'] = $totalAccidents;
                                        $trip_perDate['total_ridership_count'] = $totalRidershipCount;
                                        $trip_perDate['total_ridership_tickets'] = $totalRidershipTicket;
                                        $trip_perDate['total_farebox'] = $totalFarebox;
                    
                                        $data_perDate[$all_date] = $trip_perDate;
            
                                        $routeTripPlanned += $trip_perDate['total_trip_planned'];
                                        $routeKMPlanned += $trip_perDate['total_km_planned'];
                                        $routeKMServed += $trip_perDate['total_km_served'];
                                        $routeTripServed += $trip_perDate['total_trip_served'];
                                        $routeMissedTrip += $trip_perDate['total_missed_trip'];
                                        $routeEarlyLate += $trip_perDate['total_early_late'];
                                        $routeBreakdown += $trip_perDate['total_breakdown'];
                                        $routeAccidents += $trip_perDate['total_accidents'];
                                        $routeRidershipCount += $trip_perDate['total_ridership_count'];
                                        $routeRidershipTicket += $trip_perDate['total_ridership_tickets'];
                                        $routeFarebox += $trip_perDate['total_farebox'];
                                    }
            
                                    //total per route
                                    $trip_perRoute['total_trip_planned'] = $routeTripPlanned;
                                    $trip_perRoute['total_km_planned'] = $routeKMPlanned;
                                    $trip_perRoute['total_trip_served'] = $routeTripServed;
                                    $trip_perRoute['total_km_served'] = $routeKMServed;
                                    $trip_perRoute['total_missed_trip'] = $routeMissedTrip;
                                    $trip_perRoute['total_early_late'] = $routeEarlyLate;
                                    $trip_perRoute['total_breakdown'] = $routeBreakdown;
                                    $trip_perRoute['total_accidents'] = $routeAccidents;
                                    $trip_perRoute['total_ridership_count'] = $routeRidershipCount;
                                    $trip_perRoute['total_ridership_tickets'] = $routeRidershipTicket;
                                    $trip_perRoute['total_farebox'] = $routeFarebox;
            
                                    $data_perDate['total_per_route'] = $trip_perRoute;
                                    $data_perRoute[$routePerCompany->route_number . ' - ' . $routePerCompany->route_name] = $data_perDate;
            
                                    $companyTripPlanned += $trip_perRoute['total_trip_planned'];
                                    $companyKMPlanned += $trip_perRoute['total_km_planned'];
                                    $companyKMServed += $trip_perRoute['total_km_served'];
                                    $companyTripServed += $trip_perRoute['total_trip_served'];
                                    $companyMissedTrip += $trip_perRoute['total_missed_trip'];
                                    $companyEarlyLate += $trip_perRoute['total_early_late'];
                                    $companyBreakdown += $trip_perRoute['total_breakdown'];
                                    $companyAccidents += $trip_perRoute['total_accidents'];
                                    $companyRidershipCount += $trip_perRoute['total_ridership_count'];
                                    $companyRidershipTicket += $trip_perRoute['total_ridership_tickets'];
                                    $companyFarebox += $trip_perRoute['total_farebox'];
                                }
                            }
            
                            //total per company
                            $trip_perCompany['total_trip_planned'] = $companyTripPlanned;
                            $trip_perCompany['total_km_planned'] = $companyKMPlanned;
                            $trip_perCompany['total_trip_served'] = $companyTripServed;
                            $trip_perCompany['total_km_served'] = $companyKMServed;
                            $trip_perCompany['total_missed_trip'] = $companyMissedTrip;
                            $trip_perCompany['total_early_late'] = $companyEarlyLate;
                            $trip_perCompany['total_breakdown'] = $companyBreakdown;
                            $trip_perCompany['total_accidents'] = $companyAccidents;
                            $trip_perCompany['total_ridership_count'] =$companyRidershipCount;
                            $trip_perCompany['total_ridership_tickets'] = $companyRidershipTicket;
                            $trip_perCompany['total_farebox'] = $companyFarebox;
            
                            $data_perRoute['total_per_company'] = $trip_perCompany;
                            $data_perCompany[$allCompany->company_name] = $data_perRoute;
            
                            $grandTripPlanned += $trip_perCompany['total_trip_planned'];
                            $grandKMPlanned += $trip_perCompany['total_km_planned'];
                            $grandKMServed += $trip_perCompany['total_km_served'];
                            $grandTripServed += $trip_perCompany['total_trip_served'];
                            $grandMissedTrip += $trip_perCompany['total_missed_trip'];
                            $grandEarlyLate += $trip_perCompany['total_early_late'];
                            $grandBreakdown += $trip_perCompany['total_breakdown'];
                            $grandAccidents += $trip_perCompany['total_accidents'];
                            $grandRidershipCount += $trip_perCompany['total_ridership_count'];
                            $grandRidershipTicket += $trip_perCompany['total_ridership_tickets'];
                            $grandFarebox += $trip_perCompany['total_farebox'];
                        }
                        $grand['grand_trip_planned'] = $grandTripPlanned;
                        $grand['grand_km_planned'] = $grandKMPlanned;
                        $grand['grand_trip_served'] = $grandTripServed;
                        $grand['grand_km_served'] = $grandKMServed;
                        $grand['grand_missed_trip'] = $grandMissedTrip;
                        $grand['grand_early_late'] = $grandEarlyLate;
                        $grand['grand_breakdown'] = $grandBreakdown;
                        $grand['grand_accidents'] = $grandAccidents;
                        $grand['grand_ridership_count'] = $grandRidershipCount;
                        $grand['grand_ridership_tickets'] = $grandRidershipTicket;
                        $grand['grand_farebox'] = $grandFarebox;
            
                        $data_grand['all_company'] = $data_perCompany;
                        $data_grand['grand'] = $grand;
                        $summaryByRoute->add($data_grand);
                    }
                }
            }else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $companyDetails->company_name;
                if(!empty($validatedData['route_id'])) {
                    //Summary By Route all route specific company
                    if($validatedData['route_id']=='All'){
                        $routePerCompanies = Route::where('company_id', $companyDetails->id)->orderBy('route_number')->get();
                        $companyTripPlanned = 0;
                        $companyKMPlanned = 0;
                        $companyKMServed = 0;
                        $companyTripServed = 0;
                        $companyMissedTrip = 0;
                        $companyEarlyLate = 0;
                        $companyBreakdown = 0;
                        $companyAccidents = 0;
                        $companyRidershipCount = 0;
                        $companyRidershipTicket = 0;
                        $companyFarebox = 0;

                        if(count($routePerCompanies)>0){
                            foreach($routePerCompanies as $routePerCompany){
                                $routeTripPlanned = 0;
                                $routeKMPlanned = 0;
                                $routeKMServed = 0;
                                $routeTripServed = 0;
                                $routeMissedTrip = 0;
                                $routeEarlyLate = 0;
                                $routeBreakdown = 0;
                                $routeAccidents = 0;
                                $routeRidershipCount = 0;
                                $routeRidershipTicket = 0;
                                $routeFarebox = 0;
        
                                foreach ($all_dates as $all_date) {
                                    $totalKMServed = 0;
                                    $totalFarebox = 0;
                                    $totalRidershipCount = 0;
                                    $totalRidershipTicket = 0;
                                    $earlyLateCount = 0;
                                    $totalTripPlanned = 0;
                                    $tripPlannedOut = 0; 
                                    $tripPlannedIn = 0;
                                    $kmInbound = 0;
                                    $kmOutbound = 0;
                                    $tripServedIn = 0;
                                    $tripServedOut = 0;
                                    $firstDate = new Carbon($all_date);
                                    $lastDate = new Carbon($all_date .'23:59:59');
                                    $isWeekday = false;
                                    $isWeekend = false;

                                    $isWeekday = $firstDate->isWeekday();
                                    $isWeekend =  $firstDate->isWeekend();

                                    if($isWeekday){
                                        $isFriday = $firstDate->format('l');
                                        if($isFriday=='Friday'){
                                            $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,7,12])
                                            ->where('status', 1)
                                            ->where('route_id', $routePerCompany->id)
                                            ->count();
                                        }else{
                                            $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,4,6,9,10])
                                            ->where('status', 1)
                                            ->where('route_id', $routePerCompany->id)
                                            ->count();
                                        }
                                    }
                                    if($isWeekend){
                                        $isSunday = $firstDate->format('l');
                                        if($isSunday=='Sunday'){
                                            $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11])
                                            ->where('route_id', $routePerCompany->id)
                                            ->where('status', 1)
                                            ->count();
                                        }else{
                                            $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12])
                                            ->where('status', 1)
                                            ->where('route_id', $routePerCompany->id)
                                            ->count();
                                        }
                                        
                                    }
                                    $totalTripPlanned = $copies;
                                    $totalKMPlanned = $routePerCompany->inbound_distance * $copies;

                                    $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate, $lastDate])->get();
                
                                    // if (count($schedules) > 0) {
                                    //     foreach ($schedules as $schedule) {
                                    //         if ($schedule->RouteScheduleMSTR->route_id == $routePerCompany->id) {
                                    //             if ($schedule->RouteScheduleMSTR->trip_code == 0){
                                    //                 $tripPlannedOut++;
                                    //                 $kmInbound += $schedule->RouteScheduleMSTR->Route->outbound_distance;
                                    //             }elseif ($schedule->RouteScheduleMSTR->trip_code == 1){
                                    //                 $tripPlannedIn++;
                                    //                 $kmOutbound += $schedule->RouteScheduleMSTR->Route->inbound_distance;
                                    //             }
                                    //         }
                                    //     }
                                    // }
                                    // $totalTripPlanned = $tripPlannedOut + $tripPlannedIn;
                                    // $totalKMPlanned = $kmInbound + $kmOutbound;
                
                                    $allTrips = TripDetail::where('route_id', $routePerCompany->id)
                                        ->whereBetween('start_trip', [$firstDate, $lastDate])
                                        ->get();
                
                                    if (count($allTrips) > 0) {
                                        foreach ($allTrips as $allTrip) {
                                            //Total KM Service Served
                                            if($allTrip->trip_code==0){
                                                $tripServedOut++;
                                                $kmServed = $allTrip->Route->outbound_distance;
                                            }else{
                                                $tripServedIn++;
                                                $kmServed = $allTrip->Route->inbound_distance;
                                            }
                                            $totalKMServed += $kmServed;
                
                                            //Early-Late
                                            if (count($schedules) > 0) {
                                                foreach ($schedules as $schedule) {
                                                    if ($schedule->RouteScheduleMSTR->route_id == $routePerCompany->id) {
                                                        if ($schedule->RouteScheduleMSTR->id == $allTrip->route_schedule_mstr_id) {
                                                            $diff = strtotime($schedule->schedule_start_time) - strtotime($allTrip->start_trip);
                                                            if ($diff > 5 || $diff < -5) {
                                                                $earlyLateCount++;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                
                                            //Ridership Based On Count
                                            $ridershipCount = $allTrip->total_adult + $allTrip->total_concession;
                                            $totalRidershipCount += $ridershipCount;
                
                                            //Ridership Based On Ticket Sales
                                            $ridershipTicket = TicketSalesTransaction::where('trip_number', $allTrip->trip_number)->count();
                                            $totalRidershipTicket += $ridershipTicket;
                
                                            //Farebox Collection
                                            $farebox = $allTrip->total_adult_amount + $allTrip->total_concession_amount;
                                            if($farebox==0){
                                                $checkTickets = TicketSalesTransaction::where('trip_number', $allTrip->trip_number)->get();
                                                if(count($checkTickets)>0){
                                                    foreach($checkTickets as $checkTicket){
                                                        $farebox += $checkTicket->actual_amount;
                                                    }
                                                }
                                            }
                                            $totalFarebox += $farebox;
                                        }
                                    }
                
                                    //Total Trip Served
                                    $totalTripServed = count($allTrips);
                
                                    //Total Missed Trip
                                    $diffIn = $tripPlannedIn - $tripServedIn;
                                    $diffOut = $tripPlannedOut - $tripServedOut;
                                    if($diffIn>0){
                                        if($diffOut>0){
                                            $totalMissedTrip = $diffIn + $diffOut;
                                        }else{
                                            $totalMissedTrip = $diffIn;
                                        }
                                    }else{
                                        if($diffOut>0){
                                            $totalMissedTrip = $diffOut;
                                        }else{
                                            $totalMissedTrip = 0;
                                        }
                                    }
                
                                    /**Total Breakdown & Total Accidents need to revise**/
                                    $totalBreakdown = 0;
                                    $totalAccidents = 0;
                
                                    if ($firstDate->isWeekDay()) {
                                        $trip_perDate['day'] = "WEEKDAY";
                                    } elseif ($firstDate->isWeekend()) {
                                        $trip_perDate['day'] = "WEEKEND";
                                    }
        
                                    //total per date
                                    $trip_perDate['total_trip_planned'] = $totalTripPlanned;
                                    $trip_perDate['total_km_planned'] = $totalKMPlanned;
                                    $trip_perDate['total_trip_served'] = $totalTripServed;
                                    $trip_perDate['total_km_served'] = $totalKMServed;
                                    $trip_perDate['total_missed_trip'] = $totalMissedTrip;
                                    $trip_perDate['total_early_late'] = $earlyLateCount;
                                    $trip_perDate['total_breakdown'] = $totalBreakdown;
                                    $trip_perDate['total_accidents'] = $totalAccidents;
                                    $trip_perDate['total_ridership_count'] = $totalRidershipCount;
                                    $trip_perDate['total_ridership_tickets'] = $totalRidershipTicket;
                                    $trip_perDate['total_farebox'] = $totalFarebox;
                
                                    $data_perDate[$all_date] = $trip_perDate;
        
                                    $routeTripPlanned += $trip_perDate['total_trip_planned'];
                                    $routeKMPlanned += $trip_perDate['total_km_planned'];
                                    $routeKMServed += $trip_perDate['total_km_served'];
                                    $routeTripServed += $trip_perDate['total_trip_served'];
                                    $routeMissedTrip += $trip_perDate['total_missed_trip'];
                                    $routeEarlyLate += $trip_perDate['total_early_late'];
                                    $routeBreakdown += $trip_perDate['total_breakdown'];
                                    $routeAccidents += $trip_perDate['total_accidents'];
                                    $routeRidershipCount += $trip_perDate['total_ridership_count'];
                                    $routeRidershipTicket += $trip_perDate['total_ridership_tickets'];
                                    $routeFarebox += $trip_perDate['total_farebox'];
                                }
        
                                //total per route
                                $trip_perRoute['total_trip_planned'] = $routeTripPlanned;
                                $trip_perRoute['total_km_planned'] = $routeKMPlanned;
                                $trip_perRoute['total_trip_served'] = $routeTripServed;
                                $trip_perRoute['total_km_served'] = $routeKMServed;
                                $trip_perRoute['total_missed_trip'] = $routeMissedTrip;
                                $trip_perRoute['total_early_late'] = $routeEarlyLate;
                                $trip_perRoute['total_breakdown'] = $routeBreakdown;
                                $trip_perRoute['total_accidents'] = $routeAccidents;
                                $trip_perRoute['total_ridership_count'] = $routeRidershipCount;
                                $trip_perRoute['total_ridership_tickets'] = $routeRidershipTicket;
                                $trip_perRoute['total_farebox'] = $routeFarebox;
        
                                $data_perDate['total_per_route'] = $trip_perRoute;
                                $data_perRoute[$routePerCompany->route_number . ' - ' . $routePerCompany->route_name] = $data_perDate;
        
                                $companyTripPlanned += $trip_perRoute['total_trip_planned'];
                                $companyKMPlanned += $trip_perRoute['total_km_planned'];
                                $companyKMServed += $trip_perRoute['total_km_served'];
                                $companyTripServed += $trip_perRoute['total_trip_served'];
                                $companyMissedTrip += $trip_perRoute['total_missed_trip'];
                                $companyEarlyLate += $trip_perRoute['total_early_late'];
                                $companyBreakdown += $trip_perRoute['total_breakdown'];
                                $companyAccidents += $trip_perRoute['total_accidents'];
                                $companyRidershipCount += $trip_perRoute['total_ridership_count'];
                                $companyRidershipTicket += $trip_perRoute['total_ridership_tickets'];
                                $companyFarebox += $trip_perRoute['total_farebox'];
                            }
                        }
        
                        //total per company
                        $trip_perCompany['total_trip_planned'] = $companyTripPlanned;
                        $trip_perCompany['total_km_planned'] = $companyKMPlanned;
                        $trip_perCompany['total_trip_served'] = $companyTripServed;
                        $trip_perCompany['total_km_served'] = $companyKMServed;
                        $trip_perCompany['total_missed_trip'] = $companyMissedTrip;
                        $trip_perCompany['total_early_late'] = $companyEarlyLate;
                        $trip_perCompany['total_breakdown'] = $companyBreakdown;
                        $trip_perCompany['total_accidents'] = $companyAccidents;
                        $trip_perCompany['total_ridership_count'] =$companyRidershipCount;
                        $trip_perCompany['total_ridership_tickets'] = $companyRidershipTicket;
                        $trip_perCompany['total_farebox'] = $companyFarebox;
        
                        $data_perRoute['total_per_company'] = $trip_perCompany;
                        $data_perCompany[$companyDetails->company_name] = $data_perRoute;
        
                        $data_grand['all_company'] = $data_perCompany;
                        $data_grand['grand'] = $trip_perCompany;
                        $summaryByRoute->add($data_grand);
                    }
                    //Summary By Route specific route specific company
                    else{
                        $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                        $routeTripPlanned = 0;
                        $routeKMPlanned = 0;
                        $routeKMServed = 0;
                        $routeTripServed = 0;
                        $routeMissedTrip = 0;
                        $routeEarlyLate = 0;
                        $routeBreakdown = 0;
                        $routeAccidents = 0;
                        $routeRidershipCount = 0;
                        $routeRidershipTicket = 0;
                        $routeFarebox = 0;
        
                        foreach ($all_dates as $all_date) {
                            $totalKMServed = 0;
                            $totalFarebox = 0;
                            $totalRidershipCount = 0;
                            $totalRidershipTicket = 0;
                            $earlyLateCount = 0;
                            $totalTripPlanned = 0;
                            $tripPlannedOut = 0; 
                            $tripPlannedIn = 0;
                            $kmInbound = 0;
                            $kmOutbound = 0;
                            $tripServedIn = 0;
                            $tripServedOut = 0;
                            $firstDate = new Carbon($all_date);
                            $lastDate = new Carbon($all_date .'23:59:59');
                            $isWeekday = false;
                            $isWeekend = false;

                            $isWeekday = $firstDate->isWeekday();
                            $isWeekend =  $firstDate->isWeekend();

                            if($isWeekday){
                                $isFriday = $firstDate->format('l');
                                if($isFriday=='Friday'){
                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,7,12])
                                    ->where('status', 1)
                                    ->where('route_id', $selectedRoute->id)
                                    ->count();
                                }else{
                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,4,6,9,10])
                                    ->where('status', 1)
                                    ->where('route_id', $selectedRoute->id)
                                    ->count();
                                }
                            }
                            if($isWeekend){
                                $isSunday = $firstDate->format('l');
                                if($isSunday=='Sunday'){
                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11])
                                    ->where('route_id', $selectedRoute->id)
                                    ->where('status', 1)
                                    ->count();
                                }else{
                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12])
                                    ->where('status', 1)
                                    ->where('route_id', $selectedRoute->id)
                                    ->count();
                                }
                                
                            }
                            $totalTripPlanned = $copies;
                            $totalKMPlanned = $selectedRoute->inbound_distance * $copies;

                            $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate, $lastDate])->get();
        
                            // if (count($schedules) > 0) {
                            //     foreach ($schedules as $schedule) {
                            //         if ($schedule->RouteScheduleMSTR->route_id == $selectedRoute->id) {
                            //             if ($schedule->RouteScheduleMSTR->trip_code == 0){
                            //                 $tripPlannedOut++;
                            //                 $kmInbound += $schedule->RouteScheduleMSTR->Route->outbound_distance;
                            //             }elseif ($schedule->RouteScheduleMSTR->trip_code == 1){
                            //                 $tripPlannedIn++;
                            //                 $kmOutbound += $schedule->RouteScheduleMSTR->Route->inbound_distance;
                            //             }
                            //         }
                            //     }
                            // }
                            // $totalTripPlanned = $tripPlannedOut + $tripPlannedIn;
                            // $totalKMPlanned = $kmInbound + $kmOutbound;
        
                            $allTrips = TripDetail::where('route_id', $selectedRoute->id)
                                ->whereBetween('start_trip', [$firstDate, $lastDate])
                                ->get();
        
                            if (count($allTrips) > 0) {
                                foreach ($allTrips as $allTrip) {
                                    //Total KM Service Served
                                    if($allTrip->trip_code==0){
                                        $tripServedOut++;
                                        $kmServed = $allTrip->Route->outbound_distance;
                                    }else{
                                        $tripServedIn++;
                                        $kmServed = $allTrip->Route->inbound_distance;
                                    }
                                    $totalKMServed += $kmServed;
        
                                    //Early-Late
                                    if (count($schedules) > 0) {
                                        foreach ($schedules as $schedule) {
                                            if ($schedule->RouteScheduleMSTR->route_id == $selectedRoute->id) {
                                                if ($schedule->RouteScheduleMSTR->id == $allTrip->route_schedule_mstr_id) {
                                                    $diff = strtotime($schedule->schedule_start_time) - strtotime($allTrip->start_trip);
                                                    if ($diff > 5 || $diff < -5) {
                                                        $earlyLateCount++;
                                                    }
                                                }
                                            }
                                        }
                                    }
        
                                    //Ridership Based On Count
                                    $ridershipCount = $allTrip->total_adult + $allTrip->total_concession;
                                    $totalRidershipCount += $ridershipCount;
        
                                    //Ridership Based On Ticket Sales
                                    $ridershipTicket = TicketSalesTransaction::where('trip_number', $allTrip->trip_number)->count();
                                    $totalRidershipTicket += $ridershipTicket;
        
                                    //Farebox Collection
                                    $farebox = $allTrip->total_adult_amount + $allTrip->total_concession_amount;
                                    if($farebox==0){
                                        $checkTickets = TicketSalesTransaction::where('trip_number', $allTrip->trip_number)->get();
                                        if(count($checkTickets)>0){
                                            foreach($checkTickets as $checkTicket){
                                                $farebox += $checkTicket->actual_amount;
                                            }
                                        }
                                    }
                                    $totalFarebox += $farebox;
                                }
                            }
        
                            //Total Trip Served
                            $totalTripServed = count($allTrips);
        
                            //Total Missed Trip
                            $diffIn = $tripPlannedIn - $tripServedIn;
                            $diffOut = $tripPlannedOut - $tripServedOut;
                            if($diffIn>0){
                                if($diffOut>0){
                                    $totalMissedTrip = $diffIn + $diffOut;
                                }else{
                                    $totalMissedTrip = $diffIn;
                                }
                            }else{
                                if($diffOut>0){
                                    $totalMissedTrip = $diffOut;
                                }else{
                                    $totalMissedTrip = 0;
                                }
                            }
        
                            /**Total Breakdown & Total Accidents need to revise**/
                            $totalBreakdown = 0;
                            $totalAccidents = 0;
        
                            if ($firstDate->isWeekDay()) {
                                $trip_perDate['day'] = "WEEKDAY";
                            } elseif ($firstDate->isWeekend()) {
                                $trip_perDate['day'] = "WEEKEND";
                            }
        
                            //total per date
                            $trip_perDate['total_trip_planned'] = $totalTripPlanned;
                            $trip_perDate['total_km_planned'] = $totalKMPlanned;
                            $trip_perDate['total_trip_served'] = $totalTripServed;
                            $trip_perDate['total_km_served'] = $totalKMServed;
                            $trip_perDate['total_missed_trip'] = $totalMissedTrip;
                            $trip_perDate['total_early_late'] = $earlyLateCount;
                            $trip_perDate['total_breakdown'] = $totalBreakdown;
                            $trip_perDate['total_accidents'] = $totalAccidents;
                            $trip_perDate['total_ridership_count'] = $totalRidershipCount;
                            $trip_perDate['total_ridership_tickets'] = $totalRidershipTicket;
                            $trip_perDate['total_farebox'] = $totalFarebox;
        
                            $data_perDate[$all_date] = $trip_perDate;
        
                            $routeTripPlanned += $trip_perDate['total_trip_planned'];
                            $routeKMPlanned += $trip_perDate['total_km_planned'];
                            $routeKMServed += $trip_perDate['total_km_served'];
                            $routeTripServed += $trip_perDate['total_trip_served'];
                            $routeMissedTrip += $trip_perDate['total_missed_trip'];
                            $routeEarlyLate += $trip_perDate['total_early_late'];
                            $routeBreakdown += $trip_perDate['total_breakdown'];
                            $routeAccidents += $trip_perDate['total_accidents'];
                            $routeRidershipCount += $trip_perDate['total_ridership_count'];
                            $routeRidershipTicket += $trip_perDate['total_ridership_tickets'];
                            $routeFarebox += $trip_perDate['total_farebox'];
                        }
        
                        //total per route
                        $trip_perRoute['total_trip_planned'] = $routeTripPlanned;
                        $trip_perRoute['total_km_planned'] = $routeKMPlanned;
                        $trip_perRoute['total_trip_served'] = $routeTripServed;
                        $trip_perRoute['total_km_served'] = $routeKMServed;
                        $trip_perRoute['total_missed_trip'] = $routeMissedTrip;
                        $trip_perRoute['total_early_late'] = $routeEarlyLate;
                        $trip_perRoute['total_breakdown'] = $routeBreakdown;
                        $trip_perRoute['total_accidents'] = $routeAccidents;
                        $trip_perRoute['total_ridership_count'] = $routeRidershipCount;
                        $trip_perRoute['total_ridership_tickets'] = $routeRidershipTicket;
                        $trip_perRoute['total_farebox'] = $routeFarebox;
        
                        $data_perDate['total_per_route'] = $trip_perRoute;
                        $data_perRoute[$selectedRoute->route_number . ' - ' . $selectedRoute->route_name] = $data_perDate;
        
                        $data_perRoute['total_per_company'] = $trip_perRoute;
                        $data_perCompany[$companyDetails->company_name] = $data_perRoute;
        
                        $data_grand['all_company'] = $data_perCompany;
                        $data_grand['grand'] = $trip_perRoute;
                        $summaryByRoute->add($data_grand);
                    }
                }
            }
            return Excel::download(new SPADSummaryByRoute($summaryByRoute, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Summary_By_Route_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printSummaryNetwork()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE INprintSummaryNetwork()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo'] . '23:59:59');

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
        $grandTng = 0;
        if ($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                if(!empty($validatedData['route_id'])) {
                    //Summary By Network all route all company
                    if($validatedData['route_id']=='All'){
                        $allRoutes = Route::orderBy('route_number')->get();
            
                        foreach($allRoutes as $allRoute) {
                            $totalKMServed = 0;
                            $totalFarebox = 0;
                            $totalTng = 0;
                            $totalRidership = 0;
                            $earlyLateCount = 0;
                            $totalTripPlanned = 0;
                            $tripPlannedIn = 0;
                            $tripPlannedOut = 0;
                            $kmInbound = 0;
                            $kmOutbound = 0;
            
                            $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->get();
                            if (count($schedules) > 0) {
                                foreach ($schedules as $schedule) {
                                    if ($schedule->RouteScheduleMSTR->route_id == $allRoute->id) {
                                        $totalTripPlanned++;
                                        if ($schedule->RouteScheduleMSTR->trip_code == 1) {
                                            $tripPlannedIn++;
                                            $kmInbound += $allRoute->inbound_distance;
                                        }else{
                                            $tripPlannedOut++;
                                            $kmOutbound += $allRoute->outbound_distance;
                                        }
                                    }
                                }
                            }
                            $totalKMPlanned = $kmInbound + $kmOutbound;
                            
                            $allTrips = TripDetail::where('route_id', $allRoute->id)
                                ->whereBetween('start_trip', [$dateFrom, $dateTo])
                                ->get();
            
                            $tripServed = 0;
                            $tripServedIn = 0;
                            $tripServedOut = 0;
                            if (count($allTrips) > 0) {
                                foreach ($allTrips as $allTrip) {
                                    $tripServed++;
            
                                    //Total KM Service Served
                                    if($allTrip->trip_code==0){
                                        $kmServed = $allTrip->Route->outbound_distance;
                                        $tripServedOut++;
                                    }
                                    elseif($allTrip->trip_code==1){
                                        $kmServed = $allTrip->Route->inbound_distance;
                                        $tripServedIn++;
                                    }
                                    $totalKMServed += $kmServed;
                                    
                                    //Early-Late
                                    if ($allTrip->route_schedule_mstr_id != NULL) {
                                        $diff = strtotime($allTrip->routeScheduleMSTR->schedule_start_time) - strtotime($allTrip->start_trip);
                                        if ($diff > 5 || $diff < -5) {
                                            $earlyLateCount++;
                                        }
                                    }
            
                                    //Ridership
                                    $ridership = $allTrip->total_adult + $allTrip->total_concession;
                                    if($ridership==0){
                                        $ridership = TicketSalesTransaction::where('trip_id', $allTrip->id)->count();
                                    }
                                    $totalRidership += $ridership;
                                    
                                    //Farebox && TnG Collection 
                                    $farebox = 0;
                                    $tng = 0;
                                    $ticketsPerTrips = TicketSalesTransaction::where('trip_id', $allTrip->id)->get();
                                    if(count($ticketsPerTrips)>0){
                                        foreach($ticketsPerTrips as $ticketsPerTrip){
                                            if($ticketsPerTrip->fare_type==0){
                                                $farebox += $ticketsPerTrip->actual_amount;
                                            }elseif($ticketsPerTrip->fare_type==2){
                                                $tng += $ticketsPerTrip->actual_amount;
                                            }
                                        }
                                    }
                                    $totalFarebox += $farebox;
                                    $totalTng += $tng;
                                }
                            }
            
                            //Total No Bus Deployed
                            $totalBusDeployed = TripDetail::where('route_id', $allRoute->id)
                                ->whereBetween('start_trip', [$dateFrom, $dateTo])
                                ->distinct('bus_id')
                                ->count();
            
                            //Total Trip Served
                            $totalTripServed = $tripServed;
            
                            //Total Missed Trip
                            $diffIn = $tripPlannedIn - $tripServedIn;
                            $diffOut = $tripPlannedOut - $tripServedOut;
                            if($diffIn>0){
                                if($diffOut>0){
                                    $totalMissedTrip = $diffIn + $diffOut;
                                }else{
                                    $totalMissedTrip = $diffIn;
                                }
                            }else{
                                if($diffOut>0){
                                    $totalMissedTrip = $diffOut;
                                }else{
                                    $totalMissedTrip = 0;
                                }
                            }
            
                            //Total Claim = KM/day * 1.33
                            $claim = 1.33 * $totalKMServed;
                            $totalClaim = round($claim,0);
            
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
                            $perRoute['total_tng'] = $totalTng;
            
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
                            $grandTng += $perRoute['total_tng'];
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
                        $grand['grand_tng'] = $grandTng;
            
                        $data['grand'] = $grand;
                        $summaryByNetwork->add($data);
                    }
                }
            }else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $companyDetails->company_name;
                if(!empty($validatedData['route_id'])) {
                    //Summary By Network all route specific company
                    if($validatedData['route_id']=='All'){
                        $routeByCompanies = Route::where('company_id', $companyDetails->id)->orderBy('route_number')->get();
        
                        foreach($routeByCompanies as $routeByCompany) {
                            $totalKMServed = 0;
                            $totalFarebox = 0;
                            $totalTng = 0;
                            $totalRidership = 0;
                            $earlyLateCount = 0;
                            $totalTripPlanned = 0;
                            $kmInbound = 0;
                            $kmOutbound = 0;
                            $tripPlannedOut = 0;
                            $tripPlannedIn = 0;
        
                            $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->get();
                            if (count($schedules) > 0) {
                                foreach ($schedules as $schedule) {
                                    if ($schedule->RouteScheduleMSTR->route_id == $routeByCompany->id) {
                                        $totalTripPlanned++;
                                        if ($schedule->RouteScheduleMSTR->trip_code == 1) {
                                            $tripPlannedIn++;
                                            $kmInbound += $routeByCompany->inbound_distance;
                                        }else{
                                            $tripPlannedOut++;
                                            $kmOutbound += $routeByCompany->outbound_distance;
                                        }
                                    }
                                }
                            }
                            $totalKMPlanned = $kmInbound + $kmOutbound;
        
                            $allTrips = TripDetail::where('route_id', $routeByCompany->id)
                                ->whereBetween('start_trip', [$dateFrom, $dateTo])
                                ->get();
        
                            $tripServed = 0;
                            $tripServedIn = 0;
                            $tripServedOut = 0;
                            if (count($allTrips) > 0) {
                                foreach ($allTrips as $allTrip) {
                                    $tripServed++;
        
                                   //Total KM Service Served
                                    if($allTrip->trip_code==0){
                                        $kmServed = $allTrip->Route->outbound_distance;
                                        $tripServedOut++;
                                    }
                                    elseif($allTrip->trip_code==1){
                                        $kmServed = $allTrip->Route->inbound_distance;
                                        $tripServedIn++;
                                    }
                                    $totalKMServed += $kmServed;
        
                                    //Early-Late
                                    if ($allTrip->route_schedule_mstr_id != NULL) {
                                        $diff = strtotime($allTrip->routeScheduleMSTR->schedule_start_time) - strtotime($allTrip->start_trip);
                                        if ($diff > 5 || $diff < -5) {
                                            $earlyLateCount++;
                                        }
                                    }
        
                                    //Ridership
                                    $ridership = $allTrip->total_adult + $allTrip->total_concession;
                                    if($ridership==0){
                                        $ridership = TicketSalesTransaction::where('trip_id', $allTrip->id)->count();
                                    }
                                    $totalRidership += $ridership;
        
                                    //Farebox && TnG Collection 
                                    $farebox = 0;
                                    $tng = 0;
                                    $ticketsPerTrips = TicketSalesTransaction::where('trip_id', $allTrip->id)->get();
                                    if(count($ticketsPerTrips)>0){
                                        foreach($ticketsPerTrips as $ticketsPerTrip){
                                            if($ticketsPerTrip->fare_type==0){
                                                $farebox += $ticketsPerTrip->actual_amount;
                                            }elseif($ticketsPerTrip->fare_type==2){
                                                $tng += $ticketsPerTrip->actual_amount;
                                            }
                                        }
                                    }
                                    $totalFarebox += $farebox;
                                    $totalTng += $tng;
                                }
                            }
        
                            //Total No Bus Deployed
                            $totalBusDeployed = TripDetail::where('route_id', $routeByCompany->id)
                                ->whereBetween('start_trip', [$dateFrom, $dateTo])
                                ->distinct('bus_id')
                                ->count();
        
                            //Total Trip Served
                            $totalTripServed = $tripServed;
        
                            //Total Missed Trip
                            $diffIn = $tripPlannedIn - $tripServedIn;
                            $diffOut = $tripPlannedOut - $tripServedOut;
                            if($diffIn>0){
                                if($diffOut>0){
                                    $totalMissedTrip = $diffIn + $diffOut;
                                }else{
                                    $totalMissedTrip = $diffIn;
                                }
                            }else{
                                if($diffOut>0){
                                    $totalMissedTrip = $diffOut;
                                }else{
                                    $totalMissedTrip = 0;
                                }
                            }
        
                            //Total Claim = KM/day * 1.33
                            $claim = 1.33 * $totalKMServed;
                            $totalClaim = round($claim,0);
        
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
                            $perRoute['total_tng'] = $totalTng;
        
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
                            $grandTng += $perRoute['total_tng'];
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
                        $grand['grand_tng'] = $grandTng;
        
                        $data['grand'] = $grand;
                        $summaryByNetwork->add($data);
                    }
                    //Summary By Network specific route specific company
                    else{
                        $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                        $totalKMServed = 0;
                        $totalFarebox = 0;
                        $totalTng = 0;
                        $totalRidership = 0;
                        $earlyLateCount = 0;
                        $totalTripPlanned = 0;
                        $tripPlannedIn = 0;
                        $tripPlannedOut = 0;
                        $kmInbound = 0;
                        $kmOutbound = 0;
                        $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->get();
                        if (count($schedules) > 0) {
                            foreach ($schedules as $schedule) {
                                if ($schedule->RouteScheduleMSTR->route_id == $selectedRoute->id) {
                                    $totalTripPlanned++;
                                    if ($schedule->RouteScheduleMSTR->trip_code == 1) {
                                        $tripPlannedIn++;
                                        $kmInbound += $selectedRoute->inbound_distance;
                                    }else{
                                        $tripPlannedOut++;
                                        $kmOutbound += $selectedRoute->outbound_distance;
                                    }
                                }
                            }
                        }
                        $totalKMPlanned = $kmInbound + $kmOutbound;
        
                        $allTrips = TripDetail::where('route_id', $selectedRoute->id)
                            ->whereBetween('start_trip', [$dateFrom, $dateTo])
                            ->get();
        
                        $tripServed = 0;
                        $tripServedIn = 0;
                        $tripServedOut = 0;
                        if (count($allTrips) > 0) {
                            foreach ($allTrips as $allTrip) {
                                $tripServed++;
                                
                                 //Total KM Service Served
                                 if($allTrip->trip_code==0){
                                    $kmServed = $allTrip->Route->outbound_distance;
                                    $tripServedOut++;
                                }
                                elseif($allTrip->trip_code==1){
                                    $kmServed = $allTrip->Route->inbound_distance;
                                    $tripServedIn++;
                                }
                                $totalKMServed += $kmServed;
        
                                //Early-Late
                                if ($allTrip->route_schedule_mstr_id != NULL) {
                                    $diff = strtotime($allTrip->routeScheduleMSTR->schedule_start_time) - strtotime($allTrip->start_trip);
                                    if ($diff > 5 || $diff < -5) {
                                        $earlyLateCount++;
                                    }
                                }
        
                                //Ridership
                                $ridership = $ridership = $allTrip->total_adult + $allTrip->total_concession;
                                if($ridership==0){
                                    $ridership = TicketSalesTransaction::where('trip_id', $allTrip->id)->count();
                                }
                                $totalRidership += $ridership;
        
                                //Farebox && TnG Collection 
                                $farebox = 0;
                                $tng = 0;
                                $ticketsPerTrips = TicketSalesTransaction::where('trip_id', $allTrip->id)->get();
                                if(count($ticketsPerTrips)>0){
                                    foreach($ticketsPerTrips as $ticketsPerTrip){
                                        if($ticketsPerTrip->fare_type==0){
                                            $farebox += $ticketsPerTrip->actual_amount;
                                        }elseif($ticketsPerTrip->fare_type==2){
                                            $tng += $ticketsPerTrip->actual_amount;
                                        }
                                    }
                                }
                                $totalFarebox += $farebox;
                                $totalTng += $tng;
                            }
                        }
        
                        //Total No Bus Deployed
                        $totalBusDeployed = TripDetail::where('route_id', $selectedRoute->id)
                            ->whereBetween('start_trip', [$dateFrom, $dateTo])
                            ->distinct('bus_id')
                            ->count();
        
                        //Total Trip Served
                        $totalTripServed = $tripServed;
        
                        //Total Missed Trip
                        $diffIn = $tripPlannedIn - $tripServedIn;
                        $diffOut = $tripPlannedOut - $tripServedOut;
                        if($diffIn>0){
                            if($diffOut>0){
                                $totalMissedTrip = $diffIn + $diffOut;
                            }else{
                                $totalMissedTrip = $diffIn;
                            }
                        }else{
                            if($diffOut>0){
                                $totalMissedTrip = $diffOut;
                            }else{
                                $totalMissedTrip = 0;
                            }
                        }
        
                        //Total Claim = KM/day * 1.33
                        $claim = 1.33 * $totalKMServed;
                        $totalClaim = round($claim,0);
        
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
                        $perRoute['total_tng'] = $totalTng;
        
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
                        $grandTng += $perRoute['total_tng'];
        
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
                        $grand['grand_tng'] = $grandTng;
        
                        $data['grand'] = $grand;
                        $summaryByNetwork->add($data);
                    }
                }
            }
            return Excel::download(new SPADSummaryByNetwork($summaryByNetwork, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Summary_By_Network_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printISBSF()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printISBSF()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

        $dateFrom = new Carbon($validatedData['dateFrom']);

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
        if ($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                if(!empty($validatedData['route_id'])) {
                    //ISBSF all route all company
                    if($validatedData['route_id']=='All'){
                        $allRoutes = Route::orderBy('route_number')->get();
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
                                $lastDate = new Carbon($all_date .'23:59:59');
            
                                //Planned Trip
                                $plannedTrip = 0;
                                $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate,$lastDate])->get();
                                if (count($schedules) > 0) {
                                    foreach ($schedules as $schedule) {
                                        if ($schedule->RouteScheduleMSTR->route_id == $allRoute->id) {
                                            $plannedTrip++;
                                        }
                                    }
                                }
                                $plannedTripArr[$all_date] = $plannedTrip;
                                $finalPlannedTrip += $plannedTrip;
            
                                $allTrips = TripDetail::where('route_id',  $allRoute->id)
                                    ->whereBetween('start_trip', [$firstDate,$lastDate])
                                    ->get();
            
                                $ontimeCount = 0;
                                $farebox =0;
                                $ridership =0;
                                $completedOut =0;
                                $completedIn =0;
                                if(count($allTrips)>0) {
                                    foreach ($allTrips as $allTrip) {
                                        //Trip On Time
                                        if($allTrip->route_schedule_mstr_id!=NULL){
                                            $diff = strtotime($allTrip->RouteScheduleMSTR->schedule_start_time) - strtotime($allTrip->start_trip);
                                            if ($diff < 5 || $diff > -5) {
                                                $ontimeCount++;
                                            }
                                        }
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
            
                                        //Completed Trip Out & In
                                        if($allTrip->trip_code==0){
                                            $completedOut++;
                                        }elseif($allTrip->trip_code==1){
                                            $completedIn++;
                                        }
                                    }
                                }
                                $totalTripOnTimeArr[$all_date] = $ontimeCount;
                                $finalTripOnTime += $ontimeCount;
            
                                $fareboxArr[$all_date] = $farebox;
                                $finalFarebox += $farebox;
            
                                $ridershipArr[$all_date] = $ridership;
                                $finalRidership += $ridership;
            
                                $completedOutArr[$all_date] = $completedOut;
                                $finalCompletedOut += $completedOut;
            
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
                                    $tripCompliance = round((($totalCompletedTrip/$plannedTrip) * 100),1);
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
                                $totalDistanceOut = $outboundDistance * $completedOut;
                                $totalDistanceOutArr[$all_date] = $totalDistanceOut;
                                $finalTotalDistanceOut += $totalDistanceOut;
            
                                //Total KM Inbound
                                $totalDistanceIn = $inboundDistance * $completedIn;
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
                                    $punctuality = round((($ontimeCount / $totalCompletedTrip) * 100),1);
                                    $realibility =  round(((($totalCompletedTrip - $totalTripBreakdown) / $totalCompletedTrip) * 100),1);
                                    $routeCompliance = round(((($totalCompletedTrip - $offRoute) / $totalCompletedTrip) * 100),1);
                                }
                                $punctualityArr[$all_date] = $punctuality;
                                $sumPunctuality += $punctuality;
                                $realibilityArr[$all_date] = $realibility;
                                $sumRealibility += $realibility;
                                $routeComplianceArr[$all_date] = $routeCompliance;
                                $sumRouteCompliance += $routeCompliance;
            
                                //Number of Bus
                                $numBus = TripDetail::whereBetween('start_trip',[$firstDate,$lastDate])
                                    ->where('route_id', $allRoute->id)
                                    ->groupBy('bus_id')
                                    ->count();
                                $numBusArr[$all_date] = $numBus;
                                $finalNumBus += $numBus;
                            }
                            $finalTripCompliance = round((($sumTripCompliance/(count($all_dates)*100))*100),1);
                            $finalRouteCompliance = round((($sumRouteCompliance/(count($all_dates)*100))*100),1);
                            $finalPunctuality = round((($sumPunctuality/(count($all_dates)*100))*100),1);
                            $finalRealibility = round((($sumRealibility/(count($all_dates)*100))*100),1);
            
                            // $tripComplianceFormat = number_format((float)$finalTripCompliance, 1, '.', '');
                            // $routeComplianceFormat = number_format((float)$finalRouteCompliance, 1, '.', '');
                            // $punctualityFormat = number_format((float)$finalPunctuality, 1, '.', '');
                            // $realibilityFormat = number_format((float)$finalRealibility, 1, '.', '');
            
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
            }else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $companyDetails->company_name;

                if(!empty($validatedData['route_id'])) {
                    //ISBSF all route specific company
                    if($validatedData['route_id']=='All'){
                        $routeByCompanies = Route::where('company_id', $this->selectedCompany)->orderBy('route_number')->get();
        
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
                                $lastDate = new Carbon($all_date . '23:59:59');
        
                                //Planned Trip
                                $plannedTrip = 0;
                                $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate,$lastDate])->get();
                                if (count($schedules) > 0) {
                                    foreach ($schedules as $schedule) {
                                        if ($schedule->RouteScheduleMSTR->route_id == $routeByCompany->id) {
                                            $plannedTrip++;
                                        }
                                    }
                                }
                                $plannedTripArr[$all_date] = $plannedTrip;
                                $finalPlannedTrip += $plannedTrip;
        
                                $allTrips = TripDetail::where('route_id',  $routeByCompany->id)
                                    ->whereBetween('start_trip',[$firstDate,$lastDate])
                                    ->get();
        
                                $ontimeCount = 0;
                                $farebox =0;
                                $ridership =0;
                                $completedOut = 0;
                                $completedIn = 0;
                                if(count($allTrips)>0) {
                                    foreach ($allTrips as $allTrip) {
                                        //Trip On Time
                                        if($allTrip->route_schedule_mstr_id!=NULL){
                                            $diff = strtotime($allTrip->RouteScheduleMSTR->schedule_start_time) - strtotime($allTrip->start_trip);
                                            if ($diff < 5 || $diff > -5) {
                                                $ontimeCount++;
                                            }
                                        }
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
        
                                        //Completed Trip Out & In
                                        if($allTrip->trip_code==0){
                                            $completedOut++;
                                        }elseif($allTrip->trip_code==1){
                                            $completedIn++;
                                        }
                                    }
                                }
                                $totalTripOnTimeArr[$all_date] = $ontimeCount;
                                $finalTripOnTime += $ontimeCount;
                                $fareboxArr[$all_date] = $farebox;
                                $finalFarebox += $farebox;
                                $ridershipArr[$all_date] = $ridership;
                                $finalRidership += $ridership;
                                $completedOutArr[$all_date] = $completedOut;
                                $finalCompletedOut += $completedOut;
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
                                    $tripCompliance = round((($totalCompletedTrip/$plannedTrip) * 100),1);
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
                                $totalDistanceOut = $outboundDistance * $completedOut;
                                $totalDistanceOutArr[$all_date] = $totalDistanceOut;
                                $finalTotalDistanceOut += $totalDistanceOut;
            
                                //Total KM Inbound
                                $totalDistanceIn = $inboundDistance * $completedIn;
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
                                    $punctuality = round((($ontimeCount / $totalCompletedTrip) * 100),1);
                                    $realibility = round(((($totalCompletedTrip - $totalTripBreakdown) / $totalCompletedTrip) * 100),1);
                                    $routeCompliance = round(((($totalCompletedTrip - $offRoute) / $totalCompletedTrip) * 100),1);
                                }
                                $punctualityArr[$all_date] = $punctuality;
                                $sumPunctuality += $punctuality;
                                $realibilityArr[$all_date] = $realibility;
                                $sumRealibility += $realibility;
                                $routeComplianceArr[$all_date] = $routeCompliance;
                                $sumRouteCompliance += $routeCompliance;
        
                                //Number of Bus
                                $numBus = TripDetail::whereBetween('start_trip',[$firstDate,$lastDate])
                                    ->where('route_id',  $routeByCompany->id)
                                    ->groupBy('bus_id')
                                    ->count();
                                $numBusArr[$all_date] = $numBus;
                                $finalNumBus += $numBus;
                            }
                            $finalTripCompliance = round((($sumTripCompliance/(count($all_dates)*100))*100),1);
                            $finalRouteCompliance = round((($sumRouteCompliance/(count($all_dates)*100))*100),1);
                            $finalPunctuality = round((($sumPunctuality/(count($all_dates)*100))*100),1);
                            $finalRealibility = round((($sumRealibility/(count($all_dates)*100))*100),1);
        
                            // $tripComplianceFormat = number_format((float)$finalTripCompliance, 1, '.', '');
                            // $routeComplianceFormat = number_format((float)$finalRouteCompliance, 1, '.', '');
                            // $punctualityFormat = number_format((float)$finalPunctuality, 1, '.', '');
                            // $realibilityFormat = number_format((float)$finalRealibility, 1, '.', '');
        
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
                    //ISBSF specific route specific company
                    else{
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
                            $lastDate = new Carbon($all_date . '23:59:59');
        
                            //Planned Trip
                            //Total Trip On Time
                            $plannedTrip = 0;
                            $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$firstDate,$lastDate])->get();
                            if (count($schedules) > 0) {
                                foreach ($schedules as $schedule) {
                                    if ($schedule->RouteScheduleMSTR->route_id == $validatedRoute->id) {
                                        $plannedTrip++;
                                    }
                                }
                            }
                            $plannedTripArr[$all_date] = $plannedTrip;
                            $finalPlannedTrip += $plannedTrip;
        
                            $allTrips = TripDetail::where('route_id',  $validatedRoute->id)
                                ->whereBetween('start_trip', [$firstDate,$lastDate])
                                ->get();
        
                            $ontimeCount = 0;
                            $farebox =0;
                            $ridership =0;
                            $completedOut =0;
                            $completedIn =0;
                            if(count($allTrips)>0){
                                foreach ($allTrips as $allTrip){
                                    //Trip On Time
                                    if($allTrip->route_schedule_mstr_id!=NULL){
                                        $diff = strtotime($allTrip->RouteScheduleMSTR->schedule_start_time) - strtotime($allTrip->start_trip);
                                        if ($diff < 5 || $diff > -5) {
                                            $ontimeCount++;
                                        }
                                    }
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
                                    //Completed Trip Out & In
                                    if($allTrip->trip_code==0){
                                        $completedOut++;
                                    }elseif($allTrip->trip_code==1){
                                        $completedIn++;
                                    }
                                }
                            }
                            $totalTripOnTimeArr[$all_date] = $ontimeCount;
                            $finalTripOnTime += $ontimeCount;
                            $fareboxArr[$all_date] = $farebox;
                            $finalFarebox += $farebox;
                            $ridershipArr[$all_date] = $ridership;
                            $finalRidership += $ridership;
                            $completedOutArr[$all_date] = $completedOut;
                            $finalCompletedOut += $completedOut;
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
                                $tripCompliance = round((($totalCompletedTrip/$plannedTrip) * 100),1);
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
                            $totalDistanceOut = $outboundDistance * $completedOut;
                            $totalDistanceOutArr[$all_date] = $totalDistanceOut;
                            $finalTotalDistanceOut += $totalDistanceOut;
        
                            //Total KM Inbound
                            $totalDistanceIn = $inboundDistance * $completedIn;
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
                                $punctuality = round((($ontimeCount / $totalCompletedTrip) * 100),1);
                                $realibility = round(((($totalCompletedTrip - $totalTripBreakdown) / $totalCompletedTrip) * 100),1);
                                $routeCompliance = round(((($totalCompletedTrip - $offRoute) / $totalCompletedTrip) * 100),1);
        
                            }
                            $punctualityArr[$all_date] = $punctuality;
                            $sumPunctuality += $punctuality;
                            $realibilityArr[$all_date] = $realibility;
                            $sumRealibility += $realibility;
                            $routeComplianceArr[$all_date] = $routeCompliance;
                            $sumRouteCompliance += $routeCompliance;
        
                            //Number of Bus
                            $numBus = TripDetail::whereBetween('start_trip',[$firstDate,$lastDate])
                                ->where('route_id',  $validatedRoute->id)
                                ->groupBy('bus_id')
                                ->count();
                            $numBusArr[$all_date] = $numBus;
                            $finalNumBus += $numBus;
                        }
                        $finalTripCompliance = round((($sumTripCompliance/(count($all_dates)*100))*100),1);
                        $finalRouteCompliance = round((($sumRouteCompliance/(count($all_dates)*100))*100),1);
                        $finalPunctuality = round((($sumPunctuality/(count($all_dates)*100))*100),1);
                        $finalRealibility = round((($sumRealibility/(count($all_dates)*100))*100),1);
        
                        // $tripComplianceFormat = number_format((float)$finalTripCompliance, 1, '.', '');
                        // $routeComplianceFormat = number_format((float)$finalRouteCompliance, 1, '.', '');
                        // $punctualityFormat = number_format((float)$finalPunctuality, 1, '.', '');
                        // $realibilityFormat = number_format((float)$finalRealibility, 1, '.', '');
        
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
                        //$data[$i++] = $route;
        
                        $isbsfSPAD->add($data);
                    } 
                }
            }
            return Excel::download(new SPADIsbsf($isbsfSPAD, $validatedData['dateFrom'], $validatedData['dateTo'], $colspan, $all_dates,$monthName, $days, $networkArea), 'ISBSF_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printTripMissed()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN  printTripMissed()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

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
        if ($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                if(!empty($validatedData['route_id'])) {
                   //TripMissed all route for all company
                    if($validatedData['route_id']=='All'){
                        $allRoutes = Route::orderBy('route_number')->get();
            
                        foreach($allRoutes as $allRoute){
                            $routeNameIn = $allRoute->route_name;
                            $routeNameOut = implode(" - ",array_reverse(explode(" - ", $routeNameIn)));
                            $totalTripMissedIn = 0;
                            $totalTripMissedOut = 0;
                            $trip_data = [];
            
                            foreach($all_dates as $all_date) {
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date . '23:59:59');
                                $countTripMissedIn = 0;
                                $countTripMissedOut = 0;
                                $tripIn = [];
                                $tripOut = [];
            
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
                                        if ($tripPlan->RouteScheduleMSTR->route_id == $allRoute->id) {
                                            $tripServedIn = false;
                                            $tripServedOut = false;
            
                                            if(count($allInboundTrips) > 0){
                                                foreach ($allInboundTrips as $allInboundTrip) {
                                                    if ($tripPlan->RouteScheduleMSTR->id == $allInboundTrip->route_schedule_mstr_id) {
                                                        $tripServedIn = true;
                                                    }
                                                }
                                            }

                                            if ($tripServedIn == false) {
                                                $countTripMissedIn++;
                                                $notServedIn['route_name'] = $routeNameIn;
                                                $notServedIn['trip_no'] = 'T' . $countTripMissedIn;
                                                if($tripPlan->route_schedule_mstr_id != NULL){
                                                    $notServedIn['trip_time'] = $tripPlan->RouteScheduleMSTR->schedule_start_time;
                                                    $notServedIn['bus_reg_no'] = $tripPlan->RouteScheduleMSTR->Bus->bus_registration_number;
                                                    $notServedIn['bus_age'] = $tripPlan->RouteScheduleMSTR->Bus->bus_age;
                                                }else{
                                                    $notServedIn['trip_time'] = 'NO DATA';
                                                    $notServedIn['bus_reg_no'] = 'NO DATA';
                                                    $notServedIn['bus_age'] = 'NO DATA';
                                                }
                                                $notServedIn['km_rate'] = 1.33;
                                                $tripIn[$countTripMissedIn] = $notServedIn;
                                            }
            
                                            if(count($allOutboundTrips) > 0){
                                                foreach ($allOutboundTrips as $allOutboundTrip) {
                                                    if ($tripPlan->RouteScheduleMSTR->id == $allOutboundTrip->route_schedule_mstr_id) {
                                                        $tripServedOut = true;
                                                    }
                                                }
                                            }

                                            if ($tripServedOut == false) {
                                                $countTripMissedOut++;
                                                $notServedOut['route_name'] = $routeNameOut;
                                                $notServedOut['trip_no'] = 'T' . $countTripMissedOut;
                                                if($tripPlan->route_schedule_mstr_id != NULL){
                                                    $notServedOut['trip_time'] = $tripPlan->RouteScheduleMSTR->schedule_start_time;
                                                    $notServedOut['bus_reg_no'] = $tripPlan->RouteScheduleMSTR->Bus->bus_registration_number;
                                                    $notServedOut['bus_age'] = $tripPlan->RouteScheduleMSTR->Bus->bus_age;
                                                }else{
                                                    $notServedOut['trip_time'] = 'NO DATA';
                                                    $notServedOut['bus_reg_no'] = 'NO DATA';
                                                    $notServedOut['bus_age'] = 'NO DATA';
                                                }
                                                $notServedOut['km_rate'] = 1.33;
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
                            $grand = [];
                        }else{
                            $grand['allRoute'] = $route;
                            $grand['grand'] = $sumGrand;
                        }
            
                        $tripMissed->add($grand);
                    }
                }
            }else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $companyDetails->company_name;
                if(!empty($validatedData['route_id'])) {
                    //TripMissed all route for specific company
                    if($validatedData['route_id']=='All'){
                        $routeByCompanies = Route::where('company_id', $companyDetails->id)->orderBy('route_number')->get();
        
                        foreach($routeByCompanies as $routeByCompany){
                            $routeNameIn = $routeByCompany->route_name;
                            $routeNameOut = implode(" - ",array_reverse(explode(" - ", $routeNameIn)));
                            $totalTripMissedIn = 0;
                            $totalTripMissedOut = 0;
                            $trip_data = [];
        
                            foreach($all_dates as $all_date){
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date . '23:59:59');
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
        
                                            if(count($allInboundTrips)>0) {
                                                foreach ($allInboundTrips as $allInboundTrip) {
                                                    if ($tripPlan->RouteScheduleMSTR->id == $allInboundTrip->route_schedule_mstr_id) {
                                                        $tripServedIn = true;
                                                    }
                                                }
                                            }
                                            if ($tripServedIn == false) {
                                                $countTripMissedIn++;
                                                $notServedIn['route_name'] = $routeNameIn;
                                                $notServedIn['trip_no'] = 'T' . $countTripMissedIn;
                                                if($tripPlan->route_schedule_mstr_id != NULL){
                                                    $notServedIn['trip_time'] = $tripPlan->RouteScheduleMSTR->schedule_start_time;
                                                    $notServedIn['bus_reg_no'] = $tripPlan->RouteScheduleMSTR->Bus->bus_registration_number;
                                                    $notServedIn['bus_age'] = $tripPlan->RouteScheduleMSTR->Bus->bus_age;
                                                }else{
                                                    $notServedIn['trip_time'] = 'NO DATA';
                                                    $notServedIn['bus_reg_no'] = 'NO DATA';
                                                    $notServedIn['bus_age'] = 'NO DATA';
                                                }
                                                $notServedIn['km_rate'] = 1.33;
        
                                                $tripIn[$countTripMissedIn] = $notServedIn;
                                            }
        
                                            if(count($allOutboundTrips)>0) {
                                                foreach ($allOutboundTrips as $allOutboundTrip) {
                                                    if ($tripPlan->RouteScheduleMSTR->id == $allOutboundTrip->route_schedule_mstr_id) {
                                                        $tripServedOut = true;
                                                    }
                                                }
                                            }
                                            if ($tripServedOut == false) {
                                                $countTripMissedOut++;
                                                $notServedOut['route_name'] = $routeNameOut;
                                                $notServedOut['trip_no'] = 'T' . $countTripMissedOut;
                                                if($tripPlan->route_schedule_mstr_id != NULL){
                                                    $notServedOut['trip_time'] = $tripPlan->RouteScheduleMSTR->schedule_start_time;
                                                    $notServedOut['bus_reg_no'] = $tripPlan->RouteScheduleMSTR->Bus->bus_registration_number;
                                                    $notServedOut['bus_age'] = $tripPlan->RouteScheduleMSTR->Bus->bus_age;
                                                }else{
                                                    $notServedOut['trip_time'] = 'NO DATA';
                                                    $notServedOut['bus_reg_no'] = 'NO DATA';
                                                    $notServedOut['bus_age'] = 'NO DATA';
                                                }
                                                $notServedOut['km_rate'] = 1.33;
        
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
                            $grand = [];
                        }else{
                            $grand['allRoute'] = $route;
                            $grand['grand'] = $sumGrand;
                        }
        
                        $tripMissed->add($grand);
                    }
                    //TripMissed certain route for specific company
                    else{
                        $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                        $routeNameIn = $selectedRoute->route_name;
                        $routeNameOut = implode(" - ",array_reverse(explode(" - ", $routeNameIn)));
                        $totalTripMissedIn = 0;
                        $totalTripMissedOut = 0;
                        $trip_data = [];
        
                        foreach($all_dates as $all_date){
                            $firstDate = new Carbon($all_date);
                            $lastDate = new Carbon($all_date .'23:59:59');
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
        
                                        if(count($allInboundTrips)>0) {
                                            foreach ($allInboundTrips as $allInboundTrip) {
                                                if ($tripPlan->RouteScheduleMSTR->id == $allInboundTrip->route_schedule_mstr_id) {
                                                    $tripServedIn = true;
                                                }
                                            }
                                        }
                                        if ($tripServedIn == false) {
                                            $countTripMissedIn++;
                                            $notServedIn['route_name'] = $routeNameIn;
                                            $notServedIn['trip_no'] = 'T' . $countTripMissedIn;
                                            if($tripPlan->route_schedule_mstr_id != NULL){
                                                $notServedIn['trip_time'] = $tripPlan->RouteScheduleMSTR->schedule_start_time;
                                                $notServedIn['bus_reg_no'] = $tripPlan->RouteScheduleMSTR->Bus->bus_registration_number;
                                                $notServedIn['bus_age'] = $tripPlan->RouteScheduleMSTR->Bus->bus_age;
                                            }else{
                                                $notServedIn['trip_time'] = 'NO DATA';
                                                $notServedIn['bus_reg_no'] = 'NO DATA';
                                                $notServedIn['bus_age'] = 'NO DATA';
                                            }
                                            $notServedIn['km_rate'] = 1.33;
                                            $tripIn[$countTripMissedIn] = $notServedIn;
                                        }
        
                                        if(count($allOutboundTrips)>0) {
                                            foreach ($allOutboundTrips as $allOutboundTrip) {
                                                if ($tripPlan->RouteScheduleMSTR->id == $allOutboundTrip->route_schedule_mstr_id) {
                                                    $tripServedOut = true;
                                                }
                                            }
                                        }
                                        if ($tripServedOut == false) {
                                            $countTripMissedOut++;
                                            $notServedOut['route_name'] = $routeNameOut;
                                            $notServedOut['trip_no'] = 'T' . $countTripMissedOut;
                                            if($tripPlan->route_schedule_mstr_id != NULL){
                                                $notServedOut['trip_time'] = $tripPlan->RouteScheduleMSTR->schedule_start_time;
                                                $notServedOut['bus_reg_no'] = $tripPlan->RouteScheduleMSTR->Bus->bus_registration_number;
                                                $notServedOut['bus_age'] = $tripPlan->RouteScheduleMSTR->Bus->bus_age;
                                            }else{
                                                $notServedOut['trip_time'] = 'NO DATA';
                                                $notServedOut['bus_reg_no'] = 'NO DATA';
                                                $notServedOut['bus_age'] = 'NO DATA';
                                            }
                                            $notServedOut['km_rate'] = 1.33;
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
                }
            }
            return Excel::download(new SPADTripMissed($tripMissed, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Trip_Missed_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printTripPlanned()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printTripPlanned()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

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
        if ($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                if(!empty($validatedData['route_id'])) {
                    //TripPlanned all route for all company
                    if($validatedData['route_id']=='All'){
                        $allRoutes = Route::orderBy('route_number')->get();
            
                        foreach($allRoutes as $allRoute){
                            $routeNameIn = implode(" - ",array_reverse(explode("-", $allRoute->route_name)));
                            $routeNameOut = $allRoute->route_name;
                            $totalTripPlannedIn = 0;
                            $totalTripPlannedOut = 0;
                            $trip_data = [];
            
                            foreach($all_dates as $all_date) {
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date . '23:59:59');
                                $countTripPlannedIn = 0;
                                $countTripPlannedOut = 0;
                                $tripIn = [];
                                $tripOut = [];

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
                                        if ($schedule->RouteScheduleMSTR->route_id == $allRoute->id) {
                                            $tripServedIn = false;
                                            $tripServedOut = false;
            
                                            if($schedule->RouteScheduleMSTR->trip_code == 1) {
                                                //Inbound
                                                $countTripPlannedIn++;
                                                $plannedIn['route_name'] = $routeNameIn;
                                                $plannedIn['trip_date'] = $all_date;
                                                $plannedIn['service_time'] = $schedule->RouteScheduleMSTR->schedule_start_time;
                                                $plannedIn['trip_no'] = 'T' . $countTripPlannedIn;
                                                if($schedule->RouteScheduleMSTR->bus_id != NULL) {
                                                    $plannedIn['bus_reg_no'] = $schedule->RouteScheduleMSTR->Bus->bus_registration_number;
                                                    $plannedIn['bus_age'] = $schedule->RouteScheduleMSTR->Bus->bus_age;
                                                }else{
                                                    $plannedIn['bus_reg_no'] = 'NO DATA';
                                                    $plannedIn['bus_age'] = 'NO DATA';
                                                }
                                                $plannedIn['km_rate'] = 1.33;
                
                                                if (count($allInboundTrips) > 0) {
                                                    foreach ($allInboundTrips as $allInboundTrip) {
                                                        if ($schedule->RouteScheduleMSTR->id == $allInboundTrip->route_schedule_mstr_id) {
                                                            $tripServedIn = true;
                                                        }
                                                    }
                                                }

                                                if ($tripServedIn == false) {
                                                    $plannedIn['status'] = "MISSED TRIP";
                                                } else {
                                                    $plannedIn['status'] = "TRIP SERVED";
                                                }
                                                $tripIn[$countTripPlannedIn] = $plannedIn;
                                            }else{
                                                //Outbound
                                                $countTripPlannedOut++;
                                                $plannedOut['route_name'] = $routeNameOut;
                                                $plannedOut['trip_date'] = $all_date;
                                                $plannedOut['service_time'] = $schedule->RouteScheduleMSTR->schedule_start_time;
                                                $plannedOut['trip_no'] = 'T' . $countTripPlannedOut;
                                                if($schedule->RouteScheduleMSTR->bus_id != NULL) {
                                                    $plannedOut['bus_reg_no'] = $schedule->RouteScheduleMSTR->Bus->bus_registration_number;
                                                    $plannedOut['bus_age'] = $schedule->RouteScheduleMSTR->Bus->bus_age;
                                                }else{
                                                    $plannedOut['bus_reg_no'] = 'NO DATA';
                                                    $plannedOut['bus_age'] = 'NO DATA';
                                                }
                                                $plannedOut['km_rate'] = 1.33;
                
                                                if (count($allOutboundTrips) > 0) {
                                                    foreach ($allOutboundTrips as $allOutboundTrip) {
                                                        if ($schedule->RouteScheduleMSTR->id == $allOutboundTrip->route_schedule_mstr_id) {
                                                            $tripServedOut = true;
                                                        }
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
                            $grand = [];
                        }else{
                            $grand['allRoute'] = $route;
                            $grand['grand'] = $sumGrand;
                        }
                        $tripPlanned->add($grand);
                    }
                }
            }else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $companyDetails->company_name;
    
                if(!empty($validatedData['route_id'])) {
                    //TripPlanned all route for specific company
                    if($validatedData['route_id']=='All'){
                        $routeByCompanies = Route::where('company_id', $companyDetails->id)->orderBy('route_number')->get();
        
                        foreach($routeByCompanies as $routeByCompany){
                            $routeNameOut = $routeByCompany->route_name;
                            $routeNameIn = implode(" - ",array_reverse(explode("-", $routeByCompany->route_name)));
                            $totalTripPlannedIn = 0;
                            $totalTripPlannedOut = 0;
                            $trip_data = [];
                            $tripIn = [];
                            $tripOut = [];
        
                            foreach($all_dates as $all_date) {
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date . '23:59:59');$countTripPlannedIn = 0;
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
                                        if ($schedule->RouteScheduleMSTR->route_id == $routeByCompany->id) {
                                            $tripServedIn = false;
                                            $tripServedOut = false;
        
                                            if($schedule->RouteScheduleMSTR->trip_code == 1){
                                                //inbound
                                                $countTripPlannedIn++;
                                                $plannedIn['route_name'] = $routeNameIn;
                                                $plannedIn['trip_date'] = $all_date;
                                                $plannedIn['service_time'] = $schedule->RouteScheduleMSTR->schedule_start_time;
                                                $plannedIn['trip_no'] = 'T' . $countTripPlannedIn;
                                                if($schedule->RouteScheduleMSTR->bus_id != NULL) {
                                                    $plannedIn['bus_reg_no'] = $schedule->RouteScheduleMSTR->Bus->bus_registration_number;
                                                    $plannedIn['bus_age'] = $schedule->RouteScheduleMSTR->Bus->bus_age;
                                                }else{
                                                    $plannedIn['bus_reg_no'] = 'NO DATA';
                                                    $plannedIn['bus_age'] = 'NO DATA';
                                                }
                                                $plannedIn['km_rate'] = 1.33;
            
                                                if (count($allInboundTrips) > 0) {
                                                    foreach ($allInboundTrips as $allInboundTrip) {
                                                        if ($schedule->RouteScheduleMSTR->id == $allInboundTrip->route_schedule_mstr_id) {
                                                            $tripServedIn = true;
                                                        }
                                                    }
                                                }
                                                if ($tripServedIn == false) {
                                                    $plannedIn['status'] = "MISSED TRIP";
                                                } else {
                                                    $plannedIn['status'] = "TRIP SERVED";
                                                }
                                                $tripIn[$countTripPlannedIn] = $plannedIn;

                                            }else{
                                                //Outbound
                                                $countTripPlannedOut++;
                                                $plannedOut['route_name'] = $routeNameOut;
                                                $plannedOut['trip_date'] = $all_date;
                                                $plannedOut['service_time'] = $schedule->RouteScheduleMSTR->schedule_start_time;
                                                $plannedOut['trip_no'] = 'T' . $countTripPlannedOut;
                                                if($schedule->RouteScheduleMSTR->bus_id != NULL) {
                                                    $plannedOut['bus_reg_no'] = $schedule->RouteScheduleMSTR->Bus->bus_registration_number;
                                                    $plannedOut['bus_age'] = $schedule->RouteScheduleMSTR->Bus->bus_age;
                                                }else{
                                                    $plannedOut['bus_reg_no'] = 'NO DATA';
                                                    $plannedOut['bus_age'] = 'NO DATA';
                                                }
                                                $plannedOut['km_rate'] = 1.33;
            
                                                if (count($allOutboundTrips) > 0) {
                                                    foreach ($allOutboundTrips as $allOutboundTrip) {
                                                        if ($schedule->RouteScheduleMSTR->id == $allOutboundTrip->route_schedule_mstr_id) {
                                                            $tripServedOut = true;
                                                        }
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
                            $grand = [];
                        }else{
                            $grand['allRoute'] = $route;
                            $grand['grand'] = $sumGrand;
                        }
                        $tripPlanned->add($grand);
                    }
                    //TripPlanned certain route for specific company
                    else{
                        $selectedRoute = Route::where('id', $validatedData['route_id'])->first();
                        $routeNameIn = $selectedRoute->route_name;
                        $routeNameOut = implode(" - ",array_reverse(explode("-", $routeNameIn)));
                        $totalTripPlannedIn = 0;
                        $totalTripPlannedOut = 0;
                        $trip_data = [];
        
                        foreach($all_dates as $all_date) {
                            $firstDate = new Carbon($all_date);
                            $lastDate = new Carbon($all_date . '23:59:59');
                            $countTripPlannedIn = 0;
                            $countTripPlannedOut = 0;
                            $tripIn = [];
                            $tripOut = [];
        
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
                                    if ($schedule->RouteScheduleMSTR->route_id == $selectedRoute->id) {
                                        $tripServedIn = false;
                                        $tripServedOut = false;
        
                                        if($schedule->RouteScheduleMSTR->trip_code){
                                            //Inbound
                                            $countTripPlannedIn++;
                                            $plannedIn['route_name'] = $routeNameIn;
                                            $plannedIn['trip_date'] = $all_date;
                                            $plannedIn['service_time'] = $schedule->RouteScheduleMSTR->schedule_start_time;
                                            $plannedIn['trip_no'] = 'T' . $countTripPlannedIn;
                                            if($schedule->RouteScheduleMSTR->bus_id != NULL) {
                                                $plannedIn['bus_reg_no'] = $schedule->RouteScheduleMSTR->Bus->bus_registration_number;
                                                $plannedIn['bus_age'] = $schedule->RouteScheduleMSTR->Bus->bus_age;
                                            }else{
                                                $plannedIn['bus_reg_no'] = 'NO DATA';
                                                $plannedIn['bus_age'] = 'NO DATA';
                                            }
                                            $plannedIn['km_rate'] = 1.33;
            
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
                                        }else{
                                            //Outbound
                                            $countTripPlannedOut++;
                                            $plannedOut['route_name'] = $routeNameOut;
                                            $plannedOut['trip_date'] = $all_date;
                                            $plannedOut['service_time'] = $schedule->RouteScheduleMSTR->schedule_start_time;
                                            $plannedOut['trip_no'] = 'T' . $countTripPlannedOut;
                                            if($schedule->RouteScheduleMSTR->bus_id != NULL) {
                                                $plannedOut['bus_reg_no'] = $schedule->RouteScheduleMSTR->Bus->bus_registration_number;
                                                $plannedOut['bus_age'] = $schedule->RouteScheduleMSTR->Bus->bus_age;
                                            }else{
                                                $plannedOut['bus_reg_no'] = 'NO DATA';
                                                $plannedOut['bus_age'] = 'NO DATA';
                                            }
                                            $plannedOut['km_rate'] = 1.33;
            
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
                            $grand = [];
                        }else{
                            $perDate['allDate'] = $tripPerDate;
                            $perDate['total_per_route'] = $sumTotal;
                            $sumGrand = $sumTotal;
                            $route[$selectedRoute->route_number] = $perDate;
                            $grand['allRoute'] = $route;
                            $grand['grand'] = $sumGrand;
                        }
        
                        $tripPlanned->add($grand);
                    }
                }
            }
            return Excel::download(new SPADTripPlanned($tripPlanned, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Trip_Planned_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }
}
