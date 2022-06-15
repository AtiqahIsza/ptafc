<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Company;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Support\Facades\DB;
use App\Models\Bus;
use Carbon\Carbon;

class GpsRealtime extends Component
{
    //public $gpses;
    public $companies;
    public $buses;
    public $vehiclePosition;
    public $selectedCompany = NULL;
    public $selectedBus = NULL;

    public $redGPS; //no location update > 1day
    public $yellowGPS; //no location update > 1hr
    public $greenGPS; //active location

    public function mount()
    {
        $this->redGPS = collect();
        $this->yellowGPS = collect();
        $this->greenGPS = collect();
        //$this->gpses = collect();
        $this->companies = collect();
        $this->buses = collect();
        $this->vehiclePosition = collect();
    }

    public function render()
    {
        $this->companies = Company::orderBy('company_name')->get();
        return view('livewire.gps-realtime');
    }

    public function updatedSelectedCompany($company)
    {
        $this->redGPS = NULL;
        $this->yellowGPS = NULL;
        $this->greenGPS = NULL;

        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE updatedSelectedCompanyNew{}: " . $company);
        $out->writeln("selectedCompany: " . $this->selectedCompany);
        $out->writeln("selectedBus: " . $this->selectedBus);

        $currentDate = Carbon::now();

        if ($this->selectedCompany!=NULL){
            if ($this->selectedCompany=='ALL'){
                $this->buses = Bus::orderBy('bus_registration_number')->get();
                if ($this->selectedBus!=NULL){
                    if ($this->selectedBus=='ALL'){
                        $out->writeln("YOU ARE IN HERE both ALL");
                        $join = DB::table('vehicle_position')
                            ->select('bus_id', DB::raw('MAX(id) as last_id'))
                            ->groupBy('bus_id');

                        $this->greenGPS = DB::table('vehicle_position as a')
                        ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']);

                        $this->yellowGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->redGPS = DB::table('vehicle_position as a')
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']);     
                        
                    }else{
                        $out->writeln("YOU ARE IN HERE company==ALL bus!=null");
                        $join = DB::table('vehicle_position')
                            ->select('bus_id', DB::raw('MAX(id) as last_id'))
                            ->where('bus_id', $this->selectedBus)
                            ->groupBy('bus_id');
                        $this->greenGPS = DB::table('vehicle_position as a')
                        ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']);

                        $this->yellowGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->redGPS = DB::table('vehicle_position as a')
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']); 
                    }
                }
                //selectedBus==NULL
                else{
                    $out->writeln("YOU ARE IN HERE both ALL");
                    $join = DB::table('vehicle_position')
                        ->select('bus_id', DB::raw('MAX(id) as last_id'))
                        ->groupBy('bus_id');
                        $this->greenGPS = DB::table('vehicle_position as a')
                        ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']);

                        $this->yellowGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->redGPS = DB::table('vehicle_position as a')
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']); 
                }
            }
            else{
                $this->buses = Bus::where('company_id', $this->selectedCompany)
                    ->orderBy('bus_registration_number')->get();
                if ($this->selectedBus!=NULL){
                    if ($this->selectedBus=='ALL'){
                        $out->writeln("YOU ARE IN HERE company!=NULL bus==ALL");

                        $busByCompanies = Bus::select('id')
                            ->where('company_id', $this->selectedCompany)
                            ->get();

                        $busByCompaniesArr = [];
                        foreach($busByCompanies as $busByCompany){
                            $busByCompaniesArr[] = $busByCompany->id;
                        }

                        $join = DB::table('vehicle_position')
                            ->select('bus_id', DB::raw('MAX(id) as last_id'))
                            ->whereIn('bus_id', $busByCompaniesArr)
                            ->groupBy('bus_id');

                        $this->greenGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->yellowGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->redGPS = DB::table('vehicle_position as a')
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']); 
                    }else{
                        $out->writeln("YOU ARE IN HERE company!=NULL bus!=NULL");
                        $join = DB::table('vehicle_position')
                            ->select('bus_id', DB::raw('MAX(id) as last_id'))
                            ->where('bus_id', $this->selectedBus)
                            ->groupBy('bus_id');

                        $this->greenGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->yellowGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->redGPS = DB::table('vehicle_position as a')
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']); 
                    }
                }
                //selectedBus==NULL
                else{
                    $out->writeln("YOU ARE IN HERE company!=NULL bus==NULL");

                    $busByCompanies = Bus::select('id')
                    ->where('company_id', $this->selectedCompany)
                    ->get();

                    $busByCompaniesArr = [];
                    foreach($busByCompanies as $busByCompany){
                        $busByCompaniesArr[] = $busByCompany->id;
                    }

                    $join = DB::table('vehicle_position')
                        ->select('bus_id', DB::raw('MAX(id) as last_id'))
                        ->whereIn('bus_id', $busByCompaniesArr)
                        ->groupBy('bus_id');

                    $this->greenGPS = DB::table('vehicle_position as a')
                        ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']);

                    $this->yellowGPS = DB::table('vehicle_position as a')
                        ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']);

                    $this->redGPS = DB::table('vehicle_position as a')
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']); 
                }
            }
        }
        //selectedCompany==NULL
        else{
            if ($this->selectedBus=='ALL'){
                $out->writeln("YOU ARE IN HERE company==NULL bus==ALL");
                $join = DB::table('vehicle_position')
                    ->select('bus_id', DB::raw('MAX(id) as last_id'))
                    ->groupBy('bus_id');

                $this->greenGPS = DB::table('vehicle_position as a')
                    ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                    ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                    ->join('bus as c', 'c.id', '=', 'a.bus_id')
                    ->joinSub($join, 'b', function ($join) {
                        $join->on('a.id', '=', 'b.last_id');
                    })
                    ->get(['a.*', 'c.bus_registration_number']);

                $this->yellowGPS = DB::table('vehicle_position as a')
                    ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                    ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                    ->join('bus as c', 'c.id', '=', 'a.bus_id')
                    ->joinSub($join, 'b', function ($join) {
                        $join->on('a.id', '=', 'b.last_id');
                    })
                    ->get(['a.*', 'c.bus_registration_number']);

                $this->redGPS = DB::table('vehicle_position as a')
                    ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                    ->join('bus as c', 'c.id', '=', 'a.bus_id')
                    ->joinSub($join, 'b', function ($join) {
                        $join->on('a.id', '=', 'b.last_id');
                    })
                    ->get(['a.*', 'c.bus_registration_number']); 
            }else{
                $this->redGPS = collect();
                $this->yellowGPS = collect();
                $this->greenGPS = collect();
            }
        }
        $this->dispatchBrowserEvent('livewire:load', ['redGPS' => $this->redGPS, 'yellowGPS' => $this->yellowGPS, 'greenGPS' => $this->greenGPS]);
    }

