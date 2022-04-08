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
    public $removedBusId;
    public $state = [];
    public $selectedCompany = NULL;
    public $showEditModal = false;
    public $sectorId = 0;
    public $manufacturing_date;

    public function mount()
    {
        $this->buses = collect();
        $this->companies = collect();
        $this->sectors = collect();
        $this->routes = collect();
        $this->busTypes = collect();
    }

    public function render()
    {
        $this->companies = Company::all();
        $this->sectors = Sector::all();
        $this->routes = Route::all();
        $this->busTypes = BusType::all();
        return view('livewire.manage-bus');
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->buses = Bus::where('company_id', $company)->get();
        }
    }

    public function edit(Bus $bus)
    {
        //dd($user);
        $this->reset();
        $this->showEditModal = true;
        $this->buses = $bus;
        $this->state = $bus->toArray();
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

        $this->buses->update($validatedData);

        return redirect()->to('/settings/manageBus')->with(['message' => 'Bus updated successfully!']);

        //return Redirect::back()->with(['message' => 'Bus updated successfully!']);
        //$this->emit('hide-form');
        //session()->flash('message', 'Sector successfully updated!');
        //$this->dispatchBrowserEvent('hide-form', ['message' => 'Sector updated successfully!']);
    }

    public function addNew()
    {
        $this->reset();
        $this->showEditModal = false;
        $this->dispatchBrowserEvent('show-form');
    }

    public function createBus()
    {
        $validatedData = Validator::make($this->state, [
            'bus_registration_number' => ['required', 'string', 'max:255'],
            'bus_series_number' => ['required', 'string', 'max:255'],
            'company_id' => ['required', 'int'],
            'bus_manufacturing_date',
            'bus_type_id' => ['required', 'int'],
            'mac_address' => ['regex:((([a-zA-z0-9]{2}[-:]){5}([a-zA-z0-9]{2}))|(([a-zA-z0-9]{2}:){5}([a-zA-z0-9]{2})))'],
        ])->validate();

        $create = Bus::create($validatedData);

        if($create){
            return redirect()->to('/settings/manageBus')->with(['message' => 'Bus added successfully!']);
        }
        return redirect()->to('/settings/manageBus')->with(['message' => 'Failed To Add Bus!']);

        //return Redirect::back()->with(['message' => 'Bus added successfully!']);
        //$this->dispatchBrowserEvent('hide-form', ['message' => 'Sector added successfully!']);
    }

    public function confirmRemoval($id)
    {
        $this->removedBusId = $id;
        $this->dispatchBrowserEvent('show-delete-modal');
    }

    public function removeBus()
    {
        $sector = Bus::findOrFail($this->removedBusId);
        $sector->delete();

        return redirect()->to('/settings/manageBus')->with(['message' => 'Bus removed successfully!']);

        //return Redirect::back()->with(['message' => 'Bus removed successfully!']);
        //$this->dispatchBrowserEvent('hide-delete-modal', ['message' => 'Company deleted successfully!']);
    }
}
