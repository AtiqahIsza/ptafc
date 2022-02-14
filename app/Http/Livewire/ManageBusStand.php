<?php

namespace App\Http\Livewire;

use App\Models\BusStand;
use App\Models\Company;
use App\Models\Route;
use App\Models\Stage;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class ManageBusStand extends Component
{
    public $companies;
    public $routes;
    public $stages;
    public $busStands;

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
        if (!is_null($route)) {
            $this->stages = Stage::where('route_id', $route)->get();
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
}
