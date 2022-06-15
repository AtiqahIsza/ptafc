<?php

namespace App\Http\Livewire;

use App\Models\Company;
use App\Models\Route;
use App\Models\Bus;
use App\Models\Stage;
use App\Models\StageMap;
use App\Models\VehiclePosition;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Support\Facades\DB;
use App\Controllers\VehiclePositionController;
use Illuminate\Support\Carbon;

class GpsRealtimeList extends Component
{
    public $companies;
    public $gpses;
    public $buses;
    public $viewedBus;
    public $vehiclePosition;
    
    public $selectedCompany = NULL;
    public $selectedBus = NULL;
    public $selectedDate = NULL;
    public $showViewModal = false;

    public function mount()
    {
        $this->gpses = collect();
        $this->companies = collect();
        $this->buses = collect();
        $this->viewedBus = collect();
        $this->vehiclePosition = collect();
    }

    public function render()
    {
        //$this->buses = Bus::orderBy('bus_registration_number')->get();
        $this->companies = Company::orderBy('company_name')->get();
        return view('livewire.gps-realtime');
    }

    public function updatedSelectedCompany($company)
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE  updatedSelectedCompany{}: " . $company);
        $out->writeln("selectedCompany: " . $this->selectedCompany);
        $out->writeln("selectedBus: " . $this->selectedBus);

