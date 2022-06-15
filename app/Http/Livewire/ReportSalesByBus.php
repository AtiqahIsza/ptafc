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
            'bus_id' => ['int'],
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
            $companyDetails = Company::where('id', $this->selectedCompany)->first();
            $companyName = $companyDetails->company_name;

            if(!empty($this->state['bus_id'])){
                $busDetails = Bus::where('id', $this->state['bus_id'])->first();
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
                                $routeNameOut = implode(" - ", array_reverse(explode(" - ", $tripDetails->Route->route_name)));
                                $perTrip['route_desc'] = $tripDetails->Route->route_number . ' ' . $routeNameOut;
                                $perTrip['trip_type'] = 'OB';

                            } else {
                                $perTrip['route_desc'] = $tripDetails->Route->route_number . ' ' . $tripDetails->Route->route_name;
                                $perTrip['trip_type'] = 'IB';
                            }

                            $perTrip['trip_details'] = 'T' . $tripDetails->id . ' - ' .
                                $all_date . ' ' . $tripDetails->start_trip . ' - ' .
                                $all_date . ' ' . $tripDetails->end_trip;

                            $totalBy = $totalCash + $totalCard + $totalTouchNGo + $totalCancelled;

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
            //Sales By Bus all bus specific company
            else{
                $busPerCompanies = Bus::where('company_id',$companyDetails->id)->get();
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
                                    $routeNameOut = implode(" - ", array_reverse(explode(" - ", $tripDetails->Route->route_name)));
                                    $perTrip['route_desc'] = $tripDetails->Route->route_number . ' ' . $routeNameOut;
                                    $perTrip['trip_type'] = 'OB';

                                } else {
                                    $perTrip['route_desc'] = $tripDetails->Route->route_number . ' ' . $tripDetails->Route->route_name;
                                    $perTrip['trip_type'] = 'IB';
                                }

                                $perTrip['trip_details'] = 'T' . $tripDetails->id . ' - ' .
                                    $all_date . ' ' . $tripDetails->start_trip . ' - ' .
                                    $all_date . ' ' . $tripDetails->end_trip;

                                $totalBy = $totalCash + $totalCard + $totalTouchNGo + $totalCancelled;

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
        //Sales By Bus all route all company
        else {
            $companyName = 'ALL';
            $allBuses = Bus::all();
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

                            //$data['perTrip'] = $perTrip;
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
                                $routeNameOut = implode(" - ", array_reverse(explode(" - ", $tripDetails->Route->route_name)));
                                $perTrip['route_desc'] = $tripDetails->Route->route_number . ' ' . $routeNameOut;
                                $perTrip['trip_type'] = 'OB';

                            } else {
                                $perTrip['route_desc'] = $tripDetails->Route->route_number . ' ' . $tripDetails->Route->route_name;
                                $perTrip['trip_type'] = 'IB';
                            }

                            $perTrip['trip_details'] = 'T' . $tripDetails->id . ' - ' .
                                $all_date . ' ' . $tripDetails->start_trip . ' - ' .
                                $all_date . ' ' . $tripDetails->end_trip;

                            $totalBy = $totalCash + $totalCard + $totalTouchNGo + $totalCancelled;

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
        return Excel::download(new SalesByBus($salesByBus, $companyName), 'SalesByBus.xlsx');
    }

    /*public function print()
    {
        //Set the header
        $row = [[
            "id"=>'ID',
            "nickname"=>'User nickname',
            "gender_text"=>'Gender',
            "mobile"=>'mobile phone number',
            "addtime"=>'create time'
        ]];

        $list=[
                0=>[
                "id"=>'1',
                "nickname"=>'Zhang San',
                "gender_text"=>'Male',
                "mobile"=>'18812345678',
                "addtime"=>'2019-11-21 '
            ],
                2=>[
                "id"=>'2',
                "nickname"=>'Li Si',
                "gender_text"=>'Female',
                "mobile"=>'18812349999',
                "addtime"=>'2019-11-21 '
            ]
        ];

        //Execute export
        $data = $list;//Data to be imported
        $header = $row;//Export header
        $excel = new SalesByBus($data, $header,'export sheetName');
        $excel->setColumnWidth(['B' => 40,'C' => 40]);
        $excel->setRowHeight([1 => 40, 2 => 50]);
        $excel->setFont(['A1:Z1265' =>'Song Ti']);
        $excel->setFontSize(['A1:I1' => 14,'A2:Z1265' => 10]);
        $excel->setBold(['A1:Z2' => true]);
        $excel->setBackground(['A1:A1' => '808080','C1:C1' => '708080']);
        $excel->setMergeCells(['A1:I1']);
        $excel->setBorders(['A2:D5' =>'#000000']);
        return Excel::download($excel,'Export file.xlsx');
        //$export = new SalesByBus([$arr]);
        //return Excel::download($export,'Sales Report By Bus.xlsx');
    }*/

    /*public function print()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'bus_id' => ['required', 'int'],
        ])->validate();

        $out->writeln("dateFrom:" . $validatedData['dateFrom']);
        $out->writeln("dateTo:" . $validatedData['dateTo']);
        $out->writeln("bus_id:" . $validatedData['bus_id']);

        $dateRange = CarbonPeriod::create($validatedData['dateFrom'], $validatedData['dateTo']);
        $allRoutes = Route::all();
        //dd($dateRange->toArray());

        $row = [[
            "bus_registration_number" => 'Bus Registration Number',
            "creation_by" => 'Creation By',
            "closed_by" => 'Closed By',
            "route_description" => 'Route Description',
            "system_trip_details" => 'System Trip Details',
            "no" => 'No',
            "sales_date" => 'Sales Date',
            "ticket_no" => 'Ticket No',
            "from" => 'From',
            "to" => 'To',
            "type" => 'Type',
            "cash" => 'Cash',
            "card" => 'Card',
            "touch_n_go" => 'Touch & Go',
            "cancelled" => 'Cancelled',
            "by" => 'By',
            "total_sales" => 'Total Sales'
        ]];
        /*$arr[] = array(
            'Bus Registration Number',
            'Creation By',
            'Closed By',
            'Route Description',
            'System Trip Details',
            'No',
            'Sales Date',
            'Ticket No',
            'From',
            'To',
            'Type',
            'Cash',
            'Card',
            'Touch & Go',
            'Cancelled',
            'By',
            'Total Sales'
        );
        $i=0;

        //foreach ($allRoutes as $allRoute){

            //$stagePerRoute = Stage::where('route_id', $allRoute->id)->get();
            $stagePerRoute = Stage::all();
            $total = 0.0;

            $listCollect = collect();

            foreach ($stagePerRoute as $stage) {

                $tripDetails = $stage->stage_name . '-' . $stage->stage_name;

                $i = $i++;
                //$total = $total + $stage->no_of_km;

                $list['bus_registration_number'] = $stage->stage_name;
                $list['creation_by'] = $stage->stage_name;
                $list['closed_by'] = $stage->stage_name;
                $list['route_description'] = $stage->stage_name;
                $list['system_trip_details'] = $tripDetails;
                $list['no'] = $i;
                $list['sales_date'] = $stage->stage_name;
                $list['ticket_no'] = $stage->stage_name;
                $list['from'] = $stage->stage_name;
                $list['to'] = $stage->stage_name;
                $list['type'] = 'Type';
                $list['cash'] = 'Cash';
                $list['card'] = 'Card';
                $list['touch_n_go'] = 'Touch & Go';
                $list['cancelled'] = 'Cancelled';
                $list['by'] = 'By';
                $list['total_sales'] = 'Total Sales';

                $listCollect->add($list);
            }
        //}

        //dd($list);

        $data = $listCollect;//Data to be imported
        $header = $row;//Export header
        $excel = new SalesByBus($data, $header,'export sheetName');
        $excel->setColumnWidth(['B' => 40,'C' => 40]);
        $excel->setRowHeight([1 => 40, 2 => 50]);
        $excel->setFont(['A1:Z1265' =>'Song Ti']);
        $excel->setFontSize(['A1:I1' => 14,'A2:Z1265' => 10]);
        $excel->setBold(['A1:Z2' => true]);
        $excel->setBackground(['A1:A1' => '808080','C1:C1' => '708080']);
        //$excel->setMergeCells(['A1:I1']);
        $excel->setBorders(['A2:D5' =>'#000000']);
        return Excel::download($excel,'Export file.xlsx');
        //$export = new SalesByBus([$arr]);
        //return Excel::download($export,'Sales Report By Bus.xlsx');

        /*return (function ($print) use($arr){
            $print->setTitle('Sales Report By Bus');
            $print->sheet('Sales Report By Bus',function($sheet) use ($arr){
                $sheet->fromArray($arr, null, 'A1', false, false);
            });
        })->download('Sales Report By Bus.xlsx');
    }*/

    /*public function print()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'bus_id' => ['required', 'int'],
        ])->validate();

        $out->writeln("dateFrom:" . $validatedData['dateFrom']);
        $out->writeln("dateTo:" . $validatedData['dateTo']);
        $out->writeln("bus_id:" . $validatedData['bus_id']);

        $dateRange = CarbonPeriod::create($validatedData['dateFrom'], $validatedData['dateTo']);
        //dd($dateRange->toArray());

        $arr[] = array(
            'Bus Registration Number',
            'Creation By',
            'Closed By',
            'Route Description',
            'System Trip Details',
            'No',
            'Sales Date',
            'Ticket No',
            'From',
            'To',
            'Type',
            'Cash',
            'Card',
            'Touch & Go',
            'Cancelled',
            'By',
            'Total Sales'
        );
        $i=0;

        foreach ($dateRange as $date){

            $ticketSales = TicketSalesTransaction::where('bus_id', $validatedData['bus_id'])
                ->where('sales_date', $date)
                ->get();

            foreach ($ticketSales as $ticketSale) {

                $out->writeln('Bus Registration Number: ' . $dataArr->bus_registration_number);
                $out->writeln('Company Name: ' . $dataArr->company->company_name);
                $out->writeln('Sector Name: ' . $dataArr->sector->sector_name);
                $out->writeln('Route Name: ' . $dataArr->route->route_name);

                $tripDetails = $ticketSale->trip_details->start_trip . '-' . $ticketSale->trip_details->end_trip;

                $i = $i++;
                $total = 0.0;

                if($ticketSale->fare_type == 1){
                    $arr['Cash'] = $ticketSale->actual_amount;
                    $total = $total + $ticketSale->actual_amount;
                } //Cash
                elseif($ticketSale->fare_type == 2){
                    $arr['Card'] = $ticketSale->actual_amount;
                    $total = $total + $ticketSale->actual_amount;
                }//Card
                elseif($ticketSale->fare_type == 3){
                    $arr['Touch & Go'] = $ticketSale->actual_amount;
                    $total = $total + $ticketSale->actual_amount;
                }//Touch & Go

                $arr[] = array(
                    'No' => $i,
                    'Bus Registration Number' => $ticketSale->bus->bus_registration_number,
                    'Creation By' => $ticketSale->sales_date,
                    'Closed By' => $ticketSale->sales_date,
                    'Route Description' => $ticketSale->route->route_name,
                    'System Trip Details' => $tripDetails,
                    'Sales Date' => $ticketSale->sales_date,
                    'Ticket No' => '',
                    'From' => $ticketSale->fromstage_stage_id,
                    'To' => $ticketSale->tostage_stage_id
                );
            }
            $arr['Total Sales'] = $total;
        }

        $export = new SalesByBus([$arr]);
        return Excel::download($export,'Sales Report By Bus.xlsx');

        /*return (function ($print) use($arr){
            $print->setTitle('Sales Report By Bus');
            $print->sheet('Sales Report By Bus',function($sheet) use ($arr){
                $sheet->fromArray($arr, null, 'A1', false, false);
            });
        })->download('Sales Report By Bus.xlsx');
    }*/

}
