<?php

namespace App\Http\Livewire;

use App\Exports\SalesByBus;
use App\Exports\SalesByRoute;
use App\Models\Bus;
use App\Models\Company;
use App\Models\Route;
use App\Models\Stage;
use App\Models\StageFare;
use App\Models\TicketSalesTransaction;
use App\Models\TripDetail;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use phpDocumentor\Reflection\Types\Collection;
use Symfony\Component\Console\Output\ConsoleOutput;

class ReportSalesByRoute extends Component
{
    public $routes;
    public $companies;
    public $selectedCompany;
    public $state = [];
    public $data = [];
    public $tot = [];
    public $grand = [];

    public function render()
    {
        $this->companies = Company::all();
        return view('livewire.report-sales-by-route');
    }

    public function mount()
    {
        $this->companies = collect();
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
        $out->writeln("YOU ARE IN HERE salesByRoute print()");

        $validatedData = Validator::make($this->state, [
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required', 'int'],
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
        $colspan = ((count($all_dates) + 1) * 2) + 2;

        $salesByRoute = collect();
        $grandCollect = collect();

        $firstStages = Stage::where('route_id', $validatedData['route_id'])->orderby('stage_order')->get();
        if (count($firstStages) > 0){
            foreach ($firstStages as $firstStage) {
                $secondStages = Stage::where('route_id', $validatedData['route_id'])->orderby('stage_order')->get();

                if (count($secondStages) > 0) {
                    foreach ($secondStages as $secondStage) {
                        $fromto = $firstStage->stage_name . ' - ' . $secondStage->stage_name;
                        $totalCount=0;
                        $totalSales =0;
                        foreach ($all_dates as $all_date) {
                            $firstDate = new Carbon($all_date);
                            $lastDate = new Carbon($all_date . '11:59:59');
                            $countQty = 0;
                            $sales = 0.0;

                            $tripPerRoutes = TripDetail::where('route_id',  $validatedData['route_id'])
                                ->whereBetween('start_trip', [$firstDate, $lastDate])
                                ->get();

                            if (count($tripPerRoutes) > 0) {
                                foreach ($tripPerRoutes as $tripPerRoute) {
                                    $salesPerDate = TicketSalesTransaction::where('trip_id', $tripPerRoute->id)
                                        ->where('fromstage_stage_id', $firstStage->id)
                                        ->where('tostage_stage_id', $secondStage->id)
                                        ->whereBetween('sales_date', [$firstDate, $lastDate])
                                        ->orderby('fromstage_stage_id')
                                        ->get();

                                    if (count($salesPerDate) > 0) {
                                        foreach ($salesPerDate as $salePerDate) {
                                            $countQty++;
                                            $sales += $salePerDate->actual_amount;
                                        }
                                    }
                                }
                            }
                            $perDate['qty'] = $countQty;
                            $perDate['sales'] = $sales;
                            $stage[$all_date] = $perDate;

                            $totalCount += $countQty;
                            $totalSales += $sales;
                        }

                        $perStage['all_date'] = $stage;

                        $totalStage['qty'] = $totalCount;
                        $totalStage['sales'] = $totalSales;
                        $perStage['total_per_stage'] = $totalStage;

                        $data[$fromto] = $perStage;
                    }
                }
            }
        }
        $salesByRoute->add($data);

        //Calculate Grand
        $grandStage = [];
        $grandCount=0;
        $grandSales=0;
        foreach ($all_dates as $all_date) {
            $firstDate = new Carbon($all_date);
            $lastDate = new Carbon($all_date . '11:59:59');
            $totalQty = 0;
            $totalSales = 0.0;

            $tripPerRoutes = TripDetail::where('route_id',  $validatedData['route_id'])
                ->whereBetween('start_trip', [$firstDate, $lastDate])
                ->get();

            if (count($tripPerRoutes) > 0) {
                foreach ($tripPerRoutes as $tripPerRoute) {
                    $salesPerDate = TicketSalesTransaction::where('trip_id', $tripPerRoute->id)
                        ->whereBetween('sales_date', [$firstDate, $lastDate])
                        ->get();

                    if (count($salesPerDate) > 0) {
                        foreach ($salesPerDate as $salePerDate) {
                            $totalQty++;
                            $totalSales += $salePerDate->actual_amount;
                        }
                    }
                }
            }
            $perDate['qty'] = $totalQty;
            $perDate['sales'] = $totalSales;
            $grandStage[$all_date] = $perDate;

            $grandCount += $totalQty;
            $grandSales += $totalSales;
        }

        $grand_total['all_stage'] = $grandStage;
        $grand['grand_qty'] = $grandCount;
        $grand['grand_sales'] = $grandSales;
        $grand_total['grand_sales_by_route'] = $grand;

        $grandCollect->add($grand_total);

        return Excel::download(new SalesByRoute($salesByRoute, $grandCollect, $all_dates, $colspan), 'SalesByRoute.xlsx');
    }
}
