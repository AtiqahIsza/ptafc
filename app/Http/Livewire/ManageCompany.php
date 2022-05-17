<?php

namespace App\Http\Livewire;

use App\Models\Company;
use App\Models\RegionCode;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class ManageCompany extends Component
{
    public $companies;
    public $regions;
    public $editedCompanies;
    public $removedCompanyId;
    public $removedCompany;
    public $state = [];
    public $selectedRegion = NULL;
    public $showEditModal = false;

    public function mount()
    {
        $this->regions = collect();
        $this->companies = collect();
    }

    public function render()
    {
        $this->regions = RegionCode::all();
        return view('livewire.manage-company');
    }

    public function updatedSelectedRegion($region)
    {
        if (!is_null($region)) {
            $this->companies = Company::where('region_id', $region)->get();
        }
    }

    public function edit(Company $company)
    {
        $this->showEditModal = true;
        $this->companies = Company::where('region_id', $this->selectedRegion)->get();
        $this->editedCompanies = $company;
        $this->state = $company->toArray();
        $this->dispatchBrowserEvent('show-form');
    }

    public function updateCompany()
    {
        $validatedData = Validator::make($this->state,[
            'company_name' => ['required', 'string', 'max:255'],
            'company_type' => ['required', 'int'],
            'region_id' => ['required', 'int'],
            'address1' => ['required', 'string', 'max:255'],
            'address2' => ['required', 'string', 'max:255'],
            'postcode' => ['required','regex:/\b\d{5}\b/'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'minimum_balance' => ['required', 'between:0,99.99'],
        ])->validate();

        $success = $this->editedCompanies->update($validatedData);

        if($success){
            $this->companies = Company::where('region_id', $this->selectedRegion)->get();
            $this->dispatchBrowserEvent('hide-form-edit');
        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }
    }

    public function addNew()
    {
        $this->state = [];
        $this->showEditModal = false;
        $this->dispatchBrowserEvent('show-form');
    }

    public function createCompany()
    {
        $validatedData = Validator::make($this->state, [
            'company_name' => ['required', 'string', 'max:255'],
            'company_type' => ['required', 'int'],
            'region_id' => ['required', 'int'],
            'address1' => ['required', 'string', 'max:255'],
            'address2' => ['required', 'string', 'max:255'],
            'postcode' => ['required','regex:/\b\d{5}\b/'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'minimum_balance' => ['required', 'between:0,99.99'],
        ])->validate();

        $create = Company::create($validatedData);

        if($create){
            $this->companies = Company::where('region_id', $this->selectedRegion)->get();
            $this->dispatchBrowserEvent('hide-form-add');
        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }
    }

    public function confirmRemoval($companyId)
    {
        $this->removedCompanyId = $companyId;
        $selectedRemoved = Company::where('id', $this->removedCompanyId)->first();
        $this->removedCompany = $selectedRemoved->company_name;
        $this->dispatchBrowserEvent('show-delete-modal');
    }

    public function removeCompany()
    {
        $company = Company::findOrFail($this->removedCompanyId);
        $successRemove = $company ->delete();

        if($successRemove) {
            $this->companies = Company::where('region_id', $this->selectedRegion)->get();
            $this->dispatchBrowserEvent('hide-delete-modal');
        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }
    }
}
