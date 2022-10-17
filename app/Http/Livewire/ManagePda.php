<?php

namespace App\Http\Livewire;

use App\Models\Company;
use App\Models\PDAProfile;
use App\Models\RegionCode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Component;
use Symfony\Component\Console\Output\ConsoleOutput;

class ManagePda extends Component
{
    public $companies;
    public $companyModals;
    public $pdas;
    public $editedPDA;

    public $showEditModal = false;

    public $selectedRegion = NULL;
    public $selectedRegionModal = NULL;
    public $selectedCompany = NULL;
    public $state = [];

    public function render()
    {
        $this->companies = Company::orderBy('company_name')->get();
        return view('livewire.manage-pda');
    }

    public function mount()
    {
        $this->pdas = collect();
        $this->companies = collect();
        $this->companyModals = collect();
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->pdas = PDAProfile::where('company_id', $company)->orderBy('status')->get();
        }
    }

    public function addNew()
    {
        $this->state = [];
        $this->pdas = PDAProfile::where('company_id', $this->selectedCompany)->orderBy('status')->get();

        $this->showEditModal = false;
        $this->companyModals = Company::orderBy('company_name')->get();
        $this->dispatchBrowserEvent('show-add-form');
    }

    public function createPDA()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE createPDA()");

        $validatedData = Validator::make($this->state, [
            'pda_tag' => ['required', 'string', 'max:255'],
            'imei' => ['required', 'string'],
            'company_id' => ['required', 'int'],
            'status' => ['required', 'int'],
        ])->validate();

        $validatedData['date_created'] = Carbon::now();
        $validatedData['date_registered'] = Carbon::now();
        $validatedData['pda_key'] = Str::random(60);
        $validatedData['created_by'] = auth()->user()->id;
        $validatedData['updated_by'] = auth()->user()->id;

        $success = PDAProfile::create($validatedData);

        if($success){
            $this->pdas = PDAProfile::where('company_id', $this->selectedCompany)->orderBy('status')->get();
            $this->dispatchBrowserEvent('hide-add-form');
        }else{
            $this->dispatchBrowserEvent('hide-failed-form');
        }
    }

    public function edit(PDAProfile $pda)
    {
        $this->pdas = PDAProfile::where('company_id', $this->selectedCompany)->orderBy('status')->get();

        $this->showEditModal = true;
        $this->editedPDA = $pda;
        $this->state = $pda->toArray();
        $this->companyModals = Company::orderBy('company_name')->get();
        $this->dispatchBrowserEvent('show-form');
    }

    public function updatePDA()
    {
        $validatedData = Validator::make($this->state,[
            'pda_tag' => ['required', 'string', 'max:255'],
            'imei' => ['required', 'string'],
            'company_id' => ['required', 'int'],
            'status' => ['required', 'int'],
        ])->validate();

        $validatedData['updated_by'] = auth()->user()->id;
        $success = $this->editedPDA->update($validatedData);

        if($success){
            $this->pdas = PDAProfile::where('company_id', $this->selectedCompany)->orderBy('status')->get();
            $this->dispatchBrowserEvent('hide-edit-form');
        }else{
            $this->dispatchBrowserEvent('hide-failed-edit');
        }
    }
}
