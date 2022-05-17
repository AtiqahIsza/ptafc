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
    public $schedules;
    public $selectedRegion = NULL;
    public $selectedCompany = NULL;
    public $selectedRoute = NULL;
    public $addNewButton = false;
    public $removedId;
    public $removedSchedule;
    public $editSchedules;
    public $editButton = false;
    public $state = [];
    public $buses;

    public function render()
    {
        $this->regions = RegionCode::orderBy('description')->get();
        return view('livewire.manage-route-scheduler');
    }

    public function mount()
    {
        $this->regions = collect();
        $this->companies = collect();
        $this->routes = collect();
        $this->schedules = collect();
        $this->buses = collect();
        $this->state = collect();
        $this->removedSchedule = collect();
    }

    public function updatedSelectedRegion($region)
    {
        if (!is_null($region)) {
            $this->selectedRegion = $region;
            $this->companies = Company::where('region_id', $region)
                ->orderBy('company_name')
                ->get();
        }
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->selectedCompany = $company;
            $this->routes = Route::where('company_id', $company)
                ->orderBy('route_name')
                ->get();
        }
    }

    public function updatedSelectedRoute($route)
    {
        if (!is_null($route)) {
            //$this->selectedRoute = $route;
            //$this->schedules = RouteSchedulerMSTR::where('route_id', $route)->get();
            $out = new ConsoleOutput();
            $out->writeln("YOU ARE IN HERE - manage");
            $this->schedules = RouteSchedulerMSTR::where('route_id', $route)
                ->orderBy('schedule_start_time')
                ->get();

            $this->emit('viewEvent', $route);
        }
    }

    public function addNew()
    {
        $this->selectedRoute = NULL;
        $this->addNewButton = true;
    }
}
