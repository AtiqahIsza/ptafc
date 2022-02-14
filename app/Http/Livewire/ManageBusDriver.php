<?php

namespace App\Http\Livewire;

use App\Models\Bus;
use App\Models\BusDriver;
use App\Models\Company;
use App\Models\Route;
use App\Models\Sector;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class ManageBusDriver extends Component
{
    public $drivers;
    public $companies;
    public $sectors;
    public $routes;
    public $buses;
    public $state = [];
    public $selectedCompany = NULL;
    //public $showEditModal = false;

    public function mount()
    {
        $this->drivers = collect();
        $this->companies = collect();
        $this->sectors = collect();
        $this->routes = collect();
        $this->buses = collect();
    }

    public function render()
    {
        $this->companies = Company::all();
        return view('livewire.manage-bus-driver');
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->drivers = BusDriver::where('company_id', $company)->get();
        }
    }

    public function addNew()
    {
        $this->reset();
        $this->sectors = Sector::all();
        $this->routes = Route::all();
        $this->buses = Bus::all();
        $this->dispatchBrowserEvent('show-form');
    }

    public function createBusDriver()
    {
        //dd($this->buses);

        $validatedData = Validator::make($this->state, [
            'driver_name' => ['required', 'string', 'max:255'],
            'employee_number' => ['required', 'string', 'max:255'],
            'id_number' => ['required', 'string', 'max:255'],
            'driver_role' => ['required', 'int'],
            'status' => ['required', 'int'],
            'target_collection' => ['required', 'between:0,99.99'],
            'driver_number' => ['required', 'string', 'max:255'],
            'driver_password' => ['required', 'string', 'min:8', 'confirmed'],
            'company_id' => ['required', 'int'],
            'sector_id' => ['required', 'int'],
            'route_id' => ['required', 'int'],
            'bus_id' => ['required', 'int'],
        ])->validate();

        $validatedData['driver_password'] = bcrypt($validatedData['driver_password']);

        BusDriver::create($validatedData);

        return redirect()->to('/settings/manageBusDriver')->with(['message' => 'Bus Driver Added Successfully!']);

        //return Redirect::back()->with(['message' => 'Bus added successfully!']);
        //$this->dispatchBrowserEvent('hide-form', ['message' => 'Sector added successfully!']);
    }
}
