<?php

namespace App\Http\Livewire;

use App\Models\Company;
use App\Models\Route;
use App\Models\Stage;
use App\Models\StageFare;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Support\Facades\Validator;

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
            $sqlMax = Stage::where('route_id', $route)->groupby('route_id');
            $this->maxColumns = $sqlMax->max('stage_order');
            $this->stageFrom = Stage::where('route_id', $route)->orderby('stage_order')->get()->toArray();
            $this->stageTo = Stage::where('route_id', $route)->orderby('stage_order')->get();
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

    public function modalDisc(Route $route)
    {
        $this->selectedRoute = $route;
        $this->dispatchBrowserEvent('show-disc-form');
    }

    public function applyDiscount()
    {
        $validatedData = Validator::make($this->state,[
            'discount' => ['required', 'numeric'],
        ])->validate();

        $discount = $validatedData['discount'];

        $adultFares = StageFare::where('route_id', $this->selectedRoute->id)->orderby('tostage_stage_id')->get();

        $countUpdate=0;
        foreach($adultFares as $adultFare){
            $calc = $adultFare->fare - ($adultFare->fare * ($discount/100));
            $updateConcFare = StageFare::where('route_id', $this->selectedRoute->id)
            ->where('tostage_stage_id', $adultFare->tostage_stage_id)
            ->where('fromstage_stage_id', $adultFare->fromstage_stage_id)
            ->update(['consession_fare' => $calc]);

            if($updateConcFare){
                $countUpdate++;
            }
        }
        $sqlMax = Stage::where('route_id', $this->selectedRoute->id)->groupby('route_id');
        $this->maxColumns = $sqlMax->max('stage_order');
        $this->stageFrom = Stage::where('route_id', $this->selectedRoute->id)->orderby('stage_order')->get()->toArray();
        $this->stageTo = Stage::where('route_id', $this->selectedRoute->id)->orderby('stage_order')->get();
        $this->stageFares = StageFare::where('route_id', $this->selectedRoute->id)->get();
        $this->stages = Stage::where('route_id', $this->selectedRoute->id)->get();

        if($countUpdate==0){
            $this->dispatchBrowserEvent('hide-disc-failed');
        }else{
            $this->dispatchBrowserEvent('hide-disc-edit');
        }
    }
}
