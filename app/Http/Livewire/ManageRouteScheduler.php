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
    public $routes;
    public $schedules;
    public $selectedCompany = NULL;
    public $selectedRoute = NULL;
    public $addNewButton = false;
    public $removedId;
    public $removedSchedule;
    public $editSchedules;
    public $showEditModal = false;
    public $state = [];
    public $buses;
    public $editedCompanies;
    public $editedRoutes;
    public $editedSchedule;
    public $editedBuses;
    public $selectedEditCompany = NULL;

    public function render()
    {
        $this->companies = Company::orderBy('company_name')->get();
        return view('livewire.manage-route-scheduler');
    }

    public function mount()
    {
        $this->companies = collect();
        $this->routes = collect();
        $this->schedules = collect();
        $this->buses = collect();
        $this->editedCompanies = collect();
        $this->editedRoutes = collect();
        $this->editedSchedule = collect();
        $this->editedBuses = collect();
        $this->state = collect();
        $this->removedSchedule = collect();
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
            $this->schedules = RouteSchedulerMSTR::where('route_id', $route)->orderBy('schedule_start_time')->get();

            //$this->emit('viewEvent', $route);
        }
    }

    public function updatedSelectedEditCompany($company)
    {
        if (!is_null($company)) {
            $this->editedRoutes = Route::where('company_id', $company)->orderBy('route_name')->get();
            $this->editedBuses = Bus::where('company_id', $company)->orderBy('bus_registration_number')->get();
        }
    }

    public function addNew()
    {
        $this->schedules = RouteSchedulerMSTR::where('route_id', $this->selectedRoute)->orderBy('schedule_start_time')->get();

        $this->state = [];
        $this->showEditModal = false;
        $this->selectedEditCompany =  NULL;
        $this->editedCompanies = Company::orderBy('company_name')->get();
        $this->editedRoutes = Route::orderBy('route_name')->get();
        $this->editedBuses = Bus::orderBy('bus_registration_number')->get();
        $this->dispatchBrowserEvent('show-form');
    }

    public function addRouteSchedule()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE");

        $validatedData = Validator::make($this->state, [
            'schedule_start_time'=> ['required', 'date_format:H:i'],
            'schedule_end_time'=> ['required', 'date_format:H:i'],
            'route_id' => ['required', 'int'],
            'bus_id' => ['required', 'int'],
            'trip_code'=> ['required', 'int'],
            'trip_type'=> ['required', 'int'],
            'status'=> ['required', 'int'],
        ])->validate();

        //$validatedData['route_id'] = $this->selectedRoute;

        $out->writeln($validatedData['schedule_start_time']);
        $out->writeln($validatedData['route_id']);
        $out->writeln($validatedData['status']);
        $out->writeln($validatedData['trip_type']);

        $success = RouteSchedulerMSTR::create($validatedData);

        if($success){
            $this->schedules = RouteSchedulerMSTR::where('route_id', $this->selectedRoute)->orderBy('schedule_start_time')->get();
            $this->dispatchBrowserEvent('hide-form-add');
        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }
    }

    public function edit(RouteSchedulerMSTR $schedule){
        $this->schedules = RouteSchedulerMSTR::where('route_id', $this->selectedRoute)->orderBy('schedule_start_time')->get();

        $companyId = $schedule->Route->company_id;
        $this->selectedEditCompany =  $companyId;
        $this->editedCompanies = Company::orderBy('company_name')->get();
        $this->editedRoutes = Route::where('company_id', $this->selectedEditCompany)->orderBy('route_name')->get();
        $this->editedBuses = Bus::where('company_id', $this->selectedEditCompany)->orderBy('bus_registration_number')->get();
        $this->editSchedules = $schedule;
        $this->state = $schedule->toArray();
        $this->showEditModal = true;
        $this->dispatchBrowserEvent('show-form');
    }

    public function updateRouteSchedule()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE");

        $validatedData = Validator::make($this->state, [
            'schedule_start_time'=> ['required'],
            'schedule_end_time'=> ['required'],
            'route_id' => ['required', 'int'],
            'bus_id'=> ['required', 'int'],
            'trip_code'=> ['required', 'int'],
            'trip_type'=> ['required', 'int'],
            'status'=> ['required', 'int'],
        ])->validate();

        $out->writeln($validatedData['schedule_start_time']);
        $out->writeln($validatedData['route_id']);
        $out->writeln($validatedData['status']);
        $out->writeln($validatedData['trip_type']);

        $success = $this->editSchedules->update($validatedData);

        if($success){
            $this->schedules = RouteSchedulerMSTR::where('route_id', $this->selectedRoute)->orderBy('schedule_start_time')->get();
            $this->dispatchBrowserEvent('hide-form-edit');
        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }
    }

    public function confirmRemoval($id)
    {
        $this->schedules = RouteSchedulerMSTR::where('route_id', $this->selectedRoute)->orderBy('schedule_start_time')->get();

        $this->removedId = $id;
        $selectedRemoved = RouteSchedulerMSTR::where('id', $this->removedId)->first();
        $this->removedSchedule = $selectedRemoved->schedule_start_time . ' for ' .  $selectedRemoved->Route->route_name;

        $this->dispatchBrowserEvent('show-delete-modal');
    }

    public function removeSchedule()
    {
        $remove = RouteSchedulerMSTR::findOrFail($this->removedId);
        $successRemove = $remove->delete();

        if($successRemove){
            $this->schedules = RouteSchedulerMSTR::where('route_id', $this->selectedRoute)->orderBy('schedule_start_time')->get();
            $this->dispatchBrowserEvent('hide-delete-modal');
        }else{
            $this->dispatchBrowserEvent('hide-delete-failed');
        }
    }
}
