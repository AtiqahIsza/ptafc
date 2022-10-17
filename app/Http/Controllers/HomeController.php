<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Route;
use App\Models\RouteSchedulerDetail;
use App\Models\TicketSalesTransaction;
use App\Models\TripDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $collectionByCompanies = $this->getCollectionByCompany();
        $missedTrips = $this->getMissedTrip();
        $earlyLateTrips = $this->getEarlyLateTrip();
        $breakdownTrips = $this->getBreakdownTrip();
        $totalTrips = $this->getTotalTrips();
        $grandCollection = $this->getGrandCollection();
        $vehicleSummary = $this->getVehicleSummary();
        //dd($collectionByCompanies);
        return view('home',compact('collectionByCompanies','missedTrips','earlyLateTrips','breakdownTrips','totalTrips','grandCollection','vehicleSummary'));
    }

    public function getPassengerType() : JsonResponse
    {
        $out = new ConsoleOutput();
        try
        {
            $currStart = Carbon::create(Carbon::now())->startOfMonth()->toDateString();
            $currEnd = Carbon::create(Carbon::now())->endOfMonth()->toDateString();
            $currStartMonth = new Carbon($currStart);
            $currEndMonth = new Carbon($currEnd . '23:59:59');

            $countAdult = TicketSalesTransaction::where('passenger_type', 0)
                ->whereBetween('sales_date', [$currStartMonth,$currEndMonth])
                ->count();
            $countConcession = TicketSalesTransaction::where('passenger_type', 1)
                ->whereBetween('sales_date', [$currStartMonth,$currEndMonth])
                ->count();

            $dataPoints[] = $countAdult;
            $dataPoints[] = $countConcession;

            //dd($dataPoints);
            return $this->returnResponse (1, $dataPoints, 'Successfully Returned Count Passenger Type');
        }
        catch (\Exception $e)
        {
            $error['error_log'] = $e;
            $out->writeln($e);
            return $this->returnResponse (2, $error, 'Error Encountered. See Log');
        }
    }

    public function getMissedTrip()
    {
        $out = new ConsoleOutput();
        try {
            //Current Month
            $currStart = Carbon::create(Carbon::now())->startOfMonth()->toDateString();
            $currEnd = Carbon::create(Carbon::now())->endOfMonth()->toDateString();
            $currStartMonth = new Carbon($currStart);
            $currEndMonth = new Carbon($currEnd . '23:59:59');

            $startDate = new Carbon($currStart);
            $endDate = new Carbon($currEnd . '23:59:59');
            $all_dates = array();
            while ($startDate->lte($endDate)) {
                $all_dates[] = $startDate->toDateString();

                $startDate->addDay();
            }
            $missedTrip = 0;
            $tripServed = 0;
            $tripPlanned  = 0;
            $out->writeln("currStartMonth after loop all_Date" . $currStartMonth);
            foreach ($all_dates as $all_date) {
                $startDay = new Carbon($all_date);
                $endDay = new Carbon($all_date . '23:59:59');
                $countTrips = TripDetail::whereBetween('start_trip', [$startDay, $endDay])->count();
                $countScheduleIn = RouteSchedulerDetail::whereBetween('schedule_date', [$startDay, $endDay])->count();
                $countScheduleOut = $countScheduleIn;
                $countSchedule = $countScheduleIn + $countScheduleOut;
                //$out->writeln("missedTrip" . $startDay ."countTrips:" . $countTrips);
                //$out->writeln("missedTrip" . $startDay . "countSchedule:" . $countSchedule);

                $tripServed += $countTrips;
                $tripPlanned += $countSchedule;
                // $diff = $countSchedule - $countTrips;
                // if ($diff > 0){
                //     $missedTrip += $diff;
                // }
            }
            $missedTrip = $tripPlanned - $tripServed;
            if ($missedTrip<0){
                $missedTrip = 0;
            }


            //Previous Month
            $prevStart = Carbon::create(Carbon::now())->startOfMonth()->subMonthsNoOverflow()->toDateString();
            $prevEnd = Carbon::create(Carbon::now())->subMonthsNoOverflow()->endOfMonth()->toDateString();
            $previousStartMonth = new Carbon($prevStart);
            $previousEndMonth = new Carbon($prevEnd . '23:59:59');

            $startDatePrev = $previousStartMonth;
            $endDatePrev = $previousEndMonth;
            $all_dates_prev = array();

            while ($startDatePrev->lte($endDatePrev)) {
                $all_dates_prev[] = $startDatePrev->toDateString();
                $startDatePrev->addDay();
            }
            $missedTripPrev = 0;

            foreach ($all_dates_prev as $all_date_prev) {
                $startDay = new Carbon($all_date_prev);
                $endDay = new Carbon($all_date_prev . '23:59:59');
                $countTrips = TripDetail::whereBetween('start_trip', [$startDay, $endDay])->count();
                $countScheduleIn = RouteSchedulerDetail::whereBetween('schedule_date', [$startDay, $endDay])->count();
                $countScheduleOut = $countScheduleIn;
                $countSchedule = $countScheduleIn + $countScheduleOut;

                $diff = $countSchedule - $countTrips;
                if ($diff > 0) {
                    $missedTripPrev += $diff;
                }
            }

            /**Increment
             * If $increment > 0 == missed trip increasing than last month (red)
             * If $increment < 0 == missed trip decreasing than last month (green)
             */
            $out->writeln("missedTripPrev: " . $missedTripPrev);
            $out->writeln("missedTrip: " . $missedTrip);
            if($missedTripPrev==0 && $missedTrip>0){
                $incrementFormat = 100;
            }
            elseif($missedTripPrev==0 && $missedTrip==0){
                $incrementFormat = 0;
            }
            else{
                $increment = (($missedTrip - $missedTripPrev) /$missedTripPrev) * 100/100;
                $incrementFormat = number_format((float)$increment, 2, '.', '');
            }

            $out->writeln("Carbon:now()" . Carbon::now());
            $out->writeln("currStartMonth" . $currStartMonth);
            $formatStartMonth = $currStartMonth->format('M d');
            $out->writeln("formatStartMonth" .$formatStartMonth);
            $formatEndMonth = $currEndMonth->format('d');
            $data = [
                'missed_trip' => $missedTrip,
                'start_date' => $formatStartMonth,
                'end_date' => $formatEndMonth,
                'increment' => $incrementFormat,
            ];

            return $data;
            //return $this->returnResponse (1, $collectionCompany, 'Successfully Returned Collection By Company');
        }
        catch (\Exception $e)
        {
            $error['error_log'] = $e;
            $out->writeln($e);
            return $this->returnResponse (2, $error, 'Error Encountered. See Log');
        }
    }

    public function getEarlyLateTrip()
    {
        $out = new ConsoleOutput();
        try {
            //Current Month
            $currStart = Carbon::create(Carbon::now())->startOfMonth()->toDateString();
            $currEnd = Carbon::create(Carbon::now())->endOfMonth()->toDateString();
            $currStartMonth = new Carbon($currStart);
            $currEndMonth = new Carbon($currEnd . '23:59:59');

            $startDate = new Carbon($currStart);
            $endDate = new Carbon($currEnd . '23:59:59');
            $all_dates = array();

            while ($startDate->lte($endDate)) {
                $all_dates[] = $startDate->toDateString();
                $startDate->addDay();
            }
            
            $countEarlyLate = 0;
            foreach ($all_dates as $all_date) {
                $startDay = new Carbon($all_date);
                $endDay = new Carbon($all_date . '23:59:59');
                $allTrips = TripDetail::whereBetween('start_trip', [$startDay, $endDay])->get();

                if(count($allTrips)>0){
                    foreach ($allTrips as $allTrip){
                        if ($allTrip->route_schedule_mstr_id != NULL) {
                            //$scheduleTime = Carbon::create($allTrip->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                            //$scheduleTime = strtotime($allTrip->RouteScheduleMSTR->schedule_start_time);
                            //$start_time = date("H:i", strtotime($allTrip->start_trip));
    
                            $diff = strtotime($allTrip->RouteScheduleMSTR->schedule_start_time) - strtotime($allTrip->start_trip);
    
                            if ($diff > 5 || $diff < -5) {
                                $countEarlyLate++;
                            }
                        }
                    }
                }         
            }

            //Previous Month
            $prevStart = Carbon::create(Carbon::now())->startOfMonth()->subMonthsNoOverflow()->toDateString();
            $prevEnd = Carbon::create(Carbon::now())->subMonthsNoOverflow()->endOfMonth()->toDateString();
            $previousStartMonth = new Carbon($prevStart);
            $previousEndMonth = new Carbon($prevEnd . '23:59:59');

            $startDatePrev = $previousStartMonth;
            $endDatePrev = $previousEndMonth;
            $all_dates_prev = array();

            while ($startDatePrev->lte($endDatePrev)) {
                $all_dates_prev[] = $startDatePrev->toDateString();
                $startDatePrev->addDay();
            }
            $countEarlyLatePrev = 0;

            foreach ($all_dates_prev as $all_date_prev) {
                $startDay = new Carbon($all_date_prev);
                $endDay = new Carbon($all_date_prev . '23:59:59');
                $allTrips = TripDetail::whereBetween('start_trip', [$startDay, $endDay])->get();

                foreach ($allTrips as $allTrip) {
                    if ($allTrip->route_schedule_mstr_id != NULL) {
                        //$scheduleTime = Carbon::create($allTrip->RouteScheduleMSTR->schedule_start_time)->format('H:i');
                        //$start_time = date("H:i", strtotime($allTrip->start_trip));
                        $diff = strtotime($allTrip->RouteScheduleMSTR->schedule_start_time) - strtotime($allTrip->start_trip);

                        if ($diff > 5 || $diff < -5) {
                            $countEarlyLatePrev++;
                        }
                    }
                }
            }

            /**Increment
             * If $increment > 0 == early/late trip increasing than last month (red)
             * If $increment < 0 == early/late trip decreasing than last month (green)
             */
            $out->writeln("countEarlyLatePrev: " . $countEarlyLatePrev);
            $out->writeln("countEarlyLate: " . $countEarlyLate);
            if($countEarlyLatePrev==0 && $countEarlyLate>0){
                $incrementFormat = 100;
            }elseif($countEarlyLatePrev==0 && $countEarlyLate==0){
                $incrementFormat = 0;
            }
            else{
                $increment = (($countEarlyLate - $countEarlyLatePrev) /$countEarlyLatePrev) * 100/100;
                $incrementFormat = number_format((float)$increment, 2, '.', '');
            }

            $formatStartMonth = $currStartMonth->format('M d');
            $formatEndMonth = $currEndMonth->format('d');
            $data = [
                'early_late_trip' => $countEarlyLate,
                'start_date' => $formatStartMonth,
                'end_date' => $formatEndMonth,
                'increment' => $incrementFormat,
            ];

            return $data;
            //return $this->returnResponse (1, $collectionCompany, 'Successfully Returned Collection By Company');
        }
        catch (\Exception $e)
        {
            $error['error_log'] = $e;
            $out->writeln($e);
            $data = [
                'early_late_trip' => $e,
                'start_date' => $e,
                'end_date' => $e,
                'increment' => 0
            ];

            return $data;
            //return $this->returnResponse (2, $error, 'Error Encountered. See Log');
        }
    }

    public function getBreakdownTrip()
    {
        $out = new ConsoleOutput();
        try {
            $data = [];

            //Current Month
            $currStart = Carbon::create(Carbon::now())->startOfMonth()->toDateString();
            $currEnd = Carbon::create(Carbon::now())->endOfMonth()->toDateString();
            $currStartMonth = new Carbon($currStart);
            $currEndMonth = new Carbon($currEnd . '23:59:59');

            $startDate = new Carbon($currStart);
            $endDate = new Carbon($currEnd . '23:59:59');
            $all_dates = array();

            while ($startDate->lte($endDate)) {
                $all_dates[] = $startDate->toDateString();
                $startDate->addDay();
            }
            $countBreakdown = 0;

            foreach ($all_dates as $all_date) {
                $startDay = new Carbon($all_date);
                $endDay = new Carbon($all_date . '23:59:59');
                $allTrips = TripDetail::whereBetween('start_trip', [$startDay, $endDay])->get();

                foreach ($allTrips as $allTrip){
                    //do count here
                }
            }

            //Previous Month
            $prevStart = Carbon::create(Carbon::now())->startOfMonth()->subMonthsNoOverflow()->toDateString();
            $prevEnd = Carbon::create(Carbon::now())->subMonthsNoOverflow()->endOfMonth()->toDateString();
            $previousStartMonth = new Carbon($prevStart);
            $previousEndMonth = new Carbon($prevEnd . '23:59:59');

            $startDatePrev = $previousStartMonth;
            $endDatePrev = $previousEndMonth;
            $all_dates_prev = array();

            while ($startDatePrev->lte($endDatePrev)) {
                $all_dates_prev[] = $startDatePrev->toDateString();
                $startDatePrev->addDay();
            }
            $countEarlyLatePrev = 0;

            foreach ($all_dates_prev as $all_date_prev) {
                $startDay = new Carbon($all_date_prev);
                $endDay = new Carbon($all_date_prev . '23:59:59');
                $allTrips = TripDetail::whereBetween('start_trip', [$startDay, $endDay])->get();

                foreach ($allTrips as $allTrip){
                    //do count here
                }
            }

            /**Increment
             * If $increment > 0 == early/late trip increasing than last month (red)
             * If $increment < 0 == early/late trip decreasing than last month (green)

            if($breakdownPrev==0 && $breakdown>0){
                $incrementFormat = 100;
            }elseif($breakdownPrev==0 && $breakdown==0){
            $incrementFormat = 0;
            }else{
                $increment = (($breakdown - $breakdownPrev) /$breakdownPrev) * 100;
                $incrementFormat = number_format((float)$increment, 2, '.', '');
            }*/

            $formatStartMonth = $currStartMonth->format('M d');
            $formatEndMonth = $currEndMonth->format('d');
            $data = [
                'breakdown_trip' => 0,
                'start_date' => $formatStartMonth,
                'end_date' => $formatEndMonth,
                'increment' => 0,
            ];

            return $data;
            //return $this->returnResponse (1, $collectionCompany, 'Successfully Returned Collection By Company');
        }
        catch (\Exception $e)
        {
            $error['error_log'] = $e;
            $out->writeln($e);
            return $this->returnResponse (2, $error, 'Error Encountered. See Log');
        }
    }

    public function getTotalTrips()
    {
        $out = new ConsoleOutput();
        try {
            //Current Month
            $currStart = Carbon::create(Carbon::now())->startOfMonth()->toDateString();
            $currEnd = Carbon::create(Carbon::now())->endOfMonth()->toDateString();
            $currStartMonth = new Carbon($currStart);
            $currEndMonth = new Carbon($currEnd . '23:59:59');

            $tripPlanned = RouteSchedulerDetail::whereBetween('schedule_date',[$currStartMonth,$currEndMonth])->count();
            $tripMade = TripDetail::whereBetween('start_trip',[$currStartMonth,$currEndMonth])->count();

            $formatStartMonth = $currStartMonth->format('M d');
            $formatEndMonth = $currEndMonth->format('d');
            $data = [
                'trip_planned' => $tripPlanned*2,
                'trip_made' => $tripMade,
                'start_date' => $formatStartMonth,
                'end_date' => $formatEndMonth,
            ];

            return $data;
            //return $this->returnResponse (1, $collectionCompany, 'Successfully Returned Collection By Company');
        }
        catch (\Exception $e)
        {
            $error['error_log'] = $e;
            $out->writeln($e);
            return $this->returnResponse (2, $error, 'Error Encountered. See Log');
        }
    }

    public function getCollectionByCompany()
    {
        $out = new ConsoleOutput();
        try
        {
            $currStart = Carbon::create(Carbon::now())->startOfMonth()->toDateString();
            $currEnd = Carbon::create(Carbon::now())->endOfMonth()->toDateString();
            $currStartMonth = new Carbon($currStart);
            $currEndMonth = new Carbon($currEnd . '23:59:59');

            $prevStart = Carbon::create(Carbon::now())->startOfMonth()->subMonthsNoOverflow()->toDateString();
            $prevEnd = Carbon::create(Carbon::now())->subMonthsNoOverflow()->endOfMonth()->toDateString();
            $previousStartMonth = new Carbon($prevStart);
            $previousEndMonth = new Carbon($prevEnd . '23:59:59');

            $allCompanies = Company::all();
            $collectionCompanyBar = collect();
            foreach($allCompanies as $company){
                $allRoutes = Route::where('company_id', $company->id)->get();
                $sumRidershipPerRoute = 0;
                $sumFareboxPerRoute  = 0.0;
                $sumPrevRidership = 0;
                $sumPrevFarebox = 0.0;

                foreach ($allRoutes as $route){
                    $allTrips = TripDetail::where('route_id', $route->id)
                        ->whereBetween('start_trip', [$currStartMonth, $currEndMonth])
                        ->get();
                    $sumRidershipPerTrip = 0.0;
                    $sumFareboxPerTrip = 0.0;

                    foreach ($allTrips as $trip){
                        $adultAmount = $trip->total_adult_amount;
                        $concessionAmount = $trip->total_concession_amount;
                        $adultTotal = $trip->total_adult;
                        $concessionTotal = $trip->total_concession;

                        $sumRidership = $adultTotal + $concessionTotal;
                        $sumFarebox = $adultAmount + $concessionAmount;

                        $sumRidershipPerTrip += $sumRidership;
                        $sumFareboxPerTrip += $sumFarebox;
                    }

                    $sumRidershipPerRoute += $sumRidershipPerTrip;
                    $sumFareboxPerRoute += $sumFareboxPerTrip;

                    //Previous Month Ridership collection
                    $prevRidership = TicketSalesTransaction::whereBetween('sales_date', [$previousStartMonth, $previousEndMonth])->count();
                    $sumPrevRidership += $prevRidership;

                    //Previous month farebox collection
                    $adultPrev = TripDetail::where('route_id', $route->id)
                        ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                        ->sum('total_adult_amount');
                    $concessionPrev = TripDetail::where('route_id', $route->id)
                        ->whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])
                        ->sum('total_concession_amount');
                    $prevFarebox = $adultPrev + $concessionPrev;
                    $sumPrevFarebox += $prevFarebox;
                }

                //Increment ridership collection (%)
                if($sumPrevRidership==0 && $sumRidershipPerRoute>0){
                    $increaseRidershipFormat = 100;
                }elseif($sumPrevRidership==0 && $sumRidershipPerRoute==0){
                    $increaseRidershipFormat = 0;
                }else{
                    $increaseRidership = (($sumRidershipPerRoute - $sumPrevRidership) / $sumPrevRidership) * 100/100;
                    $increaseRidershipFormat = number_format((float)$increaseRidership, 2, '.', '');
                }

                //Increment farebox collection (%)
                if($sumPrevFarebox==0 && $sumFareboxPerRoute>0){
                    $increaseFareboxFormat = 100;
                }elseif($sumPrevFarebox==0 && $sumFareboxPerRoute==0){
                    $increaseFareboxFormat = 0;
                }else{
                    $increaseFarebox = (($sumFareboxPerRoute - $sumPrevFarebox) / $sumPrevFarebox) * 100/100;
                    $increaseFareboxFormat = number_format((float)$increaseFarebox, 2, '.', '');
                }

                $data = [
                    'company_name' => $company->company_name,
                    'prev_farebox' => $sumPrevFarebox,
                    'farebox' => $sumFareboxPerRoute,
                    'farebox_in' => $increaseFareboxFormat,
                    'prev_ridership' => $sumPrevRidership,
                    'ridership' => $sumRidershipPerRoute,
                    'ridership_in' => $increaseRidershipFormat,

                ];
                $collectionCompanyBar->add($data);
            }
            //dd($collectionCompanyBar);
            return $collectionCompanyBar;
            //return $this->returnResponse (1, $collectionCompany, 'Successfully Returned Collection By Company');
        }
        catch (\Exception $e)
        {
            $error['error_log'] = $e;
            $out->writeln($e);
            return $this->returnResponse (2, $error, 'Error Encountered. See Log');
        }
    }

    public function getGrandCollection()
    {
        $out = new ConsoleOutput();
        try
        {
            $currStart = Carbon::create(Carbon::now())->startOfMonth()->toDateString();
            $currEnd = Carbon::create(Carbon::now())->endOfMonth()->toDateString();
            $currStartMonth = new Carbon($currStart);
            $currEndMonth = new Carbon($currEnd . '23:59:59');

            $prevStart = Carbon::create(Carbon::now())->startOfMonth()->subMonthsNoOverflow()->toDateString();
            $prevEnd = Carbon::create(Carbon::now())->subMonthsNoOverflow()->endOfMonth()->toDateString();
            $previousStartMonth = new Carbon($prevStart);
            $previousEndMonth = new Carbon($prevEnd . '23:59:59');

            $allCollection = collect();

            $allTrips = TripDetail::whereBetween('start_trip', [$currStartMonth, $currEndMonth])->get();
            $grandRidership = 0;
            $grandFarebox = 0;

            foreach ($allTrips as $allTrip){
                $sumRidership = $allTrip->total_adult + $allTrip->total_concession;
                $sumFarebox = $allTrip->total_adult_amount + $allTrip->total_concession_amount;

                $grandRidership += $sumRidership;
                $grandFarebox += $sumFarebox;
            }

            $allTrips = TripDetail::whereBetween('start_trip', [$currStartMonth, $currEndMonth])->get();
            $grandRidership = 0;
            $grandFarebox = 0;

            foreach ($allTrips as $allTrip){
                $sumRidership = $allTrip->total_adult + $allTrip->total_concession;
                $sumFarebox = $allTrip->total_adult_amount + $allTrip->total_concession_amount;

                $grandRidership += $sumRidership;
                $grandFarebox += $sumFarebox;
            }

            $allTripPrevs = TripDetail::whereBetween('start_trip', [$previousStartMonth, $previousEndMonth])->get();
            $grandRidershipPrev = 0;
            $grandFareboxPrev = 0;

            foreach ($allTripPrevs as $allTripPrev){
                $previdership = $allTripPrev->total_adult + $allTripPrev->total_concession;
                $prevFarebox = $allTripPrev->total_adult_amount + $allTripPrev->total_concession_amount;

                $grandRidershipPrev += $previdership;
                $grandFareboxPrev += $prevFarebox;
            }

            $out->writeln("grandRidership: " . $grandRidership);
            $out->writeln("grandFarebox: " . $grandFarebox);
            $out->writeln("grandRidershipPrev: " . $grandRidershipPrev);
            $out->writeln("grandFareboxPrev: " . $grandFareboxPrev);

            //Increment ridership collection (%)
            if($grandRidershipPrev==0 && $grandRidership>0){
                $increaseRidershipFormat = 100;
            }elseif($grandRidershipPrev==0 && $grandRidership==0){
                $increaseRidershipFormat = 0;
            }else{
                $increaseRidership = (($grandRidership -$grandRidershipPrev) / $grandRidershipPrev) * 100/100;
                $increaseRidershipFormat = number_format((float)$increaseRidership, 2, '.', '');
            }

            //Increment farebox collection (%)
            if($grandFareboxPrev==0 && $grandFarebox>0){
                $increaseFareboxFormat = 100;
            }elseif($grandFareboxPrev==0 && $grandFarebox==0){
                $increaseFareboxFormat = 0;
            }else{
                $increaseFarebox = (($grandFarebox - $grandFareboxPrev) / $grandFareboxPrev) * 100/100;
                $increaseFareboxFormat = number_format((float)$increaseFarebox, 2, '.', '');
            }

            $data = [
                'grand_farebox' => $grandFarebox,
                'grand_farebox_in' => $increaseRidershipFormat,
                'grand_ridership' => $grandRidership,
                'grand_ridership_in' => $increaseFareboxFormat,
            ];

            //dd($data);
            return $data;
        }
        catch (\Exception $e)
        {
            $error['error_log'] = $e;
            $out->writeln($e);
            return $this->returnResponse (2, $error, 'Error Encountered. See Log');
        }
    }

    public function getCollectionByCompanyBar()
    {
        $out = new ConsoleOutput();
        try
        {
            $currStart = Carbon::create(Carbon::now())->startOfMonth()->toDateString();
            $currEnd = Carbon::create(Carbon::now())->endOfMonth()->toDateString();
            $currStartMonth = new Carbon($currStart);
            $currEndMonth = new Carbon($currEnd . '23:59:59');

            $allCompanies = Company::all();
            //$collectionCompany = collect();
            $companyName = [];
            $farebox = [];
            $ridership = [];

            foreach($allCompanies as $company){
                $allRoutes = Route::where('company_id', $company->id)->get();
                $sumRidershipPerRoute = 0;
                $sumFareboxPerRoute  = 0.0;
                $sumPrevRidership = 0;
                $sumPrevFarebox = 0.0;

                foreach ($allRoutes as $route){
                    $allTrips = TripDetail::where('route_id', $route->id)
                        ->whereBetween('start_trip', [$currStartMonth, $currEndMonth])
                        ->get();
                    $sumRidershipPerTrip = 0.0;
                    $sumFareboxPerTrip = 0.0;

                    foreach ($allTrips as $trip){
                        $adultAmount = $trip->total_adult_amount;
                        $concessionAmount = $trip->total_concession_amount;
                        $adultTotal = $trip->total_adult;
                        $concessionTotal = $trip->total_concession;

                        $sumRidership = $adultTotal + $concessionTotal;
                        $sumFarebox = $adultAmount + $concessionAmount;

                        $sumRidershipPerTrip += $sumRidership;
                        $sumFareboxPerTrip += $sumFarebox;
                    }

                    $sumRidershipPerRoute += $sumRidershipPerTrip;
                    $sumFareboxPerRoute += $sumFareboxPerTrip;

                }
                $formatStartMonth = $currStartMonth->format('M d');
                $formatEndMonth = $currEndMonth->format('d');

                $companyName[] = $company->company_name;
                $farebox[] = $sumFareboxPerRoute;
                $ridership[] = $sumRidershipPerRoute;

            }
            $fareboxRidership[] = $farebox;
            $fareboxRidership[] = $ridership;

            $collectionCompany = [
                'company_name' => $companyName,
                'farebox_ridership' => $fareboxRidership,
            ];
            return $this->returnResponse (1, $collectionCompany, 'Successfully Returned Collection By Company Bar');
        }
        catch (\Exception $e)
        {
            $error['error_log'] = $e;
            $out->writeln($e);
            return $this->returnResponse (2, $error, 'Error Encountered. See Log');
        }
    }

    public function getVehicleSummary()
    {
        $currentDate = Carbon::now();

        $join = DB::table('vehicle_position')
            ->select('bus_id', DB::raw('MAX(id) as last_id'))
            ->groupBy('bus_id');

        $allBus = DB::table('vehicle_position as a')
            ->join('bus as c', 'a.bus_id', '=', 'c.id')
            ->joinSub($join, 'b', function ($join) {
                $join->on('a.id', '=', 'b.last_id');
            })
            ->where('c.status', 1)
            ->count();

        $onlineBus = DB::table('vehicle_position as a')
            ->join('bus as c', 'a.bus_id', '=', 'c.id')
            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '00:10:00'")
            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
            ->joinSub($join, 'b', function ($join) {
                $join->on('a.id', '=', 'b.last_id');
            })
            ->where('c.status', 1)
            ->count();

        $stationaryBus = DB::table('vehicle_position as a')
            ->join('bus as c', 'a.bus_id', '=', 'c.id')
            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '00:10:00'")
            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
            ->joinSub($join, 'b', function ($join) {
                $join->on('a.id', '=', 'b.last_id');
            })
            ->where('c.status', 1)
            ->count();

        $offlineBus = DB::table('vehicle_position as a')
            ->join('bus as c', 'a.bus_id', '=', 'c.id')
            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
            ->joinSub($join, 'b', function ($join) {
                $join->on('a.id', '=', 'b.last_id');
            })
            ->where('c.status', 1)
            ->count();

        $collectionVehicle = [
            'allBus' => $allBus,
            'stationaryBus' => $stationaryBus,
            'onlineBus' => $onlineBus,
            'offlineBus' => $offlineBus,
        ];

        return $collectionVehicle;   
     }

    public function returnResponse ($statusCode, $payload, $statusDescription) : JsonResponse
    {
        $response['statusCode'] = $statusCode ;
        $response['payload'] = $payload;
        $response['statusDescription'] = $statusDescription;

        return response()->json($response);
    }
}
