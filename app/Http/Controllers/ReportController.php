<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
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

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function show(Report $report)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function edit(Report $report)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Report $report)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function destroy(Report $report)
    {
        //
    }

    public function viewSalesByBus()
    {
        return view('reports.salesByBus');
    }

    public function viewSalesByRoute()
    {
        return view('reports.salesByRoute');
    }

    public function viewSalesByDriver()
    {
        return view('reports.salesByDriver');
    }

    public function viewCollectionByCompany()
    {
        return view('reports.collectionByCompany');
    }

    public function viewMonthlySummary()
    {
        return view('reports.monthlySummary');
    }

    public function viewDailySummary()
    {
        return view('reports.dailySummary');
    }

    public function viewAverageSummary()
    {
        return view('reports.averageSummary');
    }

    public function viewReportSPAD()
    {
        return view('reports.reportSPAD');
    }

    public function viewClaimDetails(Request $request)
    {
        //dd($request);
        $validatedDateFrom = $request->dateFrom;
        $validatedDateTo = $request->dateTo;
        $validatedRoute = $request->routeID;
        $validatedCompany = $request->companyID;

        $startDate = new Carbon($validatedDateFrom);
        $endDate = new Carbon($validatedDateTo);
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
        if($validatedCompany=='All'){
            $networkArea = 'All';
            //ClaimDetails all routes for all company
            if($validatedRoute=='All'){
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
    
                        $routeNameIn = $allRoute->route_name;
                        if (count($allTripInbounds) > 0) {
                            $existInTrip = true;
                            $countIn = 0;
    
                            foreach ($allTripInbounds as $allTripInbound) {
                                $inbound['trip_id'] = $allTripInbound->trip_number;
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
                                $outbound['trip_id'] = $allTripOutbound->trip_number;
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
        }else{
            $companyDetails = Company::where('id', $validatedCompany)->first();
            $networkArea = $companyDetails->company_name;
            //ClaimDetails all routes for specific company
            if($validatedRoute=='All'){
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
                        $routeNameIn = $allRouteCompany->route_name;
                        if (count($allTripInbounds) > 0) {
                            $existInTrip = true;
                            $countIn = 0;
    
                            foreach ($allTripInbounds as $allTripInbound) {
                                $inbound['trip_id'] = $allTripInbound->trip_number;
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
                                $outbound['trip_id'] = $allTripOutbound->trip_number;
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
                $selectedRoute = Route::where('id', $validatedRoute)->first();

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
                                $inbound['trip_id'] = $allTripInbound->trip_number;
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
                                $outbound['trip_id'] = $allTripOutbound->trip_number;
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
        return view('reports.claimDetailsGPS', compact('networkArea', 'claimDetails', 'validatedDateFrom', 'validatedDateTo'));
        //return Excel::download(new SPADClaimDetails($all_dates, $claimDetails, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'ClaimDetails_Report_SPAD_'.Carbon::now()->format('YmdHis').'.xlsx');
    
    }

    public function viewClaimDetailsGPS(Request $request)
    {
        $vehiclePositions = VehiclePosition::where('trip_id', $request->tripID)->get();
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
        return view('reports.viewClaimDetailsGPS', compact('allGPS'));
    }



}
