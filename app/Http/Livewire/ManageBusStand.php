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
    public $busStands;
    public $busStandsModal;

    public $removedBusStandRouteId;

    public $haveRouteMap= false;
    public $haveBusStand= false;

    public $selectedCompany = NULL;
    public $selectedRoute= NULL;

    public function mount()
    {
        $this->companies = Company::all();
        $this->routes = collect();
    }

    public function render()
    {
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
        $this->haveRouteMap = false;
        $this->haveBusStand = false;
        //$this->selectedRoute = Route::where('id', $route)->first();

        if (!is_null($route)) {
            $existRouteMap = RouteMap::where('route_id', $route)->first();
            if($existRouteMap){
                $this->haveRouteMap = true;
            }
            $existBusStand = BusStand::where('route_id', $route)->first();
            if($existBusStand){
                $this->haveBusStand = true;
            }
            $this->busStands = BusStand::where('route_id', $route)->get();
        }
    }

    public function confirmRemoval($route)
    {
        $this->removedBusStandRouteId = $route;
    }

    public function removeSector()
    {
        $busStand = BusStand::where('route_id',$this->removedBusStandRouteId);
        $busStand->delete();

        return redirect()->to('/settings/manageBusStand')->with(['message' => 'Bus Stand removed successfully!']);
    }

    public function editDesc(BusStand $busstand)
    {
        //dd($user);
        //$this->reset();
        $this->busStandsModal = $busstand;
        $this->state = $busstand->toArray();
    }

    public function updateDesc()
    {
        $validatedData = Validator::make($this->state,[
            'description' => ['required', 'string', 'max:255'],
        ])->validate();

        $this->busStandsModal->update($validatedData);

        return redirect()->to('/settings/manageBusStand')->with(['message' => 'Bus Stand updated successfully!']);

        //return Redirect::back()->with(['message' => 'Sector updated successfully!']);
        //$this->emit('hide-form');
        //session()->flash('message', 'Sector successfully updated!');
        //$this->dispatchBrowserEvent('hide-form', ['message' => 'Sector updated successfully!']);
    }

}
