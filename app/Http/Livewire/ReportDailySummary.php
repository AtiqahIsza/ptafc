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
            $this->routes = Route::where('company_id', $company)->where('status', 1)->get();
        }
    }

    public function printFast(){
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printDailySummary()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

        
        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo'] . " 23:59:59");

        $startDayNo = $dateFrom->format('d');
        $endDayNo = $dateTo->format('d');

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo'] . " 23:59:59");

        $all_dates = array();
        
        while ($startDate->lte($endDate)) {
            $all_dates[] = $startDate->toDateString();
            $startDate->addDay();
        }

        if ($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                //Daily Summary all route all company
                if($validatedData['route_id']=='All'){

                    $getAllRoutes = Route::where('status', 1)->orderBy('company_id')->orderBy('route_number')->get();
                    $allTrips = [];

                    foreach($getAllRoutes as $allRoute){
                        $out->writeln("All Route: " . $allRoute->route_number);
                        $tripPerRoute = DB::select("WITH RECURSIVE n_dte (n) AS (
                            SELECT " . $startDayNo . "
                            UNION ALL
                            SELECT n + 1
                            FROM n_dte
                            WHERE n < " . $endDayNo . "
                            ),
                            all_mnth_days AS (
                            SELECT route.id as route_id, route.route_number, route.route_name, route.company_id,
                            DATE_ADD(MIN(trip_details.start_trip), INTERVAL - DAY(MIN(trip_details.start_trip)) + 1 DAY) AS mnth_first_day,
                            n,
                            CAST(DATE_ADD(DATE_ADD(MIN(trip_details.start_trip), INTERVAL - DAY(MIN(trip_details.start_trip)) + 1 DAY), INTERVAL n - 1 DAY) AS DATE) AS all_days_in_mnth
                            FROM mybas.trip_details
                            JOIN n_dte
                            JOIN mybas.route ON route.id = trip_details.route_id
                            WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND route.id = '". $allRoute->id . "'
                            group by n
                            ORDER BY n ASC)
                            
                            SELECT
                            amd.all_days_in_mnth AS service_date, amd.route_id, amd.route_number, amd.route_name, amd.company_id, 
                            (case when (count(ie.id) IS NULL) THEN 0 ELSE count(ie.id) END) as trip_served,
                            (case when (SUM(ie.total_mileage) IS NULL) THEN 0 ELSE SUM(ie.total_mileage) END) as km_served,
                            (case when (SUM(ie.total_adult_amount) + SUM(ie.total_concession_amount) IS NULL) THEN 0 
                            ELSE SUM(ie.total_adult_amount) + SUM(ie.total_concession_amount) END) AS farebox, 
                            (case when (SUM(ie.total_adult) + SUM(ie.total_concession) IS NULL) THEN 0 
                            ELSE SUM(ie.total_adult) + SUM(ie.total_concession) END) AS ridership
                            FROM all_mnth_days AS amd
                            LEFT JOIN trip_details AS ie ON CAST(ie.start_trip AS DATE) = amd.all_days_in_mnth
                            AND ie.route_id = amd.route_id
                            GROUP BY service_date;");

                        $noTripArr = [];
                        if(!$tripPerRoute){
                            $out->writeln("In tripPerRoute < 0");
                            foreach($all_dates as $all_date){
                                $noTrip = new \stdClass;
                                $noTrip->service_date = $all_date;
                                $noTrip->route_id = $allRoute->id;
                                $noTrip->route_number = $allRoute->route_number;
                                $noTrip->route_name = $allRoute->route_name;
                                $noTrip->company_id = $allRoute->company_id;
                                $noTrip->trip_served = 0;
                                $noTrip->km_served = 0.00;
                                $noTrip->farebox = 0.00;
                                $noTrip->ridership = 0;

                                $noTripArr[] = $noTrip;
                            }
                            $allTrips = array_merge($allTrips, $noTripArr);
                        }else{
                            $out->writeln("In tripPerRoute > 0");
                            $allTrips = array_merge($allTrips, $tripPerRoute);
                        }
                        //$allTrips = array_merge($allTrips, $tripPerRoute);
                        //$allTrips[$routeNo] = $tripPerRoute;
                    }

                    //Trip Planned
                    $tripPlanned = DB::select("SELECT route.route_number, route.route_name, cast(route_scheduler_details.schedule_date as date) as planned_date,
                    count(route_scheduler_details.id) as trip_planned, 
                    count(route_scheduler_details.id) * route.distance as km_planned
                    FROM mybas.route
                    JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                    JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                    WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                    AND route.status = 1
                    GROUP BY planned_date, route_number
                    ORDER BY route_number;");

                    $lastKey = array_key_last($allTrips);
                    $pastNo = "";
                    $pastCompany = "";
                    $count = 0;
                    $countMissedTrip = 0;
                    $countMissedTripPerRoute = 0;
                    $countMissedTripGrand = 0;
                    $currTotServedPerRoute = [];
                    $currTotPlannedPerRoute = [];
                    $currTotServedPerCompany = [];
                    $currTotPlannedPerCompany = [];
                    foreach($allTrips as $key => $allTrip){
                        $out->writeln("New Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                        $startDay = new Carbon($allTrip->service_date);
                        $endDay = new Carbon($allTrip->service_date . '23:59:59');
                        if ($startDay->isWeekDay()) {
                            $allTrip->day = "WEEKDAY";
                        } elseif ($startDay->isWeekend()) {
                            $allTrip->day = "WEEKEND";
                        }
                        $found = false;

                        //Enter total per route
                        if($allTrip->route_number!= $pastNo && $pastNo != NULL){
                            $out->writeln("Inside Splice Total Route Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                            $out->writeln("up count: " . $count);

                            if($count==0){
                                array_splice($allTrips, $key, 0, $currTotServedPerRoute);
                            }else{
                                $newKey = $key + $count;
                                $out->writeln("newKey: " . $newKey);
                                array_splice($allTrips, $newKey, 0, $currTotServedPerRoute);
                            }
                            $count++;
                            $countMissedTripPerRoute = 0;
                            $out->writeln("low count: " . $count);
                        }

                        //Enter total per company
                        if($allTrip->company_id != $pastCompany && $pastCompany != NULL){
                            $out->writeln("Inside Splice Total Company Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                            $out->writeln("up count: " . $count);

                            if($count==0){
                                array_splice($allTrips, $key, 0, $currTotServedPerCompany);
                            }else{
                                $newKey = $key + $count;
                                $out->writeln("newKey: " . $newKey);
                                array_splice($allTrips, $newKey, 0, $currTotServedPerCompany);
                            }
                            $count++;
                            $countMissedTrip = 0;
                            $out->writeln("low count: " . $count);
                        }
                        foreach($tripPlanned as $key2 => $tripPlan){
                            if($tripPlan->route_number == $allTrip->route_number){
                                if($tripPlan->planned_date == $allTrip->service_date){
                                    $allTrip->trip_planned = $tripPlan->trip_planned;
                                    $allTrip->km_planned = $tripPlan->km_planned;

                                    //Total Missed Trip
                                    $diff= $tripPlan->trip_planned - $allTrip->trip_served;
                                    if($diff>0){
                                        $allTrip->missed_trip = $diff;
                                        $countMissedTrip += $diff;
                                        $countMissedTripPerRoute += $diff;
                                        $countMissedTripGrand += $diff;
                                    }else{
                                        $allTrip->missed_trip = 0;
                                    }

                                    //Trip Compliance
                                    if($allTrip->trip_planned==0){
                                        $allTrip->trip_compliance = 0;
                                    }else{
                                        $allTrip->trip_compliance = round((($allTrip->trip_served/$allTrip->trip_planned) * 100),0);
                                    }

                                    $found = true;
                                    break ;
                                }
                            }
                        }
                        if(!$found){
                            $allTrip->trip_planned = 0;
                            $allTrip->km_planned = 0;
                            $allTrip->missed_trip = 0;
                            $allTrip->trip_compliance = 0;
                        }
                        
                        //Early Late
                        $earlyLateCount = DB::select("SELECT count(trip_details.id) as countEarlyLate
                        FROM trip_details
                        WHERE trip_details.id IN ( 
                        SELECT trip_details.id
                        FROM mybas.trip_details
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE trip_details.start_trip BETWEEN '" . $startDay . "' AND '" . $endDay . "'
                        AND trip_details.route_id =  " . $allTrip->route_id . "
                        AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                        OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                        GROUP BY trip_details.id);");

                        foreach($earlyLateCount as $key3 => $earlyLate){
                            $allTrip->earlyLate = $earlyLate->countEarlyLate;
                        }

                        $currTotServedPerRoute = DB::select("SELECT route.route_number, route.route_name, company.company_name, count(trip_details.id) as trip_served, 
                        SUM(trip_details.total_mileage) as km_served,
                        SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                        SUM(total_adult) + SUM(total_concession) AS ridership
                        FROM mybas.trip_details
                        JOIN mybas.route ON route.id = trip_details.route_id
                        JOIN mybas.company ON company.id = route.company_id
                        WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.route_number = '" . $allTrip->route_number . "'
                        GROUP BY route_number
                        ORDER BY route.company_id, route_number;");

                        $currTotPlannedPerRoute = DB::select("SELECT route.route_number, route.route_name, cast(route_scheduler_details.schedule_date as date) as planned_date,
                        count(route_scheduler_details.id) as trip_planned, 
                        count(route_scheduler_details.id) * route.distance as km_planned
                        FROM mybas.route
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.route_number = '" . $allTrip->route_number . "'
                        GROUP BY route.route_number
                        ORDER BY route.route_number;");

                        $found = false;
                        if($currTotServedPerRoute){
                            foreach($currTotServedPerRoute as $key4 => $totalTripServe){
                                //Early Late Per Route
                                $earlyLateCountPerRoute = DB::select("SELECT count(trip_details.id) as countEarlyLate
                                FROM trip_details
                                WHERE trip_details.id IN ( 
                                SELECT trip_details.id
                                FROM mybas.trip_details
                                JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                                JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                                WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                                AND trip_details.route_id =  " . $allTrip->route_id . "
                                AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                                OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                                GROUP BY trip_details.id);");

                                foreach($earlyLateCountPerRoute as $key5 => $earlyLatePerRoute){
                                    $totalTripServe->earlyLate = $earlyLatePerRoute->countEarlyLate;
                                }

                                foreach($currTotPlannedPerRoute as $key6 => $totalTripPlan){
                                    $totalTripServe->trip_planned = $totalTripPlan->trip_planned;
                                    $totalTripServe->km_planned = $totalTripPlan->km_planned;

                                    //Total Missed Trip Ver 2
                                    $totalTripServe->missed_trip = $countMissedTripPerRoute;

                                    //Trip Compliance
                                    if($totalTripServe->trip_planned==0){
                                        $totalTripServe->trip_compliance = 0;
                                    }else{
                                        $totalTripServe->trip_compliance = round((($totalTripServe->trip_served/$totalTripServe->trip_planned) * 100),0);
                                    }

                                    $found = true;
                                    break ;
                                }
                                if(!$found){
                                    $totalTripServe->trip_planned = 0;
                                    $totalTripServe->km_planned = 0;
                                    $totalTripServe->missed_trip = 0;
                                    $totalTripServe->trip_compliance = 0;
                                }
                            }
                        }else{
                            $noTrip = new \stdClass;
                            $noTrip->route_number = $allTrip->route_number;
                            $noTrip->route_name = $allTrip->route_name;
                            $noTrip->company_id = $allTrip->company_id;
                            $noTrip->trip_served = 0;
                            $noTrip->km_served = 0.00;
                            $noTrip->farebox = 0.00;
                            $noTrip->ridership = 0;
                            $noTrip->earlyLate = 0;
                            
                            foreach($currTotPlannedPerRoute as $key6 => $totalTripPlan){
                                $noTrip->trip_planned = $totalTripPlan->trip_planned;
                                $noTrip->km_planned = $totalTripPlan->km_planned;

                                //Total Missed Trip Ver 2
                                $noTrip->missed_trip = $countMissedTripPerRoute;

                                //Trip Compliance
                                if($noTrip->trip_planned==0){
                                    $noTrip->trip_compliance = 0;
                                }else{
                                    $noTrip->trip_compliance = round((($noTrip->trip_served/$noTrip->trip_planned) * 100),0);
                                }

                                $found = true;
                                break ;
                            }
                            if(!$found){
                                $noTrip->trip_planned = 0;
                                $noTrip->km_planned = 0;
                                $noTrip->missed_trip = 0;
                                $noTrip->trip_compliance = 0;
                            }
                            $currTotServedPerRoute[] = $noTrip;
                        }
                        $pastNo = $allTrip->route_number;

                        $currTotServedPerCompany = DB::select("SELECT company.company_name, count(trip_details.id) as trip_served, 
                        SUM(trip_details.total_mileage) as km_served,
                        SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                        SUM(total_adult) + SUM(total_concession) AS ridership
                        FROM mybas.trip_details
                        JOIN mybas.route ON route.id = trip_details.route_id
                        JOIN mybas.company ON company.id = route.company_id
                        WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.company_id = " . $allTrip->company_id . "
                        AND route.status = 1;");

                        $currTotPlannedPerCompany = DB::select("SELECT company.company_name, count(route_scheduler_details.id) as trip_planned, 
                        count(route_scheduler_details.id) * route.distance as km_planned
                        FROM mybas.route
                        JOIN mybas.company ON company.id = route.company_id
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.company_id = " . $allTrip->company_id . "
                        AND route.status = 1;");

                        $found = false;
                        foreach($currTotServedPerCompany as $key7 => $companyTripServe){
                            //Early Late Per Company
                            $earlyLateCountPerCompany = DB::select("SELECT count(trip_details.id) as countEarlyLate
                            FROM trip_details
                            WHERE trip_details.id IN ( 
                            SELECT trip_details.id
                            FROM mybas.trip_details
                            JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                            JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                            WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND trip_details.route_id IN (SELECT id FROM mybas.route WHERE company_id = " . $allTrip->company_id . ")
                            AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                            OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                            GROUP BY trip_details.id);");

                            foreach($earlyLateCountPerCompany  as $key5 => $earlyLatePerCompany){
                                $companyTripServe->earlyLate = $earlyLatePerCompany->countEarlyLate;
                            }

                            foreach($currTotPlannedPerCompany as $key8 => $companyTripPlan){
                                $companyTripServe->trip_planned = $companyTripPlan->trip_planned;
                                $companyTripServe->km_planned = $companyTripPlan->km_planned;

                                //Total Missed Trip Ver 2
                                $companyTripServe->missed_trip = $countMissedTrip;

                                //Total Missed Trip
                                // $diff= $companyTripPlan->trip_planned - $companyTripServe->trip_served;
                                // if($diff>0){
                                //     $companyTripServe->missed_trip = $diff;
                                // }else{
                                //     $companyTripServe->missed_trip = 0;
                                // }

                                //Trip Compliance
                                if($companyTripServe->trip_planned==0){
                                    $companyTripServe->trip_compliance = 0;
                                }else{
                                    $companyTripServe->trip_compliance = round((($companyTripServe->trip_served/$companyTripServe->trip_planned) * 100),0);
                                }

                                $found = true;
                                break ;
                            }
                            if(!$found){
                                $companyTripServe->trip_planned = 0;
                                $companyTripServe->km_planned = 0;
                                $companyTripServe->missed_trip = 0;
                                $companyTripServe->trip_compliance = 0;
                            }
                        }
                        $pastCompany = $allTrip->company_id;

                        if($lastKey==$key){
                            $grandTotServed = DB::select("SELECT count(trip_details.id) as trip_served, 
                            SUM(trip_details.total_mileage) as km_served,
                            SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                            SUM(total_adult) + SUM(total_concession) AS ridership
                            FROM mybas.trip_details
                            JOIN mybas.route ON route.id = trip_details.route_id
                            WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND route.status = 1;");

                            $grandTotPlanned = DB::select("SELECT count(route_scheduler_details.id) as trip_planned, 
                            count(route_scheduler_details.id) * route.distance as km_planned
                            FROM mybas.route
                            JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                            JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                            WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND route.status = 1;");

                            foreach($grandTotServed as $grandInKey => $grandServed){
                                $out->writeln("In grand total loop");
                                //Early Late
                                $grandTotEarlyLate = DB::select("SELECT count(trip_details.id) as countEarlyLate
                                FROM trip_details
                                WHERE trip_details.id IN ( 
                                SELECT trip_details.id
                                FROM mybas.trip_details
                                JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                                JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                                WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                                AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                                OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00'));");

                                foreach($grandTotEarlyLate as $grandEarlyLateKey => $grandEarlyLate){
                                    $grandServed->earlyLate = $grandEarlyLate->countEarlyLate;
                                }

                                foreach($grandTotPlanned as $grandPlanKey => $grandPlanned){
                                    $grandServed->trip_planned = $grandPlanned->trip_planned;
                                    $grandServed->km_planned = $grandPlanned->km_planned;
                                    $grandServed->missed_trip = $countMissedTripGrand;
                                    if($grandServed->trip_planned==0){
                                        $grandServed->trip_compliance = 0;
                                    }else{
                                        $grandServed->trip_compliance = round((($grandServed->trip_served/$grandServed->trip_planned) * 100),0);
                                    }
                                }
                            }

                            $newKey = $key + $count+1;
                            $out->writeln("last newKey: " . $newKey);
                            array_splice($allTrips, $newKey, 0, $grandTotServed);
                            array_splice($allTrips, $newKey, 0, $currTotServedPerCompany);
                            array_splice($allTrips, $newKey, 0, $currTotServedPerRoute);
                        }
                    }
                }
            }else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $companyDetails->company_name;
                //Daily Summary all route specific company
                if($validatedData['route_id']=='All'){

                    $getAllRoutes = Route::where('status', 1)
                        ->where('company_id', $companyDetails->id)
                        ->orderBy('company_id')
                        ->orderBy('route_number')
                        ->get();
                    $allTrips = [];

                    foreach($getAllRoutes as $allRoute){
                        $out->writeln("All Route: " . $allRoute->route_number);
                        $tripPerRoute = DB::select("WITH RECURSIVE n_dte (n) AS (
                            SELECT " . $startDayNo . "
                            UNION ALL
                            SELECT n + 1
                            FROM n_dte
                            WHERE n < " .  $endDayNo . "
                            ),
                            all_mnth_days AS (
                            SELECT route.id as route_id, route.route_number, route.route_name, route.company_id,
                            DATE_ADD(MIN(trip_details.start_trip), INTERVAL - DAY(MIN(trip_details.start_trip)) + 1 DAY) AS mnth_first_day,
                            n,
                            CAST(DATE_ADD(DATE_ADD(MIN(trip_details.start_trip), INTERVAL - DAY(MIN(trip_details.start_trip)) + 1 DAY), INTERVAL n - 1 DAY) AS DATE) AS all_days_in_mnth
                            FROM mybas.trip_details
                            JOIN n_dte
                            JOIN mybas.route ON route.id = trip_details.route_id
                            WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND route.id = '". $allRoute->id . "'
                            group by n
                            ORDER BY n ASC)
                            
                            SELECT
                            amd.all_days_in_mnth AS service_date, amd.route_id, amd.route_number, amd.route_name, amd.company_id, 
                            (case when (count(ie.id) IS NULL) THEN 0 ELSE count(ie.id) END) as trip_served,
                            (case when (SUM(ie.total_mileage) IS NULL) THEN 0 ELSE SUM(ie.total_mileage) END) as km_served,
                            (case when (SUM(ie.total_adult_amount) + SUM(ie.total_concession_amount) IS NULL) THEN 0 
                            ELSE SUM(ie.total_adult_amount) + SUM(ie.total_concession_amount) END) AS farebox, 
                            (case when (SUM(ie.total_adult) + SUM(ie.total_concession) IS NULL) THEN 0 
                            ELSE SUM(ie.total_adult) + SUM(ie.total_concession) END) AS ridership
                            FROM all_mnth_days AS amd
                            LEFT JOIN trip_details AS ie ON CAST(ie.start_trip AS DATE) = amd.all_days_in_mnth
                            AND ie.route_id = amd.route_id
                            GROUP BY service_date;");

                        $noTripArr = [];
                        if(!$tripPerRoute){
                            $out->writeln("In tripPerRoute < 0");
                            foreach($all_dates as $all_date){
                                $noTrip = new \stdClass;
                                $noTrip->service_date = $all_date;
                                $noTrip->route_id = $allRoute->id;
                                $noTrip->route_number = $allRoute->route_number;
                                $noTrip->route_name = $allRoute->route_name;
                                $noTrip->company_id = $allRoute->company_id;
                                $noTrip->trip_served = 0;
                                $noTrip->km_served = 0.00;
                                $noTrip->farebox = 0.00;
                                $noTrip->ridership = 0;

                                $noTripArr[] = $noTrip;
                            }
                            $allTrips = array_merge($allTrips, $noTripArr);
                        }else{
                            $out->writeln("In tripPerRoute > 0");
                            $allTrips = array_merge($allTrips, $tripPerRoute);
                        }
                        //$allTrips = array_merge($allTrips, $tripPerRoute);
                        //$allTrips[$routeNo] = $tripPerRoute;
                    }

                    //Trip Planned
                    $tripPlanned = DB::select("SELECT route.route_number, route.route_name, cast(route_scheduler_details.schedule_date as date) as planned_date,
                    count(route_scheduler_details.id) as trip_planned, 
                    count(route_scheduler_details.id) * route.distance as km_planned
                    FROM mybas.route
                    JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                    JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                    WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                    AND route.status = 1
                    GROUP BY planned_date, route_number
                    ORDER BY route_number;");

                    $lastKey = array_key_last($allTrips);
                    $pastNo = "";
                    $pastCompany = "";
                    $count = 0;
                    $countMissedTrip = 0;
                    $countMissedTripPerRoute = 0;
                    $countMissedTripGrand = 0;
                    $currTotServedPerRoute = [];
                    $currTotPlannedPerRoute = [];
                    $currTotServedPerCompany = [];
                    $currTotPlannedPerCompany = [];
                    foreach($allTrips as $key => $allTrip){
                        $out->writeln("New Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                        $startDay = new Carbon($allTrip->service_date);
                        $endDay = new Carbon($allTrip->service_date . '23:59:59');
                        if ($startDay->isWeekDay()) {
                            $allTrip->day = "WEEKDAY";
                        } elseif ($startDay->isWeekend()) {
                            $allTrip->day = "WEEKEND";
                        }
                        $found = false;

                        //Enter total per route
                        if($allTrip->route_number!= $pastNo && $pastNo != NULL){
                            $out->writeln("Inside Splice Total Route Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                            $out->writeln("up count: " . $count);

                            if($count==0){
                                array_splice($allTrips, $key, 0, $currTotServedPerRoute);
                            }else{
                                $newKey = $key + $count;
                                $out->writeln("newKey: " . $newKey);
                                array_splice($allTrips, $newKey, 0, $currTotServedPerRoute);
                            }
                            $count++;
                            $countMissedTripPerRoute = 0;
                            $out->writeln("low count: " . $count);
                        }

                        //Enter total per company
                        if($allTrip->company_id != $pastCompany && $pastCompany != NULL){
                            $out->writeln("Inside Splice Total Company Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                            $out->writeln("up count: " . $count);

                            if($count==0){
                                array_splice($allTrips, $key, 0, $currTotServedPerCompany);
                            }else{
                                $newKey = $key + $count;
                                $out->writeln("newKey: " . $newKey);
                                array_splice($allTrips, $newKey, 0, $currTotServedPerCompany);
                            }
                            $count++;
                            $countMissedTrip = 0;
                            $out->writeln("low count: " . $count);
                        }
                        foreach($tripPlanned as $key2 => $tripPlan){
                            if($tripPlan->route_number == $allTrip->route_number){
                                if($tripPlan->planned_date == $allTrip->service_date){
                                    $allTrip->trip_planned = $tripPlan->trip_planned;
                                    $allTrip->km_planned = $tripPlan->km_planned;

                                    //Total Missed Trip
                                    $diff= $tripPlan->trip_planned - $allTrip->trip_served;
                                    if($diff>0){
                                        $allTrip->missed_trip = $diff;
                                        $countMissedTrip += $diff;
                                        $countMissedTripPerRoute += $diff;
                                        $countMissedTripGrand += $diff;
                                    }else{
                                        $allTrip->missed_trip = 0;
                                    }

                                    //Trip Compliance
                                    if($allTrip->trip_planned==0){
                                        $allTrip->trip_compliance = 0;
                                    }else{
                                        $allTrip->trip_compliance = round((($allTrip->trip_served/$allTrip->trip_planned) * 100),0);
                                    }

                                    $found = true;
                                    break ;
                                }
                            }
                        }
                        if(!$found){
                            $allTrip->trip_planned = 0;
                            $allTrip->km_planned = 0;
                            $allTrip->missed_trip = 0;
                            $allTrip->trip_compliance = 0;
                        }
                        
                        //Early Late
                        $earlyLateCount = DB::select("SELECT count(trip_details.id) as countEarlyLate
                        FROM trip_details
                        WHERE trip_details.id IN ( 
                        SELECT trip_details.id
                        FROM mybas.trip_details
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE trip_details.start_trip BETWEEN '" . $startDay . "' AND '" . $endDay . "'
                        AND trip_details.route_id =  " . $allTrip->route_id . "
                        AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                        OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                        GROUP BY trip_details.id);");

                        foreach($earlyLateCount as $key3 => $earlyLate){
                            $allTrip->earlyLate = $earlyLate->countEarlyLate;
                        }

                        $currTotServedPerRoute = DB::select("SELECT route.route_number, route.route_name, company.company_name, count(trip_details.id) as trip_served, 
                        SUM(trip_details.total_mileage) as km_served,
                        SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                        SUM(total_adult) + SUM(total_concession) AS ridership
                        FROM mybas.trip_details
                        JOIN mybas.route ON route.id = trip_details.route_id
                        JOIN mybas.company ON company.id = route.company_id
                        WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.route_number = '" . $allTrip->route_number . "'
                        GROUP BY route_number
                        ORDER BY route.company_id, route_number;");

                        $currTotPlannedPerRoute = DB::select("SELECT route.route_number, route.route_name, cast(route_scheduler_details.schedule_date as date) as planned_date,
                        count(route_scheduler_details.id) as trip_planned, 
                        count(route_scheduler_details.id) * route.distance as km_planned
                        FROM mybas.route
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.route_number = '" . $allTrip->route_number . "'
                        GROUP BY route.route_number
                        ORDER BY route.route_number;");

                        $found = false;
                        if($currTotServedPerRoute){
                            foreach($currTotServedPerRoute as $key4 => $totalTripServe){
                                //Early Late Per Route
                                $earlyLateCountPerRoute = DB::select("SELECT count(trip_details.id) as countEarlyLate
                                FROM trip_details
                                WHERE trip_details.id IN ( 
                                SELECT trip_details.id
                                FROM mybas.trip_details
                                JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                                JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                                WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                                AND trip_details.route_id =  " . $allTrip->route_id . "
                                AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                                OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                                GROUP BY trip_details.id);");

                                foreach($earlyLateCountPerRoute as $key5 => $earlyLatePerRoute){
                                    $totalTripServe->earlyLate = $earlyLatePerRoute->countEarlyLate;
                                }

                                foreach($currTotPlannedPerRoute as $key6 => $totalTripPlan){
                                    $totalTripServe->trip_planned = $totalTripPlan->trip_planned;
                                    $totalTripServe->km_planned = $totalTripPlan->km_planned;

                                    //Total Missed Trip Ver 2
                                    $totalTripServe->missed_trip = $countMissedTripPerRoute;

                                    //Trip Compliance
                                    if($totalTripServe->trip_planned==0){
                                        $totalTripServe->trip_compliance = 0;
                                    }else{
                                        $totalTripServe->trip_compliance = round((($totalTripServe->trip_served/$totalTripServe->trip_planned) * 100),0);
                                    }

                                    $found = true;
                                    break ;
                                }
                                if(!$found){
                                    $totalTripServe->trip_planned = 0;
                                    $totalTripServe->km_planned = 0;
                                    $totalTripServe->missed_trip = 0;
                                    $totalTripServe->trip_compliance = 0;
                                }
                            }
                        }else{
                            $noTrip = new \stdClass;
                            $noTrip->route_number = $allTrip->route_number;
                            $noTrip->route_name = $allTrip->route_name;
                            $noTrip->company_id = $allTrip->company_id;
                            $noTrip->trip_served = 0;
                            $noTrip->km_served = 0.00;
                            $noTrip->farebox = 0.00;
                            $noTrip->ridership = 0;
                            $noTrip->earlyLate = 0;
                            
                            foreach($currTotPlannedPerRoute as $key6 => $totalTripPlan){
                                $noTrip->trip_planned = $totalTripPlan->trip_planned;
                                $noTrip->km_planned = $totalTripPlan->km_planned;

                                //Total Missed Trip Ver 2
                                $noTrip->missed_trip = $countMissedTripPerRoute;

                                //Trip Compliance
                                if($noTrip->trip_planned==0){
                                    $noTrip->trip_compliance = 0;
                                }else{
                                    $noTrip->trip_compliance = round((($noTrip->trip_served/$noTrip->trip_planned) * 100),0);
                                }

                                $found = true;
                                break ;
                            }
                            if(!$found){
                                $noTrip->trip_planned = 0;
                                $noTrip->km_planned = 0;
                                $noTrip->missed_trip = 0;
                                $noTrip->trip_compliance = 0;
                            }
                            $currTotServedPerRoute[] = $noTrip;
                        }
                        $pastNo = $allTrip->route_number;

                        $currTotServedPerCompany = DB::select("SELECT company.company_name, count(trip_details.id) as trip_served, 
                        SUM(trip_details.total_mileage) as km_served,
                        SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                        SUM(total_adult) + SUM(total_concession) AS ridership
                        FROM mybas.trip_details
                        JOIN mybas.route ON route.id = trip_details.route_id
                        JOIN mybas.company ON company.id = route.company_id
                        WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.company_id = " . $allTrip->company_id . "
                        AND route.status = 1;");

                        $currTotPlannedPerCompany = DB::select("SELECT company.company_name, count(route_scheduler_details.id) as trip_planned, 
                        count(route_scheduler_details.id) * route.distance as km_planned
                        FROM mybas.route
                        JOIN mybas.company ON company.id = route.company_id
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.company_id = " . $allTrip->company_id . "
                        AND route.status = 1;");

                        $found = false;
                        foreach($currTotServedPerCompany as $key7 => $companyTripServe){
                            //Early Late Per Company
                            $earlyLateCountPerCompany = DB::select("SELECT count(trip_details.id) as countEarlyLate
                            FROM trip_details
                            WHERE trip_details.id IN ( 
                            SELECT trip_details.id
                            FROM mybas.trip_details
                            JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                            JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                            WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND trip_details.route_id IN (SELECT id FROM mybas.route WHERE company_id = " . $allTrip->company_id . ")
                            AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                            OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                            GROUP BY trip_details.id);");

                            foreach($earlyLateCountPerCompany  as $key5 => $earlyLatePerCompany){
                                $companyTripServe->earlyLate = $earlyLatePerCompany->countEarlyLate;
                            }

                            foreach($currTotPlannedPerCompany as $key8 => $companyTripPlan){
                                $companyTripServe->trip_planned = $companyTripPlan->trip_planned;
                                $companyTripServe->km_planned = $companyTripPlan->km_planned;

                                //Total Missed Trip Ver 2
                                $companyTripServe->missed_trip = $countMissedTrip;

                                //Total Missed Trip
                                // $diff= $companyTripPlan->trip_planned - $companyTripServe->trip_served;
                                // if($diff>0){
                                //     $companyTripServe->missed_trip = $diff;
                                // }else{
                                //     $companyTripServe->missed_trip = 0;
                                // }

                                //Trip Compliance
                                if($companyTripServe->trip_planned==0){
                                    $companyTripServe->trip_compliance = 0;
                                }else{
                                    $companyTripServe->trip_compliance = round((($companyTripServe->trip_served/$companyTripServe->trip_planned) * 100),0);
                                }

                                $found = true;
                                break ;
                            }
                            if(!$found){
                                $companyTripServe->trip_planned = 0;
                                $companyTripServe->km_planned = 0;
                                $companyTripServe->missed_trip = 0;
                                $companyTripServe->trip_compliance = 0;
                            }
                        }
                        $pastCompany = $allTrip->company_id;

                        if($lastKey==$key){
                            $grandTotServed = DB::select("SELECT count(trip_details.id) as trip_served, 
                            SUM(trip_details.total_mileage) as km_served,
                            SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                            SUM(total_adult) + SUM(total_concession) AS ridership
                            FROM mybas.trip_details
                            JOIN mybas.route ON route.id = trip_details.route_id
                            WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND route.company_id = " . $allTrip->company_id . "
                            AND route.status = 1;");

                            $grandTotPlanned = DB::select("SELECT count(route_scheduler_details.id) as trip_planned, 
                            count(route_scheduler_details.id) * route.distance as km_planned
                            FROM mybas.route
                            JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                            JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                            WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND route.company_id = " . $allTrip->company_id . "
                            AND route.status = 1;");

                            foreach($grandTotServed as $grandInKey => $grandServed){
                                $out->writeln("In grand total loop");
                                //Early Late
                                $grandTotEarlyLate = DB::select("SELECT count(trip_details.id) as countEarlyLate
                                FROM trip_details
                                WHERE trip_details.id IN ( 
                                SELECT trip_details.id
                                FROM mybas.trip_details
                                JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                                JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                                WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                                AND trip_details.route_id IN (SELECT id FROM mybas.route WHERE company_id = " . $allTrip->company_id . ")
                                AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                                OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00'));");

                                foreach($grandTotEarlyLate as $grandEarlyLateKey => $grandEarlyLate){
                                    $grandServed->earlyLate = $grandEarlyLate->countEarlyLate;
                                }

                                foreach($grandTotPlanned as $grandPlanKey => $grandPlanned){
                                    $grandServed->trip_planned = $grandPlanned->trip_planned;
                                    $grandServed->km_planned = $grandPlanned->km_planned;
                                    $grandServed->missed_trip = $countMissedTripGrand;
                                    if($grandServed->trip_planned==0){
                                        $grandServed->trip_compliance = 0;
                                    }else{
                                        $grandServed->trip_compliance = round((($grandServed->trip_served/$grandServed->trip_planned) * 100),0);
                                    }
                                }
                            }

                            $newKey = $key + $count+1;
                            $out->writeln("last newKey: " . $newKey);
                            array_splice($allTrips, $newKey, 0, $grandTotServed);
                            array_splice($allTrips, $newKey, 0, $currTotServedPerCompany);
                            array_splice($allTrips, $newKey, 0, $currTotServedPerRoute);
                        }
                    }
                }
                //Daily Summary specific route specific company
                else{
                    $allRoute = Route::where('id', $validatedData['route_id'])->first();
                    $allTrips = [];

                    if($allRoute){
                        $out->writeln("All Route: " . $allRoute->route_number);
                        $tripPerRoute = DB::select("WITH RECURSIVE n_dte (n) AS (
                            SELECT " . $startDayNo . "
                            UNION ALL
                            SELECT n + 1
                            FROM n_dte
                            WHERE n < " . $endDayNo . "
                            ),
                            all_mnth_days AS (
                            SELECT route.id as route_id, route.route_number, route.route_name, route.company_id,
                            DATE_ADD(MIN(trip_details.start_trip), INTERVAL - DAY(MIN(trip_details.start_trip)) + 1 DAY) AS mnth_first_day,
                            n,
                            CAST(DATE_ADD(DATE_ADD(MIN(trip_details.start_trip), INTERVAL - DAY(MIN(trip_details.start_trip)) + 1 DAY), INTERVAL n - 1 DAY) AS DATE) AS all_days_in_mnth
                            FROM mybas.trip_details
                            JOIN n_dte
                            JOIN mybas.route ON route.id = trip_details.route_id
                            WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND route.id = '". $allRoute->id . "'
                            group by n
                            ORDER BY n ASC)
                            
                            SELECT
                            amd.all_days_in_mnth AS service_date, amd.route_id, amd.route_number, amd.route_name, amd.company_id, 
                            (case when (count(ie.id) IS NULL) THEN 0 ELSE count(ie.id) END) as trip_served,
                            (case when (SUM(ie.total_mileage) IS NULL) THEN 0 ELSE SUM(ie.total_mileage) END) as km_served,
                            (case when (SUM(ie.total_adult_amount) + SUM(ie.total_concession_amount) IS NULL) THEN 0 
                            ELSE SUM(ie.total_adult_amount) + SUM(ie.total_concession_amount) END) AS farebox, 
                            (case when (SUM(ie.total_adult) + SUM(ie.total_concession) IS NULL) THEN 0 
                            ELSE SUM(ie.total_adult) + SUM(ie.total_concession) END) AS ridership
                            FROM all_mnth_days AS amd
                            LEFT JOIN trip_details AS ie ON CAST(ie.start_trip AS DATE) = amd.all_days_in_mnth
                            AND ie.route_id = amd.route_id
                            GROUP BY service_date;");

                        $noTripArr = [];
                        if(!$tripPerRoute){
                            $out->writeln("In tripPerRoute < 0");
                            foreach($all_dates as $all_date){
                                $noTrip = new \stdClass;
                                $noTrip->service_date = $all_date;
                                $noTrip->route_id = $allRoute->id;
                                $noTrip->route_number = $allRoute->route_number;
                                $noTrip->route_name = $allRoute->route_name;
                                $noTrip->company_id = $allRoute->company_id;
                                $noTrip->trip_served = 0;
                                $noTrip->km_served = 0.00;
                                $noTrip->farebox = 0.00;
                                $noTrip->ridership = 0;

                                $noTripArr[] = $noTrip;
                            }
                            $allTrips = array_merge($allTrips, $noTripArr);
                        }else{
                            $out->writeln("In tripPerRoute > 0");
                            $allTrips = array_merge($allTrips, $tripPerRoute);
                        }
                        //$allTrips = array_merge($allTrips, $tripPerRoute);
                        //$allTrips[$routeNo] = $tripPerRoute;
                    }

                    //Trip Planned
                    $tripPlanned = DB::select("SELECT route.route_number, route.route_name, cast(route_scheduler_details.schedule_date as date) as planned_date,
                    count(route_scheduler_details.id) as trip_planned, 
                    count(route_scheduler_details.id) * route.distance as km_planned
                    FROM mybas.route
                    JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                    JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                    WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                    AND route.status = 1
                    GROUP BY planned_date, route_number
                    ORDER BY route_number;");

                    $lastKey = array_key_last($allTrips);
                    $pastNo = "";
                    $pastCompany = "";
                    $count = 0;
                    $countMissedTrip = 0;
                    $countMissedTripPerRoute = 0;
                    $currTotServedPerRoute = [];
                    $currTotPlannedPerRoute = [];
                    $currTotServedPerCompany = [];
                    $currTotPlannedPerCompany = [];
                    foreach($allTrips as $key => $allTrip){
                        $out->writeln("New Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                        $startDay = new Carbon($allTrip->service_date);
                        $endDay = new Carbon($allTrip->service_date . '23:59:59');
                        if ($startDay->isWeekDay()) {
                            $allTrip->day = "WEEKDAY";
                        } elseif ($startDay->isWeekend()) {
                            $allTrip->day = "WEEKEND";
                        }
                        $found = false;

                        foreach($tripPlanned as $key2 => $tripPlan){
                            if($tripPlan->route_number == $allTrip->route_number){
                                if($tripPlan->planned_date == $allTrip->service_date){
                                    $allTrip->trip_planned = $tripPlan->trip_planned;
                                    $allTrip->km_planned = $tripPlan->km_planned;

                                    //Total Missed Trip
                                    $diff= $tripPlan->trip_planned - $allTrip->trip_served;
                                    if($diff>0){
                                        $allTrip->missed_trip = $diff;
                                        $countMissedTrip += $diff;
                                        $countMissedTripPerRoute += $diff;
                                    }else{
                                        $allTrip->missed_trip = 0;
                                    }

                                    //Trip Compliance
                                    if($allTrip->trip_planned==0){
                                        $allTrip->trip_compliance = 0;
                                    }else{
                                        $allTrip->trip_compliance = round((($allTrip->trip_served/$allTrip->trip_planned) * 100),0);
                                    }

                                    $found = true;
                                    break ;
                                }
                            }
                        }
                        if(!$found){
                            $allTrip->trip_planned = 0;
                            $allTrip->km_planned = 0;
                            $allTrip->missed_trip = 0;
                            $allTrip->trip_compliance = 0;
                        }
                        
                        //Early Late
                        $earlyLateCount = DB::select("SELECT count(trip_details.id) as countEarlyLate
                        FROM trip_details
                        WHERE trip_details.id IN ( 
                        SELECT trip_details.id
                        FROM mybas.trip_details
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE trip_details.start_trip BETWEEN '" . $startDay . "' AND '" . $endDay . "'
                        AND trip_details.route_id =  " . $allTrip->route_id . "
                        AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                        OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                        GROUP BY trip_details.id);");

                        foreach($earlyLateCount as $key3 => $earlyLate){
                            $allTrip->earlyLate = $earlyLate->countEarlyLate;
                        }

                        if($lastKey==$key){
                            $currTotServedPerRoute = DB::select("SELECT route.route_number, route.route_name, company.company_name, count(trip_details.id) as trip_served, 
                            SUM(trip_details.total_mileage) as km_served,
                            SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                            SUM(total_adult) + SUM(total_concession) AS ridership
                            FROM mybas.trip_details
                            JOIN mybas.route ON route.id = trip_details.route_id
                            JOIN mybas.company ON company.id = route.company_id
                            WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND route.route_number = '" . $allTrip->route_number . "'
                            GROUP BY route_number
                            ORDER BY route.company_id, route_number;");

                            $currTotPlannedPerRoute = DB::select("SELECT route.route_number, route.route_name, cast(route_scheduler_details.schedule_date as date) as planned_date,
                            count(route_scheduler_details.id) as trip_planned, 
                            count(route_scheduler_details.id) * route.distance as km_planned
                            FROM mybas.route
                            JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                            JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                            WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND route.route_number = '" . $allTrip->route_number . "'
                            GROUP BY route.route_number
                            ORDER BY route.route_number;");

                            $found = false;
                            if($currTotServedPerRoute){
                                foreach($currTotServedPerRoute as $key4 => $totalTripServe){
                                    $companyTotal = new \stdClass;
                                    $grandTotal = new \stdClass;

                                    $companyTotal->company_name = $totalTripServe->company_name;
                                    $companyTotal->trip_served = $totalTripServe->trip_served;
                                    $companyTotal->km_served = $totalTripServe->km_served;
                                    $companyTotal->farebox = $totalTripServe->farebox;
                                    $companyTotal->ridership = $totalTripServe->ridership;

                                    $grandTotal->trip_served = $totalTripServe->trip_served;
                                    $grandTotal->km_served = $totalTripServe->km_served;
                                    $grandTotal->farebox = $totalTripServe->farebox;
                                    $grandTotal->ridership = $totalTripServe->ridership;

                                    //Early Late Per Route
                                    $earlyLateCountPerRoute = DB::select("SELECT count(trip_details.id) as countEarlyLate
                                    FROM trip_details
                                    WHERE trip_details.id IN ( 
                                    SELECT trip_details.id
                                    FROM mybas.trip_details
                                    JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                                    JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                                    WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                                    AND trip_details.route_id =  " . $allTrip->route_id . "
                                    AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                                    OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                                    GROUP BY trip_details.id);");

                                    foreach($earlyLateCountPerRoute as $key5 => $earlyLatePerRoute){
                                        $totalTripServe->earlyLate = $earlyLatePerRoute->countEarlyLate;
                                        $companyTotal->earlyLate = $earlyLatePerRoute->countEarlyLate;
                                        $grandTotal->earlyLate = $earlyLatePerRoute->countEarlyLate;
                                    }

                                    foreach($currTotPlannedPerRoute as $key6 => $totalTripPlan){
                                        $totalTripServe->trip_planned = $totalTripPlan->trip_planned;
                                        $companyTotal->trip_planned = $totalTripPlan->trip_planned;
                                        $grandTotal->trip_planned = $totalTripPlan->trip_planned;
                                        $totalTripServe->km_planned = $totalTripPlan->km_planned;
                                        $companyTotal->km_planned = $totalTripPlan->km_planned;
                                        $grandTotal->km_planned = $totalTripPlan->km_planned;
                                        //Total Missed Trip Ver 2
                                        $totalTripServe->missed_trip = $countMissedTripPerRoute;
                                        $companyTotal->missed_trip = $countMissedTripPerRoute;
                                        $grandTotal->missed_trip = $countMissedTripPerRoute;
                                        //Trip Compliance
                                        if($totalTripServe->trip_planned==0){
                                            $totalTripServe->trip_compliance = 0;
                                            $companyTotal->trip_compliance = 0;
                                            $grandTotal->trip_compliance = 0;
                                        }else{
                                            $totalTripServe->trip_compliance = round((($totalTripServe->trip_served/$totalTripServe->trip_planned) * 100),0);
                                            $companyTotal->trip_compliance = round((($companyTotal->trip_served/$companyTotal->trip_planned) * 100),0);
                                            $grandTotal->trip_compliance = round((($grandTotal->trip_served/$grandTotal->trip_planned) * 100),0);
                                        }

                                        $found = true;
                                        break ;
                                    }
                                    if(!$found){
                                        $totalTripServe->trip_planned = 0;
                                        $totalTripServe->km_planned = 0;
                                        $totalTripServe->missed_trip = 0;
                                        $totalTripServe->trip_compliance = 0;

                                        $companyTotal->trip_planned = 0;
                                        $companyTotal->km_planned = 0;
                                        $companyTotal->missed_trip = 0;
                                        $companyTotal->trip_compliance = 0;

                                        $grandTotal->trip_planned = 0;
                                        $grandTotal->km_planned = 0;
                                        $grandTotal->missed_trip = 0;
                                        $grandTotal->trip_compliance = 0;
                                    }
                                }
                                $currTotServedPerCompany[] = $companyTotal;
                                $currTotServedPerGrand[] = $grandTotal;
                            }else{
                                $noTrip = new \stdClass;
                                $noTrip->route_number = $allTrip->route_number;
                                $noTrip->route_name = $allTrip->route_name;
                                $noTrip->company_id = $allTrip->company_id;
                                $noTrip->trip_served = 0;
                                $noTrip->km_served = 0.00;
                                $noTrip->farebox = 0.00;
                                $noTrip->ridership = 0;
                                $noTrip->earlyLate = 0;

                                $companyTotal = new \stdClass;
                                $companyTotal->company_id = $allTrip->company_id;
                                $companyTotal->trip_served = 0;
                                $companyTotal->km_served = 0.00;
                                $companyTotal->farebox = 0.00;
                                $companyTotal->ridership = 0;
                                $companyTotal->earlyLate = 0;

                                $grandTotal = new \stdClass;
                                $grandTotal->trip_served = 0;
                                $grandTotal->km_served = 0.00;
                                $grandTotal->farebox = 0.00;
                                $grandTotal->ridership = 0;
                                $grandTotal->earlyLate = 0;
                                
                                foreach($currTotPlannedPerRoute as $key6 => $totalTripPlan){
                                    $noTrip->trip_planned = $totalTripPlan->trip_planned;
                                    $noTrip->km_planned = $totalTripPlan->km_planned;
                                    $companyTotal->trip_planned = $totalTripPlan->trip_planned;
                                    $companyTotal->km_planned = $totalTripPlan->km_planned;
                                    $grandTotal->trip_planned = $totalTripPlan->trip_planned;
                                    $grandTotal->km_planned = $totalTripPlan->km_planned;

                                    //Total Missed Trip Ver 2
                                    $noTrip->missed_trip = $countMissedTripPerRoute;
                                    $companyTotal->missed_trip = $countMissedTripPerRoute;
                                    $grandTotal->missed_trip = $countMissedTripPerRoute;

                                    //Trip Compliance
                                    if($noTrip->trip_planned==0){
                                        $noTrip->trip_compliance = 0;
                                        $companyTotal->trip_compliance = 0;
                                        $grandTotal->trip_compliance = 0;
                                    }else{
                                        $noTrip->trip_compliance = round((($noTrip->trip_served/$noTrip->trip_planned) * 100),0);
                                        $companyTotal->trip_compliance = round((($companyTotal->trip_served/$companyTotal->trip_planned) * 100),0);
                                        $grandTotal->trip_compliance = round((($grandTotal->trip_served/$grandTotal->trip_planned) * 100),0);
                                    }

                                    $found = true;
                                    break ;
                                }
                                if(!$found){
                                    $noTrip->trip_planned = 0;
                                    $noTrip->km_planned = 0;
                                    $noTrip->missed_trip = 0;
                                    $noTrip->trip_compliance = 0;

                                    $companyTotal->trip_planned = 0;
                                    $companyTotal->km_planned = 0;
                                    $companyTotal->missed_trip = 0;
                                    $companyTotal->trip_compliance = 0;

                                    $grandTotal->trip_planned = 0;
                                    $grandTotal->km_planned = 0;
                                    $grandTotal->missed_trip = 0;
                                    $grandTotal->trip_compliance = 0;
                                }
                                $currTotServedPerRoute[] = $noTrip;
                                $currTotServedPerCompany[] = $companyTotal;
                                $currTotServedPerGrand[] = $grandTotal;
                            }

                            $newKey = $key + $count+1;
                            $out->writeln("last newKey: " . $newKey);
                            array_splice($allTrips, $newKey, 0, $currTotServedPerGrand);
                            array_splice($allTrips, $newKey, 0, $currTotServedPerCompany);
                            array_splice($allTrips, $newKey, 0, $currTotServedPerRoute);
                        }
                    }
                }
            }
            return Excel::download(new DailySummary($allTrips, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Daily_Summary_Report_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printFastWithMissingRoute(){
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printDailySummary()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo'] . " 23:59:59");
        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo'] . " 23:59:59");
        $all_dates = array();

        while ($startDate->lte($endDate)) {
            $all_dates[] = $startDate->toDateString();
            $startDate->addDay();
        }

        if ($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                //Daily Summary all route all company
                if($validatedData['route_id']=='All'){

                    $getAllRoutes = Route::where('status', 1)->orderBy('company_id')->orderBy('route_number')->get();
                    $allTrips = [];

                    foreach($getAllRoutes as $allRoute){
                        $tripPerRoute = DB::select("WITH RECURSIVE n_dte (n) AS (
                            SELECT 1
                            UNION ALL
                            SELECT n + 1
                            FROM n_dte
                            WHERE n < 31
                            ),
                            all_mnth_days AS (
                            SELECT route.id as route_id, route.route_number, route.route_name, route.company_id,
                            DATE_ADD(MIN(trip_details.start_trip), INTERVAL - DAY(MIN(trip_details.start_trip)) + 1 DAY) AS mnth_first_day,
                            n,
                            CAST(DATE_ADD(DATE_ADD(MIN(trip_details.start_trip), INTERVAL - DAY(MIN(trip_details.start_trip)) + 1 DAY), INTERVAL n - 1 DAY) AS DATE) AS all_days_in_mnth
                            FROM mybas.trip_details
                            JOIN n_dte
                            JOIN mybas.route ON route.id = trip_details.route_id
                            WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND route.id = '". $allRoute->id . "'
                            group by n
                            ORDER BY n ASC)
                            
                            SELECT
                            amd.all_days_in_mnth AS service_date, amd.route_id, amd.route_number, amd.route_name, amd.company_id, 
                            (case when (count(ie.id) IS NULL) THEN 0 ELSE count(ie.id) END) as trip_served,
                            (case when (SUM(ie.total_mileage) IS NULL) THEN 0 ELSE SUM(ie.total_mileage) END) as km_served,
                            (case when (SUM(ie.total_adult_amount) + SUM(ie.total_concession_amount) IS NULL) THEN 0 
                            ELSE SUM(ie.total_adult_amount) + SUM(ie.total_concession_amount) END) AS farebox, 
                            (case when (SUM(ie.total_adult) + SUM(ie.total_concession) IS NULL) THEN 0 
                            ELSE SUM(ie.total_adult) + SUM(ie.total_concession) END) AS ridership
                            FROM all_mnth_days AS amd
                            LEFT JOIN trip_details AS ie ON CAST(ie.start_trip AS DATE) = amd.all_days_in_mnth
                            AND ie.route_id = amd.route_id
                            GROUP BY service_date;");

                        $routeNo = $allRoute->route_number;
                        $allTrips = array_merge($allTrips, $tripPerRoute);
                    }

                    //dd($allTrips);

                    //Trip Planned
                    $tripPlanned = DB::select("SELECT route.route_number, route.route_name, cast(route_scheduler_details.schedule_date as date) as planned_date,
                    count(route_scheduler_details.id) as trip_planned, 
                    count(route_scheduler_details.id) * route.distance as km_planned
                    FROM mybas.route
                    JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                    JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                    WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                    AND route.status = 1
                    GROUP BY planned_date, route_number
                    ORDER BY route_number;");

                    $lastKey = array_key_last($allTrips);
                    $pastNo = "";
                    $pastCompany = "";
                    $count = 0;
                    $countMissedTrip = 0;
                    $countMissedTripPerRoute = 0;
                    $currTotServedPerRoute = [];
                    $currTotPlannedPerRoute = [];
                    $currTotServedPerCompany = [];
                    $currTotPlannedPerCompany = [];
                    foreach($allTrips as $key => $allTrip){
                        $out->writeln("New Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                        $startDay = new Carbon($allTrip->service_date);
                        $endDay = new Carbon($allTrip->service_date . '23:59:59');
                        if ($startDay->isWeekDay()) {
                            $allTrip->day = "WEEKDAY";
                        } elseif ($startDay->isWeekend()) {
                            $allTrip->day = "WEEKEND";
                        }
                        $found = false;

                        //Enter total per route
                        if($allTrip->route_number!= $pastNo && $pastNo != NULL){
                            $out->writeln("Inside Splice Total Route Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                            $out->writeln("up count: " . $count);

                            if($count==0){
                                array_splice($allTrips, $key, 0, $currTotServedPerRoute);
                            }else{
                                $newKey = $key + $count;
                                $out->writeln("newKey: " . $newKey);
                                array_splice($allTrips, $newKey, 0, $currTotServedPerRoute);
                            }
                            $count++;
                            $countMissedTripPerRoute = 0;
                            $out->writeln("low count: " . $count);
                        }

                        //Enter total per company
                        if($allTrip->company_id != $pastCompany && $pastCompany != NULL){
                            $out->writeln("Inside Splice Total Company Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                            $out->writeln("up count: " . $count);

                            if($count==0){
                                array_splice($allTrips, $key, 0, $currTotServedPerCompany);
                            }else{
                                $newKey = $key + $count;
                                $out->writeln("newKey: " . $newKey);
                                array_splice($allTrips, $newKey, 0, $currTotServedPerCompany);
                            }
                            $count++;
                            $countMissedTrip = 0;
                            $out->writeln("low count: " . $count);
                        }
                        foreach($tripPlanned as $key2 => $tripPlan){
                            if($tripPlan->route_number == $allTrip->route_number){
                                if($tripPlan->planned_date == $allTrip->service_date){
                                    $allTrip->trip_planned = $tripPlan->trip_planned;
                                    $allTrip->km_planned = $tripPlan->km_planned;

                                    //Total Missed Trip
                                    $diff= $tripPlan->trip_planned - $allTrip->trip_served;
                                    if($diff>0){
                                        $allTrip->missed_trip = $diff;
                                        $countMissedTrip += $diff;
                                        $countMissedTripPerRoute += $diff;
                                    }else{
                                        $allTrip->missed_trip = 0;
                                    }

                                    //Trip Compliance
                                    if($allTrip->trip_planned==0){
                                        $allTrip->trip_compliance = 0;
                                    }else{
                                        $allTrip->trip_compliance = round((($allTrip->trip_served/$allTrip->trip_planned) * 100),0);
                                    }

                                    $found = true;
                                    break ;
                                }
                            }
                        }
                        if(!$found){
                            $allTrip->trip_planned = 0;
                            $allTrip->km_planned = 0;
                            $allTrip->missed_trip = 0;
                            $allTrip->trip_compliance = 0;
                        }
                        
                        //Early Late
                        $earlyLateCount = DB::select("SELECT count(trip_details.id) as countEarlyLate
                        FROM trip_details
                        WHERE trip_details.id IN ( 
                        SELECT trip_details.id
                        FROM mybas.trip_details
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE trip_details.start_trip BETWEEN '" . $startDay . "' AND '" . $endDay . "'
                        AND trip_details.route_id =  " . $allTrip->route_id . "
                        AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                        OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                        GROUP BY trip_details.id);");

                        foreach($earlyLateCount as $key3 => $earlyLate){
                            $allTrip->earlyLate = $earlyLate->countEarlyLate;
                        }

                        $currTotServedPerRoute = DB::select("SELECT route.route_number, route.route_name, company.company_name, count(trip_details.id) as trip_served, 
                        count(trip_details.id) * trip_details.total_mileage as km_served,
                        SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                        SUM(total_adult) + SUM(total_concession) AS ridership
                        FROM mybas.trip_details
                        JOIN mybas.route ON route.id = trip_details.route_id
                        JOIN mybas.company ON company.id = route.company_id
                        WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.route_number = '" . $allTrip->route_number . "'
                        GROUP BY route_number
                        ORDER BY route.company_id, route_number;");

                        $currTotPlannedPerRoute = DB::select("SELECT route.route_number, route.route_name, cast(route_scheduler_details.schedule_date as date) as planned_date,
                        count(route_scheduler_details.id) as trip_planned, 
                        count(route_scheduler_details.id) * route.distance as km_planned
                        FROM mybas.route
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.route_number = '" . $allTrip->route_number . "'
                        GROUP BY route.route_number
                        ORDER BY route.route_number;");

                        $found = false;
                        foreach($currTotServedPerRoute as $key4 => $totalTripServe){
                            //Early Late Per Route
                            $earlyLateCountPerRoute = DB::select("SELECT count(trip_details.id) as countEarlyLate
                            FROM trip_details
                            WHERE trip_details.id IN ( 
                            SELECT trip_details.id
                            FROM mybas.trip_details
                            JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                            JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                            WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND trip_details.route_id =  " . $allTrip->route_id . "
                            AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                            OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                            GROUP BY trip_details.id);");

                            foreach($earlyLateCountPerRoute as $key5 => $earlyLatePerRoute){
                                $totalTripServe->earlyLate = $earlyLatePerRoute->countEarlyLate;
                            }

                            foreach($currTotPlannedPerRoute as $key6 => $totalTripPlan){
                                $totalTripServe->trip_planned = $totalTripPlan->trip_planned;
                                $totalTripServe->km_planned = $totalTripPlan->km_planned;

                                //Total Missed Trip Ver 2
                                $totalTripServe->missed_trip = $countMissedTripPerRoute;

                                //Total Missed Trip
                                // $diff= $totalTripPlan->trip_planned - $totalTripServe->trip_served;
                                // if($diff>0){
                                //     $totalTripServe->missed_trip = $diff;
                                // }else{
                                //     $totalTripServe->missed_trip = 0;
                                // }

                                //Trip Compliance
                                if($totalTripServe->trip_planned==0){
                                    $totalTripServe->trip_compliance = 0;
                                }else{
                                    $totalTripServe->trip_compliance = round((($totalTripServe->trip_served/$totalTripServe->trip_planned) * 100),0);
                                }

                                $found = true;
                                break ;
                            }
                            if(!$found){
                                $totalTripServe->trip_planned = 0;
                                $totalTripServe->km_planned = 0;
                                $totalTripServe->missed_trip = 0;
                                $totalTripServe->trip_compliance = 0;
                            }
                        }
                        $pastNo = $allTrip->route_number;

                        $currTotServedPerCompany = DB::select("SELECT company.company_name, count(trip_details.id) as trip_served, 
                        count(trip_details.id) * trip_details.total_mileage as km_served,
                        SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                        SUM(total_adult) + SUM(total_concession) AS ridership
                        FROM mybas.trip_details
                        JOIN mybas.route ON route.id = trip_details.route_id
                        JOIN mybas.company ON company.id = route.company_id
                        WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.company_id = " . $allTrip->company_id . "
                        AND route.status = 1;");

                        $currTotPlannedPerCompany = DB::select("SELECT company.company_name, count(route_scheduler_details.id) as trip_planned, 
                        count(route_scheduler_details.id) * route.distance as km_planned
                        FROM mybas.route
                        JOIN mybas.company ON company.id = route.company_id
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.company_id = " . $allTrip->company_id . "
                        AND route.status = 1;");

                        $found = false;
                        foreach($currTotServedPerCompany as $key7 => $companyTripServe){
                            //Early Late Per Company
                            $earlyLateCountPerCompany = DB::select("SELECT count(trip_details.id) as countEarlyLate
                            FROM trip_details
                            WHERE trip_details.id IN ( 
                            SELECT trip_details.id
                            FROM mybas.trip_details
                            JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                            JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                            WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND trip_details.route_id IN (SELECT id FROM mybas.route WHERE company_id = " . $allTrip->company_id . ")
                            AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                            OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                            GROUP BY trip_details.id);");

                            foreach($earlyLateCountPerCompany  as $key5 => $earlyLatePerCompany){
                                $companyTripServe->earlyLate = $earlyLatePerCompany->countEarlyLate;
                            }

                            foreach($currTotPlannedPerCompany as $key8 => $companyTripPlan){
                                $companyTripServe->trip_planned = $companyTripPlan->trip_planned;
                                $companyTripServe->km_planned = $companyTripPlan->km_planned;

                                //Total Missed Trip Ver 2
                                $companyTripServe->missed_trip = $countMissedTrip;

                                //Total Missed Trip
                                // $diff= $companyTripPlan->trip_planned - $companyTripServe->trip_served;
                                // if($diff>0){
                                //     $companyTripServe->missed_trip = $diff;
                                // }else{
                                //     $companyTripServe->missed_trip = 0;
                                // }

                                //Trip Compliance
                                if($companyTripServe->trip_planned==0){
                                    $companyTripServe->trip_compliance = 0;
                                }else{
                                    $companyTripServe->trip_compliance = round((($companyTripServe->trip_served/$companyTripServe->trip_planned) * 100),0);
                                }

                                $found = true;
                                break ;
                            }
                            if(!$found){
                                $companyTripServe->trip_planned = 0;
                                $companyTripServe->km_planned = 0;
                                $companyTripServe->missed_trip = 0;
                                $companyTripServe->trip_compliance = 0;
                            }
                        }
                        $pastCompany = $allTrip->company_id;

                        if($lastKey==$key){
                            $newKey = $key + $count+1;
                            $out->writeln("last newKey: " . $newKey);
                            array_splice($allTrips, $newKey, 0, $currTotServedPerCompany);
                            array_splice($allTrips, $newKey, 0, $currTotServedPerRoute);
                        }
                    }
                }
            }else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $companyDetails->company_name;
                //Daily Summary all route specific company
                if($validatedData['route_id']=='All'){

                }
                //Daily Summary specific route specific company
                else{

                }
            }
            return Excel::download(new DailySummary($allTrips, count($all_dates), $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Daily_Summary_Report_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

    public function printFastWithMissingDate(){
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN printDailySummary()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo'] . " 23:59:59");
        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo'] . " 23:59:59");
        $all_dates = array();

        while ($startDate->lte($endDate)) {
            $all_dates[] = $startDate->toDateString();
            $startDate->addDay();
        }

        if ($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $networkArea = 'All';
                //Daily Summary all route all company
                if($validatedData['route_id']=='All'){
                    //Exclude Trip Planned
                    $allTrips = DB::select("SELECT route.id as route_id, route.route_number, route.route_name,  route.company_id,
                    cast(trip_details.start_trip as date) as service_date,
                    count(trip_details.id) as trip_served, count(trip_details.id) * trip_details.total_mileage as km_served,
                    SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                    SUM(total_adult) + SUM(total_concession) AS ridership
                    FROM mybas.trip_details
                    JOIN mybas.route ON route.id = trip_details.route_id
                    WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                    AND route.status = 1
                    GROUP BY route_number, service_date, route.id
                    ORDER BY route.company_id, route_number, service_date;");

                    //Trip Planned
                    $tripPlanned = DB::select("SELECT route.route_number, route.route_name, cast(route_scheduler_details.schedule_date as date) as planned_date,
                    count(route_scheduler_details.id) as trip_planned, 
                    count(route_scheduler_details.id) * route.distance as km_planned
                    FROM mybas.route
                    JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                    JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                    WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                    AND route.status = 1
                    GROUP BY planned_date, route_number
                    ORDER BY route_number;");

                    //Enter missing data that not served any trip
                    $countMiss = 0;
                    $pastRouteNo = "";
                    foreach($allTrips as $inkey => $allTrip){
                        $out->writeln("Inside checking missing loop: " . $allTrip->route_number . " " . $allTrip->service_date);
                        if($pastRouteNo!=$allTrip->route_number){
                            $out->writeln("Inside " . $pastRouteNo ." != " . $allTrip->route_number . " && " . $pastRouteNo . " != NULL ");
                            $afterDay = $validatedData['dateFrom'];
                        } 
                        $out->writeln("Up Afterday: " . $afterDay);

                        if($allTrip->service_date!=$afterDay){
                            $out->writeln("Inside " . $allTrip->service_date . " != " .$afterDay);

                            $missingDate = new \stdClass();
                            $noTrip = new \stdClass();
                            $noTrip->route_id = $allTrip->route_id;
                            $noTrip->route_number = $allTrip->route_number;
                            $noTrip->route_name = $allTrip->route_name;
                            $noTrip->company_id = $allTrip->company_id;
                            $noTrip->service_date = $afterDay;
                            $noTrip->trip_served = 0;
                            $noTrip->km_served = 0.00;
                            $noTrip->farebox = 0.0;
                            $noTrip->ridership = 0;

                            $newKey = $inkey + $countMiss;
                            $out->writeln("newKey: " . $newKey);

                            $missingDate->$newKey = $noTrip;
                            array_splice($allTrips, $newKey, 0, $missingDate);
                            $countMiss++;
                            
                        }
                        $afterDay = Carbon::parse($allTrip->service_date)->addDay()->format('Y-m-d');
                        $pastRouteNo = $allTrip->route_number;
                        $out->writeln("Down afterday: " . $afterDay);
                        $out->writeln("Down pastRouteNo: " . $pastRouteNo);
                    }
                    //dd($allTrips);

                    $lastKey = array_key_last($allTrips);
                    $pastNo = "";
                    $pastCompany = "";
                    $count = 0;
                    $countMissedTrip = 0;
                    $countMissedTripPerRoute = 0;
                    $currTotServedPerRoute = [];
                    $currTotPlannedPerRoute = [];
                    $currTotServedPerCompany = [];
                    $currTotPlannedPerCompany = [];
                    foreach($allTrips as $key => $allTrip){
                        $out->writeln("New Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                        $startDay = new Carbon($allTrip->service_date);
                        $endDay = new Carbon($allTrip->service_date . '23:59:59');
                        if ($startDay->isWeekDay()) {
                            $allTrip->day = "WEEKDAY";
                        } elseif ($startDay->isWeekend()) {
                            $allTrip->day = "WEEKEND";
                        }
                        $found = false;

                        //Enter total per route
                        if($allTrip->route_number!= $pastNo && $pastNo != NULL){
                            $out->writeln("Inside Splice Total Route Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                            $out->writeln("up count: " . $count);

                            if($count==0){
                                array_splice($allTrips, $key, 0, $currTotServedPerRoute);
                            }else{
                                $newKey = $key + $count;
                                $out->writeln("newKey: " . $newKey);
                                array_splice($allTrips, $newKey, 0, $currTotServedPerRoute);
                            }
                            $count++;
                            $countMissedTripPerRoute = 0;
                            $out->writeln("low count: " . $count);
                        }

                        //Enter total per company
                        if($allTrip->company_id != $pastCompany && $pastCompany != NULL){
                            $out->writeln("Inside Splice Total Company Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                            $out->writeln("up count: " . $count);

                            if($count==0){
                                array_splice($allTrips, $key, 0, $currTotServedPerCompany);
                            }else{
                                $newKey = $key + $count;
                                $out->writeln("newKey: " . $newKey);
                                array_splice($allTrips, $newKey, 0, $currTotServedPerCompany);
                            }
                            $count++;
                            $countMissedTrip = 0;
                            $out->writeln("low count: " . $count);
                        }
                        foreach($tripPlanned as $key2 => $tripPlan){
                            if($tripPlan->route_number == $allTrip->route_number){
                                if($tripPlan->planned_date == $allTrip->service_date){
                                    $allTrip->trip_planned = $tripPlan->trip_planned;
                                    $allTrip->km_planned = $tripPlan->km_planned;

                                    //Total Missed Trip
                                    $diff= $tripPlan->trip_planned - $allTrip->trip_served;
                                    if($diff>0){
                                        $allTrip->missed_trip = $diff;
                                        $countMissedTrip += $diff;
                                        $countMissedTripPerRoute += $diff;
                                    }else{
                                        $allTrip->missed_trip = 0;
                                    }

                                    //Trip Compliance
                                    if($allTrip->trip_planned==0){
                                        $allTrip->trip_compliance = 0;
                                    }else{
                                        $allTrip->trip_compliance = round((($allTrip->trip_served/$allTrip->trip_planned) * 100),0);
                                    }

                                    $found = true;
                                    break ;
                                }
                            }
                        }
                        if(!$found){
                            $allTrip->trip_planned = 0;
                            $allTrip->km_planned = 0;
                            $allTrip->missed_trip = 0;
                            $allTrip->trip_compliance = 0;
                        }
                        
                        //Early Late
                        $earlyLateCount = DB::select("SELECT count(trip_details.id) as countEarlyLate
                        FROM trip_details
                        WHERE trip_details.id IN ( 
                        SELECT trip_details.id
                        FROM mybas.trip_details
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE trip_details.start_trip BETWEEN '" . $startDay . "' AND '" . $endDay . "'
                        AND trip_details.route_id =  " . $allTrip->route_id . "
                        AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                        OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                        GROUP BY trip_details.id);");

                        foreach($earlyLateCount as $key3 => $earlyLate){
                            $allTrip->earlyLate = $earlyLate->countEarlyLate;
                        }

                        $currTotServedPerRoute = DB::select("SELECT route.route_number, route.route_name, company.company_name, count(trip_details.id) as trip_served, 
                        count(trip_details.id) * trip_details.total_mileage as km_served,
                        SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                        SUM(total_adult) + SUM(total_concession) AS ridership
                        FROM mybas.trip_details
                        JOIN mybas.route ON route.id = trip_details.route_id
                        JOIN mybas.company ON company.id = route.company_id
                        WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.route_number = '" . $allTrip->route_number . "'
                        GROUP BY route_number
                        ORDER BY route.company_id, route_number;");

                        $currTotPlannedPerRoute = DB::select("SELECT route.route_number, route.route_name, cast(route_scheduler_details.schedule_date as date) as planned_date,
                        count(route_scheduler_details.id) as trip_planned, 
                        count(route_scheduler_details.id) * route.distance as km_planned
                        FROM mybas.route
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.route_number = '" . $allTrip->route_number . "'
                        GROUP BY route.route_number
                        ORDER BY route.route_number;");

                        $found = false;
                        foreach($currTotServedPerRoute as $key4 => $totalTripServe){
                            //Early Late Per Route
                            $earlyLateCountPerRoute = DB::select("SELECT count(trip_details.id) as countEarlyLate
                            FROM trip_details
                            WHERE trip_details.id IN ( 
                            SELECT trip_details.id
                            FROM mybas.trip_details
                            JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                            JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                            WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND trip_details.route_id =  " . $allTrip->route_id . "
                            AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                            OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                            GROUP BY trip_details.id);");

                            foreach($earlyLateCountPerRoute as $key5 => $earlyLatePerRoute){
                                $totalTripServe->earlyLate = $earlyLatePerRoute->countEarlyLate;
                            }

                            foreach($currTotPlannedPerRoute as $key6 => $totalTripPlan){
                                $totalTripServe->trip_planned = $totalTripPlan->trip_planned;
                                $totalTripServe->km_planned = $totalTripPlan->km_planned;

                                //Total Missed Trip Ver 2
                                $totalTripServe->missed_trip = $countMissedTripPerRoute;

                                //Total Missed Trip
                                // $diff= $totalTripPlan->trip_planned - $totalTripServe->trip_served;
                                // if($diff>0){
                                //     $totalTripServe->missed_trip = $diff;
                                // }else{
                                //     $totalTripServe->missed_trip = 0;
                                // }

                                //Trip Compliance
                                if($totalTripServe->trip_planned==0){
                                    $totalTripServe->trip_compliance = 0;
                                }else{
                                    $totalTripServe->trip_compliance = round((($totalTripServe->trip_served/$totalTripServe->trip_planned) * 100),0);
                                }

                                $found = true;
                                break ;
                            }
                            if(!$found){
                                $totalTripServe->trip_planned = 0;
                                $totalTripServe->km_planned = 0;
                                $totalTripServe->missed_trip = 0;
                                $totalTripServe->trip_compliance = 0;
                            }
                        }
                        $pastNo = $allTrip->route_number;

                        $currTotServedPerCompany = DB::select("SELECT company.company_name, count(trip_details.id) as trip_served, 
                        count(trip_details.id) * trip_details.total_mileage as km_served,
                        SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                        SUM(total_adult) + SUM(total_concession) AS ridership
                        FROM mybas.trip_details
                        JOIN mybas.route ON route.id = trip_details.route_id
                        JOIN mybas.company ON company.id = route.company_id
                        WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.company_id = " . $allTrip->company_id . "
                        AND route.status = 1;");

                        $currTotPlannedPerCompany = DB::select("SELECT company.company_name, count(route_scheduler_details.id) as trip_planned, 
                        count(route_scheduler_details.id) * route.distance as km_planned
                        FROM mybas.route
                        JOIN mybas.company ON company.id = route.company_id
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.company_id = " . $allTrip->company_id . "
                        AND route.status = 1;");

                        $found = false;
                        foreach($currTotServedPerCompany as $key7 => $companyTripServe){
                            //Early Late Per Company
                            $earlyLateCountPerCompany = DB::select("SELECT count(trip_details.id) as countEarlyLate
                            FROM trip_details
                            WHERE trip_details.id IN ( 
                            SELECT trip_details.id
                            FROM mybas.trip_details
                            JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                            JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                            WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND trip_details.route_id IN (SELECT id FROM mybas.route WHERE company_id = " . $allTrip->company_id . ")
                            AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                            OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                            GROUP BY trip_details.id);");

                            foreach($earlyLateCountPerCompany  as $key5 => $earlyLatePerCompany){
                                $companyTripServe->earlyLate = $earlyLatePerCompany->countEarlyLate;
                            }

                            foreach($currTotPlannedPerCompany as $key8 => $companyTripPlan){
                                $companyTripServe->trip_planned = $companyTripPlan->trip_planned;
                                $companyTripServe->km_planned = $companyTripPlan->km_planned;

                                //Total Missed Trip Ver 2
                                $companyTripServe->missed_trip = $countMissedTrip;

                                //Total Missed Trip
                                // $diff= $companyTripPlan->trip_planned - $companyTripServe->trip_served;
                                // if($diff>0){
                                //     $companyTripServe->missed_trip = $diff;
                                // }else{
                                //     $companyTripServe->missed_trip = 0;
                                // }

                                //Trip Compliance
                                if($companyTripServe->trip_planned==0){
                                    $companyTripServe->trip_compliance = 0;
                                }else{
                                    $companyTripServe->trip_compliance = round((($companyTripServe->trip_served/$companyTripServe->trip_planned) * 100),0);
                                }

                                $found = true;
                                break ;
                            }
                            if(!$found){
                                $companyTripServe->trip_planned = 0;
                                $companyTripServe->km_planned = 0;
                                $companyTripServe->missed_trip = 0;
                                $companyTripServe->trip_compliance = 0;
                            }
                        }
                        $pastCompany = $allTrip->company_id;

                        if($lastKey==$key){
                            $newKey = $key + $count+1;
                            $out->writeln("last newKey: " . $newKey);
                            array_splice($allTrips, $newKey, 0, $currTotServedPerCompany);
                            array_splice($allTrips, $newKey, 0, $currTotServedPerRoute);
                        }
                    }
                }
            }else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $networkArea = $companyDetails->company_name;
                //Daily Summary all route specific company
                if($validatedData['route_id']=='All'){
                    //Exclude Trip Planned
                    $allTrips = DB::select("SELECT route.id as route_id, route.route_number, route.route_name,  route.company_id,
                    cast(trip_details.start_trip as date) as service_date,
                    count(trip_details.id) as trip_served, count(trip_details.id) * trip_details.total_mileage as km_served,
                    SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                    SUM(total_adult) + SUM(total_concession) AS ridership
                    FROM mybas.trip_details
                    JOIN mybas.route ON route.id = trip_details.route_id
                    WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                    AND route.status = 1
                    AND route.company_id = " . $companyDetails->id . "
                    GROUP BY route_number, service_date, route.id
                    ORDER BY route.company_id, route_number, service_date;");

                    //Trip Planned
                    $tripPlanned = DB::select("SELECT route.route_number, route.route_name, cast(route_scheduler_details.schedule_date as date) as planned_date,
                    count(route_scheduler_details.id) as trip_planned, 
                    count(route_scheduler_details.id) * route.distance as km_planned
                    FROM mybas.route
                    JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                    JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                    WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                    AND route.status = 1
                    AND route.company_id = " . $companyDetails->id . "
                    GROUP BY planned_date, route_number
                    ORDER BY route_number;");

                    $lastKey = array_key_last($allTrips);
                    $pastNo = "";
                    $pastCompany = "";
                    $count = 0;
                    $countMissedTrip = 0;
                    $countMissedTripPerRoute = 0;
                    $currTotServedPerRoute = [];
                    $currTotPlannedPerRoute = [];
                    $currTotServedPerCompany = [];
                    $currTotPlannedPerCompany = [];
                    foreach($allTrips as $key => $allTrip){
                        $out->writeln("New Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                        $startDay = new Carbon($allTrip->service_date);
                        $endDay = new Carbon($allTrip->service_date . '23:59:59');
                        if ($startDay->isWeekDay()) {
                            $allTrip->day = "WEEKDAY";
                        } elseif ($startDay->isWeekend()) {
                            $allTrip->day = "WEEKEND";
                        }
                        $found = false;

                        //Enter total per route
                        if($allTrip->route_number!= $pastNo && $pastNo != NULL){
                            $out->writeln("Inside Splice Total Route Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                            $out->writeln("up count: " . $count);

                            if($count==0){
                                array_splice($allTrips, $key, 0, $currTotServedPerRoute);
                            }else{
                                $newKey = $key + $count;
                                $out->writeln("newKey: " . $newKey);
                                array_splice($allTrips, $newKey, 0, $currTotServedPerRoute);
                            }
                            $count++;
                            $countMissedTripPerRoute = 0;
                            $out->writeln("low count: " . $count);
                        }

                        //Enter total per company
                        if($allTrip->company_id != $pastCompany && $pastCompany != NULL){
                            $out->writeln("Inside Splice Total Company Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                            $out->writeln("up count: " . $count);

                            if($count==0){
                                array_splice($allTrips, $key, 0, $currTotServedPerCompany);
                            }else{
                                $newKey = $key + $count;
                                $out->writeln("newKey: " . $newKey);
                                array_splice($allTrips, $newKey, 0, $currTotServedPerCompany);
                            }
                            $count++;
                            $countMissedTrip = 0;
                            $out->writeln("low count: " . $count);
                        }
                        foreach($tripPlanned as $key2 => $tripPlan){
                            if($tripPlan->route_number == $allTrip->route_number){
                                if($tripPlan->planned_date == $allTrip->service_date){
                                    $allTrip->trip_planned = $tripPlan->trip_planned;
                                    $allTrip->km_planned = $tripPlan->km_planned;

                                    //Total Missed Trip
                                    $diff= $tripPlan->trip_planned - $allTrip->trip_served;
                                    if($diff>0){
                                        $allTrip->missed_trip = $diff;
                                        $countMissedTrip += $diff;
                                        $countMissedTripPerRoute += $diff;
                                    }else{
                                        $allTrip->missed_trip = 0;
                                    }

                                    //Trip Compliance
                                    if($allTrip->trip_planned==0){
                                        $allTrip->trip_compliance = 0;
                                    }else{
                                        $allTrip->trip_compliance = round((($allTrip->trip_served/$allTrip->trip_planned) * 100),0);
                                    }

                                    //Trip Compliance
                                    if($allTrip->trip_planned==0){
                                        $allTrip->trip_compliance = 0;
                                    }else{
                                        $allTrip->trip_compliance = round((($allTrip->trip_served/$allTrip->trip_planned) * 100),0);
                                    }

                                    $found = true;
                                    break ;
                                }
                            }
                        }
                        if(!$found){
                            $allTrip->trip_planned = 0;
                            $allTrip->km_planned = 0;
                            $allTrip->missed_trip = 0;
                            $allTrip->trip_compliance = 0;
                        }
                        
                        //Early Late
                        $earlyLateCount = DB::select("SELECT count(trip_details.id) as countEarlyLate
                        FROM trip_details
                        WHERE trip_details.id IN ( 
                        SELECT trip_details.id
                        FROM mybas.trip_details
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE trip_details.start_trip BETWEEN '" . $startDay . "' AND '" . $endDay . "'
                        AND trip_details.route_id =  " . $allTrip->route_id . "
                        AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                        OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                        GROUP BY trip_details.id);");

                        foreach($earlyLateCount as $key3 => $earlyLate){
                            $allTrip->earlyLate = $earlyLate->countEarlyLate;
                        }

                        $currTotServedPerRoute = DB::select("SELECT route.route_number, route.route_name, company.company_name, count(trip_details.id) as trip_served, 
                        count(trip_details.id) * trip_details.total_mileage as km_served,
                        SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                        SUM(total_adult) + SUM(total_concession) AS ridership
                        FROM mybas.trip_details
                        JOIN mybas.route ON route.id = trip_details.route_id
                        JOIN mybas.company ON company.id = route.company_id
                        WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.route_number = '" . $allTrip->route_number . "'
                        GROUP BY route_number
                        ORDER BY route.company_id, route_number;");

                        $currTotPlannedPerRoute = DB::select("SELECT route.route_number, route.route_name, cast(route_scheduler_details.schedule_date as date) as planned_date,
                        count(route_scheduler_details.id) as trip_planned, 
                        count(route_scheduler_details.id) * route.distance as km_planned
                        FROM mybas.route
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.route_number = '" . $allTrip->route_number . "'
                        GROUP BY route.route_number
                        ORDER BY route.route_number;");

                        $found = false;
                        foreach($currTotServedPerRoute as $key4 => $totalTripServe){
                            //Early Late Per Route
                            $earlyLateCountPerRoute = DB::select("SELECT count(trip_details.id) as countEarlyLate
                            FROM trip_details
                            WHERE trip_details.id IN ( 
                            SELECT trip_details.id
                            FROM mybas.trip_details
                            JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                            JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                            WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND trip_details.route_id =  " . $allTrip->route_id . "
                            AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                            OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                            GROUP BY trip_details.id);");

                            foreach($earlyLateCountPerRoute as $key5 => $earlyLatePerRoute){
                                $totalTripServe->earlyLate = $earlyLatePerRoute->countEarlyLate;
                            }

                            foreach($currTotPlannedPerRoute as $key6 => $totalTripPlan){
                                $totalTripServe->trip_planned = $totalTripPlan->trip_planned;
                                $totalTripServe->km_planned = $totalTripPlan->km_planned;

                                //Total Missed Trip Ver 2
                                $totalTripServe->missed_trip = $countMissedTripPerRoute;

                                //Total Missed Trip
                                // $diff= $totalTripPlan->trip_planned - $totalTripServe->trip_served;
                                // if($diff>0){
                                //     $totalTripServe->missed_trip = $diff;
                                // }else{
                                //     $totalTripServe->missed_trip = 0;
                                // }

                                //Trip Compliance
                                if($totalTripServe->trip_planned==0){
                                    $totalTripServe->trip_compliance = 0;
                                }else{
                                    $totalTripServe->trip_compliance = round((($totalTripServe->trip_served/$totalTripServe->trip_planned) * 100),0);
                                }

                                $found = true;
                                break ;
                            }
                            if(!$found){
                                $totalTripServe->trip_planned = 0;
                                $totalTripServe->km_planned = 0;
                                $totalTripServe->missed_trip = 0;
                                $totalTripServe->trip_compliance = 0;
                            }
                        }
                        $pastNo = $allTrip->route_number;

                        $currTotServedPerCompany = DB::select("SELECT company.company_name, count(trip_details.id) as trip_served, 
                        count(trip_details.id) * trip_details.total_mileage as km_served,
                        SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                        SUM(total_adult) + SUM(total_concession) AS ridership
                        FROM mybas.trip_details
                        JOIN mybas.route ON route.id = trip_details.route_id
                        JOIN mybas.company ON company.id = route.company_id
                        WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.company_id = " . $allTrip->company_id . "
                        AND route.status = 1;");

                        $currTotPlannedPerCompany = DB::select("SELECT company.company_name, count(route_scheduler_details.id) as trip_planned, 
                        count(route_scheduler_details.id) * route.distance as km_planned
                        FROM mybas.route
                        JOIN mybas.company ON company.id = route.company_id
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.company_id = " . $allTrip->company_id . "
                        AND route.status = 1;");

                        $found = false;
                        foreach($currTotServedPerCompany as $key7 => $companyTripServe){
                            //Early Late Per Company
                            $earlyLateCountPerCompany = DB::select("SELECT count(trip_details.id) as countEarlyLate
                            FROM trip_details
                            WHERE trip_details.id IN ( 
                            SELECT trip_details.id
                            FROM mybas.trip_details
                            JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                            JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                            WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND trip_details.route_id IN (SELECT id FROM mybas.route WHERE company_id = " . $allTrip->company_id . ")
                            AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                            OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                            GROUP BY trip_details.id);");

                            foreach($earlyLateCountPerCompany  as $key5 => $earlyLatePerCompany){
                                $companyTripServe->earlyLate = $earlyLatePerCompany->countEarlyLate;
                            }

                            foreach($currTotPlannedPerCompany as $key8 => $companyTripPlan){
                                $companyTripServe->trip_planned = $companyTripPlan->trip_planned;
                                $companyTripServe->km_planned = $companyTripPlan->km_planned;

                                //Total Missed Trip Ver 2
                                $companyTripServe->missed_trip = $countMissedTrip;

                                //Total Missed Trip
                                // $diff= $companyTripPlan->trip_planned - $companyTripServe->trip_served;
                                // if($diff>0){
                                //     $companyTripServe->missed_trip = $diff;
                                // }else{
                                //     $companyTripServe->missed_trip = 0;
                                // }

                                //Trip Compliance
                                if($companyTripServe->trip_planned==0){
                                    $companyTripServe->trip_compliance = 0;
                                }else{
                                    $companyTripServe->trip_compliance = round((($companyTripServe->trip_served/$companyTripServe->trip_planned) * 100),0);
                                }

                                $found = true;
                                break ;
                            }
                            if(!$found){
                                $companyTripServe->trip_planned = 0;
                                $companyTripServe->km_planned = 0;
                                $companyTripServe->missed_trip = 0;
                                $companyTripServe->trip_compliance = 0;
                            }
                        }
                        $pastCompany = $allTrip->company_id;

                        if($lastKey==$key){
                            $newKey = $key + $count+1;
                            $out->writeln("last newKey: " . $newKey);
                            array_splice($allTrips, $newKey, 0, $currTotServedPerCompany);
                            array_splice($allTrips, $newKey, 0, $currTotServedPerRoute);
                        }
                    }
                }
                //Daily Summary specific route specific company
                else{
                    //Exclude Trip Planned
                    $allTrips = DB::select("SELECT route.id as route_id, route.route_number, route.route_name,  route.company_id,
                    cast(trip_details.start_trip as date) as service_date,
                    count(trip_details.id) as trip_served, count(trip_details.id) * trip_details.total_mileage as km_served,
                    SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                    SUM(total_adult) + SUM(total_concession) AS ridership
                    FROM mybas.trip_details
                    JOIN mybas.route ON route.id = trip_details.route_id
                    WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                    AND route.status = 1
                    AND route.id = " . $validatedData['route_id'] . "
                    GROUP BY route_number, service_date, route.id
                    ORDER BY route.company_id, route_number, service_date;");

                    //Trip Planned
                    $tripPlanned = DB::select("SELECT route.route_number, route.route_name, cast(route_scheduler_details.schedule_date as date) as planned_date,
                    count(route_scheduler_details.id) as trip_planned, 
                    count(route_scheduler_details.id) * route.distance as km_planned
                    FROM mybas.route
                    JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                    JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                    WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                    AND route.status = 1
                    AND route.id = " . $validatedData['route_id'] . "
                    GROUP BY planned_date, route_number
                    ORDER BY route_number;");

                    $lastKey = array_key_last($allTrips);
                    $pastNo = "";
                    $pastCompany = "";
                    $count = 0;
                    $countMissedTrip = 0;
                    $countMissedTripPerRoute = 0;
                    $currTotServedPerRoute = [];
                    $currTotPlannedPerRoute = [];
                    $currTotServedPerCompany = [];
                    $currTotPlannedPerCompany = [];
                    foreach($allTrips as $key => $allTrip){
                        $out->writeln("New Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                        $startDay = new Carbon($allTrip->service_date);
                        $endDay = new Carbon($allTrip->service_date . '23:59:59');
                        if ($startDay->isWeekDay()) {
                            $allTrip->day = "WEEKDAY";
                        } elseif ($startDay->isWeekend()) {
                            $allTrip->day = "WEEKEND";
                        }
                        $found = false;

                        //Enter total per route
                        if($allTrip->route_number!= $pastNo && $pastNo != NULL){
                            $out->writeln("Inside Splice Total Route Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                            $out->writeln("up count: " . $count);

                            if($count==0){
                                array_splice($allTrips, $key, 0, $currTotServedPerRoute);
                            }else{
                                $newKey = $key + $count;
                                $out->writeln("newKey: " . $newKey);
                                array_splice($allTrips, $newKey, 0, $currTotServedPerRoute);
                            }
                            $count++;
                            $countMissedTripPerRoute = 0;
                            $out->writeln("low count: " . $count);
                        }

                        //Enter total per company
                        if($allTrip->company_id != $pastCompany && $pastCompany != NULL){
                            $out->writeln("Inside Splice Total Company Loop: " . $key . " - " . $allTrip->route_number . " - " . $allTrip->service_date . " ");
                            $out->writeln("up count: " . $count);

                            if($count==0){
                                array_splice($allTrips, $key, 0, $currTotServedPerCompany);
                            }else{
                                $newKey = $key + $count;
                                $out->writeln("newKey: " . $newKey);
                                array_splice($allTrips, $newKey, 0, $currTotServedPerCompany);
                            }
                            $count++;
                            $countMissedTrip = 0;
                            $out->writeln("low count: " . $count);
                        }
                        foreach($tripPlanned as $key2 => $tripPlan){
                            if($tripPlan->route_number == $allTrip->route_number){
                                if($tripPlan->planned_date == $allTrip->service_date){
                                    $allTrip->trip_planned = $tripPlan->trip_planned;
                                    $allTrip->km_planned = $tripPlan->km_planned;

                                    //Total Missed Trip
                                    $diff= $tripPlan->trip_planned - $allTrip->trip_served;
                                    if($diff>0){
                                        $allTrip->missed_trip = $diff;
                                        $countMissedTrip += $diff;
                                        $countMissedTripPerRoute += $diff;
                                    }else{
                                        $allTrip->missed_trip = 0;
                                    }

                                    //Trip Compliance
                                    if($allTrip->trip_planned==0){
                                        $allTrip->trip_compliance = 0;
                                    }else{
                                        $allTrip->trip_compliance = round((($allTrip->trip_served/$allTrip->trip_planned) * 100),0);
                                    }

                                    //Trip Compliance
                                    if($allTrip->trip_planned==0){
                                        $allTrip->trip_compliance = 0;
                                    }else{
                                        $allTrip->trip_compliance = round((($allTrip->trip_served/$allTrip->trip_planned) * 100),0);
                                    }

                                    $found = true;
                                    break ;
                                }
                            }
                        }
                        if(!$found){
                            $allTrip->trip_planned = 0;
                            $allTrip->km_planned = 0;
                            $allTrip->missed_trip = 0;
                            $allTrip->trip_compliance = 0;
                        }
                        
                        //Early Late
                        $earlyLateCount = DB::select("SELECT count(trip_details.id) as countEarlyLate
                        FROM trip_details
                        WHERE trip_details.id IN ( 
                        SELECT trip_details.id
                        FROM mybas.trip_details
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE trip_details.start_trip BETWEEN '" . $startDay . "' AND '" . $endDay . "'
                        AND trip_details.route_id =  " . $allTrip->route_id . "
                        AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                        OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                        GROUP BY trip_details.id);");

                        foreach($earlyLateCount as $key3 => $earlyLate){
                            $allTrip->earlyLate = $earlyLate->countEarlyLate;
                        }

                        $currTotServedPerRoute = DB::select("SELECT route.route_number, route.route_name, company.company_name, count(trip_details.id) as trip_served, 
                        count(trip_details.id) * trip_details.total_mileage as km_served,
                        SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                        SUM(total_adult) + SUM(total_concession) AS ridership
                        FROM mybas.trip_details
                        JOIN mybas.route ON route.id = trip_details.route_id
                        JOIN mybas.company ON company.id = route.company_id
                        WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.route_number = '" . $allTrip->route_number . "'
                        GROUP BY route_number
                        ORDER BY route.company_id, route_number;");

                        $currTotPlannedPerRoute = DB::select("SELECT route.route_number, route.route_name, cast(route_scheduler_details.schedule_date as date) as planned_date,
                        count(route_scheduler_details.id) as trip_planned, 
                        count(route_scheduler_details.id) * route.distance as km_planned
                        FROM mybas.route
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.route_number = '" . $allTrip->route_number . "'
                        GROUP BY route.route_number
                        ORDER BY route.route_number;");

                        $found = false;
                        foreach($currTotServedPerRoute as $key4 => $totalTripServe){
                            //Early Late Per Route
                            $earlyLateCountPerRoute = DB::select("SELECT count(trip_details.id) as countEarlyLate
                            FROM trip_details
                            WHERE trip_details.id IN ( 
                            SELECT trip_details.id
                            FROM mybas.trip_details
                            JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                            JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                            WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND trip_details.route_id =  " . $allTrip->route_id . "
                            AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                            OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                            GROUP BY trip_details.id);");

                            foreach($earlyLateCountPerRoute as $key5 => $earlyLatePerRoute){
                                $totalTripServe->earlyLate = $earlyLatePerRoute->countEarlyLate;
                            }

                            foreach($currTotPlannedPerRoute as $key6 => $totalTripPlan){
                                $totalTripServe->trip_planned = $totalTripPlan->trip_planned;
                                $totalTripServe->km_planned = $totalTripPlan->km_planned;

                                //Total Missed Trip Ver 2
                                $totalTripServe->missed_trip = $countMissedTripPerRoute;

                                //Total Missed Trip
                                // $diff= $totalTripPlan->trip_planned - $totalTripServe->trip_served;
                                // if($diff>0){
                                //     $totalTripServe->missed_trip = $diff;
                                // }else{
                                //     $totalTripServe->missed_trip = 0;
                                // }

                                //Trip Compliance
                                if($totalTripServe->trip_planned==0){
                                    $totalTripServe->trip_compliance = 0;
                                }else{
                                    $totalTripServe->trip_compliance = round((($totalTripServe->trip_served/$totalTripServe->trip_planned) * 100),0);
                                }

                                $found = true;
                                break ;
                            }
                            if(!$found){
                                $totalTripServe->trip_planned = 0;
                                $totalTripServe->km_planned = 0;
                                $totalTripServe->missed_trip = 0;
                                $totalTripServe->trip_compliance = 0;
                            }
                        }
                        $pastNo = $allTrip->route_number;

                        $currTotServedPerCompany = DB::select("SELECT company.company_name, count(trip_details.id) as trip_served, 
                        count(trip_details.id) * trip_details.total_mileage as km_served,
                        SUM(total_adult_amount) + SUM(total_concession_amount) AS farebox, 
                        SUM(total_adult) + SUM(total_concession) AS ridership
                        FROM mybas.trip_details
                        JOIN mybas.route ON route.id = trip_details.route_id
                        JOIN mybas.company ON company.id = route.company_id
                        WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.company_id = " . $allTrip->company_id . "
                        AND route.status = 1;");

                        $currTotPlannedPerCompany = DB::select("SELECT company.company_name, count(route_scheduler_details.id) as trip_planned, 
                        count(route_scheduler_details.id) * route.distance as km_planned
                        FROM mybas.route
                        JOIN mybas.company ON company.id = route.company_id
                        JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.route_id = route.id
                        JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                        WHERE route_scheduler_details.schedule_date BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        AND route.company_id = " . $allTrip->company_id . "
                        AND route.status = 1;");

                        $found = false;
                        foreach($currTotServedPerCompany as $key7 => $companyTripServe){
                            //Early Late Per Company
                            $earlyLateCountPerCompany = DB::select("SELECT count(trip_details.id) as countEarlyLate
                            FROM trip_details
                            WHERE trip_details.id IN ( 
                            SELECT trip_details.id
                            FROM mybas.trip_details
                            JOIN mybas.route_scheduler_mstr ON route_scheduler_mstr.id = trip_details.route_schedule_mstr_id
                            JOIN mybas.route_scheduler_details ON route_scheduler_details.route_scheduler_mstr_id = route_scheduler_mstr.id
                            WHERE trip_details.start_trip BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                            AND trip_details.route_id IN (SELECT id FROM mybas.route WHERE company_id = " . $allTrip->company_id . ")
                            AND (TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) < '-00:05:00'
                            OR TIMEDIFF(route_scheduler_mstr.schedule_start_time, cast(trip_details.start_trip as TIME)) > '00:05:00')
                            GROUP BY trip_details.id);");

                            foreach($earlyLateCountPerCompany  as $key5 => $earlyLatePerCompany){
                                $companyTripServe->earlyLate = $earlyLatePerCompany->countEarlyLate;
                            }

                            foreach($currTotPlannedPerCompany as $key8 => $companyTripPlan){
                                $companyTripServe->trip_planned = $companyTripPlan->trip_planned;
                                $companyTripServe->km_planned = $companyTripPlan->km_planned;

                                //Total Missed Trip Ver 2
                                $companyTripServe->missed_trip = $countMissedTrip;

                                //Total Missed Trip
                                // $diff= $companyTripPlan->trip_planned - $companyTripServe->trip_served;
                                // if($diff>0){
                                //     $companyTripServe->missed_trip = $diff;
                                // }else{
                                //     $companyTripServe->missed_trip = 0;
                                // }

                                //Trip Compliance
                                if($companyTripServe->trip_planned==0){
                                    $companyTripServe->trip_compliance = 0;
                                }else{
                                    $companyTripServe->trip_compliance = round((($companyTripServe->trip_served/$companyTripServe->trip_planned) * 100),0);
                                }

                                $found = true;
                                break ;
                            }
                            if(!$found){
                                $companyTripServe->trip_planned = 0;
                                $companyTripServe->km_planned = 0;
                                $companyTripServe->missed_trip = 0;
                                $companyTripServe->trip_compliance = 0;
                            }
                        }
                        $pastCompany = $allTrip->company_id;

                        if($lastKey==$key){
                            $newKey = $key + $count+1;
                            $out->writeln("last newKey: " . $newKey);
                            array_splice($allTrips, $newKey, 0, $currTotServedPerCompany);
                            array_splice($allTrips, $newKey, 0, $currTotServedPerRoute);
                        }
                    }
                }
            }
            return Excel::download(new DailySummary($allTrips, count($all_dates), $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Daily_Summary_Report_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
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
                        $allCompanies = Company::all();
            
                        foreach($allCompanies as $allCompany){
                            $routePerCompanies = Route::where('company_id', $allCompany->id)->where('status', 1)->orderBy('route_number')->get();
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
                                                $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,7,12,13])
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
                                                $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11,13])
                                                ->where('route_id', $routePerCompany->id)
                                                ->where('status', 1)
                                                ->count();
                                            }else{
                                                $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12,13])
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
                        $routePerCompanies = Route::where('company_id', $companyDetails->id)->where('status', 1)->orderBy('route_number')->get();
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
                                            $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,7,12,13])
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
                                            $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11,13])
                                            ->where('route_id', $routePerCompany->id)
                                            ->where('status', 1)
                                            ->count();
                                        }else{
                                            $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12,13])
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
                        $selectedRoute = Route::where('id', $validatedData['route_id'])->where('status', 1)->first();
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
                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,7,12,13])
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
                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11,13])
                                    ->where('route_id', $selectedRoute->id)
                                    ->where('status', 1)
                                    ->count();
                                }else{
                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12,13])
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

    public function printTest()
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

                            if($allCompany->id==9){
                                $routeAlorSetars = Route::where('company_id',9)->orderBy('route_number')->get();
                                if(count($routeAlorSetars)>0){
                                    foreach($routeAlorSetars as $routeAlorSetar){
                                        $out->writeln("YOU ARE IN routeAlorSetar loop");
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
                                        
                                        if($routeAlorSetar->route_number=='ML03'){
                                            $out->writeln("YOU ARE IN routeAlorSetar->route_number==ML03");
                                            $moreRouteAlorSetars = Route::where('route_number','ML019')->first();
                                            $out->writeln("moreRouteAlorSetars: " . $moreRouteAlorSetars);
                                            foreach ($all_dates as $all_date) {
                                                $out->writeln("YOU ARE IN all_date: " . $all_date);
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
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->count();
                                                    }else{
                                                        $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,4,6,9,10])
                                                        ->where('status', 1)
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->count();
                                                    }
                                                }
                                                if($isWeekend){
                                                    $isSunday = $firstDate->format('l');
                                                    if($isSunday=='Sunday'){
                                                        $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11])
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->where('status', 1)
                                                        ->count();
                                                    }else{
                                                        $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12])
                                                        ->where('status', 1)
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->count();
                                                    }
                                                    
                                                }
                                                $totalTripPlanned = $copies;
                                                $totalKMPlanned = $routeAlorSetar->inbound_distance * $copies;

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
                            
                                                $allTrips = TripDetail::whereIn('route_id', [$routeAlorSetar->id, $moreRouteAlorSetars->id])
                                                    ->orderBy('start_trip')
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
                                                                if ($schedule->RouteScheduleMSTR->route_id == $routeAlorSetar->id) {
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
                                            $data_perRoute[$routeAlorSetar->route_number . ' - ' . $routeAlorSetar->route_name] = $data_perDate;
                                        }
                                        elseif($routeAlorSetar->route_number=='ML04'){
                                            $out->writeln("YOU ARE IN routeAlorSetar->route_number==C11");
                                            $moreRouteAlorSetars = Route::where('route_number','ML99')->first();
                                            $out->writeln("moreRouteAlorSetars: " . $moreRouteAlorSetars);
                                            foreach ($all_dates as $all_date) {
                                                $out->writeln("YOU ARE IN all_date: " . $all_date);
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
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->count();
                                                    }else{
                                                        $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,4,6,9,10])
                                                        ->where('status', 1)
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->count();
                                                    }
                                                }
                                                if($isWeekend){
                                                    $isSunday = $firstDate->format('l');
                                                    if($isSunday=='Sunday'){
                                                        $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11])
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->where('status', 1)
                                                        ->count();
                                                    }else{
                                                        $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12])
                                                        ->where('status', 1)
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->count();
                                                    }
                                                    
                                                }
                                                $totalTripPlanned = $copies;
                                                $totalKMPlanned = $routeAlorSetar->inbound_distance * $copies;

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
                            
                                                $allTrips = TripDetail::whereIn('route_id', [$routeAlorSetar->id, $moreRouteAlorSetars->id])
                                                    ->orderBy('start_trip')
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
                                                                if ($schedule->RouteScheduleMSTR->route_id == $routeAlorSetar->id) {
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
                                            $data_perRoute[$routeAlorSetar->route_number . ' - ' . $routeAlorSetar->route_name] = $data_perDate;
                                        }
                                        elseif($routeAlorSetar->route_number=='ML05'){
                                            $out->writeln("YOU ARE IN routeAlorSetar->route_number==ML05");
                                            $moreRouteAlorSetars = Route::where('route_number','ML020')->first();
                                            $out->writeln("moreRouteAlorSetars: " . $moreRouteAlorSetars);
                                            foreach ($all_dates as $all_date) {
                                                $out->writeln("YOU ARE IN all_date:" . $all_date);
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
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->count();
                                                    }else{
                                                        $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,4,6,9,10])
                                                        ->where('status', 1)
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->count();
                                                    }
                                                }
                                                if($isWeekend){
                                                    $isSunday = $firstDate->format('l');
                                                    if($isSunday=='Sunday'){
                                                        $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11])
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->where('status', 1)
                                                        ->count();
                                                    }else{
                                                        $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12])
                                                        ->where('status', 1)
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->count();
                                                    }
                                                    
                                                }
                                                $totalTripPlanned = $copies;
                                                $totalKMPlanned = $routeAlorSetar->inbound_distance * $copies;

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
                            
                                                $allTrips = TripDetail::whereIn('route_id', [$routeAlorSetar->id, $moreRouteAlorSetars->id])
                                                    ->orderBy('start_trip')
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
                                                                if ($schedule->RouteScheduleMSTR->route_id == $routeAlorSetar->id) {
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
                                            $data_perRoute[$routeAlorSetar->route_number . ' - ' . $routeAlorSetar->route_name] = $data_perDate;
                                        }
                                        elseif($routeAlorSetar->route_number=='ML06'){
                                            $out->writeln("YOU ARE IN routeAlorSetar->route_number==ML05");
                                            $moreRouteAlorSetars = Route::where('route_number','ML006')->first();
                                            $out->writeln("moreRouteAlorSetars: " . $moreRouteAlorSetars);
                                            foreach ($all_dates as $all_date) {
                                                $out->writeln("YOU ARE IN all_date:" . $all_date);
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
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->count();
                                                    }else{
                                                        $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,4,6,9,10])
                                                        ->where('status', 1)
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->count();
                                                    }
                                                }
                                                if($isWeekend){
                                                    $isSunday = $firstDate->format('l');
                                                    if($isSunday=='Sunday'){
                                                        $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11])
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->where('status', 1)
                                                        ->count();
                                                    }else{
                                                        $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12])
                                                        ->where('status', 1)
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->count();
                                                    }
                                                    
                                                }
                                                $totalTripPlanned = $copies;
                                                $totalKMPlanned = $routeAlorSetar->inbound_distance * $copies;

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
                            
                                                $allTrips = TripDetail::whereIn('route_id', [$routeAlorSetar->id, $moreRouteAlorSetars->id])
                                                    ->orderBy('start_trip')
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
                                                                if ($schedule->RouteScheduleMSTR->route_id == $routeAlorSetar->id) {
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
                                            $data_perRoute[$routeAlorSetar->route_number . ' - ' . $routeAlorSetar->route_name] = $data_perDate;
                                        }
                                        else{
                                            $out->writeln("YOU ARE IN else:" . $routeAlorSetar->route_number);
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
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->count();
                                                    }else{
                                                        $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,4,6,9,10])
                                                        ->where('status', 1)
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->count();
                                                    }
                                                }
                                                if($isWeekend){
                                                    $isSunday = $firstDate->format('l');
                                                    if($isSunday=='Sunday'){
                                                        $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11])
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->where('status', 1)
                                                        ->count();
                                                    }else{
                                                        $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12])
                                                        ->where('status', 1)
                                                        ->where('route_id', $routeAlorSetar->id)
                                                        ->count();
                                                    }
                                                    
                                                }
                                                $totalTripPlanned = $copies;
                                                $totalKMPlanned = $routeAlorSetar->inbound_distance * $copies;

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
                            
                                                $allTrips = TripDetail::where('route_id', $routeAlorSetar->id)
                                                    ->orderBy('start_trip')
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
                                                                if ($schedule->RouteScheduleMSTR->route_id == $routeAlorSetar->id) {
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
                                            $data_perRoute[$routeAlorSetar->route_number . ' - ' . $routeAlorSetar->route_name] = $data_perDate;
                                        }

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

                                if($companyTripPlanned==0){
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
                            else{
                                $routePerCompanies = Route::where('company_id', $allCompany->id)
                                ->whereNotIn('route_number', ['ML019','ML99','ML006','ML020'])
                                ->orderBy('route_number')
                                ->get();
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

                        if($companyDetails->id==9){
                            $routePerCompanies = Route::where('company_id', $companyDetails->id)->orderBy('route_number')->get();
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

                                    if($routePerCompany->route_number=='ML03'){
                                        $out->writeln("YOU ARE IN routeAlorSetar->route_number==ML03");
                                        $moreRouteAlorSetars = Route::where('route_number','ML019')->first();
                                        $out->writeln("moreRouteAlorSetars: " . $moreRouteAlorSetars);
                                        foreach ($all_dates as $all_date) {
                                            $out->writeln("YOU ARE IN all_date: " . $all_date);
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
                                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,7,12,13])
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
                                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11,13])
                                                    ->where('route_id', $routePerCompany->id)
                                                    ->where('status', 1)
                                                    ->count();
                                                }else{
                                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12,13])
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
                        
                                            $allTrips = TripDetail::whereIn('route_id', [$routePerCompany->id, $moreRouteAlorSetars->id])
                                                ->orderBy('start_trip')
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
                                    }
                                    elseif($routePerCompany->route_number=='ML04'){
                                        $out->writeln("YOU ARE IN routeAlorSetar->route_number==C11");
                                        $moreRouteAlorSetars = Route::where('route_number','ML99')->first();
                                        $out->writeln("moreRouteAlorSetars: " . $moreRouteAlorSetars);
                                        foreach ($all_dates as $all_date) {
                                            $out->writeln("YOU ARE IN all_date: " . $all_date);
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
                                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,7,12,13])
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
                                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11,13])
                                                    ->where('route_id', $routePerCompany->id)
                                                    ->where('status', 1)
                                                    ->count();
                                                }else{
                                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12,13])
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
                        
                                            $allTrips = TripDetail::whereIn('route_id', [$routePerCompany->id, $moreRouteAlorSetars->id])
                                                ->orderBy('start_trip')
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
                                    }
                                    elseif($routePerCompany->route_number=='ML05'){
                                        $out->writeln("YOU ARE IN routeAlorSetar->route_number==ML05");
                                        $moreRouteAlorSetars = Route::where('route_number','ML020')->first();
                                        $out->writeln("moreRouteAlorSetars: " . $moreRouteAlorSetars);
                                        foreach ($all_dates as $all_date) {
                                            $out->writeln("YOU ARE IN all_date:" . $all_date);
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
                                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,7,12,13])
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
                                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11,13])
                                                    ->where('route_id', $routePerCompany->id)
                                                    ->where('status', 1)
                                                    ->count();
                                                }else{
                                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12,13])
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
                        
                                            $allTrips = TripDetail::whereIn('route_id', [$routePerCompany->id, $moreRouteAlorSetars->id])
                                                ->orderBy('start_trip')
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
                                    }
                                    elseif($routePerCompany->route_number=='ML06'){
                                        $out->writeln("YOU ARE IN routeAlorSetar->route_number==ML05");
                                        $moreRouteAlorSetars = Route::where('route_number','ML006')->first();
                                        $out->writeln("moreRouteAlorSetars: " . $moreRouteAlorSetars);
                                        foreach ($all_dates as $all_date) {
                                            $out->writeln("YOU ARE IN all_date:" . $all_date);
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
                                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,7,12,13])
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
                                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11,13])
                                                    ->where('route_id', $routePerCompany->id)
                                                    ->where('status', 1)
                                                    ->count();
                                                }else{
                                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12,13])
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
                        
                                            $allTrips = TripDetail::whereIn('route_id', [$routePerCompany->id, $moreRouteAlorSetars->id])
                                                ->orderBy('start_trip')
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
                                    }
                                    else{
                                        $out->writeln("YOU ARE IN else:" . $routePerCompany->route_number);
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
                                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,7,12,13])
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
                                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11,13])
                                                    ->where('route_id', $routePerCompany->id)
                                                    ->where('status', 1)
                                                    ->count();
                                                }else{
                                                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12,13])
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
                                                ->orderBy('start_trip')
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
                                    }
            
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
                        else{
                            $routePerCompanies = Route::where('company_id', $companyDetails->id)
                            ->whereNotIn('route_number', ['ML019','ML99','ML006','ML020'])
                            ->orderBy('route_number')
                            ->get();
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
                    }
                    //Summary By Route specific route specific company
                    else{
                        if($companyDetails->id==9){    
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
            
                            if($selectedRoute->route_number=='ML03'){
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
                            }
                            elseif($selectedRoute->route_number=='ML04'){
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
                            }
                            elseif($selectedRoute->route_number=='ML05'){
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
                            }
                            elseif($selectedRoute->route_number=='ML06'){
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
                            }
                            else{
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
            }
           // return Excel::download(new DailySummary($dailySummary, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Daily_Summary_Report_'.Carbon::now()->format('YmdHis').'.xlsx');
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