    public function updatedSelectedBus($bus)
    {
        $this->redGPS = NULL;
        $this->yellowGPS = NULL;
        $this->greenGPS = NULL;

        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE updatedSelectedBusNew{}: " . $bus);
        $out->writeln("selectedCompany: " . $this->selectedCompany);
        $out->writeln("selectedBus: " . $this->selectedBus);

        $currentDate = Carbon::now();

        if ($this->selectedCompany!=NULL){
            if ($this->selectedCompany=='ALL'){
                $this->buses = Bus::orderBy('bus_registration_number')->get();
                if ($this->selectedBus!=NULL){
                    if ($this->selectedBus=='ALL'){
                        $out->writeln("YOU ARE IN HERE both ALL");
                        $join = DB::table('vehicle_position')
                            ->select('bus_id', DB::raw('MAX(id) as last_id'))
                            ->groupBy('bus_id');

                        $this->greenGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->yellowGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->redGPS = DB::table('vehicle_position as a')
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);     
                        
                    }else{
                        $out->writeln("YOU ARE IN HERE company==ALL bus!=null");
                        $join = DB::table('vehicle_position')
                            ->select('bus_id', DB::raw('MAX(id) as last_id'))
                            ->where('bus_id', $this->selectedBus)
                            ->groupBy('bus_id');
                        $this->greenGPS = DB::table('vehicle_position as a')
                        ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']);

                        $this->yellowGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->redGPS = DB::table('vehicle_position as a')
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']); 
                    }
                }
                //selectedBus==NULL
                else{
                    $out->writeln("YOU ARE IN HERE both ALL");
                    $join = DB::table('vehicle_position')
                        ->select('bus_id', DB::raw('MAX(id) as last_id'))
                        ->groupBy('bus_id');
                        $this->greenGPS = DB::table('vehicle_position as a')
                        ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']);

                        $this->yellowGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->redGPS = DB::table('vehicle_position as a')
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']); 
                }
            }
            else{
                $this->buses = Bus::where('company_id', $this->selectedCompany)
                    ->orderBy('bus_registration_number')->get();
                if ($this->selectedBus!=NULL){
                    if ($this->selectedBus=='ALL'){
                        $out->writeln("YOU ARE IN HERE company!=NULL bus==ALL");

                        $busByCompanies = Bus::select('id')
                            ->where('company_id', $this->selectedCompany)
                            ->get();

                        $busByCompaniesArr = [];
                        foreach($busByCompanies as $busByCompany){
                            $busByCompaniesArr[] = $busByCompany->id;
                        }

                        $join = DB::table('vehicle_position')
                            ->select('bus_id', DB::raw('MAX(id) as last_id'))
                            ->whereIn('bus_id', $busByCompaniesArr)
                            ->groupBy('bus_id');

                        $this->greenGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->yellowGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->redGPS = DB::table('vehicle_position as a')
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']); 
                    }else{
                        $out->writeln("YOU ARE IN HERE company!=NULL bus!=NULL");
                        $join = DB::table('vehicle_position')
                            ->select('bus_id', DB::raw('MAX(id) as last_id'))
                            ->where('bus_id', $this->selectedBus)
                            ->groupBy('bus_id');

                        $this->greenGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->yellowGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->redGPS = DB::table('vehicle_position as a')
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']); 
                    }
                }
                //selectedBus==NULL
                else{
                    $out->writeln("YOU ARE IN HERE company!=NULL bus==NULL");

                    $busByCompanies = Bus::select('id')
                    ->where('company_id', $this->selectedCompany)
                    ->get();

                    $busByCompaniesArr = [];
                    foreach($busByCompanies as $busByCompany){
                        $busByCompaniesArr[] = $busByCompany->id;
                    }

                    $join = DB::table('vehicle_position')
                        ->select('bus_id', DB::raw('MAX(id) as last_id'))
                        ->whereIn('bus_id', $busByCompaniesArr)
                        ->groupBy('bus_id');

                    $this->greenGPS = DB::table('vehicle_position as a')
                        ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']);

                    $this->yellowGPS = DB::table('vehicle_position as a')
                        ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']);

                    $this->redGPS = DB::table('vehicle_position as a')
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']); 
                }
            }
        }
        //selectedCompany==NULL
        else{
            if ($this->selectedBus=='ALL'){
                $out->writeln("YOU ARE IN HERE company==NULL bus==ALL");
                $join = DB::table('vehicle_position')
                    ->select('bus_id', DB::raw('MAX(id) as last_id'))
                    ->groupBy('bus_id');

                $this->greenGPS = DB::table('vehicle_position as a')
                    ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                    ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                    ->join('bus as c', 'c.id', '=', 'a.bus_id')
                    ->joinSub($join, 'b', function ($join) {
                        $join->on('a.id', '=', 'b.last_id');
                    })
                    ->get(['a.*', 'c.bus_registration_number']);

                $this->yellowGPS = DB::table('vehicle_position as a')
                    ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                    ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                    ->join('bus as c', 'c.id', '=', 'a.bus_id')
                    ->joinSub($join, 'b', function ($join) {
                        $join->on('a.id', '=', 'b.last_id');
                    })
                    ->get(['a.*', 'c.bus_registration_number']);

                $this->redGPS = DB::table('vehicle_position as a')
                    ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                    ->join('bus as c', 'c.id', '=', 'a.bus_id')
                    ->joinSub($join, 'b', function ($join) {
                        $join->on('a.id', '=', 'b.last_id');
                    })
                    ->get(['a.*', 'c.bus_registration_number']); 
            }else{
                $this->redGPS = collect();
                $this->yellowGPS = collect();
                $this->greenGPS = collect();
            }
        }
        $this->dispatchBrowserEvent('livewire:load', ['redGPS' => $this->redGPS, 'yellowGPS' => $this->yellowGPS, 'greenGPS' => $this->greenGPS]);
    }

    public function forPolling()
    {
        $this->redGPS = NULL;
        $this->yellowGPS = NULL;
        $this->greenGPS = NULL;

        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE forPolling()");
        $out->writeln("selectedCompany: " . $this->selectedCompany);
        $out->writeln("selectedBus: " . $this->selectedBus);

        $currentDate = Carbon::now();

        if ($this->selectedCompany!=NULL){
            if ($this->selectedCompany=='ALL'){
                $this->buses = Bus::orderBy('bus_registration_number')->get();
                if ($this->selectedBus!=NULL){
                    if ($this->selectedBus=='ALL'){
                        $out->writeln("YOU ARE IN HERE both ALL");
                        $join = DB::table('vehicle_position')
                            ->select('bus_id', DB::raw('MAX(id) as last_id'))
                            ->groupBy('bus_id');

                        $this->greenGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->yellowGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->redGPS = DB::table('vehicle_position as a')
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);     
                        
                    }else{
                        $out->writeln("YOU ARE IN HERE company==ALL bus!=null");
                        $join = DB::table('vehicle_position')
                            ->select('bus_id', DB::raw('MAX(id) as last_id'))
                            ->where('bus_id', $this->selectedBus)
                            ->groupBy('bus_id');
                        $this->greenGPS = DB::table('vehicle_position as a')
                        ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']);

                        $this->yellowGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->redGPS = DB::table('vehicle_position as a')
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']); 
                    }
                }
                //selectedBus==NULL
                else{
                    $out->writeln("YOU ARE IN HERE both ALL");
                    $join = DB::table('vehicle_position')
                        ->select('bus_id', DB::raw('MAX(id) as last_id'))
                        ->groupBy('bus_id');
                        $this->greenGPS = DB::table('vehicle_position as a')
                        ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']);

                        $this->yellowGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->redGPS = DB::table('vehicle_position as a')
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']); 
                }
            }
            else{
                $this->buses = Bus::where('company_id', $this->selectedCompany)
                    ->orderBy('bus_registration_number')->get();
                if ($this->selectedBus!=NULL){
                    if ($this->selectedBus=='ALL'){
                        $out->writeln("YOU ARE IN HERE company!=NULL bus==ALL");

                        $busByCompanies = Bus::select('id')
                            ->where('company_id', $this->selectedCompany)
                            ->get();

                        $busByCompaniesArr = [];
                        foreach($busByCompanies as $busByCompany){
                            $busByCompaniesArr[] = $busByCompany->id;
                        }

                        $join = DB::table('vehicle_position')
                            ->select('bus_id', DB::raw('MAX(id) as last_id'))
                            ->whereIn('bus_id', $busByCompaniesArr)
                            ->groupBy('bus_id');

                        $this->greenGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->yellowGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->redGPS = DB::table('vehicle_position as a')
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']); 
                    }else{
                        $out->writeln("YOU ARE IN HERE company!=NULL bus!=NULL");
                        $join = DB::table('vehicle_position')
                            ->select('bus_id', DB::raw('MAX(id) as last_id'))
                            ->where('bus_id', $this->selectedBus)
                            ->groupBy('bus_id');

                        $this->greenGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->yellowGPS = DB::table('vehicle_position as a')
                            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']);

                        $this->redGPS = DB::table('vehicle_position as a')
                            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                            ->join('bus as c', 'c.id', '=', 'a.bus_id')
                            ->joinSub($join, 'b', function ($join) {
                                $join->on('a.id', '=', 'b.last_id');
                            })
                            ->get(['a.*', 'c.bus_registration_number']); 
                    }
                }
                //selectedBus==NULL
                else{
                    $out->writeln("YOU ARE IN HERE company!=NULL bus==NULL");

                    $busByCompanies = Bus::select('id')
                    ->where('company_id', $this->selectedCompany)
                    ->get();

                    $busByCompaniesArr = [];
                    foreach($busByCompanies as $busByCompany){
                        $busByCompaniesArr[] = $busByCompany->id;
                    }

                    $join = DB::table('vehicle_position')
                        ->select('bus_id', DB::raw('MAX(id) as last_id'))
                        ->whereIn('bus_id', $busByCompaniesArr)
                        ->groupBy('bus_id');

                    $this->greenGPS = DB::table('vehicle_position as a')
                        ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']);

                    $this->yellowGPS = DB::table('vehicle_position as a')
                        ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']);

                    $this->redGPS = DB::table('vehicle_position as a')
                        ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                        ->join('bus as c', 'c.id', '=', 'a.bus_id')
                        ->joinSub($join, 'b', function ($join) {
                            $join->on('a.id', '=', 'b.last_id');
                        })
                        ->get(['a.*', 'c.bus_registration_number']); 
                }
            }
        }
        //selectedCompany==NULL
        else{
            if ($this->selectedBus=='ALL'){
                $out->writeln("YOU ARE IN HERE company==NULL bus==ALL");
                $join = DB::table('vehicle_position')
                    ->select('bus_id', DB::raw('MAX(id) as last_id'))
                    ->groupBy('bus_id');

                $this->greenGPS = DB::table('vehicle_position as a')
                    ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '01:00:00'")
                    ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                    ->join('bus as c', 'c.id', '=', 'a.bus_id')
                    ->joinSub($join, 'b', function ($join) {
                        $join->on('a.id', '=', 'b.last_id');
                    })
                    ->get(['a.*', 'c.bus_registration_number']);

                $this->yellowGPS = DB::table('vehicle_position as a')
                    ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '01:00:00'")
                    ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
                    ->join('bus as c', 'c.id', '=', 'a.bus_id')
                    ->joinSub($join, 'b', function ($join) {
                        $join->on('a.id', '=', 'b.last_id');
                    })
                    ->get(['a.*', 'c.bus_registration_number']);

                $this->redGPS = DB::table('vehicle_position as a')
                    ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
                    ->join('bus as c', 'c.id', '=', 'a.bus_id')
                    ->joinSub($join, 'b', function ($join) {
                        $join->on('a.id', '=', 'b.last_id');
                    })
                    ->get(['a.*', 'c.bus_registration_number']); 
            }else{
                $this->redGPS = collect();
                $this->yellowGPS = collect();
                $this->greenGPS = collect();
            }
        }
        $this->dispatchBrowserEvent('livewire:poll', ['redGPS' => $this->redGPS, 'yellowGPS' => $this->yellowGPS, 'greenGPS' => $this->greenGPS]);
    }


}
