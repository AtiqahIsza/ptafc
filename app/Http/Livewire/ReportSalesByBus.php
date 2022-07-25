<?php

namespace App\Http\Livewire;

use App\Exports\SalesByBus;
use App\Models\Bus;
use App\Models\Company;
use App\Models\Route;
use App\Models\Stage;
use App\Models\TicketSalesTransaction;
use App\Models\TripDetail;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Console\Output\ConsoleOutput;

class ReportSalesByBus extends Component
{
    public $buses;
    public $companies;
    public $selectedCompany;
    public $state = [];
    public $heading = [];
    public $data = [];
    public $tot = [];
    public $grand = [];

    public function render()
    {
        $this->companies = Company::all();
        return view('livewire.report-sales-by-bus');
    }

    public function mount()
    {
        $this->buses=collect();
        $this->companies=collect();
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->buses = Bus::where('company_id', $company)->get();
        }
    }

    public function print()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE salesByBus print()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'bus_id' => ['required'],
        ])->validate();

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();
            $startDate->addDay();
        }

        $salesByBus = collect();
        $grandBy = 0;
        $grandCash = 0;
        $grandCard = 0;
        $grandTouchNGo = 0;
        $grandCancelled = 0;
        if($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $companyName = 'ALL';
                if(!empty($validatedData['bus_id'])) {
                    //Sales By Bus all bus all company
                    if($validatedData['bus_id']=='All'){
                        $allBuses = Bus::orderBy('bus_registration_number')->get();
                        $AllBusTrip = [];
                        
                        foreach ($allBuses as $allBus) {
                            $busNo = $allBus->bus_registration_number;
                            $AllDates = [];
                            $finalTotalBy = 0;
                            $finalTotalCash = 0;
                            $finalTotalCard = 0;
                            $finalTotalTouchNGo = 0;
                            $finalTotalCancelled = 0;
            
                            foreach ($all_dates as $all_date) {
                                $AllTrips = [];
                                $trip = 0;
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date . '23:59:59');
                                $tripPerDates = TripDetail::whereBetween('start_trip', [$firstDate, $lastDate])
                                    ->where('bus_id', $allBus->id)
                                    ->orderby('start_trip')
                                    ->get();
            
                                if (count($tripPerDates) > 0) {
                                    foreach ($tripPerDates as $tripDetails) {
                                        $perTrip['trip_number'] = 'T'. $tripDetails->id;
                                        $perTrip['status'] = 'Closed';
                                        $perTrip['creation_by'] = $tripDetails->start_trip;
                                        $perTrip['closed_by'] = $tripDetails->end_trip;
                                        $perTrip['closed_at'] = '***NO SALE';
            
                                        $ticketSaleTransaction = TicketSalesTransaction::where('trip_id', $tripDetails->id)->get();
            
                                        $total_trip = [];
                                        $totalCash = 0;
                                        $totalCard = 0;
                                        $totalTouchNGo = 0;
                                        $totalCancelled = 0;
                                        $totalBy = 0;
                                        $allTickets = [];
                                        $i = 0;
                                        if (count($ticketSaleTransaction) > 0) {
                                            $perTrip['closed_at'] = 'Sales Screen';
            
                                            foreach ($ticketSaleTransaction as $ticketSale) {
                                                $perTicket['sales_date'] = $ticketSale->sales_date;
                                                $perTicket['ticket_number'] = $ticketSale->ticket_number;
            
                                                if($ticketSale->fromstage_stage_id != NULL){
                                                    $perTicket['from'] = $ticketSale->fromstage->stage_name;
                                                }else{
                                                    $perTicket['from'] = 'NO DATA';
                                                }
                                                
                                                if($ticketSale->tostage_stage_id != NULL){
                                                    $perTicket['to'] = $ticketSale->tostage->stage_name;
                                                }else{
                                                    $perTicket['to'] = 'NO DATA';
                                                } 
                                                
                                                if ($ticketSale->passenger_type == 0) {
                                                    $perTicket['Type'] = 'ADULT';
                                                } else {
                                                    $perTicket['Type'] = 'CONCESSION';
                                                }
            
                                                if ($ticketSale->fare_type == 0) {
                                                    $totalCash += $ticketSale->actual_amount;
                                                    $perTicket['cash'] = $ticketSale->actual_amount;
                                                    $perTicket['card'] = 0;
                                                    $perTicket['touch_n_go'] = 0;
                                                } //Cash
                                                elseif ($ticketSale->fare_type == 1) {
                                                    $totalCard += $ticketSale->actual_amount;
                                                    $perTicket['cash'] = 0;
                                                    $perTicket['card'] = $ticketSale->actual_amount;
                                                    $perTicket['touch_n_go'] = 0;
                                                } //Card
                                                else {
                                                    $totalTouchNGo += $ticketSale->actual_amount;
                                                    $perTicket['cash'] = 0;
                                                    $perTicket['card'] = 0;
                                                    $perTicket['touch_n_go'] = $ticketSale->actual_amount;
                                                } //TouchNGo
            
                                                $allTickets[$i++] = $perTicket;
                                            }
                                        }
            
                                        
                                        if ($tripDetails->trip_code == 0) {
                                            if($tripDetails->route_id!=NULL){
                                                $routeNameOut = implode(" - ", array_reverse(explode(" - ", $tripDetails->Route->route_name)));
                                                $perTrip['route_desc'] = $tripDetails->Route->route_number . ' ' . $routeNameOut;
                                            }else{
                                                $perTrip['route_desc'] = 'NO DATA';
                                            }
                                            $perTrip['trip_type'] = 'OB';
                                        } else {
                                            if($tripDetails->route_id!=NULL){
                                                $perTrip['route_desc'] = $tripDetails->Route->route_number . ' ' . $tripDetails->Route->route_name;
                                            }else{
                                                $perTrip['route_desc'] = 'NO DATA';
                                            }
                                            $perTrip['trip_type'] = 'IB';
                                        }
            
                                        $perTrip['trip_details'] = 'T' . $tripDetails->id . ' - ' .
                                            $all_date . ' ' . $tripDetails->start_trip . ' - ' .
                                            $all_date . ' ' . $tripDetails->end_trip;
            
                                        $totalBy = $totalCash + $totalCard + $totalTouchNGo;
            
                                        $perSale['total_cash'] = $totalCash;
                                        $perSale['total_card'] = $totalCard;
                                        $perSale['total_touch_n_go'] = $totalTouchNGo;
                                        $perSale['total_cancelled'] = $totalCancelled;
                                        $perSale['total_by'] = $totalBy;
            
                                        $total_trip['T'. $tripDetails->id] = $perTrip;
                                        $total_trip['all_tickets'] = $allTickets;
                                        $total_trip['total_sales_per_trip'] = $perSale;
                                        $AllTrips[$trip++] = $total_trip;
            
                                        $finalTotalBy += $totalBy;
                                        $finalTotalCash += $totalCash;
                                        $finalTotalCard += $totalCard;
                                        $finalTotalTouchNGo += $totalTouchNGo;
                                        $finalTotalCancelled += $totalCancelled;
                                    }
                                    $AllDates[$all_date] = $AllTrips;
                                }
                            }
                            if($AllDates==[]){
                                $total_bus = [];
                            }else{
                                $total_bus['total_cash_per_bus'] = $finalTotalCash;
                                $total_bus['total_card_per_bus'] = $finalTotalCard;
                                $total_bus['total_touch_n_go_per_bus'] = $finalTotalTouchNGo;
                                $total_bus['total_cancelled_per_bus'] = $finalTotalCancelled;
                                $total_bus['total_by_per_bus'] = $finalTotalBy;
                            }
                            $AllDates['total_sales_per_bus'] = $total_bus;
                            $AllBusTrip[$busNo] = $AllDates;
            
                            $grandBy += $finalTotalBy;
                            $grandCash += $finalTotalCash;
                            $grandCard += $finalTotalCard;
                            $grandTouchNGo += $finalTotalTouchNGo;
                            $grandCancelled += $finalTotalCancelled;
                        }
                        $total_grand['grand_total_cash'] = $grandCash;
                        $total_grand['grand_total_card'] = $grandCard;
                        $total_grand['grand_total_touch_n_go'] = $grandTouchNGo;
                        $total_grand['grand_total_cancelled'] = $grandCancelled;
                        $total_grand['grand_total_by'] = $grandBy;
                        $grand['AllBuses'] = $AllBusTrip;
                        $grand['grand_sales'] = $total_grand;
                        $salesByBus->add($grand);
                    }
                }
            }else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $companyName = $companyDetails->company_name;
                
                if(!empty($validatedData['bus_id'])) {
                    //Sales By Bus all bus specific company
                    if($validatedData['bus_id']=='All'){
                        $busPerCompanies = Bus::where('company_id',$companyDetails->id)->orderBy('bus_registration_number')->get();
                        $AllBusTrip = [];
                        foreach ($busPerCompanies as $busPerCompany) {
                            $busNo = $busPerCompany->bus_registration_number;
                            $AllDates = [];
                            $finalTotalBy = 0;
                            $finalTotalCash = 0;
                            $finalTotalCard = 0;
                            $finalTotalTouchNGo = 0;
                            $finalTotalCancelled = 0;
        
                            foreach ($all_dates as $all_date) {
                                $AllTrips = [];
                                $trip = 0;
                                $firstDate = new Carbon($all_date);
                                $lastDate = new Carbon($all_date . '23:59:59');
                                $tripPerDates = TripDetail::whereBetween('start_trip', [$firstDate, $lastDate])
                                    ->where('bus_id', $busPerCompany->id)
                                    ->orderby('start_trip')
                                    ->get();
        
                                if (count($tripPerDates) > 0) {
                                    foreach ($tripPerDates as $tripDetails) {
                                        $perTrip['trip_number'] = 'T'. $tripDetails->id;
                                        $perTrip['status'] = 'Closed';
                                        $perTrip['creation_by'] = $tripDetails->start_trip;
                                        $perTrip['closed_by'] = $tripDetails->end_trip;
                                        $perTrip['closed_at'] = '***NO SALE';
        
                                        $ticketSaleTransaction = TicketSalesTransaction::where('trip_id', $tripDetails->id)->get();
        
                                        $total_trip = [];
                                        $totalCash = 0;
                                        $totalCard = 0;
                                        $totalTouchNGo = 0;
                                        $totalCancelled = 0;
                                        $totalBy = 0;
                                        $allTickets = [];
                                        $i = 0;
                                        if (count($ticketSaleTransaction) > 0) {
                                            $perTrip['closed_at'] = 'Sales Screen';
        
                                            foreach ($ticketSaleTransaction as $ticketSale) {
                                                $perTicket['sales_date'] = $ticketSale->sales_date;
                                                $perTicket['ticket_number'] = $ticketSale->ticket_number;
        
                                                if($ticketSale->fromstage_stage_id != NULL){
                                                    $perTicket['from'] = $ticketSale->fromstage->stage_name;
                                                }else{
                                                    $perTicket['from'] = 'NO DATA';
                                                }
                                                
                                                if($ticketSale->tostage_stage_id != NULL){
                                                    $perTicket['to'] = $ticketSale->tostage->stage_name;
                                                }else{
                                                    $perTicket['to'] = 'NO DATA';
                                                }
        
                                                if ($ticketSale->passenger_type == 0) {
                                                    $perTicket['Type'] = 'ADULT';
                                                } else {
                                                    $perTicket['Type'] = 'CONCESSION';
                                                }
        
                                                if ($ticketSale->fare_type == 0) {
                                                    $totalCash += $ticketSale->actual_amount;
                                                    $perTicket['cash'] = $ticketSale->actual_amount;
                                                    $perTicket['card'] = 0;
                                                    $perTicket['touch_n_go'] = 0;
                                                } //Cash
                                                elseif ($ticketSale->fare_type == 1) {
                                                    $totalCard += $ticketSale->actual_amount;
                                                    $perTicket['cash'] = 0;
                                                    $perTicket['card'] = $ticketSale->actual_amount;
                                                    $perTicket['touch_n_go'] = 0;
                                                } //Card
                                                else {
                                                    $totalTouchNGo += $ticketSale->actual_amount;
                                                    $perTicket['cash'] = 0;
                                                    $perTicket['card'] = 0;
                                                    $perTicket['touch_n_go'] = $ticketSale->actual_amount;
                                                } //TouchNGo
        
                                                $allTickets[$i++] = $perTicket;
                                            }
                                        }
        
                                        if ($tripDetails->trip_code == 0) {
                                            if($tripDetails->route_id!=NULL){
                                                $routeNameOut = implode(" - ", array_reverse(explode(" - ", $tripDetails->Route->route_name)));
                                                $perTrip['route_desc'] = $tripDetails->Route->route_number . ' ' . $routeNameOut;
                                            }else{
                                                $perTrip['route_desc'] = 'NO DATA';
                                            }
                                            $perTrip['trip_type'] = 'OB';
                                        } else {
                                            if($tripDetails->route_id!=NULL){
                                                $perTrip['route_desc'] = $tripDetails->Route->route_number . ' ' . $tripDetails->Route->route_name;
                                            }else{
                                                $perTrip['route_desc'] = 'NO DATA';
                                            }
                                            $perTrip['trip_type'] = 'IB';
                                        }
        
                                        $perTrip['trip_details'] = 'T' . $tripDetails->id . ' - ' .
                                            $all_date . ' ' . $tripDetails->start_trip . ' - ' .
                                            $all_date . ' ' . $tripDetails->end_trip;
        
                                        $totalBy = $totalCash + $totalCard + $totalTouchNGo;
        
                                        $perSale['total_cash'] = $totalCash;
                                        $perSale['total_card'] = $totalCard;
                                        $perSale['total_touch_n_go'] = $totalTouchNGo;
                                        $perSale['total_cancelled'] = $totalCancelled;
                                        $perSale['total_by'] = $totalBy;
        
                                        $total_trip['T'. $tripDetails->id] = $perTrip;
                                        $total_trip['all_tickets'] = $allTickets;
                                        $total_trip['total_sales_per_trip'] = $perSale;
                                        $AllTrips[$trip++] = $total_trip;
        
                                        $finalTotalBy += $totalBy;
                                        $finalTotalCash += $totalCash;
                                        $finalTotalCard += $totalCard;
                                        $finalTotalTouchNGo += $totalTouchNGo;
                                        $finalTotalCancelled += $totalCancelled;
                                    }
                                    $AllDates[$all_date] = $AllTrips;
                                }
                            }
                            if($AllDates==[]){
                                $total_bus = [];
                            }else{
                                $total_bus['total_cash_per_bus'] = $finalTotalCash;
                                $total_bus['total_card_per_bus'] = $finalTotalCard;
                                $total_bus['total_touch_n_go_per_bus'] = $finalTotalTouchNGo;
                                $total_bus['total_cancelled_per_bus'] = $finalTotalCancelled;
                                $total_bus['total_by_per_bus'] = $finalTotalBy;
                            }
                            $AllDates['total_sales_per_bus'] = $total_bus;
                            $AllBusTrip[$busNo] = $AllDates;
        
                            $grandBy += $finalTotalBy;
                            $grandCash += $finalTotalCash;
                            $grandCard += $finalTotalCard;
                            $grandTouchNGo += $finalTotalTouchNGo;
                            $grandCancelled += $finalTotalCancelled;
                        }
                        $total_grand['grand_total_cash'] = $grandCash;
                        $total_grand['grand_total_card'] = $grandCard;
                        $total_grand['grand_total_touch_n_go'] = $grandTouchNGo;
                        $total_grand['grand_total_cancelled'] = $grandCancelled;
                        $total_grand['grand_total_by'] = $grandBy;
                        $grand['AllBuses'] = $AllBusTrip;
                        $grand['grand_sales'] = $total_grand;
                        $salesByBus->add($grand);
                    }
                    //Sales By Bus specific bus specific company
                    else{
                        $busDetails = Bus::where('id', $validatedData['bus_id'])->first();
                        $busNo = $busDetails->bus_registration_number;
        
                        $AllBusTrip = [];
                        $AllDates = [];
                        $finalTotalBy = 0;
                        $finalTotalCash = 0;
                        $finalTotalCard = 0;
                        $finalTotalTouchNGo = 0;
                        $finalTotalCancelled = 0;
        
                        foreach ($all_dates as $all_date) {
                            $AllTrips = [];
                            $trip = 0;
                            $firstDate = new Carbon($all_date);
                            $lastDate = new Carbon($all_date . '23:59:59');
                            $tripPerDates = TripDetail::whereBetween('start_trip', [$firstDate, $lastDate])
                                ->where('bus_id', $busDetails->id)
                                ->orderby('start_trip')
                                ->get();
        
                            if (count($tripPerDates) > 0) {
                                foreach ($tripPerDates as $tripDetails) {
                                    $perTrip['trip_number'] = 'T'. $tripDetails->id;
                                    $perTrip['status'] = 'Closed';
                                    $perTrip['creation_by'] = $tripDetails->start_trip;
                                    $perTrip['closed_by'] = $tripDetails->end_trip;
                                    $perTrip['closed_at'] = '***NO SALE';
        
                                    $ticketSaleTransaction = TicketSalesTransaction::where('trip_id', $tripDetails->id)->get();
        
                                    $total_trip = [];
                                    $totalCash = 0;
                                    $totalCard = 0;
                                    $totalTouchNGo = 0;
                                    $totalCancelled = 0;
                                    $totalBy = 0;
                                    $allTickets = [];
                                    $i = 0;
                                    if (count($ticketSaleTransaction) > 0) {
                                        $perTrip['closed_at'] = 'Sales Screen';
        
                                        foreach ($ticketSaleTransaction as $ticketSale) {
                                            $perTicket['sales_date'] = $ticketSale->sales_date;
                                            $perTicket['ticket_number'] = $ticketSale->ticket_number;
        
                                            if($ticketSale->fromstage_stage_id != NULL){
                                                $perTicket['from'] = $ticketSale->fromstage->stage_name;
                                            }else{
                                                $perTicket['from'] = 'No Data';
                                            }
        
                                            if($ticketSale->tostage_stage_id != NULL){
                                                $perTicket['to'] = $ticketSale->tostage->stage_name;
                                            }else{
                                                $perTicket['to'] = 'No Data';
                                            } 
                                            
                                            if ($ticketSale->passenger_type == 0) {
                                                $perTicket['Type'] = 'ADULT';
                                            } else {
                                                $perTicket['Type'] = 'CONCESSION';
                                            }
        
                                            if ($ticketSale->fare_type == 0) {
                                                $totalCash += $ticketSale->actual_amount;
                                                $perTicket['cash'] = $ticketSale->actual_amount;
                                                $perTicket['card'] = 0;
                                                $perTicket['touch_n_go'] = 0;
                                            } //Cash
                                            elseif ($ticketSale->fare_type == 1) {
                                                $totalCard += $ticketSale->actual_amount;
                                                $perTicket['cash'] = 0;
                                                $perTicket['card'] = $ticketSale->actual_amount;
                                                $perTicket['touch_n_go'] = 0;
                                            } //Card
                                            else {
                                                $totalTouchNGo += $ticketSale->actual_amount;
                                                $perTicket['cash'] = 0;
                                                $perTicket['card'] = 0;
                                                $perTicket['touch_n_go'] = $ticketSale->actual_amount;
                                            } //TouchNGo
        
                                            $allTickets[$i++] = $perTicket;
                                        }
                                    }
        
                                    if ($tripDetails->trip_code == 0) {
                                        if($tripDetails->route_id!=NULL){
                                            $routeNameOut = implode(" - ", array_reverse(explode(" - ", $tripDetails->Route->route_name)));
                                            $perTrip['route_desc'] = $tripDetails->Route->route_number . ' ' . $routeNameOut;
                                        }else{
                                            $perTrip['route_desc'] = 'No Data';
                                        }
                                        $perTrip['trip_type'] = 'OB';
        
                                    } else {
                                        if($tripDetails->route_id!=NULL){
                                            $perTrip['route_desc'] = $tripDetails->Route->route_number . ' ' . $tripDetails->Route->route_name;
                                        }else{
                                            $perTrip['route_desc'] = 'No Data';
                                        }
                                        $perTrip['trip_type'] = 'IB';
                                    }
        
                                    $perTrip['trip_details'] = 'T' . $tripDetails->id . ' - ' .
                                        $all_date . ' ' . $tripDetails->start_trip . ' - ' .
                                        $all_date . ' ' . $tripDetails->end_trip;
        
                                    $totalBy = $totalCash + $totalCard + $totalTouchNGo;
        
                                    $perSale['total_cash'] = $totalCash;
                                    $perSale['total_card'] = $totalCard;
                                    $perSale['total_touch_n_go'] = $totalTouchNGo;
                                    $perSale['total_cancelled'] = $totalCancelled;
                                    $perSale['total_by'] = $totalBy;
        
                                    $total_trip['T'. $tripDetails->id] = $perTrip;
                                    $total_trip['all_tickets'] = $allTickets;
                                    $total_trip['total_sales_per_trip'] = $perSale;
                                    $AllTrips[$trip++] = $total_trip;
        
                                    $finalTotalBy += $totalBy;
                                    $finalTotalCash += $totalCash;
                                    $finalTotalCard += $totalCard;
                                    $finalTotalTouchNGo += $totalTouchNGo;
                                    $finalTotalCancelled += $totalCancelled;
                                }
                                $AllDates[$all_date] = $AllTrips;
                            }
                        }
                        if($AllDates==[]){
                            $total_bus = [];
                        }else{
                            $total_bus['total_cash_per_bus'] = $finalTotalCash;
                            $total_bus['total_card_per_bus'] = $finalTotalCard;
                            $total_bus['total_touch_n_go_per_bus'] = $finalTotalTouchNGo;
                            $total_bus['total_cancelled_per_bus'] = $finalTotalCancelled;
                            $total_bus['total_by_per_bus'] = $finalTotalBy;
                        }
                        $AllDates['total_sales_per_bus'] = $total_bus;
                        $AllBusTrip[$busNo] = $AllDates;
        
                        $grandBy += $finalTotalBy;
                        $grandCash += $finalTotalCash;
                        $grandCard += $finalTotalCard;
                        $grandTouchNGo += $finalTotalTouchNGo;
                        $grandCancelled += $finalTotalCancelled;
        
                        $total_grand['grand_total_cash'] = $grandCash;
                        $total_grand['grand_total_card'] = $grandCard;
                        $total_grand['grand_total_touch_n_go'] = $grandTouchNGo;
                        $total_grand['grand_total_cancelled'] = $grandCancelled;
                        $total_grand['grand_total_by'] = $grandBy;
        
                        $grand['AllBuses'] = $AllBusTrip;
                        $grand['grand_sales'] = $total_grand;
                        $salesByBus->add($grand);
                    }
                }
            }
            return Excel::download(new SalesByBus($salesByBus, $companyName, $validatedData['dateFrom'], $validatedData['dateTo']), 'SalesByBus_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }

}
