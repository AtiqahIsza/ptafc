<?php

namespace App\Http\Livewire;

use App\Models\Bus;
use App\Models\BusType;
use App\Models\Company;
use App\Models\Route;
use App\Models\Sector;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class ManageBus extends Component
{
    public $buses;
    public $companies;
    public $sectors;
    public $routes;
    public $busTypes;
    public $editedBuses;
    public $editedCompanies;
    public $removedBusId;
    public $removedBus;
    public $state = [];
    public $selectedCompany = NULL;
    public $showEditModal = false;

    public function mount()
    {
        $this->buses = collect();
        $this->companies = collect();
        $this->sectors = collect();
        $this->routes = collect();
        $this->busTypes = collect();
        $this->editedBuses = collect();
        $this->editedCompanies = collect();
        $this->removedBus = collect();
    }

    public function render()
    {
        $this->companies = Company::orderBy('company_name')->get();
        return view('livewire.manage-bus');
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->buses = Bus::where('company_id', $company)->orderBy('bus_registration_number')->get();
        }
    }

    public function edit(Bus $bus)
    {
        $this->showEditModal = true;
        $this->editedBuses = $bus;
        $this->state = $bus->toArray();
        $this->busTypes = BusType::all();
        $this->editedCompanies = Company::all();
        $this->dispatchBrowserEvent('show-form');
    }

    public function updateBus()
    {
        $validatedData = Validator::make($this->state,[
            'bus_registration_number' => ['required', 'string', 'max:255'],
            'bus_series_number' => ['required', 'string', 'max:255'],
            'company_id' => ['required', 'int'],
            'bus_manufacturing_date' => ['required'],
            'bus_type_id' => ['required', 'int'],
            'mac_address' => ['required', 'regex:((([a-zA-z0-9]{2}[-:]){5}([a-zA-z0-9]{2}))|(([a-zA-z0-9]{2}:){5}([a-zA-z0-9]{2})))'],
        ])->validate();

        $success = $this->editedBuses->update($validatedData);

        if($success){
            $this->buses = Bus::where('company_id', $this->selectedCompany)->orderBy('bus_registration_number')->get();
            $this->dispatchBrowserEvent('hide-form-edit');
        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }
    }

    public function addNew()
    {
        $this->state = [];
        $this->showEditModal = false;
        $this->busTypes = BusType::all();
        $this->editedCompanies = Company::all();
        $this->dispatchBrowserEvent('show-form');
    }

    public function createBus()
    {
        $validatedData = Validator::make($this->state, [
            'bus_registration_number' => ['required', 'string', 'max:255'],
            'bus_series_number' => ['required', 'string', 'max:255'],
            'company_id' => ['required', 'int'],
            'bus_manufacturing_date' => ['required'],
            'bus_type_id' => ['required', 'int'],
            'mac_address' => ['regex:((([a-zA-z0-9]{2}[-:]){5}([a-zA-z0-9]{2}))|(([a-zA-z0-9]{2}:){5}([a-zA-z0-9]{2})))'],
        ])->validate();

        $age = Carbon::parse($validatedData['bus_manufacturing_date'])->diff(Carbon::now())->y;
        $validatedData['bus_age'] = "$age";

        //dd($validatedData);

        $create = Bus::create($validatedData);

        if($create){
            $this->buses = Bus::where('company_id', $this->selectedCompany)->orderBy('bus_registration_number')->get();
            $this->dispatchBrowserEvent('hide-form-add');
        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }
    }

    public function confirmRemoval($id)
    {
        $this->removedBusId = $id;
        $selectedRemoved = Bus::where('id', $this->removedBusId)->first();
        $this->removedBus = $selectedRemoved->bus_registration_number;
        $this->dispatchBrowserEvent('show-delete-modal');
    }

    public function removeBus()
    {
        $remove = Bus::findOrFail($this->removedBusId);
        $successRemove = $remove->delete();

        if($successRemove) {
            $this->buses = Bus::where('company_id', $this->selectedCompany)->orderBy('bus_registration_number')->get();
            $this->dispatchBrowserEvent('hide-delete-modal');
        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }
    }
}
