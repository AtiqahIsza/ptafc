<?php

namespace App\Http\Livewire;

use App\Exports\SalesByDriver;
use App\Models\BusDriver;
use App\Models\Company;
use App\Models\Route;
use App\Models\TicketSalesTransaction;
use App\Models\TripDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Console\Output\ConsoleOutput;

class ReportSalesByDriver extends Component
{
    public $drivers;
    public $companies;
    public $selectedCompany;
    public $state = [];

    public function render()
    {
        $this->companies = Company::all();
        return view('livewire.report-sales-by-driver');
    }

    public function mount()
    {
        $this->companies = collect();
        $this->drivers=collect();
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->drivers = BusDriver::where('company_id', $company)->get();
        }
    }

    /**Without all company all driver**/
    public function print()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE salesByDriver print()");

        $validatedData = Validator::make($this->state, [
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'driver_id' => ['int'],
        ])->validate();

        $out->writeln($validatedData['dateFrom']);
        $out->writeln($validatedData['dateTo']);

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)) {
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        $salesByDriver = collect();

        if($this->selectedCompany){
            $companyDetails = Company::where('id', $this->selectedCompany)->first();
            $companyName = $companyDetails->company_name;
            $grandCountAdultCashCard =0;
            $grandSalesAdultCashCard =0;
            $grandCountConcessionCashCard =0;
            $grandSalesConcessionCashCard =0;
            $grandTotalCountAdultCashCard =0;
            $grandTotalSalesAdultCashCard =0;
            $grandCountAdultTouchNGo =0;
            $grandSalesAdultTouchNGo =0;
            $grandCountConcessionTouchNGo =0;
            $grandSalesConcessionTouchNGo =0;
            $grandTotalCountAdultTouchNGo =0;
            $grandTotalSalesAdultTouchNGo =0;
            $grandTotalCountAdult =0;
            $grandTotalSalesAdult =0;
            $grandTotalCountConcession =0;
            $grandTotalSalesConcession =0;
            $grandTotalCount =0;
            $grandTotalSales =0;
            $data_per_company = [];

            //salesByDriver specific driver specific company
            if(!empty($this->state['driver_id'])) {
                $selectedDriver = BusDriver::where('id', $this->state['driver_id'])->first();
                $companyCountAdultCashCard = 0;
                $companySalesAdultCashCard = 0;
                $companyCountConcessionCashCard = 0;
                $companySalesConcessionCashCard = 0;
                $companyTotalCountAdultCashCard = 0;
                $companyTotalSalesAdultCashCard = 0;
                $companyCountAdultTouchNGo = 0;
                $companySalesAdultTouchNGo = 0;
                $companyCountConcessionTouchNGo = 0;
                $companySalesConcessionTouchNGo = 0;
                $companyTotalCountAdultTouchNGo = 0;
                $companyTotalSalesAdultTouchNGo = 0;
                $companyTotalCountAdult = 0;
                $companyTotalSalesAdult = 0;
                $companyTotalCountConcession = 0;
                $companyTotalSalesConcession = 0;
                $companyTotalCount = 0;
                $companyTotalSales = 0;
                $data_per_driver = [];

                //All trip drive by the driver
                $allTrips = TripDetail::where('driver_id', $selectedDriver->id)->get();
                $tripByDriver =[];
                $finalCountAdultCashCard = 0;
                $finalSalesAdultCashCard = 0;
                $finalCountConcessionCashCard = 0;
                $finalSalesConcessionCashCard = 0;
                $finalTotalCountAdultCashCard = 0;
                $finalTotalSalesAdultCashCard = 0;
                $finalCountAdultTouchNGo = 0;
                $finalSalesAdultTouchNGo = 0;
                $finalCountConcessionTouchNGo= 0;
                $finalSalesConcessionTouchNGo = 0;
                $finalTotalCountAdultTouchNGo = 0;
                $finalTotalSalesAdultTouchNGo = 0;
                $finalTotalCountAdult = 0;
                $finalTotalSalesAdult = 0;
                $finalTotalCountConcession = 0;
                $finalTotalSalesConcession = 0;
                $finalTotalCount = 0;
                $finalTotalSales = 0;
                $i=0;
                if (count($allTrips) > 0) {
                    foreach ($allTrips as $allTrip) {
                        $perTrip['company_name'] = $companyDetails->company_name;
                        $perTrip['bus_no'] = $allTrip->Bus->bus_registration_number;
                        $perTrip['creation_date'] = $allTrip->start_trip;
                        $perTrip['closed_by'] = $selectedDriver->driver_number;
                        $perTrip['closed_date'] = $allTrip->end_trip;

                        if($allTrip->trip_code==0){
                            $routeNameOut = implode(" - ", array_reverse(explode(" - ", $allTrip->Route->route_name)));
                            $perTrip['route_desc'] = $allTrip->Route->route_number . ' ' . $routeNameOut;
                        }else{
                            $perTrip['route_desc'] = $allTrip->Route->route_number . ' ' . $allTrip->Route->route_name;
                        }

                        $perTrip['trip_number'] = $allTrip->trip_number;
                        $perTrip['trip_no'] = 'T'. $allTrip->id;
                        $perTrip['status'] = 'Closed';

                        $allTickets = TicketSalesTransaction::where('trip_id', $allTrip->id)->get();

                        $countAdultCashCard = 0;
                        $countConcessionCashCard = 0;
                        $salesAdultCashCard = 0;
                        $salesConcessionCashCard = 0;
                        $countAdultTouchNGo = 0;
                        $salesAdultTouchNGo = 0;
                        $countConcessionTouchNGo = 0;
                        $salesConcessionTouchNGo = 0;
                        if (count($allTickets) > 0) {
                            foreach ($allTickets as $allTicket) {

                                //Cash || Card
                                if($allTicket->fare_type==0 || $allTicket->fare_type==1){
                                    if($allTicket->passenger_type==0){
                                        $countAdultCashCard++;
                                        $salesAdultCashCard += $allTicket->actual_amount;
                                    }else{
                                        $countConcessionCashCard++;
                                        $salesConcessionCashCard += $allTicket->actual_amount;
                                    }
                                }else{
                                    if($allTicket->passenger_type==0){
                                        $countAdultTouchNGo++;
                                        $salesAdultTouchNGo += $allTicket->actual_amount;
                                    }else{
                                        $countConcessionTouchNGo++;
                                        $salesConcessionTouchNGo += $allTicket->actual_amount;
                                    }
                                }
                            }
                        }
                        $perTrip['cash_adult_qty'] = $countAdultCashCard;
                        $perTrip['cash_adult_amount'] = $salesAdultCashCard;
                        $perTrip['cash_concession_qty'] = $countConcessionCashCard;
                        $perTrip['cash_concession_amount'] = $salesConcessionCashCard;
                        $perTrip['cash_total_qty'] = $countAdultCashCard + $countConcessionCashCard;
                        $perTrip['cash_total_amount'] = $salesAdultCashCard + $salesConcessionCashCard;
                        $perTrip['touchNGo_adult_qty'] = $countAdultTouchNGo;
                        $perTrip['touchNGo_adult_amount'] = $salesAdultTouchNGo;
                        $perTrip['touchNGo_concession_qty'] = $countConcessionTouchNGo;
                        $perTrip['touchNGo_concession_amount'] = $salesConcessionTouchNGo;
                        $perTrip['touchNGo_total_qty'] = $countAdultTouchNGo + $salesAdultTouchNGo;
                        $perTrip['touchNGo_total_amount'] = $countConcessionTouchNGo + $salesConcessionTouchNGo;
                        $perTrip['total_adult_qty'] = $perTrip['cash_adult_qty'] + $perTrip['touchNGo_adult_qty'];
                        $perTrip['total_adult_amount'] = $perTrip['cash_adult_amount'] + $perTrip['touchNGo_adult_amount'];
                        $perTrip['total_concession_qty'] = $perTrip['cash_concession_qty'] + $perTrip['touchNGo_concession_qty'];
                        $perTrip['total_concession_amount'] = $perTrip['cash_concession_amount'] + $perTrip['touchNGo_concession_amount'];
                        $perTrip['total_qty'] = $perTrip['cash_total_qty'] + $perTrip['touchNGo_total_qty'];
                        $perTrip['total_amount'] = $perTrip['cash_total_amount'] + $perTrip['touchNGo_total_amount'];

                        $finalCountAdultCashCard += $perTrip['cash_adult_qty'];
                        $finalSalesAdultCashCard += $perTrip['cash_adult_amount'];
                        $finalCountConcessionCashCard += $perTrip['cash_concession_qty'];
                        $finalSalesConcessionCashCard += $perTrip['cash_concession_amount'];
                        $finalTotalCountAdultCashCard += $perTrip['cash_total_qty'];
                        $finalTotalSalesAdultCashCard += $perTrip['cash_total_amount'];
                        $finalCountAdultTouchNGo += $perTrip['touchNGo_adult_qty'];
                        $finalSalesAdultTouchNGo += $perTrip['touchNGo_adult_amount'];
                        $finalCountConcessionTouchNGo += $perTrip['touchNGo_concession_qty'];
                        $finalSalesConcessionTouchNGo += $perTrip['touchNGo_concession_amount'];
                        $finalTotalCountAdultTouchNGo += $perTrip['touchNGo_total_qty'];
                        $finalTotalSalesAdultTouchNGo += $perTrip['touchNGo_total_amount'];
                        $finalTotalCountAdult += $perTrip['total_adult_qty'];
                        $finalTotalSalesAdult += $perTrip['total_adult_amount'];
                        $finalTotalCountConcession += $perTrip['total_concession_qty'];
                        $finalTotalSalesConcession +=  $perTrip['total_concession_amount'];
                        $finalTotalCount += $perTrip['total_qty'];
                        $finalTotalSales +=  $perTrip['total_amount'];

                        $tripByDriver[$i++] = $perTrip;
                    }
                }

                $total_driver['cash_adult_qty'] = $finalCountAdultCashCard;
                $total_driver['cash_adult_amount'] = $finalSalesAdultCashCard;
                $total_driver['cash_concession_qty'] = $finalCountConcessionCashCard;
                $total_driver['cash_concession_amount'] = $finalSalesConcessionCashCard;
                $total_driver['cash_total_qty'] = $finalTotalCountAdultCashCard;
                $total_driver['cash_total_amount'] = $finalTotalSalesAdultCashCard;
                $total_driver['touchNGo_adult_qty'] = $finalCountAdultTouchNGo;
                $total_driver['touchNGo_adult_amount'] = $finalSalesAdultTouchNGo;
                $total_driver['touchNGo_concession_qty'] = $finalCountConcessionTouchNGo;
                $total_driver['touchNGo_concession_amount'] = $finalSalesConcessionTouchNGo;
                $total_driver['touchNGo_total_qty'] = $finalTotalCountAdultTouchNGo;
                $total_driver['touchNGo_total_amount'] = $finalTotalSalesAdultTouchNGo;
                $total_driver['total_adult_qty'] = $finalTotalCountAdult;
                $total_driver['total_adult_amount'] = $finalTotalSalesAdult;
                $total_driver['total_concession_qty'] = $finalTotalCountConcession;
                $total_driver['total_concession_amount'] = $finalTotalSalesConcession;
                $total_driver['total_qty'] = $finalTotalCount;
                $total_driver['total_amount'] = $finalTotalSales;

                $perDriver['allTrips'] = $tripByDriver;
                if($tripByDriver==NULL){
                    $perDriver['total'] = [];
                    $data_per_driver[$selectedDriver->driver_number . ' - ' . $selectedDriver->driver_name] = [];
                }else{
                    $perDriver['total'] = $total_driver;
                    $data_per_driver[$selectedDriver->driver_number . ' - ' . $selectedDriver->driver_name] = $perDriver;
                }

                $companyCountAdultCashCard += $total_driver['cash_adult_qty'];
                $companySalesAdultCashCard += $total_driver['cash_adult_amount'];
                $companyCountConcessionCashCard += $total_driver['cash_concession_qty'];
                $companySalesConcessionCashCard += $total_driver['cash_concession_amount'];
                $companyTotalCountAdultCashCard += $total_driver['cash_total_qty'];
                $companyTotalSalesAdultCashCard += $total_driver['cash_total_amount'];
                $companyCountAdultTouchNGo += $total_driver['touchNGo_adult_qty'];
                $companySalesAdultTouchNGo += $total_driver['touchNGo_adult_amount'];
                $companyCountConcessionTouchNGo += $total_driver['touchNGo_concession_qty'];
                $companySalesConcessionTouchNGo +=  $total_driver['touchNGo_concession_amount'];
                $companyTotalCountAdultTouchNGo +=  $total_driver['touchNGo_total_qty'] ;
                $companyTotalSalesAdultTouchNGo +=  $total_driver['touchNGo_total_amount'];
                $companyTotalCountAdult +=  $total_driver['total_adult_qty'] ;
                $companyTotalSalesAdult +=  $total_driver['total_adult_amount'];
                $companyTotalCountConcession +=  $total_driver['total_concession_qty'];
                $companyTotalSalesConcession +=  $total_driver['total_concession_amount'];
                $companyTotalCount +=  $total_driver['total_qty'];
                $companyTotalSales +=  $total_driver['total_amount'];

                $total_company['cash_adult_qty'] = $companyCountAdultCashCard;
                $total_company['cash_adult_amount'] = $companySalesAdultCashCard;
                $total_company['cash_concession_qty'] = $companyCountConcessionCashCard;
                $total_company['cash_concession_amount'] = $companySalesConcessionCashCard;
                $total_company['cash_total_qty'] = $companyTotalCountAdultCashCard;
                $total_company['cash_total_amount'] = $companyTotalSalesAdultCashCard;
                $total_company['touchNGo_adult_qty'] = $companyCountAdultTouchNGo;
                $total_company['touchNGo_adult_amount'] = $companySalesAdultTouchNGo;
                $total_company['touchNGo_concession_qty'] = $companyCountConcessionTouchNGo;
                $total_company['touchNGo_concession_amount'] = $companySalesConcessionTouchNGo;
                $total_company['touchNGo_total_qty'] = $companyTotalCountAdultTouchNGo;
                $total_company['touchNGo_total_amount'] = $companyTotalSalesAdultTouchNGo;
                $total_company['total_adult_qty'] = $companyTotalCountAdult;
                $total_company['total_adult_amount'] = $companyTotalSalesAdult;
                $total_company['total_concession_qty'] = $companyTotalCountConcession;
                $total_company['total_concession_amount'] = $companyTotalSalesConcession;
                $total_company['total_qty'] = $companyTotalCount;
                $total_company['total_amount'] = $companyTotalSales;

                $perCompany['byCompany'] = $data_per_driver;
                if($data_per_driver==NULL){
                    $perCompany['total'] = [];
                }else{
                    $perCompany['total'] = $total_company;
                }
                $data_per_company[$companyDetails->company_name] = $perCompany;

                $grandCountAdultCashCard += $total_company['cash_adult_qty'];
                $grandSalesAdultCashCard += $total_company['cash_adult_amount'];
                $grandCountConcessionCashCard += $total_company['cash_concession_qty'];
                $grandSalesConcessionCashCard += $total_company['cash_concession_amount'];
                $grandTotalCountAdultCashCard += $total_company['cash_total_qty'];
                $grandTotalSalesAdultCashCard += $total_company['cash_total_amount'];
                $grandCountAdultTouchNGo += $total_company['touchNGo_adult_qty'];
                $grandSalesAdultTouchNGo += $total_company['touchNGo_adult_amount'];
                $grandCountConcessionTouchNGo += $total_company['touchNGo_concession_qty'];
                $grandSalesConcessionTouchNGo +=  $total_company['touchNGo_concession_amount'];
                $grandTotalCountAdultTouchNGo +=  $total_company['touchNGo_total_qty'] ;
                $grandTotalSalesAdultTouchNGo +=  $total_company['touchNGo_total_amount'];
                $grandTotalCountAdult +=  $total_company['total_adult_qty'] ;
                $grandTotalSalesAdult +=  $total_company['total_adult_amount'];
                $grandTotalCountConcession +=  $total_company['total_concession_qty'];
                $grandTotalSalesConcession +=  $total_company['total_concession_amount'];
                $grandTotalCount +=  $total_company['total_qty'];
                $grandTotalSales +=  $total_company['total_amount'];

                $grand['cash_adult_qty'] = $grandCountAdultCashCard;
                $grand['cash_adult_amount'] = $grandSalesAdultCashCard;
                $grand['cash_concession_qty'] = $grandCountConcessionCashCard;
                $grand['cash_concession_amount'] = $grandSalesConcessionCashCard;
                $grand['cash_total_qty'] = $grandTotalCountAdultCashCard;
                $grand['cash_total_amount'] = $grandTotalSalesAdultCashCard;
                $grand['touchNGo_adult_qty'] = $grandCountAdultTouchNGo;
                $grand['touchNGo_adult_amount'] = $grandSalesAdultTouchNGo;
                $grand['touchNGo_concession_qty'] = $grandCountConcessionTouchNGo;
                $grand['touchNGo_concession_amount'] = $grandSalesConcessionTouchNGo;
                $grand['touchNGo_total_qty'] = $grandTotalCountAdultTouchNGo;
                $grand['touchNGo_total_amount'] = $grandTotalSalesAdultTouchNGo;
                $grand['total_adult_qty'] = $grandTotalCountAdult;
                $grand['total_adult_amount'] = $grandTotalSalesAdult;
                $grand['total_concession_qty'] = $grandTotalCountConcession;
                $grand['total_concession_amount'] = $grandTotalSalesConcession;
                $grand['total_qty'] = $grandTotalCount;
                $grand['total_amount'] = $grandTotalSales;

                $grand_total['allCompanies'] = $data_per_company;
                $grand_total['grand_total'] = $grand;
                $data = $grand_total;

                $salesByDriver->add($data);
            }
            //salesByDriver all driver specific company
            else {
                $driverPerCompanies = BusDriver::where('company_id', $companyDetails->id)->get();
                $companyCountAdultCashCard = 0;
                $companySalesAdultCashCard = 0;
                $companyCountConcessionCashCard = 0;
                $companySalesConcessionCashCard = 0;
                $companyTotalCountAdultCashCard = 0;
                $companyTotalSalesAdultCashCard = 0;
                $companyCountAdultTouchNGo = 0;
                $companySalesAdultTouchNGo = 0;
                $companyCountConcessionTouchNGo = 0;
                $companySalesConcessionTouchNGo = 0;
                $companyTotalCountAdultTouchNGo = 0;
                $companyTotalSalesAdultTouchNGo = 0;
                $companyTotalCountAdult = 0;
                $companyTotalSalesAdult = 0;
                $companyTotalCountConcession = 0;
                $companyTotalSalesConcession = 0;
                $companyTotalCount = 0;
                $companyTotalSales = 0;
                $data_per_driver = [];

                if (count($driverPerCompanies) > 0) {
                    foreach ($driverPerCompanies as $driverPerCompany) {
                        //All trip drive by the driver
                        $allTrips = TripDetail::where('driver_id', $driverPerCompany->id)->get();
                        $tripByDriver = [];
                        $finalCountAdultCashCard = 0;
                        $finalSalesAdultCashCard = 0;
                        $finalCountConcessionCashCard = 0;
                        $finalSalesConcessionCashCard = 0;
                        $finalTotalCountAdultCashCard = 0;
                        $finalTotalSalesAdultCashCard = 0;
                        $finalCountAdultTouchNGo = 0;
                        $finalSalesAdultTouchNGo = 0;
                        $finalCountConcessionTouchNGo = 0;
                        $finalSalesConcessionTouchNGo = 0;
                        $finalTotalCountAdultTouchNGo = 0;
                        $finalTotalSalesAdultTouchNGo = 0;
                        $finalTotalCountAdult = 0;
                        $finalTotalSalesAdult = 0;
                        $finalTotalCountConcession = 0;
                        $finalTotalSalesConcession = 0;
                        $finalTotalCount = 0;
                        $finalTotalSales = 0;
                        $i = 0;
                        if (count($allTrips) > 0) {
                            foreach ($allTrips as $allTrip) {
                                $perTrip['company_name'] = $companyDetails->company_name;
                                $perTrip['bus_no'] = $allTrip->Bus->bus_registration_number;
                                $perTrip['creation_date'] = $allTrip->start_trip;
                                $perTrip['closed_by'] = $driverPerCompany->driver_number;
                                $perTrip['closed_date'] = $allTrip->end_trip;

                                if ($allTrip->trip_code == 0) {
                                    $routeNameOut = implode(" - ", array_reverse(explode(" - ", $allTrip->Route->route_name)));
                                    $perTrip['route_desc'] = $allTrip->Route->route_number . ' ' . $routeNameOut;
                                } else {
                                    $perTrip['route_desc'] = $allTrip->Route->route_number . ' ' . $allTrip->Route->route_name;
                                }

                                $perTrip['trip_number'] = $allTrip->trip_number;
                                $perTrip['trip_no'] = 'T' . $allTrip->id;
                                $perTrip['status'] = 'Closed';

                                $allTickets = TicketSalesTransaction::where('trip_id', $allTrip->id)->get();

                                $countAdultCashCard = 0;
                                $countConcessionCashCard = 0;
                                $salesAdultCashCard = 0;
                                $salesConcessionCashCard = 0;
                                $countAdultTouchNGo = 0;
                                $salesAdultTouchNGo = 0;
                                $countConcessionTouchNGo = 0;
                                $salesConcessionTouchNGo = 0;
                                if (count($allTickets) > 0) {
                                    foreach ($allTickets as $allTicket) {

                                        //Cash || Card
                                        if ($allTicket->fare_type == 0 || $allTicket->fare_type == 1) {
                                            if ($allTicket->passenger_type == 0) {
                                                $countAdultCashCard++;
                                                $salesAdultCashCard += $allTicket->actual_amount;
                                            } else {
                                                $countConcessionCashCard++;
                                                $salesConcessionCashCard += $allTicket->actual_amount;
                                            }
                                        } else {
                                            if ($allTicket->passenger_type == 0) {
                                                $countAdultTouchNGo++;
                                                $salesAdultTouchNGo += $allTicket->actual_amount;
                                            } else {
                                                $countConcessionTouchNGo++;
                                                $salesConcessionTouchNGo += $allTicket->actual_amount;
                                            }
                                        }
                                    }
                                }
                                $perTrip['cash_adult_qty'] = $countAdultCashCard;
                                $perTrip['cash_adult_amount'] = $salesAdultCashCard;
                                $perTrip['cash_concession_qty'] = $countConcessionCashCard;
                                $perTrip['cash_concession_amount'] = $salesConcessionCashCard;
                                $perTrip['cash_total_qty'] = $countAdultCashCard + $countConcessionCashCard;
                                $perTrip['cash_total_amount'] = $salesAdultCashCard + $salesConcessionCashCard;
                                $perTrip['touchNGo_adult_qty'] = $countAdultTouchNGo;
                                $perTrip['touchNGo_adult_amount'] = $salesAdultTouchNGo;
                                $perTrip['touchNGo_concession_qty'] = $countConcessionTouchNGo;
                                $perTrip['touchNGo_concession_amount'] = $salesConcessionTouchNGo;
                                $perTrip['touchNGo_total_qty'] = $countAdultTouchNGo + $salesAdultTouchNGo;
                                $perTrip['touchNGo_total_amount'] = $countConcessionTouchNGo + $salesConcessionTouchNGo;
                                $perTrip['total_adult_qty'] = $perTrip['cash_adult_qty'] + $perTrip['touchNGo_adult_qty'];
                                $perTrip['total_adult_amount'] = $perTrip['cash_adult_amount'] + $perTrip['touchNGo_adult_amount'];
                                $perTrip['total_concession_qty'] = $perTrip['cash_concession_qty'] + $perTrip['touchNGo_concession_qty'];
                                $perTrip['total_concession_amount'] = $perTrip['cash_concession_amount'] + $perTrip['touchNGo_concession_amount'];
                                $perTrip['total_qty'] = $perTrip['cash_total_qty'] + $perTrip['touchNGo_total_qty'];
                                $perTrip['total_amount'] = $perTrip['cash_total_amount'] + $perTrip['touchNGo_total_amount'];

                                $finalCountAdultCashCard += $perTrip['cash_adult_qty'];
                                $finalSalesAdultCashCard += $perTrip['cash_adult_amount'];
                                $finalCountConcessionCashCard += $perTrip['cash_concession_qty'];
                                $finalSalesConcessionCashCard += $perTrip['cash_concession_amount'];
                                $finalTotalCountAdultCashCard += $perTrip['cash_total_qty'];
                                $finalTotalSalesAdultCashCard += $perTrip['cash_total_amount'];
                                $finalCountAdultTouchNGo += $perTrip['touchNGo_adult_qty'];
                                $finalSalesAdultTouchNGo += $perTrip['touchNGo_adult_amount'];
                                $finalCountConcessionTouchNGo += $perTrip['touchNGo_concession_qty'];
                                $finalSalesConcessionTouchNGo += $perTrip['touchNGo_concession_amount'];
                                $finalTotalCountAdultTouchNGo += $perTrip['touchNGo_total_qty'];
                                $finalTotalSalesAdultTouchNGo += $perTrip['touchNGo_total_amount'];
                                $finalTotalCountAdult += $perTrip['total_adult_qty'];
                                $finalTotalSalesAdult += $perTrip['total_adult_amount'];
                                $finalTotalCountConcession += $perTrip['total_concession_qty'];
                                $finalTotalSalesConcession += $perTrip['total_concession_amount'];
                                $finalTotalCount += $perTrip['total_qty'];
                                $finalTotalSales += $perTrip['total_amount'];

                                $tripByDriver[$i++] = $perTrip;
                            }

                        }

                        $total_driver['cash_adult_qty'] = $finalCountAdultCashCard;
                        $total_driver['cash_adult_amount'] = $finalSalesAdultCashCard;
                        $total_driver['cash_concession_qty'] = $finalCountConcessionCashCard;
                        $total_driver['cash_concession_amount'] = $finalSalesConcessionCashCard;
                        $total_driver['cash_total_qty'] = $finalTotalCountAdultCashCard;
                        $total_driver['cash_total_amount'] = $finalTotalSalesAdultCashCard;
                        $total_driver['touchNGo_adult_qty'] = $finalCountAdultTouchNGo;
                        $total_driver['touchNGo_adult_amount'] = $finalSalesAdultTouchNGo;
                        $total_driver['touchNGo_concession_qty'] = $finalCountConcessionTouchNGo;
                        $total_driver['touchNGo_concession_amount'] = $finalSalesConcessionTouchNGo;
                        $total_driver['touchNGo_total_qty'] = $finalTotalCountAdultTouchNGo;
                        $total_driver['touchNGo_total_amount'] = $finalTotalSalesAdultTouchNGo;
                        $total_driver['total_adult_qty'] = $finalTotalCountAdult;
                        $total_driver['total_adult_amount'] = $finalTotalSalesAdult;
                        $total_driver['total_concession_qty'] = $finalTotalCountConcession;
                        $total_driver['total_concession_amount'] = $finalTotalSalesConcession;
                        $total_driver['total_qty'] = $finalTotalCount;
                        $total_driver['total_amount'] = $finalTotalSales;

                        $perDriver['allTrips'] = $tripByDriver;
                        if ($tripByDriver == NULL) {
                            $perDriver['total'] = [];
                            $data_per_driver[$driverPerCompany->driver_number . ' - ' . $driverPerCompany->driver_name] = [];
                        } else {
                            $perDriver['total'] = $total_driver;
                            $data_per_driver[$driverPerCompany->driver_number . ' - ' . $driverPerCompany->driver_name] = $perDriver;
                        }

                        $companyCountAdultCashCard += $total_driver['cash_adult_qty'];
                        $companySalesAdultCashCard += $total_driver['cash_adult_amount'];
                        $companyCountConcessionCashCard += $total_driver['cash_concession_qty'];
                        $companySalesConcessionCashCard += $total_driver['cash_concession_amount'];
                        $companyTotalCountAdultCashCard += $total_driver['cash_total_qty'];
                        $companyTotalSalesAdultCashCard += $total_driver['cash_total_amount'];
                        $companyCountAdultTouchNGo += $total_driver['touchNGo_adult_qty'];
                        $companySalesAdultTouchNGo += $total_driver['touchNGo_adult_amount'];
                        $companyCountConcessionTouchNGo += $total_driver['touchNGo_concession_qty'];
                        $companySalesConcessionTouchNGo += $total_driver['touchNGo_concession_amount'];
                        $companyTotalCountAdultTouchNGo += $total_driver['touchNGo_total_qty'];
                        $companyTotalSalesAdultTouchNGo += $total_driver['touchNGo_total_amount'];
                        $companyTotalCountAdult += $total_driver['total_adult_qty'];
                        $companyTotalSalesAdult += $total_driver['total_adult_amount'];
                        $companyTotalCountConcession += $total_driver['total_concession_qty'];
                        $companyTotalSalesConcession += $total_driver['total_concession_amount'];
                        $companyTotalCount += $total_driver['total_qty'];
                        $companyTotalSales += $total_driver['total_amount'];
                    }
                }
                $total_company['cash_adult_qty'] = $companyCountAdultCashCard;
                $total_company['cash_adult_amount'] = $companySalesAdultCashCard;
                $total_company['cash_concession_qty'] = $companyCountConcessionCashCard;
                $total_company['cash_concession_amount'] = $companySalesConcessionCashCard;
                $total_company['cash_total_qty'] = $companyTotalCountAdultCashCard;
                $total_company['cash_total_amount'] = $companyTotalSalesAdultCashCard;
                $total_company['touchNGo_adult_qty'] = $companyCountAdultTouchNGo;
                $total_company['touchNGo_adult_amount'] = $companySalesAdultTouchNGo;
                $total_company['touchNGo_concession_qty'] = $companyCountConcessionTouchNGo;
                $total_company['touchNGo_concession_amount'] = $companySalesConcessionTouchNGo;
                $total_company['touchNGo_total_qty'] = $companyTotalCountAdultTouchNGo;
                $total_company['touchNGo_total_amount'] = $companyTotalSalesAdultTouchNGo;
                $total_company['total_adult_qty'] = $companyTotalCountAdult;
                $total_company['total_adult_amount'] = $companyTotalSalesAdult;
                $total_company['total_concession_qty'] = $companyTotalCountConcession;
                $total_company['total_concession_amount'] = $companyTotalSalesConcession;
                $total_company['total_qty'] = $companyTotalCount;
                $total_company['total_amount'] = $companyTotalSales;

                $perCompany['byCompany'] = $data_per_driver;
                if ($data_per_driver == NULL) {
                    $perCompany['total'] = [];
                } else {
                    $perCompany['total'] = $total_company;
                }
                $data_per_company[$companyDetails->company_name] = $perCompany;

                $grandCountAdultCashCard += $total_company['cash_adult_qty'];
                $grandSalesAdultCashCard += $total_company['cash_adult_amount'];
                $grandCountConcessionCashCard += $total_company['cash_concession_qty'];
                $grandSalesConcessionCashCard += $total_company['cash_concession_amount'];
                $grandTotalCountAdultCashCard += $total_company['cash_total_qty'];
                $grandTotalSalesAdultCashCard += $total_company['cash_total_amount'];
                $grandCountAdultTouchNGo += $total_company['touchNGo_adult_qty'];
                $grandSalesAdultTouchNGo += $total_company['touchNGo_adult_amount'];
                $grandCountConcessionTouchNGo += $total_company['touchNGo_concession_qty'];
                $grandSalesConcessionTouchNGo += $total_company['touchNGo_concession_amount'];
                $grandTotalCountAdultTouchNGo += $total_company['touchNGo_total_qty'];
                $grandTotalSalesAdultTouchNGo += $total_company['touchNGo_total_amount'];
                $grandTotalCountAdult += $total_company['total_adult_qty'];
                $grandTotalSalesAdult += $total_company['total_adult_amount'];
                $grandTotalCountConcession += $total_company['total_concession_qty'];
                $grandTotalSalesConcession += $total_company['total_concession_amount'];
                $grandTotalCount += $total_company['total_qty'];
                $grandTotalSales += $total_company['total_amount'];

                $grand['cash_adult_qty'] = $grandCountAdultCashCard;
                $grand['cash_adult_amount'] = $grandSalesAdultCashCard;
                $grand['cash_concession_qty'] = $grandCountConcessionCashCard;
                $grand['cash_concession_amount'] = $grandSalesConcessionCashCard;
                $grand['cash_total_qty'] = $grandTotalCountAdultCashCard;
                $grand['cash_total_amount'] = $grandTotalSalesAdultCashCard;
                $grand['touchNGo_adult_qty'] = $grandCountAdultTouchNGo;
                $grand['touchNGo_adult_amount'] = $grandSalesAdultTouchNGo;
                $grand['touchNGo_concession_qty'] = $grandCountConcessionTouchNGo;
                $grand['touchNGo_concession_amount'] = $grandSalesConcessionTouchNGo;
                $grand['touchNGo_total_qty'] = $grandTotalCountAdultTouchNGo;
                $grand['touchNGo_total_amount'] = $grandTotalSalesAdultTouchNGo;
                $grand['total_adult_qty'] = $grandTotalCountAdult;
                $grand['total_adult_amount'] = $grandTotalSalesAdult;
                $grand['total_concession_qty'] = $grandTotalCountConcession;
                $grand['total_concession_amount'] = $grandTotalSalesConcession;
                $grand['total_qty'] = $grandTotalCount;
                $grand['total_amount'] = $grandTotalSales;

                $grand_total['allCompanies'] = $data_per_company;
                $grand_total['grand_total'] = $grand;
                $data = $grand_total;

                $salesByDriver->add($data);
            }
        }
        return Excel::download(new SalesByDriver($salesByDriver), 'SalesByDriver.xlsx');
    }

    /**With all company all driver**/
    /*public function print()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE salesByDriver print()");

        $validatedData = Validator::make($this->state, [
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'driver_id' => ['int'],
        ])->validate();

        $out->writeln($validatedData['dateFrom']);
        $out->writeln($validatedData['dateTo']);

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)) {
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }

        $salesByDriver = collect();

        if($this->selectedCompany){
            $companyDetails = Company::where('id', $this->selectedCompany)->first();
            $companyName = $companyDetails->company_name;
            $grandCountAdultCashCard =0;
            $grandSalesAdultCashCard =0;
            $grandCountConcessionCashCard =0;
            $grandSalesConcessionCashCard =0;
            $grandTotalCountAdultCashCard =0;
            $grandTotalSalesAdultCashCard =0;
            $grandCountAdultTouchNGo =0;
            $grandSalesAdultTouchNGo =0;
            $grandCountConcessionTouchNGo =0;
            $grandSalesConcessionTouchNGo =0;
            $grandTotalCountAdultTouchNGo =0;
            $grandTotalSalesAdultTouchNGo =0;
            $grandTotalCountAdult =0;
            $grandTotalSalesAdult =0;
            $grandTotalCountConcession =0;
            $grandTotalSalesConcession =0;
            $grandTotalCount =0;
            $grandTotalSales =0;
            $data_per_company = [];

            //salesByDriver specific driver specific company
            if(!empty($this->state['driver_id'])) {
                $selectedDriver = BusDriver::where('id', $this->state['driver_id'])->first();
                $companyCountAdultCashCard = 0;
                $companySalesAdultCashCard = 0;
                $companyCountConcessionCashCard = 0;
                $companySalesConcessionCashCard = 0;
                $companyTotalCountAdultCashCard = 0;
                $companyTotalSalesAdultCashCard = 0;
                $companyCountAdultTouchNGo = 0;
                $companySalesAdultTouchNGo = 0;
                $companyCountConcessionTouchNGo = 0;
                $companySalesConcessionTouchNGo = 0;
                $companyTotalCountAdultTouchNGo = 0;
                $companyTotalSalesAdultTouchNGo = 0;
                $companyTotalCountAdult = 0;
                $companyTotalSalesAdult = 0;
                $companyTotalCountConcession = 0;
                $companyTotalSalesConcession = 0;
                $companyTotalCount = 0;
                $companyTotalSales = 0;
                $data_per_driver = [];

                //All trip drive by the driver
                $allTrips = TripDetail::where('driver_id', $selectedDriver->id)->get();
                $tripByDriver =[];
                $finalCountAdultCashCard = 0;
                $finalSalesAdultCashCard = 0;
                $finalCountConcessionCashCard = 0;
                $finalSalesConcessionCashCard = 0;
                $finalTotalCountAdultCashCard = 0;
                $finalTotalSalesAdultCashCard = 0;
                $finalCountAdultTouchNGo = 0;
                $finalSalesAdultTouchNGo = 0;
                $finalCountConcessionTouchNGo= 0;
                $finalSalesConcessionTouchNGo = 0;
                $finalTotalCountAdultTouchNGo = 0;
                $finalTotalSalesAdultTouchNGo = 0;
                $finalTotalCountAdult = 0;
                $finalTotalSalesAdult = 0;
                $finalTotalCountConcession = 0;
                $finalTotalSalesConcession = 0;
                $finalTotalCount = 0;
                $finalTotalSales = 0;
                $i=0;
                if (count($allTrips) > 0) {
                    foreach ($allTrips as $allTrip) {
                        $perTrip['company_name'] = $companyDetails->company_name;
                        $perTrip['bus_no'] = $allTrip->Bus->bus_registration_number;
                        $perTrip['creation_date'] = $allTrip->start_trip;
                        $perTrip['closed_by'] = $selectedDriver->driver_number;
                        $perTrip['closed_date'] = $allTrip->end_trip;

                        if($allTrip->trip_code==0){
                            $routeNameOut = implode(" - ", array_reverse(explode(" - ", $allTrip->Route->route_name)));
                            $perTrip['route_desc'] = $allTrip->Route->route_number . ' ' . $routeNameOut;
                        }else{
                            $perTrip['route_desc'] = $allTrip->Route->route_number . ' ' . $allTrip->Route->route_name;
                        }

                        $perTrip['trip_number'] = $allTrip->trip_number;
                        $perTrip['trip_no'] = 'T'. $allTrip->id;
                        $perTrip['status'] = 'Closed';

                        $allTickets = TicketSalesTransaction::where('trip_id', $allTrip->id)->get();

                        $countAdultCashCard = 0;
                        $countConcessionCashCard = 0;
                        $salesAdultCashCard = 0;
                        $salesConcessionCashCard = 0;
                        $countAdultTouchNGo = 0;
                        $salesAdultTouchNGo = 0;
                        $countConcessionTouchNGo = 0;
                        $salesConcessionTouchNGo = 0;
                        if (count($allTickets) > 0) {
                            foreach ($allTickets as $allTicket) {

                                //Cash || Card
                                if($allTicket->fare_type==0 || $allTicket->fare_type==1){
                                    if($allTicket->passenger_type==0){
                                        $countAdultCashCard++;
                                        $salesAdultCashCard += $allTicket->actual_amount;
                                    }else{
                                        $countConcessionCashCard++;
                                        $salesConcessionCashCard += $allTicket->actual_amount;
                                    }
                                }else{
                                    if($allTicket->passenger_type==0){
                                        $countAdultTouchNGo++;
                                        $salesAdultTouchNGo += $allTicket->actual_amount;
                                    }else{
                                        $countConcessionTouchNGo++;
                                        $salesConcessionTouchNGo += $allTicket->actual_amount;
                                    }
                                }
                            }
                        }
                        $perTrip['cash_adult_qty'] = $countAdultCashCard;
                        $perTrip['cash_adult_amount'] = $salesAdultCashCard;
                        $perTrip['cash_concession_qty'] = $countConcessionCashCard;
                        $perTrip['cash_concession_amount'] = $salesConcessionCashCard;
                        $perTrip['cash_total_qty'] = $countAdultCashCard + $countConcessionCashCard;
                        $perTrip['cash_total_amount'] = $salesAdultCashCard + $salesConcessionCashCard;
                        $perTrip['touchNGo_adult_qty'] = $countAdultTouchNGo;
                        $perTrip['touchNGo_adult_amount'] = $salesAdultTouchNGo;
                        $perTrip['touchNGo_concession_qty'] = $countConcessionTouchNGo;
                        $perTrip['touchNGo_concession_amount'] = $salesConcessionTouchNGo;
                        $perTrip['touchNGo_total_qty'] = $countAdultTouchNGo + $salesAdultTouchNGo;
                        $perTrip['touchNGo_total_amount'] = $countConcessionTouchNGo + $salesConcessionTouchNGo;
                        $perTrip['total_adult_qty'] = $perTrip['cash_adult_qty'] + $perTrip['touchNGo_adult_qty'];
                        $perTrip['total_adult_amount'] = $perTrip['cash_adult_amount'] + $perTrip['touchNGo_adult_amount'];
                        $perTrip['total_concession_qty'] = $perTrip['cash_concession_qty'] + $perTrip['touchNGo_concession_qty'];
                        $perTrip['total_concession_amount'] = $perTrip['cash_concession_amount'] + $perTrip['touchNGo_concession_amount'];
                        $perTrip['total_qty'] = $perTrip['cash_total_qty'] + $perTrip['touchNGo_total_qty'];
                        $perTrip['total_amount'] = $perTrip['cash_total_amount'] + $perTrip['touchNGo_total_amount'];

                        $finalCountAdultCashCard += $perTrip['cash_adult_qty'];
                        $finalSalesAdultCashCard += $perTrip['cash_adult_amount'];
                        $finalCountConcessionCashCard += $perTrip['cash_concession_qty'];
                        $finalSalesConcessionCashCard += $perTrip['cash_concession_amount'];
                        $finalTotalCountAdultCashCard += $perTrip['cash_total_qty'];
                        $finalTotalSalesAdultCashCard += $perTrip['cash_total_amount'];
                        $finalCountAdultTouchNGo += $perTrip['touchNGo_adult_qty'];
                        $finalSalesAdultTouchNGo += $perTrip['touchNGo_adult_amount'];
                        $finalCountConcessionTouchNGo += $perTrip['touchNGo_concession_qty'];
                        $finalSalesConcessionTouchNGo += $perTrip['touchNGo_concession_amount'];
                        $finalTotalCountAdultTouchNGo += $perTrip['touchNGo_total_qty'];
                        $finalTotalSalesAdultTouchNGo += $perTrip['touchNGo_total_amount'];
                        $finalTotalCountAdult += $perTrip['total_adult_qty'];
                        $finalTotalSalesAdult += $perTrip['total_adult_amount'];
                        $finalTotalCountConcession += $perTrip['total_concession_qty'];
                        $finalTotalSalesConcession +=  $perTrip['total_concession_amount'];
                        $finalTotalCount += $perTrip['total_qty'];
                        $finalTotalSales +=  $perTrip['total_amount'];

                        $tripByDriver[$i++] = $perTrip;
                    }
                }

                $total_driver['cash_adult_qty'] = $finalCountAdultCashCard;
                $total_driver['cash_adult_amount'] = $finalSalesAdultCashCard;
                $total_driver['cash_concession_qty'] = $finalCountConcessionCashCard;
                $total_driver['cash_concession_amount'] = $finalSalesConcessionCashCard;
                $total_driver['cash_total_qty'] = $finalTotalCountAdultCashCard;
                $total_driver['cash_total_amount'] = $finalTotalSalesAdultCashCard;
                $total_driver['touchNGo_adult_qty'] = $finalCountAdultTouchNGo;
                $total_driver['touchNGo_adult_amount'] = $finalSalesAdultTouchNGo;
                $total_driver['touchNGo_concession_qty'] = $finalCountConcessionTouchNGo;
                $total_driver['touchNGo_concession_amount'] = $finalSalesConcessionTouchNGo;
                $total_driver['touchNGo_total_qty'] = $finalTotalCountAdultTouchNGo;
                $total_driver['touchNGo_total_amount'] = $finalTotalSalesAdultTouchNGo;
                $total_driver['total_adult_qty'] = $finalTotalCountAdult;
                $total_driver['total_adult_amount'] = $finalTotalSalesAdult;
                $total_driver['total_concession_qty'] = $finalTotalCountConcession;
                $total_driver['total_concession_amount'] = $finalTotalSalesConcession;
                $total_driver['total_qty'] = $finalTotalCount;
                $total_driver['total_amount'] = $finalTotalSales;

                $perDriver['allTrips'] = $tripByDriver;
                if($tripByDriver==NULL){
                    $perDriver['total'] = [];
                    $data_per_driver[$selectedDriver->driver_number . ' - ' . $selectedDriver->driver_name] = [];
                }else{
                    $perDriver['total'] = $total_driver;
                    $data_per_driver[$selectedDriver->driver_number . ' - ' . $selectedDriver->driver_name] = $perDriver;
                }

                $companyCountAdultCashCard += $total_driver['cash_adult_qty'];
                $companySalesAdultCashCard += $total_driver['cash_adult_amount'];
                $companyCountConcessionCashCard += $total_driver['cash_concession_qty'];
                $companySalesConcessionCashCard += $total_driver['cash_concession_amount'];
                $companyTotalCountAdultCashCard += $total_driver['cash_total_qty'];
                $companyTotalSalesAdultCashCard += $total_driver['cash_total_amount'];
                $companyCountAdultTouchNGo += $total_driver['touchNGo_adult_qty'];
                $companySalesAdultTouchNGo += $total_driver['touchNGo_adult_amount'];
                $companyCountConcessionTouchNGo += $total_driver['touchNGo_concession_qty'];
                $companySalesConcessionTouchNGo +=  $total_driver['touchNGo_concession_amount'];
                $companyTotalCountAdultTouchNGo +=  $total_driver['touchNGo_total_qty'] ;
                $companyTotalSalesAdultTouchNGo +=  $total_driver['touchNGo_total_amount'];
                $companyTotalCountAdult +=  $total_driver['total_adult_qty'] ;
                $companyTotalSalesAdult +=  $total_driver['total_adult_amount'];
                $companyTotalCountConcession +=  $total_driver['total_concession_qty'];
                $companyTotalSalesConcession +=  $total_driver['total_concession_amount'];
                $companyTotalCount +=  $total_driver['total_qty'];
                $companyTotalSales +=  $total_driver['total_amount'];

                $total_company['cash_adult_qty'] = $companyCountAdultCashCard;
                $total_company['cash_adult_amount'] = $companySalesAdultCashCard;
                $total_company['cash_concession_qty'] = $companyCountConcessionCashCard;
                $total_company['cash_concession_amount'] = $companySalesConcessionCashCard;
                $total_company['cash_total_qty'] = $companyTotalCountAdultCashCard;
                $total_company['cash_total_amount'] = $companyTotalSalesAdultCashCard;
                $total_company['touchNGo_adult_qty'] = $companyCountAdultTouchNGo;
                $total_company['touchNGo_adult_amount'] = $companySalesAdultTouchNGo;
                $total_company['touchNGo_concession_qty'] = $companyCountConcessionTouchNGo;
                $total_company['touchNGo_concession_amount'] = $companySalesConcessionTouchNGo;
                $total_company['touchNGo_total_qty'] = $companyTotalCountAdultTouchNGo;
                $total_company['touchNGo_total_amount'] = $companyTotalSalesAdultTouchNGo;
                $total_company['total_adult_qty'] = $companyTotalCountAdult;
                $total_company['total_adult_amount'] = $companyTotalSalesAdult;
                $total_company['total_concession_qty'] = $companyTotalCountConcession;
                $total_company['total_concession_amount'] = $companyTotalSalesConcession;
                $total_company['total_qty'] = $companyTotalCount;
                $total_company['total_amount'] = $companyTotalSales;

                $perCompany['byCompany'] = $data_per_driver;
                if($data_per_driver==NULL){
                    $perCompany['total'] = [];
                }else{
                    $perCompany['total'] = $total_company;
                }
                $data_per_company[$companyDetails->company_name] = $perCompany;

                $grandCountAdultCashCard += $total_company['cash_adult_qty'];
                $grandSalesAdultCashCard += $total_company['cash_adult_amount'];
                $grandCountConcessionCashCard += $total_company['cash_concession_qty'];
                $grandSalesConcessionCashCard += $total_company['cash_concession_amount'];
                $grandTotalCountAdultCashCard += $total_company['cash_total_qty'];
                $grandTotalSalesAdultCashCard += $total_company['cash_total_amount'];
                $grandCountAdultTouchNGo += $total_company['touchNGo_adult_qty'];
                $grandSalesAdultTouchNGo += $total_company['touchNGo_adult_amount'];
                $grandCountConcessionTouchNGo += $total_company['touchNGo_concession_qty'];
                $grandSalesConcessionTouchNGo +=  $total_company['touchNGo_concession_amount'];
                $grandTotalCountAdultTouchNGo +=  $total_company['touchNGo_total_qty'] ;
                $grandTotalSalesAdultTouchNGo +=  $total_company['touchNGo_total_amount'];
                $grandTotalCountAdult +=  $total_company['total_adult_qty'] ;
                $grandTotalSalesAdult +=  $total_company['total_adult_amount'];
                $grandTotalCountConcession +=  $total_company['total_concession_qty'];
                $grandTotalSalesConcession +=  $total_company['total_concession_amount'];
                $grandTotalCount +=  $total_company['total_qty'];
                $grandTotalSales +=  $total_company['total_amount'];

                $grand['cash_adult_qty'] = $grandCountAdultCashCard;
                $grand['cash_adult_amount'] = $grandSalesAdultCashCard;
                $grand['cash_concession_qty'] = $grandCountConcessionCashCard;
                $grand['cash_concession_amount'] = $grandSalesConcessionCashCard;
                $grand['cash_total_qty'] = $grandTotalCountAdultCashCard;
                $grand['cash_total_amount'] = $grandTotalSalesAdultCashCard;
                $grand['touchNGo_adult_qty'] = $grandCountAdultTouchNGo;
                $grand['touchNGo_adult_amount'] = $grandSalesAdultTouchNGo;
                $grand['touchNGo_concession_qty'] = $grandCountConcessionTouchNGo;
                $grand['touchNGo_concession_amount'] = $grandSalesConcessionTouchNGo;
                $grand['touchNGo_total_qty'] = $grandTotalCountAdultTouchNGo;
                $grand['touchNGo_total_amount'] = $grandTotalSalesAdultTouchNGo;
                $grand['total_adult_qty'] = $grandTotalCountAdult;
                $grand['total_adult_amount'] = $grandTotalSalesAdult;
                $grand['total_concession_qty'] = $grandTotalCountConcession;
                $grand['total_concession_amount'] = $grandTotalSalesConcession;
                $grand['total_qty'] = $grandTotalCount;
                $grand['total_amount'] = $grandTotalSales;

                $grand_total['allCompanies'] = $data_per_company;
                $grand_total['grand_total'] = $grand;
                $data = $grand_total;

                $salesByDriver->add($data);
            }
            //salesByDriver all driver specific company
            else {
                $driverPerCompanies = BusDriver::where('company_id', $companyDetails->id)->get();
                $companyCountAdultCashCard = 0;
                $companySalesAdultCashCard = 0;
                $companyCountConcessionCashCard = 0;
                $companySalesConcessionCashCard = 0;
                $companyTotalCountAdultCashCard = 0;
                $companyTotalSalesAdultCashCard = 0;
                $companyCountAdultTouchNGo = 0;
                $companySalesAdultTouchNGo = 0;
                $companyCountConcessionTouchNGo = 0;
                $companySalesConcessionTouchNGo = 0;
                $companyTotalCountAdultTouchNGo = 0;
                $companyTotalSalesAdultTouchNGo = 0;
                $companyTotalCountAdult = 0;
                $companyTotalSalesAdult = 0;
                $companyTotalCountConcession = 0;
                $companyTotalSalesConcession = 0;
                $companyTotalCount = 0;
                $companyTotalSales = 0;
                $data_per_driver = [];

                if (count($driverPerCompanies) > 0) {
                    foreach ($driverPerCompanies as $driverPerCompany) {
                        //All trip drive by the driver
                        $allTrips = TripDetail::where('driver_id', $driverPerCompany->id)->get();
                        $tripByDriver = [];
                        $finalCountAdultCashCard = 0;
                        $finalSalesAdultCashCard = 0;
                        $finalCountConcessionCashCard = 0;
                        $finalSalesConcessionCashCard = 0;
                        $finalTotalCountAdultCashCard = 0;
                        $finalTotalSalesAdultCashCard = 0;
                        $finalCountAdultTouchNGo = 0;
                        $finalSalesAdultTouchNGo = 0;
                        $finalCountConcessionTouchNGo = 0;
                        $finalSalesConcessionTouchNGo = 0;
                        $finalTotalCountAdultTouchNGo = 0;
                        $finalTotalSalesAdultTouchNGo = 0;
                        $finalTotalCountAdult = 0;
                        $finalTotalSalesAdult = 0;
                        $finalTotalCountConcession = 0;
                        $finalTotalSalesConcession = 0;
                        $finalTotalCount = 0;
                        $finalTotalSales = 0;
                        $i = 0;
                        if (count($allTrips) > 0) {
                            foreach ($allTrips as $allTrip) {
                                $perTrip['company_name'] = $companyDetails->company_name;
                                $perTrip['bus_no'] = $allTrip->Bus->bus_registration_number;
                                $perTrip['creation_date'] = $allTrip->start_trip;
                                $perTrip['closed_by'] = $driverPerCompany->driver_number;
                                $perTrip['closed_date'] = $allTrip->end_trip;

                                if ($allTrip->trip_code == 0) {
                                    $routeNameOut = implode(" - ", array_reverse(explode(" - ", $allTrip->Route->route_name)));
                                    $perTrip['route_desc'] = $allTrip->Route->route_number . ' ' . $routeNameOut;
                                } else {
                                    $perTrip['route_desc'] = $allTrip->Route->route_number . ' ' . $allTrip->Route->route_name;
                                }

                                $perTrip['trip_number'] = $allTrip->trip_number;
                                $perTrip['trip_no'] = 'T' . $allTrip->id;
                                $perTrip['status'] = 'Closed';

                                $allTickets = TicketSalesTransaction::where('trip_id', $allTrip->id)->get();

                                $countAdultCashCard = 0;
                                $countConcessionCashCard = 0;
                                $salesAdultCashCard = 0;
                                $salesConcessionCashCard = 0;
                                $countAdultTouchNGo = 0;
                                $salesAdultTouchNGo = 0;
                                $countConcessionTouchNGo = 0;
                                $salesConcessionTouchNGo = 0;
                                if (count($allTickets) > 0) {
                                    foreach ($allTickets as $allTicket) {

                                        //Cash || Card
                                        if ($allTicket->fare_type == 0 || $allTicket->fare_type == 1) {
                                            if ($allTicket->passenger_type == 0) {
                                                $countAdultCashCard++;
                                                $salesAdultCashCard += $allTicket->actual_amount;
                                            } else {
                                                $countConcessionCashCard++;
                                                $salesConcessionCashCard += $allTicket->actual_amount;
                                            }
                                        } else {
                                            if ($allTicket->passenger_type == 0) {
                                                $countAdultTouchNGo++;
                                                $salesAdultTouchNGo += $allTicket->actual_amount;
                                            } else {
                                                $countConcessionTouchNGo++;
                                                $salesConcessionTouchNGo += $allTicket->actual_amount;
                                            }
                                        }
                                    }
                                }
                                $perTrip['cash_adult_qty'] = $countAdultCashCard;
                                $perTrip['cash_adult_amount'] = $salesAdultCashCard;
                                $perTrip['cash_concession_qty'] = $countConcessionCashCard;
                                $perTrip['cash_concession_amount'] = $salesConcessionCashCard;
                                $perTrip['cash_total_qty'] = $countAdultCashCard + $countConcessionCashCard;
                                $perTrip['cash_total_amount'] = $salesAdultCashCard + $salesConcessionCashCard;
                                $perTrip['touchNGo_adult_qty'] = $countAdultTouchNGo;
                                $perTrip['touchNGo_adult_amount'] = $salesAdultTouchNGo;
                                $perTrip['touchNGo_concession_qty'] = $countConcessionTouchNGo;
                                $perTrip['touchNGo_concession_amount'] = $salesConcessionTouchNGo;
                                $perTrip['touchNGo_total_qty'] = $countAdultTouchNGo + $salesAdultTouchNGo;
                                $perTrip['touchNGo_total_amount'] = $countConcessionTouchNGo + $salesConcessionTouchNGo;
                                $perTrip['total_adult_qty'] = $perTrip['cash_adult_qty'] + $perTrip['touchNGo_adult_qty'];
                                $perTrip['total_adult_amount'] = $perTrip['cash_adult_amount'] + $perTrip['touchNGo_adult_amount'];
                                $perTrip['total_concession_qty'] = $perTrip['cash_concession_qty'] + $perTrip['touchNGo_concession_qty'];
                                $perTrip['total_concession_amount'] = $perTrip['cash_concession_amount'] + $perTrip['touchNGo_concession_amount'];
                                $perTrip['total_qty'] = $perTrip['cash_total_qty'] + $perTrip['touchNGo_total_qty'];
                                $perTrip['total_amount'] = $perTrip['cash_total_amount'] + $perTrip['touchNGo_total_amount'];

                                $finalCountAdultCashCard += $perTrip['cash_adult_qty'];
                                $finalSalesAdultCashCard += $perTrip['cash_adult_amount'];
                                $finalCountConcessionCashCard += $perTrip['cash_concession_qty'];
                                $finalSalesConcessionCashCard += $perTrip['cash_concession_amount'];
                                $finalTotalCountAdultCashCard += $perTrip['cash_total_qty'];
                                $finalTotalSalesAdultCashCard += $perTrip['cash_total_amount'];
                                $finalCountAdultTouchNGo += $perTrip['touchNGo_adult_qty'];
                                $finalSalesAdultTouchNGo += $perTrip['touchNGo_adult_amount'];
                                $finalCountConcessionTouchNGo += $perTrip['touchNGo_concession_qty'];
                                $finalSalesConcessionTouchNGo += $perTrip['touchNGo_concession_amount'];
                                $finalTotalCountAdultTouchNGo += $perTrip['touchNGo_total_qty'];
                                $finalTotalSalesAdultTouchNGo += $perTrip['touchNGo_total_amount'];
                                $finalTotalCountAdult += $perTrip['total_adult_qty'];
                                $finalTotalSalesAdult += $perTrip['total_adult_amount'];
                                $finalTotalCountConcession += $perTrip['total_concession_qty'];
                                $finalTotalSalesConcession += $perTrip['total_concession_amount'];
                                $finalTotalCount += $perTrip['total_qty'];
                                $finalTotalSales += $perTrip['total_amount'];

                                $tripByDriver[$i++] = $perTrip;
                            }

                        }

                        $total_driver['cash_adult_qty'] = $finalCountAdultCashCard;
                        $total_driver['cash_adult_amount'] = $finalSalesAdultCashCard;
                        $total_driver['cash_concession_qty'] = $finalCountConcessionCashCard;
                        $total_driver['cash_concession_amount'] = $finalSalesConcessionCashCard;
                        $total_driver['cash_total_qty'] = $finalTotalCountAdultCashCard;
                        $total_driver['cash_total_amount'] = $finalTotalSalesAdultCashCard;
                        $total_driver['touchNGo_adult_qty'] = $finalCountAdultTouchNGo;
                        $total_driver['touchNGo_adult_amount'] = $finalSalesAdultTouchNGo;
                        $total_driver['touchNGo_concession_qty'] = $finalCountConcessionTouchNGo;
                        $total_driver['touchNGo_concession_amount'] = $finalSalesConcessionTouchNGo;
                        $total_driver['touchNGo_total_qty'] = $finalTotalCountAdultTouchNGo;
                        $total_driver['touchNGo_total_amount'] = $finalTotalSalesAdultTouchNGo;
                        $total_driver['total_adult_qty'] = $finalTotalCountAdult;
                        $total_driver['total_adult_amount'] = $finalTotalSalesAdult;
                        $total_driver['total_concession_qty'] = $finalTotalCountConcession;
                        $total_driver['total_concession_amount'] = $finalTotalSalesConcession;
                        $total_driver['total_qty'] = $finalTotalCount;
                        $total_driver['total_amount'] = $finalTotalSales;

                        $perDriver['allTrips'] = $tripByDriver;
                        if ($tripByDriver == NULL) {
                            $perDriver['total'] = [];
                            $data_per_driver[$driverPerCompany->driver_number . ' - ' . $driverPerCompany->driver_name] = [];
                        } else {
                            $perDriver['total'] = $total_driver;
                            $data_per_driver[$driverPerCompany->driver_number . ' - ' . $driverPerCompany->driver_name] = $perDriver;
                        }

                        $companyCountAdultCashCard += $total_driver['cash_adult_qty'];
                        $companySalesAdultCashCard += $total_driver['cash_adult_amount'];
                        $companyCountConcessionCashCard += $total_driver['cash_concession_qty'];
                        $companySalesConcessionCashCard += $total_driver['cash_concession_amount'];
                        $companyTotalCountAdultCashCard += $total_driver['cash_total_qty'];
                        $companyTotalSalesAdultCashCard += $total_driver['cash_total_amount'];
                        $companyCountAdultTouchNGo += $total_driver['touchNGo_adult_qty'];
                        $companySalesAdultTouchNGo += $total_driver['touchNGo_adult_amount'];
                        $companyCountConcessionTouchNGo += $total_driver['touchNGo_concession_qty'];
                        $companySalesConcessionTouchNGo += $total_driver['touchNGo_concession_amount'];
                        $companyTotalCountAdultTouchNGo += $total_driver['touchNGo_total_qty'];
                        $companyTotalSalesAdultTouchNGo += $total_driver['touchNGo_total_amount'];
                        $companyTotalCountAdult += $total_driver['total_adult_qty'];
                        $companyTotalSalesAdult += $total_driver['total_adult_amount'];
                        $companyTotalCountConcession += $total_driver['total_concession_qty'];
                        $companyTotalSalesConcession += $total_driver['total_concession_amount'];
                        $companyTotalCount += $total_driver['total_qty'];
                        $companyTotalSales += $total_driver['total_amount'];
                    }
                }
                $total_company['cash_adult_qty'] = $companyCountAdultCashCard;
                $total_company['cash_adult_amount'] = $companySalesAdultCashCard;
                $total_company['cash_concession_qty'] = $companyCountConcessionCashCard;
                $total_company['cash_concession_amount'] = $companySalesConcessionCashCard;
                $total_company['cash_total_qty'] = $companyTotalCountAdultCashCard;
                $total_company['cash_total_amount'] = $companyTotalSalesAdultCashCard;
                $total_company['touchNGo_adult_qty'] = $companyCountAdultTouchNGo;
                $total_company['touchNGo_adult_amount'] = $companySalesAdultTouchNGo;
                $total_company['touchNGo_concession_qty'] = $companyCountConcessionTouchNGo;
                $total_company['touchNGo_concession_amount'] = $companySalesConcessionTouchNGo;
                $total_company['touchNGo_total_qty'] = $companyTotalCountAdultTouchNGo;
                $total_company['touchNGo_total_amount'] = $companyTotalSalesAdultTouchNGo;
                $total_company['total_adult_qty'] = $companyTotalCountAdult;
                $total_company['total_adult_amount'] = $companyTotalSalesAdult;
                $total_company['total_concession_qty'] = $companyTotalCountConcession;
                $total_company['total_concession_amount'] = $companyTotalSalesConcession;
                $total_company['total_qty'] = $companyTotalCount;
                $total_company['total_amount'] = $companyTotalSales;

                $perCompany['byCompany'] = $data_per_driver;
                if ($data_per_driver == NULL) {
                    $perCompany['total'] = [];
                } else {
                    $perCompany['total'] = $total_company;
                }
                $data_per_company[$companyDetails->company_name] = $perCompany;

                $grandCountAdultCashCard += $total_company['cash_adult_qty'];
                $grandSalesAdultCashCard += $total_company['cash_adult_amount'];
                $grandCountConcessionCashCard += $total_company['cash_concession_qty'];
                $grandSalesConcessionCashCard += $total_company['cash_concession_amount'];
                $grandTotalCountAdultCashCard += $total_company['cash_total_qty'];
                $grandTotalSalesAdultCashCard += $total_company['cash_total_amount'];
                $grandCountAdultTouchNGo += $total_company['touchNGo_adult_qty'];
                $grandSalesAdultTouchNGo += $total_company['touchNGo_adult_amount'];
                $grandCountConcessionTouchNGo += $total_company['touchNGo_concession_qty'];
                $grandSalesConcessionTouchNGo += $total_company['touchNGo_concession_amount'];
                $grandTotalCountAdultTouchNGo += $total_company['touchNGo_total_qty'];
                $grandTotalSalesAdultTouchNGo += $total_company['touchNGo_total_amount'];
                $grandTotalCountAdult += $total_company['total_adult_qty'];
                $grandTotalSalesAdult += $total_company['total_adult_amount'];
                $grandTotalCountConcession += $total_company['total_concession_qty'];
                $grandTotalSalesConcession += $total_company['total_concession_amount'];
                $grandTotalCount += $total_company['total_qty'];
                $grandTotalSales += $total_company['total_amount'];

                $grand['cash_adult_qty'] = $grandCountAdultCashCard;
                $grand['cash_adult_amount'] = $grandSalesAdultCashCard;
                $grand['cash_concession_qty'] = $grandCountConcessionCashCard;
                $grand['cash_concession_amount'] = $grandSalesConcessionCashCard;
                $grand['cash_total_qty'] = $grandTotalCountAdultCashCard;
                $grand['cash_total_amount'] = $grandTotalSalesAdultCashCard;
                $grand['touchNGo_adult_qty'] = $grandCountAdultTouchNGo;
                $grand['touchNGo_adult_amount'] = $grandSalesAdultTouchNGo;
                $grand['touchNGo_concession_qty'] = $grandCountConcessionTouchNGo;
                $grand['touchNGo_concession_amount'] = $grandSalesConcessionTouchNGo;
                $grand['touchNGo_total_qty'] = $grandTotalCountAdultTouchNGo;
                $grand['touchNGo_total_amount'] = $grandTotalSalesAdultTouchNGo;
                $grand['total_adult_qty'] = $grandTotalCountAdult;
                $grand['total_adult_amount'] = $grandTotalSalesAdult;
                $grand['total_concession_qty'] = $grandTotalCountConcession;
                $grand['total_concession_amount'] = $grandTotalSalesConcession;
                $grand['total_qty'] = $grandTotalCount;
                $grand['total_amount'] = $grandTotalSales;

                $grand_total['allCompanies'] = $data_per_company;
                $grand_total['grand_total'] = $grand;
                $data = $grand_total;

                $salesByDriver->add($data);
            }
        }
        //salesByDriver all driver all company
        else{
            $allCompanies =  Company::all();
            $grandCountAdultCashCard =0;
            $grandSalesAdultCashCard =0;
            $grandCountConcessionCashCard =0;
            $grandSalesConcessionCashCard =0;
            $grandTotalCountAdultCashCard =0;
            $grandTotalSalesAdultCashCard =0;
            $grandCountAdultTouchNGo =0;
            $grandSalesAdultTouchNGo =0;
            $grandCountConcessionTouchNGo =0;
            $grandSalesConcessionTouchNGo =0;
            $grandTotalCountAdultTouchNGo =0;
            $grandTotalSalesAdultTouchNGo =0;
            $grandTotalCountAdult =0;
            $grandTotalSalesAdult =0;
            $grandTotalCountConcession =0;
            $grandTotalSalesConcession =0;
            $grandTotalCount =0;
            $grandTotalSales =0;
            $data_per_company = [];

            foreach($allCompanies as $allCompany) {
                $allDrivers = BusDriver::where('company_id', $allCompany->id)->get();
                $companyCountAdultCashCard = 0;
                $companySalesAdultCashCard = 0;
                $companyCountConcessionCashCard = 0;
                $companySalesConcessionCashCard = 0;
                $companyTotalCountAdultCashCard = 0;
                $companyTotalSalesAdultCashCard = 0;
                $companyCountAdultTouchNGo = 0;
                $companySalesAdultTouchNGo = 0;
                $companyCountConcessionTouchNGo = 0;
                $companySalesConcessionTouchNGo = 0;
                $companyTotalCountAdultTouchNGo = 0;
                $companyTotalSalesAdultTouchNGo = 0;
                $companyTotalCountAdult = 0;
                $companyTotalSalesAdult = 0;
                $companyTotalCountConcession = 0;
                $companyTotalSalesConcession = 0;
                $companyTotalCount = 0;
                $companyTotalSales = 0;
                $data_per_driver = [];

                if (count($allDrivers) > 0) {
                    foreach ($allDrivers as $allDriver) {
                        //All trip drive by the driver
                        $allTrips = TripDetail::where('driver_id', $allDriver->id)->get();
                        $tripByDriver =[];
                        $finalCountAdultCashCard = 0;
                        $finalSalesAdultCashCard = 0;
                        $finalCountConcessionCashCard = 0;
                        $finalSalesConcessionCashCard = 0;
                        $finalTotalCountAdultCashCard = 0;
                        $finalTotalSalesAdultCashCard = 0;
                        $finalCountAdultTouchNGo = 0;
                        $finalSalesAdultTouchNGo = 0;
                        $finalCountConcessionTouchNGo= 0;
                        $finalSalesConcessionTouchNGo = 0;
                        $finalTotalCountAdultTouchNGo = 0;
                        $finalTotalSalesAdultTouchNGo = 0;
                        $finalTotalCountAdult = 0;
                        $finalTotalSalesAdult = 0;
                        $finalTotalCountConcession = 0;
                        $finalTotalSalesConcession = 0;
                        $finalTotalCount = 0;
                        $finalTotalSales = 0;
                        $i=0;
                        if (count($allTrips) > 0) {
                            foreach ($allTrips as $allTrip) {
                                $perTrip['company_name'] = $allCompany->company_name;
                                $perTrip['bus_no'] = $allTrip->Bus->bus_registration_number;
                                $perTrip['creation_date'] = $allTrip->start_trip;
                                $perTrip['closed_by'] = $allDriver->driver_number;
                                $perTrip['closed_date'] = $allTrip->end_trip;

                                if($allTrip->trip_code==0){
                                    $routeNameOut = implode(" - ", array_reverse(explode(" - ", $allTrip->Route->route_name)));
                                    $perTrip['route_desc'] = $allTrip->Route->route_number . ' ' . $routeNameOut;
                                }else{
                                    $perTrip['route_desc'] = $allTrip->Route->route_number . ' ' . $allTrip->Route->route_name;
                                }

                                $perTrip['trip_number'] = $allTrip->trip_number;
                                $perTrip['trip_no'] = 'T'. $allTrip->id;
                                $perTrip['status'] = 'Closed';

                                $allTickets = TicketSalesTransaction::where('trip_id', $allTrip->id)->get();

                                $countAdultCashCard = 0;
                                $countConcessionCashCard = 0;
                                $salesAdultCashCard = 0;
                                $salesConcessionCashCard = 0;
                                $countAdultTouchNGo = 0;
                                $salesAdultTouchNGo = 0;
                                $countConcessionTouchNGo = 0;
                                $salesConcessionTouchNGo = 0;
                                if (count($allTickets) > 0) {
                                    foreach ($allTickets as $allTicket) {

                                        //Cash || Card
                                        if($allTicket->fare_type==0 || $allTicket->fare_type==1){
                                            if($allTicket->passenger_type==0){
                                                $countAdultCashCard++;
                                                $salesAdultCashCard += $allTicket->actual_amount;
                                            }else{
                                                $countConcessionCashCard++;
                                                $salesConcessionCashCard += $allTicket->actual_amount;
                                            }
                                        }else{
                                            if($allTicket->passenger_type==0){
                                                $countAdultTouchNGo++;
                                                $salesAdultTouchNGo += $allTicket->actual_amount;
                                            }else{
                                                $countConcessionTouchNGo++;
                                                $salesConcessionTouchNGo += $allTicket->actual_amount;
                                            }
                                        }
                                    }
                                }
                                $perTrip['cash_adult_qty'] = $countAdultCashCard;
                                $perTrip['cash_adult_amount'] = $salesAdultCashCard;
                                $perTrip['cash_concession_qty'] = $countConcessionCashCard;
                                $perTrip['cash_concession_amount'] = $salesConcessionCashCard;
                                $perTrip['cash_total_qty'] = $countAdultCashCard + $countConcessionCashCard;
                                $perTrip['cash_total_amount'] = $salesAdultCashCard + $salesConcessionCashCard;
                                $perTrip['touchNGo_adult_qty'] = $countAdultTouchNGo;
                                $perTrip['touchNGo_adult_amount'] = $salesAdultTouchNGo;
                                $perTrip['touchNGo_concession_qty'] = $countConcessionTouchNGo;
                                $perTrip['touchNGo_concession_amount'] = $salesConcessionTouchNGo;
                                $perTrip['touchNGo_total_qty'] = $countAdultTouchNGo + $salesAdultTouchNGo;
                                $perTrip['touchNGo_total_amount'] = $countConcessionTouchNGo + $salesConcessionTouchNGo;
                                $perTrip['total_adult_qty'] = $perTrip['cash_adult_qty'] + $perTrip['touchNGo_adult_qty'];
                                $perTrip['total_adult_amount'] = $perTrip['cash_adult_amount'] + $perTrip['touchNGo_adult_amount'];
                                $perTrip['total_concession_qty'] = $perTrip['cash_concession_qty'] + $perTrip['touchNGo_concession_qty'];
                                $perTrip['total_concession_amount'] = $perTrip['cash_concession_amount'] + $perTrip['touchNGo_concession_amount'];
                                $perTrip['total_qty'] = $perTrip['cash_total_qty'] + $perTrip['touchNGo_total_qty'];
                                $perTrip['total_amount'] = $perTrip['cash_total_amount'] + $perTrip['touchNGo_total_amount'];

                                $finalCountAdultCashCard += $perTrip['cash_adult_qty'];
                                $finalSalesAdultCashCard += $perTrip['cash_adult_amount'];
                                $finalCountConcessionCashCard += $perTrip['cash_concession_qty'];
                                $finalSalesConcessionCashCard += $perTrip['cash_concession_amount'];
                                $finalTotalCountAdultCashCard += $perTrip['cash_total_qty'];
                                $finalTotalSalesAdultCashCard += $perTrip['cash_total_amount'];
                                $finalCountAdultTouchNGo += $perTrip['touchNGo_adult_qty'];
                                $finalSalesAdultTouchNGo += $perTrip['touchNGo_adult_amount'];
                                $finalCountConcessionTouchNGo += $perTrip['touchNGo_concession_qty'];
                                $finalSalesConcessionTouchNGo += $perTrip['touchNGo_concession_amount'];
                                $finalTotalCountAdultTouchNGo += $perTrip['touchNGo_total_qty'];
                                $finalTotalSalesAdultTouchNGo += $perTrip['touchNGo_total_amount'];
                                $finalTotalCountAdult += $perTrip['total_adult_qty'];
                                $finalTotalSalesAdult += $perTrip['total_adult_amount'];
                                $finalTotalCountConcession += $perTrip['total_concession_qty'];
                                $finalTotalSalesConcession +=  $perTrip['total_concession_amount'];
                                $finalTotalCount += $perTrip['total_qty'];
                                $finalTotalSales +=  $perTrip['total_amount'];

                                $tripByDriver[$i++] = $perTrip;
                            }

                        }

                        $total_driver['cash_adult_qty'] = $finalCountAdultCashCard;
                        $total_driver['cash_adult_amount'] = $finalSalesAdultCashCard;
                        $total_driver['cash_concession_qty'] = $finalCountConcessionCashCard;
                        $total_driver['cash_concession_amount'] = $finalSalesConcessionCashCard;
                        $total_driver['cash_total_qty'] = $finalTotalCountAdultCashCard;
                        $total_driver['cash_total_amount'] = $finalTotalSalesAdultCashCard;
                        $total_driver['touchNGo_adult_qty'] = $finalCountAdultTouchNGo;
                        $total_driver['touchNGo_adult_amount'] = $finalSalesAdultTouchNGo;
                        $total_driver['touchNGo_concession_qty'] = $finalCountConcessionTouchNGo;
                        $total_driver['touchNGo_concession_amount'] = $finalSalesConcessionTouchNGo;
                        $total_driver['touchNGo_total_qty'] = $finalTotalCountAdultTouchNGo;
                        $total_driver['touchNGo_total_amount'] = $finalTotalSalesAdultTouchNGo;
                        $total_driver['total_adult_qty'] = $finalTotalCountAdult;
                        $total_driver['total_adult_amount'] = $finalTotalSalesAdult;
                        $total_driver['total_concession_qty'] = $finalTotalCountConcession;
                        $total_driver['total_concession_amount'] = $finalTotalSalesConcession;
                        $total_driver['total_qty'] = $finalTotalCount;
                        $total_driver['total_amount'] = $finalTotalSales;

                        $perDriver['allTrips'] = $tripByDriver;
                        if($tripByDriver==NULL){
                            $perDriver['total'] = [];
                            $data_per_driver[$allDriver->driver_number . ' - ' . $allDriver->driver_name] = [];
                        }else{
                            $perDriver['total'] = $total_driver;
                            $data_per_driver[$allDriver->driver_number . ' - ' . $allDriver->driver_name] = $perDriver;
                        }

                        $companyCountAdultCashCard += $total_driver['cash_adult_qty'];
                        $companySalesAdultCashCard += $total_driver['cash_adult_amount'];
                        $companyCountConcessionCashCard += $total_driver['cash_concession_qty'];
                        $companySalesConcessionCashCard += $total_driver['cash_concession_amount'];
                        $companyTotalCountAdultCashCard += $total_driver['cash_total_qty'];
                        $companyTotalSalesAdultCashCard += $total_driver['cash_total_amount'];
                        $companyCountAdultTouchNGo += $total_driver['touchNGo_adult_qty'];
                        $companySalesAdultTouchNGo += $total_driver['touchNGo_adult_amount'];
                        $companyCountConcessionTouchNGo += $total_driver['touchNGo_concession_qty'];
                        $companySalesConcessionTouchNGo +=  $total_driver['touchNGo_concession_amount'];
                        $companyTotalCountAdultTouchNGo +=  $total_driver['touchNGo_total_qty'] ;
                        $companyTotalSalesAdultTouchNGo +=  $total_driver['touchNGo_total_amount'];
                        $companyTotalCountAdult +=  $total_driver['total_adult_qty'] ;
                        $companyTotalSalesAdult +=  $total_driver['total_adult_amount'];
                        $companyTotalCountConcession +=  $total_driver['total_concession_qty'];
                        $companyTotalSalesConcession +=  $total_driver['total_concession_amount'];
                        $companyTotalCount +=  $total_driver['total_qty'];
                        $companyTotalSales +=  $total_driver['total_amount'];
                    }
                }
                $total_company['cash_adult_qty'] = $companyCountAdultCashCard;
                $total_company['cash_adult_amount'] = $companySalesAdultCashCard;
                $total_company['cash_concession_qty'] = $companyCountConcessionCashCard;
                $total_company['cash_concession_amount'] = $companySalesConcessionCashCard;
                $total_company['cash_total_qty'] = $companyTotalCountAdultCashCard;
                $total_company['cash_total_amount'] = $companyTotalSalesAdultCashCard;
                $total_company['touchNGo_adult_qty'] = $companyCountAdultTouchNGo;
                $total_company['touchNGo_adult_amount'] = $companySalesAdultTouchNGo;
                $total_company['touchNGo_concession_qty'] = $companyCountConcessionTouchNGo;
                $total_company['touchNGo_concession_amount'] = $companySalesConcessionTouchNGo;
                $total_company['touchNGo_total_qty'] = $companyTotalCountAdultTouchNGo;
                $total_company['touchNGo_total_amount'] = $companyTotalSalesAdultTouchNGo;
                $total_company['total_adult_qty'] = $companyTotalCountAdult;
                $total_company['total_adult_amount'] = $companyTotalSalesAdult;
                $total_company['total_concession_qty'] = $companyTotalCountConcession;
                $total_company['total_concession_amount'] = $companyTotalSalesConcession;
                $total_company['total_qty'] = $companyTotalCount;
                $total_company['total_amount'] = $companyTotalSales;

                $perCompany['byCompany'] = $data_per_driver;
                if($data_per_driver==NULL){
                    $perCompany['total'] = [];
                }else{
                    $perCompany['total'] = $total_company;
                }
                $data_per_company[$allCompany->company_name] = $perCompany;

                $grandCountAdultCashCard += $total_company['cash_adult_qty'];
                $grandSalesAdultCashCard += $total_company['cash_adult_amount'];
                $grandCountConcessionCashCard += $total_company['cash_concession_qty'];
                $grandSalesConcessionCashCard += $total_company['cash_concession_amount'];
                $grandTotalCountAdultCashCard += $total_company['cash_total_qty'];
                $grandTotalSalesAdultCashCard += $total_company['cash_total_amount'];
                $grandCountAdultTouchNGo += $total_company['touchNGo_adult_qty'];
                $grandSalesAdultTouchNGo += $total_company['touchNGo_adult_amount'];
                $grandCountConcessionTouchNGo += $total_company['touchNGo_concession_qty'];
                $grandSalesConcessionTouchNGo +=  $total_company['touchNGo_concession_amount'];
                $grandTotalCountAdultTouchNGo +=  $total_company['touchNGo_total_qty'] ;
                $grandTotalSalesAdultTouchNGo +=  $total_company['touchNGo_total_amount'];
                $grandTotalCountAdult +=  $total_company['total_adult_qty'] ;
                $grandTotalSalesAdult +=  $total_company['total_adult_amount'];
                $grandTotalCountConcession +=  $total_company['total_concession_qty'];
                $grandTotalSalesConcession +=  $total_company['total_concession_amount'];
                $grandTotalCount +=  $total_company['total_qty'];
                $grandTotalSales +=  $total_company['total_amount'];
            }
            $grand['cash_adult_qty'] = $grandCountAdultCashCard;
            $grand['cash_adult_amount'] = $grandSalesAdultCashCard;
            $grand['cash_concession_qty'] = $grandCountConcessionCashCard;
            $grand['cash_concession_amount'] = $grandSalesConcessionCashCard;
            $grand['cash_total_qty'] = $grandTotalCountAdultCashCard;
            $grand['cash_total_amount'] = $grandTotalSalesAdultCashCard;
            $grand['touchNGo_adult_qty'] = $grandCountAdultTouchNGo;
            $grand['touchNGo_adult_amount'] = $grandSalesAdultTouchNGo;
            $grand['touchNGo_concession_qty'] = $grandCountConcessionTouchNGo;
            $grand['touchNGo_concession_amount'] = $grandSalesConcessionTouchNGo;
            $grand['touchNGo_total_qty'] = $grandTotalCountAdultTouchNGo;
            $grand['touchNGo_total_amount'] = $grandTotalSalesAdultTouchNGo;
            $grand['total_adult_qty'] = $grandTotalCountAdult;
            $grand['total_adult_amount'] = $grandTotalSalesAdult;
            $grand['total_concession_qty'] = $grandTotalCountConcession;
            $grand['total_concession_amount'] = $grandTotalSalesConcession;
            $grand['total_qty'] = $grandTotalCount;
            $grand['total_amount'] = $grandTotalSales;

            $grand_total['allCompanies'] = $data_per_company;
            $grand_total['grand_total'] = $grand;
            $data = $grand_total;

            $salesByDriver->add($data);
        }
        return Excel::download(new SalesByDriver($salesByDriver), 'SalesByDriver.xlsx');
    }*/
}
