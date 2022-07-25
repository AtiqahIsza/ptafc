<?php

namespace App\Http\Livewire;

use App\Models\Company;
use App\Exports\AverageSummary;
use App\Models\Bus;
use App\Models\Route;
use App\Models\Stage;
use App\Models\TicketSalesTransaction;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Support\Carbon;
use App\Models\RouteSchedulerMSTR;
use App\Models\RouteSchedulerDetail;
use App\Models\TripDetail;
use Illuminate\Support\Facades\DB;

class ReportAverageSummary extends Component
{
    public $companies;
    public $routes;
    public $selectedCompany = NULL;
    public $state = [];

    public function render()
    {
        $this->companies = Company::orderBy('company_name')->get();
        return view('livewire.report-average-summary');
    }

    public function mount()
    {
        $this->companies=collect();
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
        $out->writeln("YOU ARE IN printDailySummary()");

        $validatedData = Validator::make($this->state,[
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
            'route_id' => ['required'],
        ])->validate();

        $dateFrom = new Carbon($validatedData['dateFrom']);
        $dateTo = new Carbon($validatedData['dateTo']. '23:59:59');

        $startDate = new Carbon($validatedData['dateFrom']);
        $endDate = new Carbon($validatedData['dateTo']);
        $all_dates = array();

        while ($startDate->lte($endDate)) {
            $all_dates[] = $startDate->toDateString();
            $startDate->addDay();
        }

        $averageSummary = collect();
        if ($this->selectedCompany){
            $companyDetails = Company::where('id', $this->selectedCompany)->first();
            $networkArea = $companyDetails->company_name;
            if(!empty($validatedData['route_id'])) {
                //Summary By Route all route certain company
                if($validatedData['route_id']=='All'){
                    $chosenRoutes = Route::where('company_id', $this->selectedCompany)->orderBy('route_number')->get();
                    $colspan = 4 + (3 * count($chosenRoutes));

                    foreach ($all_dates as $all_date) {
                        $totalTripCompliance = 0;
                        $averagePerDate = 0;
                        $firstDate = new Carbon($all_date);
                        $lastDate = new Carbon($all_date .'23:59:59');
                        $isWeekday = false;
                        $isWeekend = false;
                        $isWeekday = $firstDate->isWeekday();
                        $isWeekend =  $firstDate->isWeekend();
                        if($isWeekday){
                            $data['week'] = 'WEEKDAY';
                        }else{
                            $data['week'] = 'WEEKEND';
                        }

                        foreach ($chosenRoutes as $routePerCompany) {
                            if($isWeekday){
                                $isFriday = $firstDate->format('l');
                                if($isFriday=='Friday'){
                                    $tripPlanned = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,7,12])
                                    ->where('status', 1)
                                    ->where('route_id', $routePerCompany->id)
                                    ->count();
                                }else{
                                    $tripPlanned = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,4,6,9,10])
                                    ->where('status', 1)
                                    ->where('route_id', $routePerCompany->id)
                                    ->count();
                                }
                            }
                            if($isWeekend){
                                $isSunday = $firstDate->format('l');
                                if($isSunday=='Sunday'){
                                    $tripPlanned = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11])
                                    ->where('route_id', $routePerCompany->id)
                                    ->where('status', 1)
                                    ->count();
                                }else{
                                    $tripPlanned = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12])
                                    ->where('status', 1)
                                    ->where('route_id', $routePerCompany->id)
                                    ->count();
                                }
                            }

                            $tripServed = TripDetail::where('route_id', $routePerCompany->id)
                                ->whereBetween('start_trip', [$firstDate, $lastDate])
                                ->count();
                            
                            if($tripPlanned==0){
                                $tripCompliancePerRoute = 0;
                            }else{
                                $tripCompliancePerRoute = round((($tripServed/$tripPlanned) * 100),0);
                            }

                            $data['trip_planned_' .  $routePerCompany->route_number] = $tripPlanned;
                            $data['trip_served_' . $routePerCompany->route_number] = $tripServed;
                            $data['trip_compliance_' . $routePerCompany->route_number] = $tripCompliancePerRoute;

                            $totalTripCompliance += $tripCompliancePerRoute;
                        }

                        $averagePerDate = ($totalTripCompliance/(100 *count($chosenRoutes)))*100;
                        $data['average'] = round($averagePerDate,0);

                        $datePerDate[$all_date] = $data;
                    }
                    $grand['data'] = $datePerDate;

                    foreach ($chosenRoutes as $routePerCompany) {
                        $schedules = RouteSchedulerMSTR::select('id')->where('route_id', $routePerCompany->id)
                            ->orderBy('schedule_start_time')
                            ->get()
                            ->toArray();

                        $grandTripPlanned = RouteSchedulerDetail::whereBetween('schedule_date',[$dateFrom,$dateTo])
                            ->whereIn('route_scheduler_mstr_id', $schedules)
                            ->count();

                        $grandTripServed = TripDetail::whereBetween('start_trip',[$dateFrom,$dateTo])
                            ->where('route_id', $routePerCompany->id)
                            ->count();

                        if($grandTripPlanned==0){
                            $grandTripCompliance = 0;
                        }else{
                            $grandTripCompliance = round((($grandTripServed/$grandTripPlanned)*100),0);
                        }

                        $total['trip_planned_' . $routePerCompany->route_number] = $grandTripPlanned;
                        $total['trip_served_' . $routePerCompany->route_number] = $grandTripServed;
                        $total['trip_compliance_' . $routePerCompany->route_number] = $grandTripCompliance;
                        $grand['grand'] = $total;
                    }

                    $averageSummary->add($grand);
                }
                else{
                    $colspan = 4 + 3;
                    foreach ($all_dates as $all_date) {
                        $totalTripCompliance = 0;
                        $averagePerDate = 0;
                        $firstDate = new Carbon($all_date);
                        $lastDate = new Carbon($all_date .'23:59:59');
                        $isWeekday = false;
                        $isWeekend = false;
                        $isWeekday = $firstDate->isWeekday();
                        $isWeekend =  $firstDate->isWeekend();
                        if($isWeekday){
                            $data['week'] = 'WEEKDAY';
                        }else{
                            $data['week'] = 'WEEKEND';
                        }

                        $chosenRoutes = Route::where('id', $validatedData['route_id'])->first();
                        if($isWeekday){
                            $isFriday = $firstDate->format('l');
                            if($isFriday=='Friday'){
                                $tripPlanned = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,7,12])
                                ->where('status', 1)
                                ->where('route_id', $chosenRoutes->id)
                                ->count();
                            }else{
                                $tripPlanned = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,4,6,9,10])
                                ->where('status', 1)
                                ->where('route_id', $chosenRoutes->id)
                                ->count();
                            }
                        }
                        if($isWeekend){
                            $isSunday = $firstDate->format('l');
                            if($isSunday=='Sunday'){
                                $tripPlanned = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11])
                                ->where('route_id', $chosenRoutes->id)
                                ->where('status', 1)
                                ->count();
                            }else{
                                $tripPlanned = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12])
                                ->where('status', 1)
                                ->where('route_id', $chosenRoutes->id)
                                ->count();
                            }
                        }

                        $tripServed = TripDetail::where('route_id', $chosenRoutes->id)
                            ->whereBetween('start_trip', [$firstDate, $lastDate])
                            ->count();
                        
                        if($tripPlanned==0){
                            $tripCompliancePerRoute = 0;
                        }else{
                            $tripCompliancePerRoute = round((($tripServed/$tripPlanned) * 100),0);
                        }

                        $data['trip_planned_' . $chosenRoutes->route_number] = $tripPlanned;
                        $data['trip_served_' . $chosenRoutes->route_number] = $tripServed;
                        $data['trip_compliance_' . $chosenRoutes->route_number] = $tripCompliancePerRoute;

                        $totalTripCompliance += $tripCompliancePerRoute;

                        $averagePerDate = ($totalTripCompliance/100)*100;
                        $data['average'] = round($averagePerDate,0);

                        $datePerDate[$all_date] = $data;
                    }
                    $averageSummary->add($datePerDate);
                }
            }
            return Excel::download(new AverageSummary($averageSummary, $chosenRoutes, $colspan, $validatedData['dateFrom'], $validatedData['dateTo'], $networkArea), 'Average_Summary_Report_'.Carbon::now()->format('YmdHis').'.xlsx');
        }else{
            $this->dispatchBrowserEvent('company-required');
        }
    }
}
