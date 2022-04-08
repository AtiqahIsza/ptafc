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
    public $state2 = [];
    public $selectedCompany = NULL;

    public $selectedAddCompany = NULL;
    public $selectedAddSector = NULL;
    public $selectedAddRoute = NULL;

    public $changedDriverId;
    public $changedDriverName;
    public $desiredStatus;
    //public $showEditModal = false;

    public function mount()
    {
        $this->drivers = collect();
        $this->companies = collect();
        $this->sectors = collect();
        $this->routes = collect();
        $this->buses = collect();
        $this->changedDriverId = collect();
        $this->changedDriverName = collect();
        $this->desiredStatus = collect();
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
        $this->companies = Company::all();
        $this->dispatchBrowserEvent('show-form');
    }

    /*public function updatedSelectedAddCompany($company)
    {
        if (!is_null($company)) {
            $this->selectedAddCompany=$company;
            $this->sectors = Sector::where('company_id', $company)->get();
        }
    }

    public function updatedSelectedAddSector($sector)
    {
        if (!is_null($sector)) {
            $this->selectedAddSector=$sector;
            $this->routes = Route::where('sector_id', $sector)->get();
        }
    }

    public function updatedSelectedAddRoute($route)
    {
        if (!is_null($route)) {
            $this->selectedAddRoute=$route;
            $this->buses = Bus::where('route_id', $route)->get();
        }
    }*/

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
            'company_id' => ['required', 'int'],
            'driver_number' => ['required', 'string', 'max:255'],
            'driver_password' => ['required', 'string', 'min:8', 'confirmed'],
        ])->validate();

        $validatedData['driver_password'] = bcrypt($validatedData['driver_password']);

        $create = BusDriver::create($validatedData);

        if($create){
            return redirect()->to('/settings/manageBusDriver')->with(['message' => 'Bus Driver Added Successfully!']);
        }
        return redirect()->to('/settings/manageBusDriver')->with(['message' => 'Failed To Add Bus Driver!']);


        //return Redirect::back()->with(['message' => 'Bus added successfully!']);
        //$this->dispatchBrowserEvent('hide-form', ['message' => 'Sector added successfully!']);
    }

    public function resetModal(BusDriver $driver)
    {
        $this->reset();
        $this->drivers = $driver;
        $this->dispatchBrowserEvent('show-form');
    }

    public function resetPassword()
    {
        $validatedData = Validator::make($this->state2,[
            'driver_password' => ['required', 'string', 'min:8', 'confirmed'],
        ])->validate();

        $validatedData['driver_password'] = bcrypt($validatedData['driver_password']);

        $this->drivers->update($validatedData);

        return redirect()->to('/settings/manageBusDriver')->with(['message' => 'Password Reset Successfully!']);
    }

    public function confirmChanges($id)
    {
        $this->changedDriverId = $id;

        $sql = BusDriver::where('id', $id)->first();
        $this->changedDriverName = $sql->driver_name;

        if($sql->status==1){
            $this->desiredStatus = 'INACTIVE';
        }
        else{
            $this->desiredStatus = 'ACTIVE';
        }
    }

    public function changeStatus()
    {
        $busdriver = BusDriver::findOrFail($this->changedDriverId);
        if ($busdriver->status == 1) {
            $busdriver->update(['status', 2]);
            $updateStatus = BusDriver::whereId($this->changedDriverId)->update(['status' => 2]);

        } else {
            $busdriver->update(['status', 1]);
            $updateStatus = BusDriver::whereId($this->changedDriverId)->update(['status' => 1]);
        }

        if ($updateStatus){
            return redirect()->to('/settings/manageBusDriver')->with(['message' => 'Status Changed Successfully!']);
        }
        return redirect()->to('/settings/manageBusDriver')->with(['message' => 'Status Changed Failed!']);

    }
}
