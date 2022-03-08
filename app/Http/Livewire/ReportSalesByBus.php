<?php

namespace App\Http\Livewire;

use App\Exports\SalesByBus;
use App\Models\Bus;
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
    public $state = [];
    public $heading = [];
    public $data = [];
    public $tot = [];
    public $grand = [];

    public function render()
    {
        return view('livewire.report-sales-by-bus');
    }

    public function mount()
    {
        $this->buses=Bus::all();
    }

    public function print()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'bus_id' => ['required', 'int'],
        ])->validate();

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)){
            $all_dates[] = $startDate->toDateString();

            $startDate->addDay();
        }
        $salesByBus = collect();

        foreach ($all_dates as $all_date)
        {
            $tripDetailsDate = TripDetail::where('start_trip', $all_date)
                ->where('end_trip', $all_date)
                ->get();

            if($tripDetailsDate) {

                $grandTotal = 0.0;

                foreach ($tripDetailsDate as $tripDetails) {

                    $data['start_trip'] = $tripDetails->start_trip;
                    $data['end_trip'] = $tripDetails->end_date;
                    $data['route_desc'] = $tripDetails->route->route_name;
                    $data['creation_by'] = $tripDetails->starrt_date;
                    $data['closed_by'] = $tripDetails->end_date;
                    $data['pda'] = $tripDetails->pda->imei;

                    $ticketSaleTransaction = TicketSalesTransaction::where('trip_id',$tripDetails->id)->get();
                        /*->where('bus_id', $validatedData['bus_id'])
                        ->where('sales_date', $all_date)
                        ->orderby('sales_date')*/

                    if ($ticketSaleTransaction) {

                        $totalCash = 0.0;
                        $totalCard = 0.0;
                        $totalTouchNGo = 0.0;
                        $totalCancelled = 0.0;
                        $totalBy = 0.0;

                        foreach ($ticketSaleTransaction as $ticketSale) {

                            $totalCash = $totalCash + $ticketSale->cash;
                            $totalCard = $totalCard + $ticketSale->card;
                            $totalTouchNGo = $totalTouchNGo + $ticketSale->touch_n_go;
                            $totalCancelled = $totalTouchNGo + $ticketSale->touch_n_go;
                        }

                        $totalBy = $totalCash + $totalCash + $totalTouchNGo + $totalCancelled;

                        $data['ticketSaleTransaction'] = $ticketSaleTransaction;
                        $data['total_cash'] = $totalCash;
                        $data['total_card'] = $totalCard;
                        $data['total_touch_n_go'] = $totalTouchNGo;
                        $data['total_cancelled'] = $totalCancelled;
                        $data['total_by'] = $totalBy;

                        $grandTotal = $grandTotal + $totalBy;

                    }
                    $salesByBus->add($data);
                }
            }

        }
        $grand['grand_total'] = $grandTotal;
        $salesByBus->add($grand);
        $busNo = Bus::where('id', $validatedData['bus_id'])->first();

        return Excel::download(new SalesByBus($salesByBus, $busNo), 'SalesByBus.xlsx');
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
