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
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\Console\Output\ConsoleOutput;

class ManageRouteScheduler extends Component
{
    public $companies;
    public $regions;
    public $routes;
    public $sectors;
    public $schedules;

    public $selectedRegion = NULL;
    public $selectedCompany = NULL;
    public $selectedSector = NULL;
    public $selectedRoute = NULL;

    public $addNewButton = false;

    public $removedId;
    public $removedSchedule;

    //protected $listeners = ['refreshManageRouteScheduler' => '$refresh'];

    public function render()
    {
        return view('livewire.manage-route-scheduler');
    }

    public function mount()
    {
        $this->regions = RegionCode::all();
        $this->sectors = collect();
        $this->companies = collect();
        $this->routes = collect();
        $this->schedules = collect();
    }

    public function updatedSelectedRegion($region)
    {
        if (!is_null($region)) {
            $this->selectedRegion = $region;
            $this->companies = Company::where('region_id', $region)->get();
        }
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->selectedCompany = $company;
            $this->sectors = Sector::where('company_id', $company)->get();
        }
    }

    public function updatedSelectedSector($sector)
    {
        if (!is_null($sector)) {
            $this->selectedSector = $sector;
            $this->routes = Route::where('sector_id', $sector)->get();
        }
    }

    public function updatedSelectedRoute($route)
    {
        if (!is_null($route)) {
            //$this->selectedRoute = $route;
            //$this->schedules = RouteSchedulerMSTR::where('route_id', $route)->get();
            $out = new ConsoleOutput();
            $out->writeln("YOU ARE IN HERE - manage");
            $this->emit('viewEvent', $route);
        }

    }

    public function addNew()
    {
        $this->selectedRoute = NULL;
        $this->addNewButton = true;
    }
}
