<?php

namespace App\Http\Livewire;

use App\Exports\SalesByRoute;
use App\Models\Route;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Console\Output\ConsoleOutput;

class ReportSalesByRoute extends Component
{
    public $routes;
    public $state = [];

    public function render()
    {
        return view('livewire.report-sales-by-route');
    }

    public function mount()
    {
        $this->routes=Route::all();
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
