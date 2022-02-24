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
    //public $userEdit;
    public $removedCompanyId;
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
        //dd($user);
        $this->reset();
        $this->showEditModal = true;
        $this->companies = $company;
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

        $this->companies->update($validatedData);

        return redirect()->to('/settings/manageCompany')->with(['message' => 'Company updated successfully!']);

        /*$this->dispatchBrowserEvent('hide-form', ['message' => 'Company updated successfully!']);
        $this->emit('hideModalEvent');
        $this->alert('success', 'Company updated successfully', [
            'position' => 'top',
        ]);*/
    }

    public function addNew()
    {
        $this->reset();
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

        Company::create($validatedData);

        return redirect()->to('/settings/manageCompany')->with(['message' => 'Company added successfully!']);

        //$this->dispatchBrowserEvent('hide-form', ['message' => 'Company added successfully!']);
    }

    public function confirmRemoval($companyId)
    {
        $this->removedCompanyId = $companyId;
        $this->dispatchBrowserEvent('show-delete-modal');
    }

    public function removeCompany()
    {
        $company = Company::findOrFail($this->removedCompanyId);
        $company ->delete();

        return redirect()->to('/settings/manageCompany')->with(['message' => 'Company removed successfully!']);
        //$this->dispatchBrowserEvent('hide-delete-modal', ['message' => 'Company deleted successfully!']);
    }
}
