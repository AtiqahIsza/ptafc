<?php

namespace App\Http\Livewire;

use App\Models\RegionCode;
use App\Models\RouteSchedulerMSTR;
use App\Models\Sector;
use Carbon\Carbon;
use App\Models\Bus;
use App\Models\BusSchedulerDetail;
use App\Models\Route;
use App\Models\RouteSchedule;
use App\Models\Company;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Symfony\Component\Console\Output\ConsoleOutput;

class ManageRouteScheduler extends Component
{
    public $companies;
    public $regions;
    public $routes;
    public $sectors;
    public $schedules;

    public $selectedRegion;
    public $selectedCompany;
    public $selectedSector;
    public $selectedRoute;

    public $addNewButton = false;

    public function render()
    {
        $this->regions = RegionCode::all();
        $this->sectors = Sector::all();
        $this->companies = Company::all();
        $this->routes = Route::all();

        return view('livewire.manage-route-scheduler');
    }

    public function mount()
    {
        $this->sectors = collect();
        $this->companies = collect();
        $this->routes = collect();
        $this->regions = collect();
        $this->schedule = collect();
    }

    public function updatedSelectedRegion($region)
    {
        if (!is_null($region)) {
            $this->selectedRegion=$region;
            $this->companies = Company::where('region_id', $region)->get();
        }
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->selectedCompany=$company;
            $this->sectors = Sector::where('company_id', $company)->get();
        }
    }

    public function updatedSelectedSector($sector)
    {
        if (!is_null($sector)) {
            $this->selectedSector=$sector;
            $this->routes = Route::where('sector_id', $sector)->get();
        }
    }

    public function updatedSelectedRoute($route)
    {
        if (!is_null($route)) {
            $this->selectedRoute=$route;
            $this->schedules = RouteSchedulerMSTR::where('route_id', $route)->get();
        }
    }

    public function addNew(){
        $this->addNewButton = true;
    }

    public function modalAdd($startDate)
    {
        $this->startDate = $startDate;
        $this->buses = Bus::all();
        $this->routes = Route::all();
        $this->dispatchBrowserEvent('add-form');
    }

    public function updateSchedule()
    {
        $validatedData = Validator::make($this->state, [
            'title'=> ['required', 'string', 'max:255'],
            'sequence'=> ['required', 'int'],
            'time'=> ['required', 'date_format:H:i'],
            'start'=> ['required', 'date_format:Y-m-d'],
            'inbus_id'=> ['required', 'int'],
            'outbus_id'=> ['required', 'int'],
            'route_id'=> ['required', 'int'],
        ])->validate();

        $result = RouteSchedule::find($this->selectedId);
        $result->update($validatedData);

        //$this->schedule::update($validatedData);

        return redirect()->to('/settings/manageScheduler')->with(['message' => 'Route Schedule updated successfully!']);

        //return Redirect::back()->with(['message' => 'Sector updated successfully!']);
        //$this->emit('hide-form');
        //session()->flash('message', 'Sector successfully updated!');
        //$this->dispatchBrowserEvent('hide-form', ['message' => 'Sector updated successfully!']);
    }

    public function confirmRemoval($id)
    {
        $this->removedId = $id;
        $this->dispatchBrowserEvent('show-delete-modal');
    }

    public function removeRouteSchedule()
    {
        $schedule = RouteSchedule::findOrFail($this->removedId);
        $schedule->delete();

        return redirect()->to('/settings/manageScheduler')->with(['message' => 'Route Schedule removed successfully!']);
    }
}
