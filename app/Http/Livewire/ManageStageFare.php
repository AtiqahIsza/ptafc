<?php

namespace App\Http\Livewire;

use App\Models\Company;
use App\Models\Route;
use App\Models\Stage;
use App\Models\StageFare;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ManageStageFare extends Component
{

    public $fareTypes;
    public $stages;
    public $stageFares;
    public $companies;
    public $routes;
    public $maxColumns = 0;
    public $stageFrom;
    public $stageTo;

    public $removedStageId;
    public $state = [];
    public $selectedCompany = NULL;
    public $selectedRoute = NULL;
    public $showEditModal = false;

    public function mount()
    {
        $this->companies = collect();
        $this->routes = collect();
    }

    public function render()
    {
        $this->companies = Company::all();
        return view('livewire.manage-stage-fare');
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->routes = Route::where('company_id', $company)->get();
        }
    }

    public function updatedSelectedRoute($route)
    {
        if (!is_null($route)) {
            /*$this->maxColumns = DB::table('stage')
                    ->select(DB::raw('max(stage_order) as max'))
                    ->groupBy('route_id')
                    ->having('route_id', $route)
                    ->first();*/
            $sqlMax = Stage::where('route_id', $route)->groupby('route_id');
            $this->maxColumns = $sqlMax->max('stage_order');
            /*$this->maxColumns = Stage::max('stage_order')
                ->groupby('route_id')
                ->having('route_id', $route);*/
           /* Stage::max('stage_order')->groupby('route_id')->having('route_id', $route);
           $this->maxColumns = DB::select('SELECT MAX(stage_order) as max
                            FROM stage
                            GROUP BY route_id
                            HAVING route_id = "'.$route.'"')->get();*/
            $this->stageFrom = Stage::where('route_id', $route)->orderby('stage_order')->get()->toArray();
            $this->stageTo = Stage::where('route_id', $route)->orderby('stage_order')->get();
            //dd($this->stageTo );
            $this->stageFares = StageFare::where('route_id', $route)->get();
            $this->stages = Stage::where('route_id', $route)->get();
        }
    }

    public function fareType($route, $type)
    {
        $this->stageFares = StageFare::where('route_id', $route)->orderby('tostage_stage_id')->get();
        $this->stages = Stage::where('route_id', $route)->get();;
        //$this->maxColumn = Stage::where('route_id', $route)->max('stage_order');

        if($type=='Adult'){
            $this->fareTypes = 'Adult';
        }
        else{
            $this->fareTypes = 'Concession';
        }
    }
}
