<?php

namespace App\Http\Livewire;

use App\Models\Company;
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
use Illuminate\Support\Carbon;
use App\Models\RouteSchedulerMSTR;
use App\Models\RouteSchedulerDetail;
use App\Models\TripDetail;
use Illuminate\Support\Facades\DB;

class ReportDailySummary extends Component
{
    public $companies;
    public $routes;
    public $selectedCompany = NULL;
    public $state = [];

    public function render()
    {
        $this->companies = Company::orderBy('company_name')->get();
        return view('livewire.report-daily-summary');
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

    public function print()
    {  
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printDailySummary()");

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

        $dailySummary = collect();
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
                        $allCompanies = Company::orderBy('company_name')->get();
            
                        foreach($allCompanies as $allCompany){
                            

                            foreach ($all_dates as $all_date) {
                                $totalTripCompliance = 0;
                                $averagePerDate = 0;
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date .'23:59:59');
                                $isWeekday = false;
                                $isWeekend = false;
                                $isWeekday = $firstDate->isWeekday();
                                $isWeekend =  $firstDate->isWeekend();
                                if($isWeekday){
                                    $data['week'] = 'WEEKDAY';
                                }else{
                                    $data['week'] = 'WEEKEND';
                                }

                                $routePerCompanies = Route::where('company_id', $allCompany->id)->orderBy('route_number')->get();
                                foreach ($routePerCompanies as $routePerCompany) {
                                    if($isWeekday){
                                        $isFriday = $firstDate->format('l');
                                        if($isFriday=='Friday'){
                                            $tripPlanned = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,7,12])
                                            ->where('status', 1)
                                            ->where('route_id', $routePerCompany->id)
                                            ->count();
                                        }else{
                                            $tripPlanned = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,4,6,9,10])
                                            ->where('status', 1)
                                            ->where('route_id', $routePerCompany->id)
                                            ->count();
                                        }
                                    }
                                    if($isWeekend){
                                        $isSunday = $firstDate->format('l');
                                        if($isSunday=='Sunday'){
                                            $tripPlanned = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11])
                                            ->where('route_id', $routePerCompany->id)
                                            ->where('status', 1)
                                            ->count();
                                        }else{
                                            $tripPlanned = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12])
                                            ->where('status', 1)
                                            ->where('route_id', $routePerCompany->id)
                                            ->count();
                                        }
                                    }

                                    $tripServed = TripDetail::where('route_id', $routePerCompany->id)
                                        ->whereBetween('start_trip', [$firstDate, $lastDate])
                                        ->count();
                                    
                                    if($tripPlanned==0){
                                        $tripCompliancePerRoute = 0;
                                    }else{
                                        $tripCompliancePerRoute = round((($tripServed/$tripPlanned) * 100),0);
                                    }

                                    $data['trip_planned_' . $routePerCompany->route_number] = $tripPlanned;
                                    $data['trip_served_' . $routePerCompany->route_number] = $tripServed;
                                    $data['trip_compliance_' . $routePerCompany->route_number] = $tripCompliancePerRoute;

                                    $totalTripCompliance += $tripCompliancePerRoute;
                                }

                                $averagePerDate = $totalTripCompliance/(100 *count($routePerCompanies));
                                $data['average'] = $averagePerDate;

                                $datePerDate[$all_date] = $data;
                            }
                        }
            
                        //$dailySummary->add($data_grand);
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

                                    if($totalTripPlanned==0){
                                        $tripCompliancePerDate = 0;
                                    }else{
                                        $tripCompliancePerDate = round((($totalTripServed/$totalTripPlanned) * 100),1);
                                    }

                                    //total per date
                                    $trip_perDate['total_trip_planned'] = $totalTripPlanned;
                                    $trip_perDate['total_km_planned'] = $totalKMPlanned;
                                    $trip_perDate['total_trip_served'] = $totalTripServed;
                                    $trip_perDate['total_km_served'] = $totalKMServed;
                                    $trip_perDate['total_missed_trip'] = $totalMissedTrip;
                                    $trip_perDate['trip_compliance'] = $tripCompliancePerDate;
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

                                if($routeTripPlanned==0){
                                    $tripCompliancePerRoute = 0;
                                }else{
                                    $tripCompliancePerRoute = round((($routeTripServed/$routeTripPlanned) * 100),1);
                                }

                                //total per route
                                $trip_perRoute['total_trip_planned'] = $routeTripPlanned;
                                $trip_perRoute['total_km_planned'] = $routeKMPlanned;
                                $trip_perRoute['total_trip_served'] = $routeTripServed;
                                $trip_perRoute['total_km_served'] = $routeKMServed;
                                $trip_perRoute['total_missed_trip'] = $routeMissedTrip;
                                $trip_perRoute['trip_compliance'] = $tripCompliancePerRoute;
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
                        if($companyTripPlanned==0){
                            $tripCompliancePerCompany= 0;
                        }else{
                            $tripCompliancePerCompany = round((($companyTripServed/$companyTripPlanned) * 100),1);
                        }

                        //total per company
                        $trip_perCompany['total_trip_planned'] = $companyTripPlanned;
                        $trip_perCompany['total_km_planned'] = $companyKMPlanned;
                        $trip_perCompany['total_trip_served'] = $companyTripServed;
                        $trip_perCompany['total_km_served'] = $companyKMServed;
                        $trip_perCompany['total_missed_trip'] = $companyMissedTrip;
                        $trip_perCompany['trip_compliance'] = $tripCompliancePerCompany;
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
                        $dailySummary->add($data_grand);
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
        
                            if($totalTripPlanned==0){
                                $tripCompliancePerDate = 0;
                            }else{
                                $tripCompliancePerDate = round((($totalTripServed/$totalTripPlanned) * 100),1);
                            }

                            //total per date
                            $trip_perDate['total_trip_planned'] = $totalTripPlanned;
                            $trip_perDate['total_km_planned'] = $totalKMPlanned;
                            $trip_perDate['total_trip_served'] = $totalTripServed;
                            $trip_perDate['total_km_served'] = $totalKMServed;
                            $trip_perDate['total_missed_trip'] = $totalMissedTrip;
                            $trip_perDate['trip_compliance'] = $tripCompliancePerDate;
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

                        if($routeTripPlanned==0){
                            $tripCompliancePerRoute = 0;
                        }else{
                            $tripCompliancePerRoute = round((($routeTripServed/$routeTripPlanned) * 100),1);
                        }
                        //total per route
                        $trip_perRoute['total_trip_planned'] = $routeTripPlanned;
                        $trip_perRoute['total_km_planned'] = $routeKMPlanned;
                        $trip_perRoute['total_trip_served'] = $routeTripServed;
                        $trip_perRoute['total_km_served'] = $routeKMServed;
                        $trip_perRoute['total_missed_trip'] = $routeMissedTrip;
                        $trip_perRoute['trip_compliance'] = $tripCompliancePerRoute;
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
                        $dailySummary->add($data_grand);
                    }
                }
            }
            return Excel::download(new DailySummary($dailySummary, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Daily_Summary_Report_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printTripCompliance()
    {  
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printDailySummary()");

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

        $dailySummary = collect();
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

                                        if($totalTripPlanned==0){
                                            $tripCompliancePerDate = 0;
                                        }else{
                                            $tripCompliancePerDate = round((($totalTripServed/$totalTripPlanned) * 100),1);
                                        }
                                        
                                        //total per date
                                        $trip_perDate['total_trip_planned'] = $totalTripPlanned;
                                        $trip_perDate['total_km_planned'] = $totalKMPlanned;
                                        $trip_perDate['total_trip_served'] = $totalTripServed;
                                        $trip_perDate['total_km_served'] = $totalKMServed;
                                        $trip_perDate['total_missed_trip'] = $totalMissedTrip;
                                        $trip_perDate['trip_compliance'] = $tripCompliancePerDate;
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

                                    if($routeTripPlanned==0){
                                        $tripCompliancePerRoute = 0;
                                    }else{
                                        $tripCompliancePerRoute = round((($routeTripServed/$routeTripPlanned) * 100),1);
                                    }

                                    //total per route
                                    $trip_perRoute['total_trip_planned'] = $routeTripPlanned;
                                    $trip_perRoute['total_km_planned'] = $routeKMPlanned;
                                    $trip_perRoute['total_trip_served'] = $routeTripServed;
                                    $trip_perRoute['total_km_served'] = $routeKMServed;
                                    $trip_perRoute['total_missed_trip'] = $routeMissedTrip;
                                    $trip_perRoute['trip_compliance'] = $tripCompliancePerRoute;
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

                            if( $companyTripPlanned==0){
                                $tripCompliancePerCompany = 0;
                            }else{
                                $tripCompliancePerCompany = round((($companyTripServed/$companyTripPlanned) * 100),1);
                            }

                            //total per company
                            $trip_perCompany['total_trip_planned'] = $companyTripPlanned;
                            $trip_perCompany['total_km_planned'] = $companyKMPlanned;
                            $trip_perCompany['total_trip_served'] = $companyTripServed;
                            $trip_perCompany['total_km_served'] = $companyKMServed;
                            $trip_perCompany['total_missed_trip'] = $companyMissedTrip;
                            $trip_perCompany['trip_compliance'] = $tripCompliancePerCompany;
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

                        if($grandTripPlanned==0){
                            $tripComplianceGrand = 0;
                        }else{
                            $tripComplianceGrand = round((($grandTripServed/$grandTripPlanned) * 100),1);
                        }

                        $grand['grand_trip_planned'] = $grandTripPlanned;
                        $grand['grand_km_planned'] = $grandKMPlanned;
                        $grand['grand_trip_served'] = $grandTripServed;
                        $grand['grand_km_served'] = $grandKMServed;
                        $grand['grand_missed_trip'] = $grandMissedTrip;
                        $grand['trip_compliance'] = $tripComplianceGrand;
                        $grand['grand_early_late'] = $grandEarlyLate;
                        $grand['grand_breakdown'] = $grandBreakdown;
                        $grand['grand_accidents'] = $grandAccidents;
                        $grand['grand_ridership_count'] = $grandRidershipCount;
                        $grand['grand_ridership_tickets'] = $grandRidershipTicket;
                        $grand['grand_farebox'] = $grandFarebox;
            
                        $data_grand['all_company'] = $data_perCompany;
                        $data_grand['grand'] = $grand;
                        $dailySummary->add($data_grand);
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

                                    if($totalTripPlanned==0){
                                        $tripCompliancePerDate = 0;
                                    }else{
                                        $tripCompliancePerDate = round((($totalTripServed/$totalTripPlanned) * 100),1);
                                    }

                                    //total per date
                                    $trip_perDate['total_trip_planned'] = $totalTripPlanned;
                                    $trip_perDate['total_km_planned'] = $totalKMPlanned;
                                    $trip_perDate['total_trip_served'] = $totalTripServed;
                                    $trip_perDate['total_km_served'] = $totalKMServed;
                                    $trip_perDate['total_missed_trip'] = $totalMissedTrip;
                                    $trip_perDate['trip_compliance'] = $tripCompliancePerDate;
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

                                if($routeTripPlanned==0){
                                    $tripCompliancePerRoute = 0;
                                }else{
                                    $tripCompliancePerRoute = round((($routeTripServed/$routeTripPlanned) * 100),1);
                                }

                                //total per route
                                $trip_perRoute['total_trip_planned'] = $routeTripPlanned;
                                $trip_perRoute['total_km_planned'] = $routeKMPlanned;
                                $trip_perRoute['total_trip_served'] = $routeTripServed;
                                $trip_perRoute['total_km_served'] = $routeKMServed;
                                $trip_perRoute['total_missed_trip'] = $routeMissedTrip;
                                $trip_perRoute['trip_compliance'] = $tripCompliancePerRoute;
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
                        if($companyTripPlanned==0){
                            $tripCompliancePerCompany= 0;
                        }else{
                            $tripCompliancePerCompany = round((($companyTripServed/$companyTripPlanned) * 100),1);
                        }

                        //total per company
                        $trip_perCompany['total_trip_planned'] = $companyTripPlanned;
                        $trip_perCompany['total_km_planned'] = $companyKMPlanned;
                        $trip_perCompany['total_trip_served'] = $companyTripServed;
                        $trip_perCompany['total_km_served'] = $companyKMServed;
                        $trip_perCompany['total_missed_trip'] = $companyMissedTrip;
                        $trip_perCompany['trip_compliance'] = $tripCompliancePerCompany;
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
                        $dailySummary->add($data_grand);
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
        
                            if($totalTripPlanned==0){
                                $tripCompliancePerDate = 0;
                            }else{
                                $tripCompliancePerDate = round((($totalTripServed/$totalTripPlanned) * 100),1);
                            }

                            //total per date
                            $trip_perDate['total_trip_planned'] = $totalTripPlanned;
                            $trip_perDate['total_km_planned'] = $totalKMPlanned;
                            $trip_perDate['total_trip_served'] = $totalTripServed;
                            $trip_perDate['total_km_served'] = $totalKMServed;
                            $trip_perDate['total_missed_trip'] = $totalMissedTrip;
                            $trip_perDate['trip_compliance'] = $tripCompliancePerDate;
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

                        if($routeTripPlanned==0){
                            $tripCompliancePerRoute = 0;
                        }else{
                            $tripCompliancePerRoute = round((($routeTripServed/$routeTripPlanned) * 100),1);
                        }
                        //total per route
                        $trip_perRoute['total_trip_planned'] = $routeTripPlanned;
                        $trip_perRoute['total_km_planned'] = $routeKMPlanned;
                        $trip_perRoute['total_trip_served'] = $routeTripServed;
                        $trip_perRoute['total_km_served'] = $routeKMServed;
                        $trip_perRoute['total_missed_trip'] = $routeMissedTrip;
                        $trip_perRoute['trip_compliance'] = $tripCompliancePerRoute;
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
                        $dailySummary->add($data_grand);
                    }
                }
            }
            return Excel::download(new DailySummary($dailySummary, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Daily_Summary_Report_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printOneDateOnly()
    {  
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printSummaryRoute()");

        $validatedData = Validator::make($this->state,[
            'dailyDate' => ['required', 'date'],
        ])->validate();

        $startDate = new Carbon($validatedData['dailyDate']);
        $endDate = new Carbon($validatedData['dailyDate'] . '23:59:59');
        
        $summaryDaily = collect();
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
        $networkArea = 'All';

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
                    $data_perDate = [];
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
                    
                    $isWeekday = false;
                    $isWeekend = false;

                    $isWeekday = $startDate->isWeekday();
                    $isWeekend =  $startDate->isWeekend();

                    if($isWeekday){
                        $isFriday = $startDate->format('l');
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
                        $isSunday = $startDate->format('l');
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

                    $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$startDate, $endDate])->get();

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
                        ->whereBetween('start_trip', [$startDate, $endDate])
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

                    if ($startDate->isWeekDay()) {
                        $trip_perDate['day'] = "WEEKDAY";
                    } elseif ($startDate->isWeekend()) {
                        $trip_perDate['day'] = "WEEKEND";
                    }

                    $tripCompliancePerRoute = round((($totalTripServed/$totalTripPlanned) * 100),1);

                    //total per date
                    $trip_perDate['total_trip_planned'] = $totalTripPlanned;
                    $trip_perDate['total_km_planned'] = $totalKMPlanned;
                    $trip_perDate['total_trip_served'] = $totalTripServed;
                    $trip_perDate['total_km_served'] = $totalKMServed;
                    $trip_perDate['total_missed_trip'] = $totalMissedTrip;
                    $trip_perDate['total_trip_compliance'] = $tripCompliancePerRoute;
                    $trip_perDate['total_early_late'] = $earlyLateCount;
                    $trip_perDate['total_breakdown'] = $totalBreakdown;
                    $trip_perDate['total_accidents'] = $totalAccidents;
                    $trip_perDate['total_ridership_count'] = $totalRidershipCount;
                    $trip_perDate['total_ridership_tickets'] = $totalRidershipTicket;
                    $trip_perDate['total_farebox'] = $totalFarebox;

                    $data_perDate[$validatedData['dailyDate']] = $trip_perDate;
                
                    //total per route
                    $trip_perRoute['total_trip_planned'] = $totalTripPlanned;
                    $trip_perRoute['total_km_planned'] = $totalKMPlanned;
                    $trip_perRoute['total_trip_served'] = $totalTripServed;
                    $trip_perRoute['total_km_served'] = $totalKMServed;
                    $trip_perRoute['total_missed_trip'] = $totalMissedTrip;
                    $trip_perRoute['total_trip_compliance'] = $tripCompliancePerRoute;
                    $trip_perRoute['total_early_late'] = $earlyLateCount;
                    $trip_perRoute['total_breakdown'] = $totalBreakdown;
                    $trip_perRoute['total_accidents'] = $totalAccidents;
                    $trip_perRoute['total_ridership_count'] = $totalRidershipCount;
                    $trip_perRoute['total_ridership_tickets'] = $totalRidershipTicket;
                    $trip_perRoute['total_farebox'] = $totalFarebox;

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

            $tripCompliancePerCompany =  round((($companyTripServed/$companyTripPlanned) * 100),1);

            //total per company
            $trip_perCompany['total_trip_planned'] = $companyTripPlanned;
            $trip_perCompany['total_km_planned'] = $companyKMPlanned;
            $trip_perCompany['total_trip_served'] = $companyTripServed;
            $trip_perCompany['total_km_served'] = $companyKMServed;
            $trip_perCompany['total_missed_trip'] = $companyMissedTrip;
            $trip_perCompany['total_trip_compliance'] = $tripCompliancePerCompany;
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
        $tripComplianceGrand =  round((($grandKMServed/$grandTripPlanned) * 100),1);

        $grand['grand_trip_planned'] = $grandTripPlanned;
        $grand['grand_km_planned'] = $grandKMPlanned;
        $grand['grand_trip_served'] = $grandTripServed;
        $grand['grand_km_served'] = $grandKMServed;
        $grand['grand_missed_trip'] = $grandMissedTrip;
        $grand['grand_trip_compliance'] = $tripComplianceGrand;
        $grand['grand_early_late'] = $grandEarlyLate;
        $grand['grand_breakdown'] = $grandBreakdown;
        $grand['grand_accidents'] = $grandAccidents;
        $grand['grand_ridership_count'] = $grandRidershipCount;
        $grand['grand_ridership_tickets'] = $grandRidershipTicket;
        $grand['grand_farebox'] = $grandFarebox;

        $data_grand['all_company'] = $data_perCompany;
        $data_grand['grand'] = $grand;
        $summaryDaily->add($data_grand);
    
        //return Excel::download(new DailySummary($summaryDaily, $validatedData['dailyDate'], $networkArea), 'Daily_Summary_Report_'.Carbon::now()->format('YmdHis').'.xlsx');
    }

    public function printOld()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE dailySummary print()");

        $validatedData = Validator::make($this->state,[
            'dailyDate' => ['required'],
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

        //return Excel::download(new DailySummary($dailyReport, $validatedData['dailyDate']), 'DailyDetailsReport.xlsx');
    }
}
