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

    public function print()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE salesByDriver print()");

        $validatedData = Validator::make($this->state, [
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'driver_id' => ['required'],
        ])->validate();

        $firstDate = new Carbon($validatedData['dateFrom']);
        $lastDate = new Carbon($validatedData['dateFrom'] . '23:59:59');

        $salesByDriver = collect();
        //Cash
        $grandCountAdultCash =0;
        $grandSalesAdultCash =0;
        $grandCountConcessionCash =0;
        $grandSalesConcessionCash =0;
        $grandTotalCountAdultCash =0;
        $grandTotalSalesAdultCash =0;
        //Card
        $grandCountAdultCard =0;
        $grandSalesAdultCard =0;
        $grandCountConcessionCard =0;
        $grandSalesConcessionCard =0;
        $grandTotalCountAdultCard =0;
        $grandTotalSalesAdultCard =0;
        //Tngo
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
        if($this->selectedCompany){
            if($this->selectedCompany=='All'){
                $companyName = 'ALL';
                if(!empty($validatedData['driver_id'])) {
                    //Sales By Driver all driver all company
                    if($validatedData['driver_id']=='All'){
                        $allCompanies =  Company::orderBy('state')->get();

                        foreach($allCompanies as $allCompany) {
                            $allDrivers = BusDriver::where('company_id', $allCompany->id)->orderBy('driver_name')->get();
                            $companyTotalCountAdult = 0;
                            $companyTotalSalesAdult = 0;
                            $companyTotalCountConcession = 0;
                            $companyTotalSalesConcession = 0;
                            $companyTotalCount = 0;
                            $companyTotalSales = 0;
                            $data_per_driver = [];
                            //Cash
                            $companyCountAdultCash = 0;
                            $companySalesAdultCash = 0;
                            $companyCountConcessionCash= 0;
                            $companySalesConcessionCash = 0;
                            $companyTotalCountAdultCash = 0;
                            $companyTotalSalesAdultCash = 0;
                            //Card
                            $companyCountAdultCard = 0;
                            $companySalesAdultCard = 0;
                            $companyCountConcessionCard = 0;
                            $companySalesConcessionCard = 0;
                            $companyTotalCountAdultCard = 0;
                            $companyTotalSalesAdultCard = 0;
                            //TouchnGo
                            $companyCountAdultTouchNGo = 0;
                            $companySalesAdultTouchNGo = 0;
                            $companyCountConcessionTouchNGo = 0;
                            $companySalesConcessionTouchNGo = 0;
                            $companyTotalCountAdultTouchNGo = 0;
                            $companyTotalSalesAdultTouchNGo = 0;
            
                            if (count($allDrivers)>0) {
                                foreach ($allDrivers as $driverPerCompany) {
                                    $finalTotalCountAdult = 0;
                                    $finalTotalSalesAdult = 0;
                                    $finalTotalCountConcession = 0;
                                    $finalTotalSalesConcession = 0;
                                    $finalTotalCount = 0;
                                    $finalTotalSales = 0;
                                    $i = 0;
                                    $tripByDriver = [];
                                    //Cash
                                    $finalCountAdultCash = 0;
                                    $finalSalesAdultCash = 0;
                                    $finalCountConcessionCash = 0;
                                    $finalSalesConcessionCash = 0;
                                    $finalTotalCountAdultCash = 0;
                                    $finalTotalSalesAdultCash = 0;
                                    //Card
                                    $finalCountAdultCard = 0;
                                    $finalSalesAdultCard = 0;
                                    $finalCountConcessionCard = 0;
                                    $finalSalesConcessionCard = 0;
                                    $finalTotalCountAdultCard = 0;
                                    $finalTotalSalesAdultCard = 0;
                                    //Tngo
                                    $finalCountAdultTouchNGo = 0;
                                    $finalSalesAdultTouchNGo = 0;
                                    $finalCountConcessionTouchNGo = 0;
                                    $finalSalesConcessionTouchNGo = 0;
                                    $finalTotalCountAdultTouchNGo = 0;
                                    $finalTotalSalesAdultTouchNGo = 0;
            
                                    //All trip drive by the driver
                                    $allTrips = TripDetail::where('driver_id', $driverPerCompany->id)
                                    ->whereBetween('start_trip', [$firstDate, $lastDate])
                                    ->get();
                                    if (count($allTrips) > 0) {
                                        foreach ($allTrips as $allTrip) {
                                            $perTrip['company_name'] = $allCompany->company_name;
                                            if ($allTrip->bus_id != NULL) {
                                                $perTrip['bus_no'] = $allTrip->Bus->bus_registration_number;
                                            }else{
                                                $perTrip['bus_no'] = "No Data";
                                            }
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
            
                                            //Cash
                                            $countAdultCash = 0;
                                            $countConcessionCash = 0;
                                            $salesAdultCash = 0;
                                            $salesConcessionCash = 0;
                                            //Card
                                            $countAdultCard = 0;
                                            $countConcessionCard = 0;
                                            $salesAdultCard = 0;
                                            $salesConcessionCard = 0;
                                            //Tngo
                                            $countAdultTouchNGo = 0;
                                            $salesAdultTouchNGo = 0;
                                            $countConcessionTouchNGo = 0;
                                            $salesConcessionTouchNGo = 0;
                                            $allTickets = TicketSalesTransaction::where('trip_id', $allTrip->id)->get();
                                            if (count($allTickets) > 0) {
                                                foreach ($allTickets as $allTicket) {
                                                    //Cash 
                                                    if ($allTicket->fare_type == 0) {
                                                        if ($allTicket->passenger_type == 0) {
                                                            $countAdultCash++;
                                                            $salesAdultCash += $allTicket->actual_amount;
                                                        } else {
                                                            $countConcessionCash++;
                                                            $salesConcessionCash += $allTicket->actual_amount;
                                                        }
                                                    }
                                                    //Card
                                                    elseif ($allTicket->fare_type == 1) {
                                                        if ($allTicket->passenger_type == 0) {
                                                            $countAdultCard++;
                                                            $salesAdultCard += $allTicket->actual_amount;
                                                        } else {
                                                            $countConcessionCard++;
                                                            $salesConcessionCard += $allTicket->actual_amount;
                                                        }
            
                                                    }
                                                    //Tngo
                                                    else {
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
                                            $perTrip['cash_adult_qty'] = $countAdultCash;
                                            $perTrip['cash_adult_amount'] = $salesAdultCash;
                                            $perTrip['cash_concession_qty'] = $countConcessionCash;
                                            $perTrip['cash_concession_amount'] = $salesConcessionCash;
                                            $perTrip['cash_total_qty'] = $countAdultCash + $countConcessionCash;
                                            $perTrip['cash_total_amount'] = $salesAdultCash + $salesConcessionCash;
                                            $perTrip['card_adult_qty'] = $countAdultCard;
                                            $perTrip['card_adult_amount'] = $salesAdultCard;
                                            $perTrip['card_concession_qty'] = $countConcessionCard;
                                            $perTrip['card_concession_amount'] = $salesConcessionCard;
                                            $perTrip['card_total_qty'] = $countAdultCard + $countConcessionCard;
                                            $perTrip['card_total_amount'] = $salesAdultCard + $salesConcessionCard;
                                            $perTrip['touchNGo_adult_qty'] = $countAdultTouchNGo;
                                            $perTrip['touchNGo_adult_amount'] = $salesAdultTouchNGo;
                                            $perTrip['touchNGo_concession_qty'] = $countConcessionTouchNGo;
                                            $perTrip['touchNGo_concession_amount'] = $salesConcessionTouchNGo;
                                            $perTrip['touchNGo_total_qty'] = $countAdultTouchNGo + $salesAdultTouchNGo;
                                            $perTrip['touchNGo_total_amount'] = $countConcessionTouchNGo + $salesConcessionTouchNGo;
                                            $perTrip['total_adult_qty'] = $perTrip['cash_adult_qty'] + $perTrip['card_adult_qty'] + $perTrip['touchNGo_adult_qty'];
                                            $perTrip['total_adult_amount'] = $perTrip['cash_adult_amount'] + $perTrip['card_adult_amount'] + $perTrip['touchNGo_adult_amount'];
                                            $perTrip['total_concession_qty'] = $perTrip['cash_concession_qty'] + $perTrip['card_concession_qty'] + $perTrip['touchNGo_concession_qty'];
                                            $perTrip['total_concession_amount'] = $perTrip['cash_concession_amount'] + $perTrip['card_concession_amount'] + $perTrip['touchNGo_concession_amount'];
                                            $perTrip['total_qty'] = $perTrip['cash_total_qty'] + $perTrip['card_total_qty'] + $perTrip['touchNGo_total_qty'];
                                            $perTrip['total_amount'] = $perTrip['cash_total_amount'] + $perTrip['card_total_amount'] + $perTrip['touchNGo_total_amount'];
            
                                            $finalCountAdultCash += $perTrip['cash_adult_qty'];
                                            $finalSalesAdultCash += $perTrip['cash_adult_amount'];
                                            $finalCountConcessionCash += $perTrip['cash_concession_qty'];
                                            $finalSalesConcessionCash += $perTrip['cash_concession_amount'];
                                            $finalTotalCountAdultCash += $perTrip['cash_total_qty'];
                                            $finalTotalSalesAdultCash += $perTrip['cash_total_amount'];
                                            $finalCountAdultCard += $perTrip['card_adult_qty'];
                                            $finalSalesAdultCard += $perTrip['card_adult_amount'];
                                            $finalCountConcessionCard += $perTrip['card_concession_qty'];
                                            $finalSalesConcessionCard += $perTrip['card_concession_amount'];
                                            $finalTotalCountAdultCard += $perTrip['card_total_qty'];
                                            $finalTotalSalesAdultCard += $perTrip['card_total_amount'];
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
            
                                    $total_driver['cash_adult_qty'] = $finalCountAdultCash;
                                    $total_driver['cash_adult_amount'] = $finalSalesAdultCash;
                                    $total_driver['cash_concession_qty'] = $finalCountConcessionCash;
                                    $total_driver['cash_concession_amount'] = $finalSalesConcessionCash;
                                    $total_driver['cash_total_qty'] = $finalTotalCountAdultCash;
                                    $total_driver['cash_total_amount'] = $finalTotalSalesAdultCash;
                                    $total_driver['card_adult_qty'] = $finalCountAdultCard;
                                    $total_driver['card_adult_amount'] = $finalSalesAdultCard;
                                    $total_driver['card_concession_qty'] = $finalCountConcessionCard;
                                    $total_driver['card_concession_amount'] = $finalSalesConcessionCard;
                                    $total_driver['card_total_qty'] = $finalTotalCountAdultCard;
                                    $total_driver['card_total_amount'] = $finalTotalSalesAdultCard;
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
            
                                    $companyCountAdultCash += $total_driver['cash_adult_qty'];
                                    $companySalesAdultCash += $total_driver['cash_adult_amount'];
                                    $companyCountConcessionCash += $total_driver['cash_concession_qty'];
                                    $companySalesConcessionCash += $total_driver['cash_concession_amount'];
                                    $companyTotalCountAdultCash += $total_driver['cash_total_qty'];
                                    $companyTotalSalesAdultCash += $total_driver['cash_total_amount'];
                                    $companyCountAdultCard += $total_driver['card_adult_qty'];
                                    $companySalesAdultCard += $total_driver['card_adult_amount'];
                                    $companyCountConcessionCard += $total_driver['card_concession_qty'];
                                    $companySalesConcessionCard += $total_driver['card_concession_amount'];
                                    $companyTotalCountAdultCard += $total_driver['card_total_qty'];
                                    $companyTotalSalesAdultCard += $total_driver['card_total_amount'];
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
                            $total_company['cash_adult_qty'] = $companyCountAdultCash;
                            $total_company['cash_adult_amount'] = $companySalesAdultCash;
                            $total_company['cash_concession_qty'] = $companyCountConcessionCash;
                            $total_company['cash_concession_amount'] = $companySalesConcessionCash;
                            $total_company['cash_total_qty'] = $companyTotalCountAdultCash;
                            $total_company['cash_total_amount'] = $companyTotalSalesAdultCash;
                            $total_company['card_adult_qty'] = $companyCountAdultCard;
                            $total_company['card_adult_amount'] = $companySalesAdultCard;
                            $total_company['card_concession_qty'] = $companyCountConcessionCard;
                            $total_company['card_concession_amount'] = $companySalesConcessionCard;
                            $total_company['card_total_qty'] = $companyTotalCountAdultCard;
                            $total_company['card_total_amount'] = $companyTotalSalesAdultCard;
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
                            $data_per_company[$allCompany->company_name] = $perCompany;
            
                            $grandCountAdultCash += $total_company['cash_adult_qty'];
                            $grandSalesAdultCash += $total_company['cash_adult_amount'];
                            $grandCountConcessionCash += $total_company['cash_concession_qty'];
                            $grandSalesConcessionCash += $total_company['cash_concession_amount'];
                            $grandTotalCountAdultCash += $total_company['cash_total_qty'];
                            $grandTotalSalesAdultCash += $total_company['cash_total_amount'];
                            $grandCountAdultCard += $total_company['card_adult_qty'];
                            $grandSalesAdultCard += $total_company['card_adult_amount'];
                            $grandCountConcessionCard += $total_company['card_concession_qty'];
                            $grandSalesConcessionCard += $total_company['card_concession_amount'];
                            $grandTotalCountAdultCard += $total_company['card_total_qty'];
                            $grandTotalSalesAdultCard += $total_company['card_total_amount'];
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
                        }

                        $grand['cash_adult_qty'] = $grandCountAdultCash;
                        $grand['cash_adult_amount'] = $grandSalesAdultCash;
                        $grand['cash_concession_qty'] = $grandCountConcessionCash;
                        $grand['cash_concession_amount'] = $grandSalesConcessionCash;
                        $grand['cash_total_qty'] = $grandTotalCountAdultCash;
                        $grand['cash_total_amount'] = $grandTotalSalesAdultCash;
                        $grand['card_adult_qty'] = $grandCountAdultCard;
                        $grand['card_adult_amount'] = $grandSalesAdultCard;
                        $grand['card_concession_qty'] = $grandCountConcessionCard;
                        $grand['card_concession_amount'] = $grandSalesConcessionCard;
                        $grand['card_total_qty'] = $grandTotalCountAdultCard;
                        $grand['card_total_amount'] = $grandTotalSalesAdultCard;
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
            }else{
                $companyDetails = Company::where('id', $this->selectedCompany)->first();
                $companyName = $companyDetails->company_name;

                if(!empty($validatedData['driver_id'])) {
                    //Sales By Driver all driver specific company
                    if($validatedData['driver_id']=='All'){
                        $driverPerCompanies = BusDriver::where('company_id', $companyDetails->id)->orderBy('driver_name')->get();
                        $companyTotalCountAdult = 0;
                        $companyTotalSalesAdult = 0;
                        $companyTotalCountConcession = 0;
                        $companyTotalSalesConcession = 0;
                        $companyTotalCount = 0;
                        $companyTotalSales = 0;
                        $data_per_driver = [];
                        //Cash
                        $companyCountAdultCash = 0;
                        $companySalesAdultCash = 0;
                        $companyCountConcessionCash= 0;
                        $companySalesConcessionCash = 0;
                        $companyTotalCountAdultCash = 0;
                        $companyTotalSalesAdultCash = 0;
                        //Card
                        $companyCountAdultCard = 0;
                        $companySalesAdultCard = 0;
                        $companyCountConcessionCard = 0;
                        $companySalesConcessionCard = 0;
                        $companyTotalCountAdultCard = 0;
                        $companyTotalSalesAdultCard = 0;
                        //TouchnGo
                        $companyCountAdultTouchNGo = 0;
                        $companySalesAdultTouchNGo = 0;
                        $companyCountConcessionTouchNGo = 0;
                        $companySalesConcessionTouchNGo = 0;
                        $companyTotalCountAdultTouchNGo = 0;
                        $companyTotalSalesAdultTouchNGo = 0;
        
                        if (count($driverPerCompanies)>0) {
                            foreach ($driverPerCompanies as $driverPerCompany) {
                                $finalTotalCountAdult = 0;
                                $finalTotalSalesAdult = 0;
                                $finalTotalCountConcession = 0;
                                $finalTotalSalesConcession = 0;
                                $finalTotalCount = 0;
                                $finalTotalSales = 0;
                                $i = 0;
                                $tripByDriver = [];
                                //Cash
                                $finalCountAdultCash = 0;
                                $finalSalesAdultCash = 0;
                                $finalCountConcessionCash = 0;
                                $finalSalesConcessionCash = 0;
                                $finalTotalCountAdultCash = 0;
                                $finalTotalSalesAdultCash = 0;
                                //Card
                                $finalCountAdultCard = 0;
                                $finalSalesAdultCard = 0;
                                $finalCountConcessionCard = 0;
                                $finalSalesConcessionCard = 0;
                                $finalTotalCountAdultCard = 0;
                                $finalTotalSalesAdultCard = 0;
                                //Tngo
                                $finalCountAdultTouchNGo = 0;
                                $finalSalesAdultTouchNGo = 0;
                                $finalCountConcessionTouchNGo = 0;
                                $finalSalesConcessionTouchNGo = 0;
                                $finalTotalCountAdultTouchNGo = 0;
                                $finalTotalSalesAdultTouchNGo = 0;
        
                                //All trip drive by the driver
                                $allTrips = TripDetail::where('driver_id', $driverPerCompany->id)
                                ->whereBetween('start_trip', [$firstDate, $lastDate])
                                ->get();
                                if (count($allTrips) > 0) {
                                    foreach ($allTrips as $allTrip) {
                                        $perTrip['company_name'] = $companyDetails->company_name;
                                        if ($allTrip->bus_id != NULL) {
                                            $perTrip['bus_no'] = $allTrip->Bus->bus_registration_number;
                                        }else{
                                            $perTrip['bus_no'] = "No Data";
                                        }
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
        
                                        //Cash
                                        $countAdultCash = 0;
                                        $countConcessionCash = 0;
                                        $salesAdultCash = 0;
                                        $salesConcessionCash = 0;
                                        //Card
                                        $countAdultCard = 0;
                                        $countConcessionCard = 0;
                                        $salesAdultCard = 0;
                                        $salesConcessionCard = 0;
                                        //Tngo
                                        $countAdultTouchNGo = 0;
                                        $salesAdultTouchNGo = 0;
                                        $countConcessionTouchNGo = 0;
                                        $salesConcessionTouchNGo = 0;
                                        $allTickets = TicketSalesTransaction::where('trip_id', $allTrip->id)->get();
                                        if (count($allTickets) > 0) {
                                            foreach ($allTickets as $allTicket) {
                                                //Cash 
                                                if ($allTicket->fare_type == 0) {
                                                    if ($allTicket->passenger_type == 0) {
                                                        $countAdultCash++;
                                                        $salesAdultCash += $allTicket->actual_amount;
                                                    } else {
                                                        $countConcessionCash++;
                                                        $salesConcessionCash += $allTicket->actual_amount;
                                                    }
                                                }
                                                //Card
                                                elseif ($allTicket->fare_type == 1) {
                                                    if ($allTicket->passenger_type == 0) {
                                                        $countAdultCard++;
                                                        $salesAdultCard += $allTicket->actual_amount;
                                                    } else {
                                                        $countConcessionCard++;
                                                        $salesConcessionCard += $allTicket->actual_amount;
                                                    }
        
                                                }
                                                //Tngo
                                                else {
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
                                        $perTrip['cash_adult_qty'] = $countAdultCash;
                                        $perTrip['cash_adult_amount'] = $salesAdultCash;
                                        $perTrip['cash_concession_qty'] = $countConcessionCash;
                                        $perTrip['cash_concession_amount'] = $salesConcessionCash;
                                        $perTrip['cash_total_qty'] = $countAdultCash + $countConcessionCash;
                                        $perTrip['cash_total_amount'] = $salesAdultCash + $salesConcessionCash;
                                        $perTrip['card_adult_qty'] = $countAdultCard;
                                        $perTrip['card_adult_amount'] = $salesAdultCard;
                                        $perTrip['card_concession_qty'] = $countConcessionCard;
                                        $perTrip['card_concession_amount'] = $salesConcessionCard;
                                        $perTrip['card_total_qty'] = $countAdultCard + $countConcessionCard;
                                        $perTrip['card_total_amount'] = $salesAdultCard + $salesConcessionCard;
                                        $perTrip['touchNGo_adult_qty'] = $countAdultTouchNGo;
                                        $perTrip['touchNGo_adult_amount'] = $salesAdultTouchNGo;
                                        $perTrip['touchNGo_concession_qty'] = $countConcessionTouchNGo;
                                        $perTrip['touchNGo_concession_amount'] = $salesConcessionTouchNGo;
                                        $perTrip['touchNGo_total_qty'] = $countAdultTouchNGo + $salesAdultTouchNGo;
                                        $perTrip['touchNGo_total_amount'] = $countConcessionTouchNGo + $salesConcessionTouchNGo;
                                        $perTrip['total_adult_qty'] = $perTrip['cash_adult_qty'] + $perTrip['card_adult_qty'] + $perTrip['touchNGo_adult_qty'];
                                        $perTrip['total_adult_amount'] = $perTrip['cash_adult_amount'] + $perTrip['card_adult_amount'] + $perTrip['touchNGo_adult_amount'];
                                        $perTrip['total_concession_qty'] = $perTrip['cash_concession_qty'] + $perTrip['card_concession_qty'] + $perTrip['touchNGo_concession_qty'];
                                        $perTrip['total_concession_amount'] = $perTrip['cash_concession_amount'] + $perTrip['card_concession_amount'] + $perTrip['touchNGo_concession_amount'];
                                        $perTrip['total_qty'] = $perTrip['cash_total_qty'] + $perTrip['card_total_qty'] + $perTrip['touchNGo_total_qty'];
                                        $perTrip['total_amount'] = $perTrip['cash_total_amount'] + $perTrip['card_total_amount'] + $perTrip['touchNGo_total_amount'];
        
                                        $finalCountAdultCash += $perTrip['cash_adult_qty'];
                                        $finalSalesAdultCash += $perTrip['cash_adult_amount'];
                                        $finalCountConcessionCash += $perTrip['cash_concession_qty'];
                                        $finalSalesConcessionCash += $perTrip['cash_concession_amount'];
                                        $finalTotalCountAdultCash += $perTrip['cash_total_qty'];
                                        $finalTotalSalesAdultCash += $perTrip['cash_total_amount'];
                                        $finalCountAdultCard += $perTrip['card_adult_qty'];
                                        $finalSalesAdultCard += $perTrip['card_adult_amount'];
                                        $finalCountConcessionCard += $perTrip['card_concession_qty'];
                                        $finalSalesConcessionCard += $perTrip['card_concession_amount'];
                                        $finalTotalCountAdultCard += $perTrip['card_total_qty'];
                                        $finalTotalSalesAdultCard += $perTrip['card_total_amount'];
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
        
                                $total_driver['cash_adult_qty'] = $finalCountAdultCash;
                                $total_driver['cash_adult_amount'] = $finalSalesAdultCash;
                                $total_driver['cash_concession_qty'] = $finalCountConcessionCash;
                                $total_driver['cash_concession_amount'] = $finalSalesConcessionCash;
                                $total_driver['cash_total_qty'] = $finalTotalCountAdultCash;
                                $total_driver['cash_total_amount'] = $finalTotalSalesAdultCash;
                                $total_driver['card_adult_qty'] = $finalCountAdultCard;
                                $total_driver['card_adult_amount'] = $finalSalesAdultCard;
                                $total_driver['card_concession_qty'] = $finalCountConcessionCard;
                                $total_driver['card_concession_amount'] = $finalSalesConcessionCard;
                                $total_driver['card_total_qty'] = $finalTotalCountAdultCard;
                                $total_driver['card_total_amount'] = $finalTotalSalesAdultCard;
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
        
                                $companyCountAdultCash += $total_driver['cash_adult_qty'];
                                $companySalesAdultCash += $total_driver['cash_adult_amount'];
                                $companyCountConcessionCash += $total_driver['cash_concession_qty'];
                                $companySalesConcessionCash += $total_driver['cash_concession_amount'];
                                $companyTotalCountAdultCash += $total_driver['cash_total_qty'];
                                $companyTotalSalesAdultCash += $total_driver['cash_total_amount'];
                                $companyCountAdultCard += $total_driver['card_adult_qty'];
                                $companySalesAdultCard += $total_driver['card_adult_amount'];
                                $companyCountConcessionCard += $total_driver['card_concession_qty'];
                                $companySalesConcessionCard += $total_driver['card_concession_amount'];
                                $companyTotalCountAdultCard += $total_driver['card_total_qty'];
                                $companyTotalSalesAdultCard += $total_driver['card_total_amount'];
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
                        $total_company['cash_adult_qty'] = $companyCountAdultCash;
                        $total_company['cash_adult_amount'] = $companySalesAdultCash;
                        $total_company['cash_concession_qty'] = $companyCountConcessionCash;
                        $total_company['cash_concession_amount'] = $companySalesConcessionCash;
                        $total_company['cash_total_qty'] = $companyTotalCountAdultCash;
                        $total_company['cash_total_amount'] = $companyTotalSalesAdultCash;
                        $total_company['card_adult_qty'] = $companyCountAdultCard;
                        $total_company['card_adult_amount'] = $companySalesAdultCard;
                        $total_company['card_concession_qty'] = $companyCountConcessionCard;
                        $total_company['card_concession_amount'] = $companySalesConcessionCard;
                        $total_company['card_total_qty'] = $companyTotalCountAdultCard;
                        $total_company['card_total_amount'] = $companyTotalSalesAdultCard;
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
        
                        $grandCountAdultCash += $total_company['cash_adult_qty'];
                        $grandSalesAdultCash += $total_company['cash_adult_amount'];
                        $grandCountConcessionCash += $total_company['cash_concession_qty'];
                        $grandSalesConcessionCash += $total_company['cash_concession_amount'];
                        $grandTotalCountAdultCash += $total_company['cash_total_qty'];
                        $grandTotalSalesAdultCash += $total_company['cash_total_amount'];
                        $grandCountAdultCard += $total_company['card_adult_qty'];
                        $grandSalesAdultCard += $total_company['card_adult_amount'];
                        $grandCountConcessionCard += $total_company['card_concession_qty'];
                        $grandSalesConcessionCard += $total_company['card_concession_amount'];
                        $grandTotalCountAdultCard += $total_company['card_total_qty'];
                        $grandTotalSalesAdultCard += $total_company['card_total_amount'];
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
        
                        $grand['cash_adult_qty'] = $grandCountAdultCash;
                        $grand['cash_adult_amount'] = $grandSalesAdultCash;
                        $grand['cash_concession_qty'] = $grandCountConcessionCash;
                        $grand['cash_concession_amount'] = $grandSalesConcessionCash;
                        $grand['cash_total_qty'] = $grandTotalCountAdultCash;
                        $grand['cash_total_amount'] = $grandTotalSalesAdultCash;
                        $grand['card_adult_qty'] = $grandCountAdultCard;
                        $grand['card_adult_amount'] = $grandSalesAdultCard;
                        $grand['card_concession_qty'] = $grandCountConcessionCard;
                        $grand['card_concession_amount'] = $grandSalesConcessionCard;
                        $grand['card_total_qty'] = $grandTotalCountAdultCard;
                        $grand['card_total_amount'] = $grandTotalSalesAdultCard;
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
                    //Sales By Driver specific driver specific company
                    else{
                        $selectedDriver = BusDriver::where('id', $validatedData['driver_id'])->first();
                        $companyTotalCountAdult = 0;
                        $companyTotalSalesAdult = 0;
                        $companyTotalCountConcession = 0;
                        $companyTotalSalesConcession = 0;
                        $companyTotalCount = 0;
                        $companyTotalSales = 0;
                        $data_per_driver = [];
                        //Cash
                        $companyCountAdultCash = 0;
                        $companySalesAdultCash = 0;
                        $companyCountConcessionCash= 0;
                        $companySalesConcessionCash = 0;
                        $companyTotalCountAdultCash = 0;
                        $companyTotalSalesAdultCash = 0;
                        //Card
                        $companyCountAdultCard = 0;
                        $companySalesAdultCard = 0;
                        $companyCountConcessionCard = 0;
                        $companySalesConcessionCard = 0;
                        $companyTotalCountAdultCard = 0;
                        $companyTotalSalesAdultCard = 0;
                        //TouchnGo
                        $companyCountAdultTouchNGo = 0;
                        $companySalesAdultTouchNGo = 0;
                        $companyCountConcessionTouchNGo = 0;
                        $companySalesConcessionTouchNGo = 0;
                        $companyTotalCountAdultTouchNGo = 0;
                        $companyTotalSalesAdultTouchNGo = 0;
        
                        if($selectedDriver){
                            $finalTotalCountAdult = 0;
                            $finalTotalSalesAdult = 0;
                            $finalTotalCountConcession = 0;
                            $finalTotalSalesConcession = 0;
                            $finalTotalCount = 0;
                            $finalTotalSales = 0;
                            $i = 0;
                            $tripByDriver = [];
                            //Cash
                            $finalCountAdultCash = 0;
                            $finalSalesAdultCash = 0;
                            $finalCountConcessionCash = 0;
                            $finalSalesConcessionCash = 0;
                            $finalTotalCountAdultCash = 0;
                            $finalTotalSalesAdultCash = 0;
                            //Card
                            $finalCountAdultCard = 0;
                            $finalSalesAdultCard = 0;
                            $finalCountConcessionCard = 0;
                            $finalSalesConcessionCard = 0;
                            $finalTotalCountAdultCard = 0;
                            $finalTotalSalesAdultCard = 0;
                            //Tngo
                            $finalCountAdultTouchNGo = 0;
                            $finalSalesAdultTouchNGo = 0;
                            $finalCountConcessionTouchNGo = 0;
                            $finalSalesConcessionTouchNGo = 0;
                            $finalTotalCountAdultTouchNGo = 0;
                            $finalTotalSalesAdultTouchNGo = 0;
        
                            //All trip drive by the driver
                            $allTrips = TripDetail::where('driver_id', $selectedDriver->id)
                            ->whereBetween('start_trip', [$firstDate, $lastDate])
                            ->get();
                            if (count($allTrips) > 0) {
                                foreach ($allTrips as $allTrip) {
                                    $perTrip['company_name'] = $companyDetails->company_name;
        
                                    if ($allTrip->bus_id != NULL) {
                                        $perTrip['bus_no'] = $allTrip->Bus->bus_registration_number;
                                    }else{
                                        $perTrip['bus_no'] = "No Data";
                                    }
                                    $perTrip['creation_date'] = $allTrip->start_trip;
                                    $perTrip['closed_by'] = $selectedDriver->driver_number;
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
        
                                    //Cash
                                    $countAdultCash = 0;
                                    $countConcessionCash = 0;
                                    $salesAdultCash = 0;
                                    $salesConcessionCash = 0;
                                    //Card
                                    $countAdultCard = 0;
                                    $countConcessionCard = 0;
                                    $salesAdultCard = 0;
                                    $salesConcessionCard = 0;
                                    //Tngo
                                    $countAdultTouchNGo = 0;
                                    $salesAdultTouchNGo = 0;
                                    $countConcessionTouchNGo = 0;
                                    $salesConcessionTouchNGo = 0;
                                    $allTickets = TicketSalesTransaction::where('trip_id', $allTrip->id)->get();
                                    if (count($allTickets) > 0) {
                                        foreach ($allTickets as $allTicket) {
                                            //Cash 
                                            if ($allTicket->fare_type == 0) {
                                                if ($allTicket->passenger_type == 0) {
                                                    $countAdultCash++;
                                                    $salesAdultCash += $allTicket->actual_amount;
                                                } else {
                                                    $countConcessionCash++;
                                                    $salesConcessionCash += $allTicket->actual_amount;
                                                }
                                            }
                                            //Card
                                            elseif ($allTicket->fare_type == 1) {
                                                if ($allTicket->passenger_type == 0) {
                                                    $countAdultCard++;
                                                    $salesAdultCard += $allTicket->actual_amount;
                                                } else {
                                                    $countConcessionCard++;
                                                    $salesConcessionCard += $allTicket->actual_amount;
                                                }
        
                                            }
                                            //Tngo
                                            else {
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
                                    $perTrip['cash_adult_qty'] = $countAdultCash;
                                    $perTrip['cash_adult_amount'] = $salesAdultCash;
                                    $perTrip['cash_concession_qty'] = $countConcessionCash;
                                    $perTrip['cash_concession_amount'] = $salesConcessionCash;
                                    $perTrip['cash_total_qty'] = $countAdultCash + $countConcessionCash;
                                    $perTrip['cash_total_amount'] = $salesAdultCash + $salesConcessionCash;
                                    $perTrip['card_adult_qty'] = $countAdultCard;
                                    $perTrip['card_adult_amount'] = $salesAdultCard;
                                    $perTrip['card_concession_qty'] = $countConcessionCard;
                                    $perTrip['card_concession_amount'] = $salesConcessionCard;
                                    $perTrip['card_total_qty'] = $countAdultCard + $countConcessionCard;
                                    $perTrip['card_total_amount'] = $salesAdultCard + $salesConcessionCard;
                                    $perTrip['touchNGo_adult_qty'] = $countAdultTouchNGo;
                                    $perTrip['touchNGo_adult_amount'] = $salesAdultTouchNGo;
                                    $perTrip['touchNGo_concession_qty'] = $countConcessionTouchNGo;
                                    $perTrip['touchNGo_concession_amount'] = $salesConcessionTouchNGo;
                                    $perTrip['touchNGo_total_qty'] = $countAdultTouchNGo + $salesAdultTouchNGo;
                                    $perTrip['touchNGo_total_amount'] = $countConcessionTouchNGo + $salesConcessionTouchNGo;
                                    $perTrip['total_adult_qty'] = $perTrip['cash_adult_qty'] + $perTrip['card_adult_qty'] + $perTrip['touchNGo_adult_qty'];
                                    $perTrip['total_adult_amount'] = $perTrip['cash_adult_amount'] + $perTrip['card_adult_amount'] + $perTrip['touchNGo_adult_amount'];
                                    $perTrip['total_concession_qty'] = $perTrip['cash_concession_qty'] + $perTrip['card_concession_qty'] + $perTrip['touchNGo_concession_qty'];
                                    $perTrip['total_concession_amount'] = $perTrip['cash_concession_amount'] + $perTrip['card_concession_amount'] + $perTrip['touchNGo_concession_amount'];
                                    $perTrip['total_qty'] = $perTrip['cash_total_qty'] + $perTrip['card_total_qty'] + $perTrip['touchNGo_total_qty'];
                                    $perTrip['total_amount'] = $perTrip['cash_total_amount'] + $perTrip['card_total_amount'] + $perTrip['touchNGo_total_amount'];
        
                                    $finalCountAdultCash += $perTrip['cash_adult_qty'];
                                    $finalSalesAdultCash += $perTrip['cash_adult_amount'];
                                    $finalCountConcessionCash += $perTrip['cash_concession_qty'];
                                    $finalSalesConcessionCash += $perTrip['cash_concession_amount'];
                                    $finalTotalCountAdultCash += $perTrip['cash_total_qty'];
                                    $finalTotalSalesAdultCash += $perTrip['cash_total_amount'];
                                    $finalCountAdultCard += $perTrip['card_adult_qty'];
                                    $finalSalesAdultCard += $perTrip['card_adult_amount'];
                                    $finalCountConcessionCard += $perTrip['card_concession_qty'];
                                    $finalSalesConcessionCard += $perTrip['card_concession_amount'];
                                    $finalTotalCountAdultCard += $perTrip['card_total_qty'];
                                    $finalTotalSalesAdultCard += $perTrip['card_total_amount'];
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
        
                            $total_driver['cash_adult_qty'] = $finalCountAdultCash;
                            $total_driver['cash_adult_amount'] = $finalSalesAdultCash;
                            $total_driver['cash_concession_qty'] = $finalCountConcessionCash;
                            $total_driver['cash_concession_amount'] = $finalSalesConcessionCash;
                            $total_driver['cash_total_qty'] = $finalTotalCountAdultCash;
                            $total_driver['cash_total_amount'] = $finalTotalSalesAdultCash;
                            $total_driver['card_adult_qty'] = $finalCountAdultCard;
                            $total_driver['card_adult_amount'] = $finalSalesAdultCard;
                            $total_driver['card_concession_qty'] = $finalCountConcessionCard;
                            $total_driver['card_concession_amount'] = $finalSalesConcessionCard;
                            $total_driver['card_total_qty'] = $finalTotalCountAdultCard;
                            $total_driver['card_total_amount'] = $finalTotalSalesAdultCard;
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
                                $data_per_driver[$selectedDriver->driver_number . ' - ' . $selectedDriver->driver_name] = [];
                            } else {
                                $perDriver['total'] = $total_driver;
                                $data_per_driver[$selectedDriver->driver_number . ' - ' . $selectedDriver->driver_name] = $perDriver;
                            }
        
                            $companyCountAdultCash += $total_driver['cash_adult_qty'];
                            $companySalesAdultCash += $total_driver['cash_adult_amount'];
                            $companyCountConcessionCash += $total_driver['cash_concession_qty'];
                            $companySalesConcessionCash += $total_driver['cash_concession_amount'];
                            $companyTotalCountAdultCash += $total_driver['cash_total_qty'];
                            $companyTotalSalesAdultCash += $total_driver['cash_total_amount'];
                            $companyCountAdultCard += $total_driver['card_adult_qty'];
                            $companySalesAdultCard += $total_driver['card_adult_amount'];
                            $companyCountConcessionCard += $total_driver['card_concession_qty'];
                            $companySalesConcessionCard += $total_driver['card_concession_amount'];
                            $companyTotalCountAdultCard += $total_driver['card_total_qty'];
                            $companyTotalSalesAdultCard += $total_driver['card_total_amount'];
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
                        $total_company['cash_adult_qty'] = $companyCountAdultCash;
                        $total_company['cash_adult_amount'] = $companySalesAdultCash;
                        $total_company['cash_concession_qty'] = $companyCountConcessionCash;
                        $total_company['cash_concession_amount'] = $companySalesConcessionCash;
                        $total_company['cash_total_qty'] = $companyTotalCountAdultCash;
                        $total_company['cash_total_amount'] = $companyTotalSalesAdultCash;
                        $total_company['card_adult_qty'] = $companyCountAdultCard;
                        $total_company['card_adult_amount'] = $companySalesAdultCard;
                        $total_company['card_concession_qty'] = $companyCountConcessionCard;
                        $total_company['card_concession_amount'] = $companySalesConcessionCard;
                        $total_company['card_total_qty'] = $companyTotalCountAdultCard;
                        $total_company['card_total_amount'] = $companyTotalSalesAdultCard;
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
        
                        $grandCountAdultCash += $total_company['cash_adult_qty'];
                        $grandSalesAdultCash += $total_company['cash_adult_amount'];
                        $grandCountConcessionCash += $total_company['cash_concession_qty'];
                        $grandSalesConcessionCash += $total_company['cash_concession_amount'];
                        $grandTotalCountAdultCash += $total_company['cash_total_qty'];
                        $grandTotalSalesAdultCash += $total_company['cash_total_amount'];
                        $grandCountAdultCard += $total_company['card_adult_qty'];
                        $grandSalesAdultCard += $total_company['card_adult_amount'];
                        $grandCountConcessionCard += $total_company['card_concession_qty'];
                        $grandSalesConcessionCard += $total_company['card_concession_amount'];
                        $grandTotalCountAdultCard += $total_company['card_total_qty'];
                        $grandTotalSalesAdultCard += $total_company['card_total_amount'];
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
        
                        $grand['cash_adult_qty'] = $grandCountAdultCash;
                        $grand['cash_adult_amount'] = $grandSalesAdultCash;
                        $grand['cash_concession_qty'] = $grandCountConcessionCash;
                        $grand['cash_concession_amount'] = $grandSalesConcessionCash;
                        $grand['cash_total_qty'] = $grandTotalCountAdultCash;
                        $grand['cash_total_amount'] = $grandTotalSalesAdultCash;
                        $grand['card_adult_qty'] = $grandCountAdultCard;
                        $grand['card_adult_amount'] = $grandSalesAdultCard;
                        $grand['card_concession_qty'] = $grandCountConcessionCard;
                        $grand['card_concession_amount'] = $grandSalesConcessionCard;
                        $grand['card_total_qty'] = $grandTotalCountAdultCard;
                        $grand['card_total_amount'] = $grandTotalSalesAdultCard;
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
            }
            return Excel::download(new SalesByDriver($salesByDriver, $companyName,  $validatedData['dateFrom'], $validatedData['dateTo']), 'SalesByDriver_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }
}
