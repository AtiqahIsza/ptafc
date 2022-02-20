<?php

namespace App\Http\Livewire;

use App\Models\BusStand;
use App\Models\Company;
use App\Models\Route;
use App\Models\RouteMap;
use App\Models\Stage;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class ManageBusStand extends Component
{
    public $companies;
    public $routes;
    public $stages;
    public $busStands;
    public $removedBusStandRouteId;
    public $routeMap= false;
    public $removeBtn= false;
    public $selectedCompany = NULL;
    public $selectedRoute= NULL;
    public $selectedStage = NULL;

    public function mount()
    {
        $this->companies = collect();
        $this->routes = collect();
    }

    public function render()
    {
        $this->companies = Company::all();
        $this->routes= Route::all();
        return view('livewire.manage-bus-stand');
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->routes = Route::where('company_id', $company)->get();
        }
    }

    public function updatedSelectedRoute($route)
    {
        $this->routeMap = false;
        $this->removeBtn = false;
        //$this->selectedRoute = Route::where('id', $route)->first();

        if (!is_null($route)) {
            $existRouteMap = RouteMap::where('route_id', $route)->first();
            if($existRouteMap){
                $this->routeMap = true;
            }
            $existBusStand = BusStand::where('route_id', $route)->first();
            if($existBusStand){
                $this->removeBtn = true;
            }
            $this->busStands = BusStand::where('route_id', $route)->get();
        }
    }

    public function updatedSelectedStage($stage)
    {
        if (!is_null($stage)) {
            $this->busStands = BusStand::where('stage_id', $stage)->get();
        }
    }

    public function addNew()
    {
        $this->dispatchBrowserEvent('show-form');
    }

    public function createBusStand()
    {
        $validatedData = Validator::make($this->state, [
            'company_id'=> ['required', 'int'],
            'route_id'=> ['required', 'int'],
            'latitude'=> ['required', 'int'],
            'longitude'=> ['required', 'int'],
        ])->validate();

        BusStand::create($validatedData);

        return redirect()->to('/settings/manageBusStand')->with(['message' => 'Bus stand added successfully!']);

        //return Redirect::back()->with(['message' => 'Sector added successfully!']);
        //$this->dispatchBrowserEvent('hide-form', ['message' => 'Sector added successfully!']);
    }

    public function confirmRemoval($route)
    {
       // $routeId = Route::select('id')->where('id',$route->id)->first();
        $this->removedBusStandRouteId = $route;
        //$this->dispatchBrowserEvent('show-delete-modal');
    }

    public function removeSector()
    {
        $busStand = BusStand::where('route_id',$this->removedBusStandRouteId);
        $busStand->delete();

        return redirect()->to('/settings/manageBusStand')->with(['message' => 'Bus Stand removed successfully!']);
    }
}