        if ($this->selectedCompany!=NULL){
            if ($this->selectedCompany=='ALL'){
                $this->buses = Bus::orderBy('bus_registration_number')->get();
                if ($this->selectedBus!=NULL){
                    if ($this->selectedBus=='ALL'){
                        $out->writeln("YOU ARE IN HERE both ALL");
                        $join = DB::table('vehicle_position')
                            ->select('trip_id', DB::raw('MAX(id) as last_id'))
                            ->groupBy('trip_id');
                        $this->gpses = DB::table('vehicle_position as a')
                            ->joinSub($join, 'b', function ($join) {
                            $join->on('a.trip_id', '=', 'b.trip_id')
                            ->on('a.id', '=', 'b.last_id');
                            })
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->get(['a.*', 'c.bus_registration_number']);
                    }else{
                        $out->writeln("YOU ARE IN HERE company==ALL bus!=null");
                        $join = DB::table('vehicle_position')
                            ->select('trip_id', DB::raw('MAX(id) as last_id'))
                            ->groupBy('trip_id');
                        $this->gpses = DB::table('vehicle_position as a')
                            ->joinSub($join, 'b', function ($join) {
                            $join->on('a.trip_id', '=', 'b.trip_id')
                            ->on('a.id', '=', 'b.last_id');
                            })
                            ->where('a.bus_id', $this->selectedBus)
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->get(['a.*', 'c.bus_registration_number']);

                    }
                }
                //selectedBus==NULL
                else{
                    $out->writeln("YOU ARE IN HERE company==ALL bus==null");
                    $join = DB::table('vehicle_position')
                        ->select('trip_id', DB::raw('MAX(id) as last_id'))
                        ->groupBy('trip_id');
                    $this->gpses = DB::table('vehicle_position as a')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.trip_id', '=', 'b.trip_id')
                            ->on('a.id', '=', 'b.last_id');
                        })
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->get(['a.*', 'c.bus_registration_number']);
                }
            }
            else{
                $this->buses = Bus::where('company_id', $this->selectedCompany)
                    ->orderBy('bus_registration_number')->get();
                if ($this->selectedBus!=NULL){
                    if ($this->selectedBus=='ALL'){
                        $out->writeln("YOU ARE IN HERE company!=NULL bus==ALL");

                        $join = DB::table('vehicle_position')
                            ->select('trip_id', DB::raw('MAX(id) as last_id'))
                            ->groupBy('trip_id');

                        $busByCompanies = Bus::select('id')
                            ->where('company_id', $this->selectedCompany)
                            ->get();

                        $busByCompaniesArr = [];
                        foreach($busByCompanies as $busByCompany){
                            $busByCompaniesArr[] = $busByCompany->id;
                        }

                        $this->gpses = DB::table('vehicle_position as a')
                            ->joinSub($join, 'b', function ($join) {
                            $join->on('a.trip_id', '=', 'b.trip_id')
                            ->on('a.id', '=', 'b.last_id');
                            })
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->whereIn('a.bus_id', $busByCompaniesArr)
                            ->get(['a.*', 'c.bus_registration_number']);
                    }else{
                        $out->writeln("YOU ARE IN HERE company!=NULL bus!=NULL");
                        $join = DB::table('vehicle_position')
                            ->select('trip_id', DB::raw('MAX(id) as last_id'))
                            ->groupBy('trip_id');
                        $this->gpses = DB::table('vehicle_position as a')
                            ->joinSub($join, 'b', function ($join) {
                            $join->on('a.trip_id', '=', 'b.trip_id')
                            ->on('a.id', '=', 'b.last_id');
                            })
                            ->where('a.bus_id', $this->selectedBus)
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->get(['a.*', 'c.bus_registration_number']);
                    }
                }
                //selectedBus==NULL
                else{
                    $out->writeln("YOU ARE IN HERE company!=NULL bus==NULL");

                    $join = DB::table('vehicle_position')
                        ->select('trip_id', DB::raw('MAX(id) as last_id'))
                        ->groupBy('trip_id');

                    $busByCompanies = Bus::select('id')
                        ->where('company_id', $this->selectedCompany)
                        ->get();

                    $busByCompaniesArr = [];
                    foreach($busByCompanies as $busByCompany){
                        $busByCompaniesArr[] = $busByCompany->id;
                    }

                    $this->gpses = DB::table('vehicle_position as a')
                        ->joinSub($join, 'b', function ($join) {
                        $join->on('a.trip_id', '=', 'b.trip_id')
                        ->on('a.id', '=', 'b.last_id');
                        })
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->whereIn('a.bus_id', $busByCompaniesArr)
                        ->get(['a.*', 'c.bus_registration_number']);
                }
            }
        }else{
            if ($this->selectedBus=='ALL'){
                $out->writeln("YOU ARE IN HERE company==NULL bus==ALL");
                    $join = DB::table('vehicle_position')
                        ->select('trip_id', DB::raw('MAX(id) as last_id'))
                        ->groupBy('trip_id');
                    $this->gpses = DB::table('vehicle_position as a')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.trip_id', '=', 'b.trip_id')
                            ->on('a.id', '=', 'b.last_id');
                        })
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->get(['a.*', 'c.bus_registration_number']);
            }else{
                $this->gpses = collect();
            }
        }
    }

    public function updatedSelectedBus($bus)
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE updatedSelectedBus{}: " . $bus);
        $out->writeln("selectedCompany: " . $this->selectedCompany);
        $out->writeln("selectedBus: " . $this->selectedBus);

        if ($this->selectedCompany!=NULL){
            if ($this->selectedCompany=='ALL'){
                $this->buses = Bus::orderBy('bus_registration_number')->get();
                if ($this->selectedBus!=NULL){
                    if ($this->selectedBus=='ALL'){
                        $out->writeln("YOU ARE IN HERE both ALL");
                        $join = DB::table('vehicle_position')
                            ->select('trip_id', DB::raw('MAX(id) as last_id'))
                            ->groupBy('trip_id');
                        $this->gpses = DB::table('vehicle_position as a')
                            ->joinSub($join, 'b', function ($join) {
                            $join->on('a.trip_id', '=', 'b.trip_id')
                            ->on('a.id', '=', 'b.last_id');
                            })
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->get(['a.*', 'c.bus_registration_number']);
                    }else{
                        $out->writeln("YOU ARE IN HERE company==ALL bus!=null");
                        $join = DB::table('vehicle_position')
                            ->select('trip_id', DB::raw('MAX(id) as last_id'))
                            ->groupBy('trip_id');
                        $this->gpses = DB::table('vehicle_position as a')
                            ->joinSub($join, 'b', function ($join) {
                            $join->on('a.trip_id', '=', 'b.trip_id')
                            ->on('a.id', '=', 'b.last_id');
                            })
                            ->where('a.bus_id', $this->selectedBus)
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->get(['a.*', 'c.bus_registration_number']);

                    }
                }
                //selectedBus==NULL
                else{
                    $out->writeln("YOU ARE IN HERE company==ALL bus==null");
                    $join = DB::table('vehicle_position')
                        ->select('trip_id', DB::raw('MAX(id) as last_id'))
                        ->groupBy('trip_id');
                    $this->gpses = DB::table('vehicle_position as a')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.trip_id', '=', 'b.trip_id')
                            ->on('a.id', '=', 'b.last_id');
                        })
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->get(['a.*', 'c.bus_registration_number']);
                }
            }
            else{
                $this->buses = Bus::where('company_id', $this->selectedCompany)
                    ->orderBy('bus_registration_number')->get();
                if ($this->selectedBus!=NULL){
                    if ($this->selectedBus=='ALL'){
                        $out->writeln("YOU ARE IN HERE company!=NULL bus==ALL");

                        $join = DB::table('vehicle_position')
                            ->select('trip_id', DB::raw('MAX(id) as last_id'))
                            ->groupBy('trip_id');

                        $busByCompanies = Bus::select('id')
                            ->where('company_id', $this->selectedCompany)
                            ->get();

                        $busByCompaniesArr = [];
                        foreach($busByCompanies as $busByCompany){
                            $busByCompaniesArr[] = $busByCompany->id;
                        }

                        $this->gpses = DB::table('vehicle_position as a')
                            ->joinSub($join, 'b', function ($join) {
                            $join->on('a.trip_id', '=', 'b.trip_id')
                            ->on('a.id', '=', 'b.last_id');
                            })
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->whereIn('a.bus_id', $busByCompaniesArr)
                            ->get(['a.*', 'c.bus_registration_number']);
                    }else{
                        $out->writeln("YOU ARE IN HERE company!=NULL bus!=NULL");
                        $join = DB::table('vehicle_position')
                            ->select('trip_id', DB::raw('MAX(id) as last_id'))
                            ->groupBy('trip_id');
                        $this->gpses = DB::table('vehicle_position as a')
                            ->joinSub($join, 'b', function ($join) {
                            $join->on('a.trip_id', '=', 'b.trip_id')
                            ->on('a.id', '=', 'b.last_id');
                            })
                            ->where('a.bus_id', $this->selectedBus)
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->get(['a.*', 'c.bus_registration_number']);
                    }
                }
                //selectedBus==NULL
                else{
                    $out->writeln("YOU ARE IN HERE company!=NULL bus==NULL");

                    $join = DB::table('vehicle_position')
                        ->select('trip_id', DB::raw('MAX(id) as last_id'))
                        ->groupBy('trip_id');

                    $busByCompanies = Bus::select('id')
                        ->where('company_id', $this->selectedCompany)
                        ->get();

                    $busByCompaniesArr = [];
                    foreach($busByCompanies as $busByCompany){
                        $busByCompaniesArr[] = $busByCompany->id;
                    }

                    $this->gpses = DB::table('vehicle_position as a')
                        ->joinSub($join, 'b', function ($join) {
                        $join->on('a.trip_id', '=', 'b.trip_id')
                        ->on('a.id', '=', 'b.last_id');
                        })
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->whereIn('a.bus_id', $busByCompaniesArr)
                        ->get(['a.*', 'c.bus_registration_number']);
                }
            }
        }else{
            if ($this->selectedBus=='ALL'){
                $out->writeln("YOU ARE IN HERE company==NULL bus==ALL");
                    $join = DB::table('vehicle_position')
                        ->select('trip_id', DB::raw('MAX(id) as last_id'))
                        ->groupBy('trip_id');
                    $this->gpses = DB::table('vehicle_position as a')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.trip_id', '=', 'b.trip_id')
                            ->on('a.id', '=', 'b.last_id');
                        })
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->get(['a.*', 'c.bus_registration_number']);
            }else{
                $this->gpses = collect();
            }
        }
    }

    /**public function updatedSelectedDate($date)
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE  updatedSelectedDate{}: " . $date);

        if (!is_null($date)) {
            if ($this->selectedBus=='ALL' && $this->selectedDate=='ALL') {
                $out->writeln("YOU ARE IN HERE  both ALL");
                $join = DB::table('vehicle_position')
                    ->select('trip_id', DB::raw('MAX(id) as last_id'))
                    ->groupBy('trip_id');
                $this->gpses = DB::table('vehicle_position as a')
                    ->joinSub($join, 'b', function ($join) {
                    $join->on('a.trip_id', '=', 'b.trip_id')
                    ->on('a.id', '=', 'b.last_id');
                    })
                    ->join('bus as c', 'c.id', '=', 'a.bus_id')
                    ->get(['a.*', 'c.bus_registration_number']);
            }
            elseif ($this->selectedBus=='ALL' || $this->selectedBus==NULL && $this->selectedDate!=NULL) {
                $dateFrom = new Carbon($this->selectedDate);
                $dateTo = new Carbon($this->selectedDate . '23:59:59');

                $out->writeln("YOU ARE IN HERE  both ALL");
                $join = DB::table('vehicle_position')
                    ->select('trip_id', DB::raw('MAX(id) as last_id'))
                    ->groupBy('trip_id');
                $this->gpses = DB::table('vehicle_position as a')
                    ->joinSub($join, 'b', function ($join) {
                    $join->on('a.trip_id', '=', 'b.trip_id')
                    ->on('a.id', '=', 'b.last_id');
                    })
                    ->join('bus as c', 'c.id', '=', 'a.bus_id')
                    ->whereBetween('a.date_time', [$dateFrom, $dateTo])
                    ->get(['a.*', 'c.bus_registration_number']);
            }
            elseif ($this->selectedBus!=NULL && $this->selectedDate==NULL) {
                $out->writeln("YOU ARE IN HERE both ALL");
                $join = DB::table('vehicle_position')
                    ->select('trip_id', DB::raw('MAX(id) as last_id'))
                    ->groupBy('trip_id');
                $this->gpses = DB::table('vehicle_position as a')
                    ->joinSub($join, 'b', function ($join) {
                    $join->on('a.trip_id', '=', 'b.trip_id')
                    ->on('a.id', '=', 'b.last_id');
                    })
                    ->join('bus as c', 'c.id', '=', 'a.bus_id')
                    ->where('a.bus_id', $this->selectedBus)
                    ->get(['a.*', 'c.bus_registration_number']);
            }
            else{
                $dateFrom = new Carbon($this->selectedDate);
                $dateTo = new Carbon($this->selectedDate . '23:59:59');

                $out->writeln("YOU ARE IN HERE specific company specific bus");
                $join = DB::table('vehicle_position')
                    ->select('trip_id', DB::raw('MAX(id) as last_id'))
                    ->groupBy('trip_id');
                $this->gpses = DB::table('vehicle_position as a')
                    ->joinSub($join, 'b', function ($join) {
                    $join->on('a.trip_id', '=', 'b.trip_id')
                    ->on('a.id', '=', 'b.last_id');
                    })
                    ->join('bus as c', 'c.id', '=', 'a.bus_id')
                    ->where('a.bus_id', $this->selectedBus)
                    ->whereBetween('a.date_time', [$dateFrom, $dateTo])
                    ->get(['a.*', 'c.bus_registration_number']);
            }
        }
    }**/
}
