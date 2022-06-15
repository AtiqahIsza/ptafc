<?php

namespace App\Http\Livewire;

use App\Models\Bus;
use App\Models\BusDriver;
use App\Models\Company;
use App\Models\Route;
use App\Models\Sector;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExportBusDriver;

class ManageBusDriver extends Component
{
    public $drivers;
    public $companies;
    public $sectors;
    public $routes;
    public $buses;

    public $state = [];
    public $state2 = [];

    public $editedDrivers;
    public $editedCompanies;

    public $selectedCompany = NULL;

    public $changedDriverId;
    public $changedDriverName;
    public $removedDriverId;
    public $removedDriverName;
    public $desiredStatus;
    //public $showEditModal = false;

    public function mount()
    {
        $this->drivers = collect();
        $this->companies = collect();
        $this->sectors = collect();
        $this->routes = collect();
        $this->buses = collect();
        $this->editedDrivers = collect();
        $this->editedCompanies = collect();
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
            $this->drivers = BusDriver::where('company_id', $company)->orderBy('driver_name')->get();
        }
    }

    public function addNew()
    {
        $this->state = [];
        $this->editedCompanies = Company::all();
        $this->dispatchBrowserEvent('show-form-add');
    }

    public function createBusDriver()
    {
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
            $this->drivers = BusDriver::where('company_id', $this->selectedCompany)->orderBy('driver_name')->get();
            $this->dispatchBrowserEvent('hide-form-add');
        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }
    }

    public function resetModal(BusDriver $driver)
    {
        $this->state2 = [];
        $this->editedDrivers = $driver;
        $this->dispatchBrowserEvent('show-reset-modal');
    }

    public function resetPassword()
    {
        $validatedData = Validator::make($this->state2,[
            'driver_password' => ['required', 'string', 'min:8', 'confirmed'],
        ])->validate();

        $validatedData['driver_password'] = bcrypt($validatedData['driver_password']);

        $success = $this->editedDrivers->update($validatedData);
        if($success){
            $this->drivers = BusDriver::where('company_id', $this->selectedCompany)->orderBy('driver_name')->get();
            $this->dispatchBrowserEvent('hide-reset-modal');
        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }
    }

    public function editModal(BusDriver $driver)
    {
        $this->editedDrivers = $driver;
        $this->editedCompanies = Company::all();
        $this->state = $driver->toArray();
        $this->dispatchBrowserEvent('show-form-edit');
    }

    public function updateBusDriver()
    {
        $validatedData = Validator::make($this->state, [
            'driver_name' => ['required', 'string', 'max:255'],
            'employee_number' => ['required', 'string', 'max:255'],
            'id_number' => ['required', 'string', 'max:255'],
            'driver_role' => ['required', 'int'],
            'status' => ['required', 'int'],
            'target_collection' => ['required', 'between:0,99.99'],
            'company_id' => ['required', 'int'],
            'driver_number' => ['required', 'string', 'max:255'],
        ])->validate();

        $success = $this->editedDrivers->update($validatedData);

        if($success){
            $this->drivers = BusDriver::where('company_id', $this->selectedCompany)->orderBy('driver_name')->get();
            $this->dispatchBrowserEvent('hide-form-edit');
        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }
    }

    public function confirmRemove($id)
    {
        $this->changedDriverId = $id;
        $this->removedDriverId = $id;
        $selectedRemoved = BusDriver::where('id', $this->removedDriverId)->first();
        $this->removedDriverName = $selectedRemoved->driver_name;
        $this->dispatchBrowserEvent('show-remove-modal');
    }

    public function removeDriver()
    {
        $busdriver = BusDriver::findOrFail($this->removedDriverId);
        $successRemove = $busdriver->delete();

        if($successRemove) {
            $this->drivers = BusDriver::where('company_id', $this->selectedCompany)->orderBy('driver_name')->get();
            $this->dispatchBrowserEvent('hide-remove-modal');
        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }
    }

    public function confirmChanges($id)
    {
        $this->changedDriverId = $id;
        $selectedChanged = BusDriver::where('id', $id)->first();
        $this->changedDriverName = $selectedChanged->driver_name;

        if($selectedChanged->status==1){
            $this->desiredStatus = 'INACTIVE';
        }
        else{
            $this->desiredStatus = 'ACTIVE';
        }
        $this->dispatchBrowserEvent('show-status-modal');
    }

    public function changeStatus()
    {
        if($this->desiredStatus == 'INACTIVE') {
            $updateStatus = BusDriver::whereId($this->changedDriverId)->update(['status' => 2]);
        }else {
            $updateStatus = BusDriver::whereId($this->changedDriverId)->update(['status' => 1]);
        }

        if ($updateStatus){
            $this->drivers = BusDriver::where('company_id', $this->selectedCompany)->orderBy('driver_name')->get();
            $this->dispatchBrowserEvent('hide-status-modal');
        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }
    }

    public function extractExcel(){
        if ($this->selectedCompany==NULL) {
            $allCompanies = Company::all();
            if(count($allCompanies)>0){
                foreach($allCompanies as $allCompany){
                    $driverPerCompanies = BusDriver::where('company_id',  $allCompany->id)->get();
                    $busDriver = [];
                    if(count($driverPerCompanies)>0){
                        foreach($driverPerCompanies as $driverPerCompany){
                            $busDriver[$driverPerCompany->driver_number] = $driverPerCompany;
                        }
                    }
                    $data[$allCompany->company_name] = $busDriver;
                }
            }
        }else{
            $companyDetails = Company::where('id', $this->selectedCompany)->first();
            if($companyDetails){
                $driverPerCompanies = BusDriver::where('company_id',  $companyDetails->id)->get();
                $busDriver = [];
                if(count($driverPerCompanies)>0){
                    foreach($driverPerCompanies as $driverPerCompany){
                        $busDriver[$driverPerCompany->driver_number] =  $driverPerCompany;
                    }
                }
                $data[$companyDetails->company_name] = $busDriver;
            }
        }
        return Excel::download(new ExportBusDriver($data), 'Bus_Drivers_Details.xlsx');
    }


}
