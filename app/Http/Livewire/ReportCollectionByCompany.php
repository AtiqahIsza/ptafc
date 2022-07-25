<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Company;
use App\Models\Route;
use App\Models\RouteSchedulerDetail;
use App\Models\TripDetail;
use App\Models\TicketSalesTransaction;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Exports\CollectionByCompany;

class ReportCollectionByCompany extends Component
{
    public $companies;
    public $selectedCompany = NULL;
    public $state = [];
    public $heading = [];
    public $data = [];
    public $tot = [];
    public $grand = [];

    public function render()
    {
        $this->companies = Company::all();
        return view('livewire.report-collection-by-company');
    }

    public function mount()
    {
        $this->companies=collect();
    }

    public function print()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printCollectionByCompany()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'company_id' => ['required'],
        ])->validate();

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo'] . '23:59:59');

        $grandClaim = 0;
        $grandTripPlanned = 0;
        $grandTripServed = 0;
        $grandMissedTrip = 0;
        $grandEarlyLate = 0;
        $grandBreakdown = 0;
        $grandAccidents = 0;
        $grandRidership = 0;
        $grandFarebox = 0;
        $grandCard = 0;
        $grandTng = 0;
        $collectionByCompany = collect();
        if (!empty($validatedData['company_id'])){
            if($validatedData['company_id']=='All'){
                $networkArea = 'ALL';
                $allCompanies = Company::all();

                foreach($allCompanies as $allCompany){

                    $routeByCompanies = Route::where('company_id', $allCompany->id)->get();
                    $companyClaim = 0;
                    $companyTripPlanned = 0;
                    $companyTripServed = 0;
                    $companyMissedTrip = 0;
                    $companyEarlyLate = 0;
                    $companyBreakdown = 0;
                    $companyAccidents = 0;
                    $companyRidership = 0;
                    $companyFarebox = 0;
                    $companyCard = 0;
                    $companyTng = 0;
                    $data_perRoute = [];
                    $perCompany = [];
                    foreach($routeByCompanies as $routeByCompany) {
                        $totalKMServed = 0;
                        $totalFarebox = 0;
                        $totalCard = 0;
                        $totalTng = 0;
                        $totalRidership = 0;
                        $earlyLateCount = 0;
                        $totalTripPlanned = 0;
                        $tripServed = 0;  

                        $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->get();
        
                        $allTrips = TripDetail::where('route_id', $routeByCompany->id)
                            ->whereBetween('start_trip', [$dateFrom, $dateTo])
                            ->get();
                        
                        if (count($allTrips) > 0) {
                            foreach ($allTrips as $allTrip) {
                                //Total KM Service Served
                                if($allTrip->trip_code==0){
                                    $kmServed = $allTrip->Route->outbound_distance;
                                }
                                elseif($allTrip->trip_code==1){
                                    $kmServed = $allTrip->Route->inbound_distance;
                                }
                                $totalKMServed += $kmServed;
        
                                //Early-Late
                                if (count($schedules) > 0) {
                                    foreach ($schedules as $schedule) {
                                        if ($schedule->RouteScheduleMSTR->route_id == $routeByCompany->id) {
                                            if ($schedule->RouteScheduleMSTR->id == $allTrip->route_schedule_mstr_id) {
                                                $diff = strtotime($schedule->schedule_start_time) - strtotime($allTrip->start_trip);
                                                if ($diff > 5 || $diff < -5) {
                                                    $earlyLateCount++;
                                                }
                                            }
                                        }
                                    }
                                }
        
                                //Ridership
                                $ridership = $allTrip->total_adult + $allTrip->total_concession;
                                $totalRidership += $ridership;
        
                                //Farebox && TnG Collection 
                                $farebox = 0;
                                $card = 0;
                                $tng = 0;
                                $ticketsPerTrips = TicketSalesTransaction::where('trip_id', $allTrip->id)->get();
                                if(count($ticketsPerTrips)>0){
                                    foreach($ticketsPerTrips as $ticketsPerTrip){
                                        if($ticketsPerTrip->fare_type==0){
                                            $farebox += $ticketsPerTrip->actual_amount;
                                        }elseif($ticketsPerTrip->fare_type==1){
                                            $card += $ticketsPerTrip->actual_amount;
                                        }elseif($ticketsPerTrip->fare_type==2){
                                            $tng += $ticketsPerTrip->actual_amount;
                                        }
                                    }
                                }
                                $totalFarebox += $farebox;
                                $totalCard += $card;
                                $totalTng += $tng;
                                $tripServed++;
                            }
                        }
        
                        //Total Trip Served
                        $totalTripServed = $tripServed;
        
                        //Total Missed Trip
                        $totalMissedTrip = $totalTripPlanned - $totalTripServed;
                        if($totalMissedTrip<0){
                            $totalMissedTrip = 0;
                        }
        
                        //Total Claim (RM)
                        $charge = 1.33;
                        $totalClaim = $charge * $totalKMServed;
        
                        /**Total Breakdown & Total Accidents need to revise**/
                        $totalBreakdown = 0;
                        $totalAccidents = 0;
        
                        $perRoute['route_name'] = $routeByCompany->route_name;
                        $perRoute['total_claim'] = $totalClaim;
                        $perRoute['total_trip_planned'] = $totalTripPlanned;
                        $perRoute['total_trip_served'] = $totalTripServed;
                        $perRoute['total_missed_trip'] = $totalMissedTrip;
                        $perRoute['total_early_late'] = $earlyLateCount;
                        $perRoute['total_breakdown'] = $totalBreakdown;
                        $perRoute['total_accidents'] = $totalAccidents;
                        $perRoute['total_ridership'] = $totalRidership;
                        $perRoute['total_farebox'] = $totalFarebox;
                        $perRoute['total_card'] = $totalCard;
                        $perRoute['total_tng'] = $totalTng;
        
                        $data_perRoute[$routeByCompany->route_number] = $perRoute;
        
                        $companyClaim += $perRoute['total_claim'];
                        $companyTripPlanned += $perRoute['total_trip_planned'];
                        $companyTripServed += $perRoute['total_trip_served'];
                        $companyMissedTrip += $perRoute['total_missed_trip'];
                        $companyEarlyLate += $perRoute['total_early_late'];
                        $companyBreakdown += $perRoute['total_breakdown'];
                        $companyAccidents += $perRoute['total_accidents'];
                        $companyRidership += $perRoute['total_ridership'];
                        $companyFarebox += $perRoute['total_farebox'];
                        $companyCard += $perRoute['total_card'];
                        $companyTng += $perRoute['total_tng'];
                    }

                    $perCompany['total_claim'] = $companyClaim;
                    $perCompany['total_trip_planned'] = $companyTripPlanned;
                    $perCompany['total_trip_served'] = $companyTripServed;
                    $perCompany['total_missed_trip'] = $companyMissedTrip;
                    $perCompany['total_early_late'] = $companyEarlyLate;
                    $perCompany['total_breakdown'] = $companyBreakdown;
                    $perCompany['total_accidents'] = $companyAccidents;
                    $perCompany['total_ridership'] = $companyRidership;
                    $perCompany['total_farebox'] = $companyFarebox;
                    $perCompany['total_card'] = $companyCard;
                    $perCompany['total_tng'] = $companyTng;
    
                    $companyArr['data_per_route'] = $data_perRoute;
                    $companyArr['total_per_company'] = $perCompany;
                    $data_perCompany[$allCompany->company_name] = $companyArr;
    
                    $grandClaim += $perCompany['total_claim'];
                    $grandTripPlanned += $perCompany['total_trip_planned'];
                    $grandTripServed += $perCompany['total_trip_served'];
                    $grandMissedTrip += $perCompany['total_missed_trip'];
                    $grandEarlyLate += $perCompany['total_early_late'];
                    $grandBreakdown += $perCompany['total_breakdown'];
                    $grandAccidents += $perCompany['total_accidents'];
                    $grandRidership += $perCompany['total_ridership'];
                    $grandFarebox += $perCompany['total_farebox'];
                    $grandCard += $perCompany['total_card'];
                    $grandTng += $perCompany['total_tng'];
                }
                $grand['grand_claim'] = $grandClaim;
                $grand['grand_trip_planned'] = $grandTripPlanned;
                $grand['grand_trip_served'] = $grandTripServed;
                $grand['grand_missed_trip'] = $grandMissedTrip;
                $grand['grand_early_late'] = $grandEarlyLate;
                $grand['grand_breakdown'] = $grandBreakdown;
                $grand['grand_accidents'] = $grandAccidents;
                $grand['grand_ridership'] = $grandRidership;
                $grand['grand_farebox'] = $grandFarebox;
                $grand['grand_card'] = $grandCard;
                $grand['grand_tng'] = $grandTng;
    
                $data['allCompany'] = $data_perCompany;
                $data['grand'] = $grand;
                $collectionByCompany->add($data);

            }
            else{
                $companyDetails = Company::where('id', $this->state['company_id'])->first();
                $networkArea = $companyDetails->company_name;

                $routeByCompanies = Route::where('company_id', $companyDetails->id)->get();
                $data_perRoute = [];
                $perRoute = [];
                foreach($routeByCompanies as $routeByCompany) {
                    $totalKMServed = 0;
                    $totalFarebox = 0;
                    $totalCard = 0;
                    $totalTng = 0;
                    $totalRidership = 0;
                    $earlyLateCount = 0;
                    $totalTripPlanned = 0;
                    $tripServed = 0;  

                    $schedules = RouteSchedulerDetail::whereBetween('schedule_date', [$dateFrom, $dateTo])->get();
    
                    $allTrips = TripDetail::where('route_id', $routeByCompany->id)
                        ->whereBetween('start_trip', [$dateFrom, $dateTo])
                        ->get();
                    
                    if (count($allTrips) > 0) {
                        foreach ($allTrips as $allTrip) {
                            //Total KM Service Served
                            if($allTrip->trip_code==0){
                                $kmServed = $allTrip->Route->outbound_distance;
                            }
                            elseif($allTrip->trip_code==1){
                                $kmServed = $allTrip->Route->inbound_distance;
                            }
                            $totalKMServed += $kmServed;
    
                            //Early-Late
                            if (count($schedules) > 0) {
                                foreach ($schedules as $schedule) {
                                    if ($schedule->RouteScheduleMSTR->route_id == $routeByCompany->id) {
                                        if ($schedule->RouteScheduleMSTR->id == $allTrip->route_schedule_mstr_id) {
                                            $diff = strtotime($schedule->schedule_start_time) - strtotime($allTrip->start_trip);
                                            if ($diff > 5 || $diff < -5) {
                                                $earlyLateCount++;
                                            }
                                        }
                                    }
                                }
                            }
    
                            //Ridership
                            $ridership = $allTrip->total_adult + $allTrip->total_concession;
                            $totalRidership += $ridership;
    
                            //Farebox && TnG Collection 
                            $farebox = 0;
                            $card = 0;
                            $tng = 0;
                            $ticketsPerTrips = TicketSalesTransaction::where('trip_id', $allTrip->id)->get();
                            if(count($ticketsPerTrips)>0){
                                foreach($ticketsPerTrips as $ticketsPerTrip){
                                    if($ticketsPerTrip->fare_type==0){
                                        $farebox += $ticketsPerTrip->actual_amount;
                                    }elseif($ticketsPerTrip->fare_type==1){
                                        $card += $ticketsPerTrip->actual_amount;
                                    }elseif($ticketsPerTrip->fare_type==2){
                                        $tng += $ticketsPerTrip->actual_amount;
                                    }
                                }
                            }
                            $totalFarebox += $farebox;
                            $totalCard += $card;
                            $totalTng += $tng;
                            $tripServed++;
                        }
                    }
    
                    //Total Trip Served
                    $totalTripServed = $tripServed;
    
                    //Total Missed Trip
                    $totalMissedTrip = $totalTripPlanned - $totalTripServed;
                    if($totalMissedTrip<0){
                        $totalMissedTrip = 0;
                    }
    
                    //Total Claim (RM)
                    $charge = 1.33;
                    $totalClaim = $charge * $totalKMServed;
    
                    /**Total Breakdown & Total Accidents need to revise**/
                    $totalBreakdown = 0;
                    $totalAccidents = 0;
    
                    $perRoute['route_name'] = $routeByCompany->route_name;
                    $perRoute['total_claim'] = $totalClaim;
                    $perRoute['total_trip_planned'] = $totalTripPlanned;
                    $perRoute['total_trip_served'] = $totalTripServed;
                    $perRoute['total_missed_trip'] = $totalMissedTrip;
                    $perRoute['total_early_late'] = $earlyLateCount;
                    $perRoute['total_breakdown'] = $totalBreakdown;
                    $perRoute['total_accidents'] = $totalAccidents;
                    $perRoute['total_ridership'] = $totalRidership;
                    $perRoute['total_farebox'] = $totalFarebox;
                    $perRoute['total_card'] = $totalCard;
                    $perRoute['total_tng'] = $totalTng;
    
                    $data_perRoute[$routeByCompany->route_number] = $perRoute;
    
                    $grandClaim += $perRoute['total_claim'];
                    $grandTripPlanned += $perRoute['total_trip_planned'];
                    $grandTripServed += $perRoute['total_trip_served'];
                    $grandMissedTrip += $perRoute['total_missed_trip'];
                    $grandEarlyLate += $perRoute['total_early_late'];
                    $grandBreakdown += $perRoute['total_breakdown'];
                    $grandAccidents += $perRoute['total_accidents'];
                    $grandRidership += $perRoute['total_ridership'];
                    $grandFarebox += $perRoute['total_farebox'];
                    $grandCard += $perRoute['total_card'];
                    $grandTng += $perRoute['total_tng'];
                }

                $perCompany['total_claim'] = $grandClaim;
                $perCompany['total_trip_planned'] = $grandTripPlanned;
                $perCompany['total_trip_served'] = $grandTripServed;
                $perCompany['total_missed_trip'] = $grandMissedTrip;
                $perCompany['total_early_late'] = $grandEarlyLate;
                $perCompany['total_breakdown'] = $grandBreakdown;
                $perCompany['total_accidents'] = $grandAccidents;
                $perCompany['total_ridership'] = $grandRidership;
                $perCompany['total_farebox'] = $grandFarebox;
                $perCompany['total_card'] = $grandCard;
                $perCompany['total_tng'] = $grandTng;

                $companyArr['data_per_route'] = $data_perRoute;
                $companyArr['total_per_company'] = $perCompany;
                $data_perCompany[$companyDetails->company_name] = $companyArr;

                $grand['grand_claim'] = $grandClaim;
                $grand['grand_trip_planned'] = $grandTripPlanned;
                $grand['grand_trip_served'] = $grandTripServed;
                $grand['grand_missed_trip'] = $grandMissedTrip;
                $grand['grand_early_late'] = $grandEarlyLate;
                $grand['grand_breakdown'] = $grandBreakdown;
                $grand['grand_accidents'] = $grandAccidents;
                $grand['grand_ridership'] = $grandRidership;
                $grand['grand_farebox'] = $grandFarebox;
                $grand['grand_card'] = $grandCard;
                $grand['grand_tng'] = $grandTng;

                $data['allCompany'] = $data_perCompany;
                $data['grand'] = $grand;
                $collectionByCompany->add($data);
            }
            return Excel::download(new CollectionByCompany($collectionByCompany, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Collection_By_Company_'.Carbon::now()->format('YmdHis').'.xlsx');

        }
    }


}
