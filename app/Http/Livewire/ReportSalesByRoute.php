<?php

namespace App\Http\Livewire;

use App\Exports\SalesByBus;
use App\Exports\SalesByRoute;
use App\Models\Bus;
use App\Models\Route;
use App\Models\Stage;
use App\Models\StageFare;
use App\Models\TicketSalesTransaction;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Console\Output\ConsoleOutput;

class ReportSalesByRoute extends Component
{
    public $routes;
    public $state = [];
    public $data = [];
    public $tot = [];
    public $grand = [];

    public function render()
    {
        return view('livewire.report-sales-by-route');
    }

    public function mount()
    {
        $this->routes=Route::all();
    }

    public function print()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required', 'int'],
        ])->validate();

        $out->writeln($validatedData['dateFrom']);
        $out->writeln($validatedData['dateTo']);

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }
        $colspan = ((count($all_dates) + 1)* 2) + 2;

        $salesByRoute = collect();

        //$allStages = Stage::where('route_id', $validatedData['route_id'])->orderby('stage_order');
        $allStageFares = StageFare::where('route_id', $validatedData['route_id'])
            ->orderby('fromstage_stage_id')
            ->get();

        $grandQuantity = 100;
        $grandSales = 12.0;
        foreach ($allStageFares  as $allStageFare)
        {
            $data['from_to'] = $allStageFare->fromstage->stage_name . " - " . $allStageFare->tostage->stage_name;

            $totSales = 0.0;
            $totQuantity = 0;
            foreach ($all_dates as $all_date)
            {
                $allSales= TicketSalesTransaction::where('route_id', $validatedData['route_id'])
                    ->where('fromstage_stage_id', $allStageFare->fromstage_stage_id)
                    ->where('tostage_stage_id', $allStageFare->tostage_stage_id)
                    ->where('sales_date', $all_date)
                    ->orderby('fromstage_stage_id')
                    ->get();

                $sales = 0.0;
                foreach ($allSales as $allSale)
                {
                    if($allSale->fare_type ==1) //Adult
                    {
                        $sales += $allStageFare->fare;
                    }
                    else{ //Concession
                        $sales += $allStageFare->consession_fare;
                    }
                }
                $qty = count($allSales);
                $perDate['date'] = $all_date;
                $perDate['quantity'] = $qty;
                $perDate['sales'] = $sales;

                $data['perDate'][$all_date] = $perDate;

                $totQuantity += $qty;
                $totSales += $sales;
            }
            $data['total_quantity'] = $totSales;
            $data['total_sales'] = $totSales;
            $salesByRoute->add($data);

            $grandQuantity += $totQuantity;
            $grandSales += $totSales;
        }

        $grandTotal = collect();
        foreach ($all_dates as $all_date)
        {
            $grand_tot_qty = 0;
            $grand_tot_sales = 0.0;
            foreach ($allStageFares  as $allStageFare)
            {
                $allSales= TicketSalesTransaction::where('route_id', $validatedData['route_id'])
                    ->where('sales_date', $all_date)
                    ->get();

                $tot_qty = 0;
                $sales = 0.0;
                foreach ($allSales as $allSale)
                {
                    if($allSale->fare_type ==1) //Adult
                    {
                        $sales += $allStageFare->fare;
                    }
                    else{ //Concession
                        $sales += $allStageFare->consession_fare;
                    }
                }
                $count_all_sales = count($allSales);

                $grand_tot_qty += $count_all_sales;
                $grand_tot_sales += $sales;
            }
            //$grand_date['date'] =  $all_date;
            $grand_date['grand_quantity'] = $grand_tot_qty;
            $grand_date['grand_sales'] = $grand_tot_sales;

            $grand['perDate'][$all_date] = $grand_date;
        }
        $grand['grand_total_quantity'] = $grandQuantity;
        $grand['grand_total_sales'] = $grandSales;

        $grandTotal->add($grand);
        return Excel::download(new SalesByRoute($salesByRoute, $grandTotal, $all_dates,$colspan), 'SalesByRoute.xlsx');
    }

    /*public function printDetails()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required', 'int'],
        ])->validate();

        $out->writeln("dateFrom:" . $validatedData['dateFrom']);
        $out->writeln("dateTo:" . $validatedData['dateTo']);
        $out->writeln("route_id:" . $validatedData['route_id']);

        $dateRange = CarbonPeriod::create($validatedData['dateFrom'], $validatedData['dateTo']);
        $saleRoutes = Route::where('id', $validatedData['route_id'])->get();
        dd($saleRoutes->toArray());

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
        $arr = [];

        $i=0;
        foreach ($saleRoutes as $saleRoute) {
            $i = $i++;

            $arr[] = array(
                'No' => $i,
                'Bus Registration Number' => $saleRoute->route_number,
                'Creation By' => $saleRoute->inbound_distance,
                'Closed By' => $saleRoute->outbound_distance,
                'Route Description' => $saleRoute->route_name,
                'System Trip Details' => $saleRoute->company->company_name,
                'Sales Date' => $saleRoute->sector->sector_name,
                'Ticket No' => $saleRoute->route_target,
                'From' => $saleRoute->fromstage_stage_id,
                'To' => $saleRoute->tostage_stage_id,
                'Type' => $saleRoute->distance,

            );
            if($saleRoute->status) {
                $arr['Total Sales'] = 'ACTIVE';
            }
            else {
                $arr['Total Sales'] = 'INACTIVE';
            }
        }

        $export = new SalesByRoute([$arr]);
        return Excel::download($export,'Sales Report By Bus.xlsx');
    }*/
}
