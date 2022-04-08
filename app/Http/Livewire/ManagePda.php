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
    public $regions;
    public $companies;
    public $regionModals;
    public $companyModals;
    public $pdas;

    public $showEditModal = false;

    public $selectedRegion = NULL;
    public $selectedRegionModal = NULL;
    public $selectedCompany = NULL;
    public $state = [];

    public function render()
    {
        $this->regions = RegionCode::all();
        return view('livewire.manage-pda');
    }

    public function mount()
    {
        $this->regions = collect();
        $this->pdas = collect();
        $this->companies = collect();
        $this->regionModals = collect();
        $this->companyModals = collect();
    }

    public function updatedSelectedRegion($region)
    {
        if (!is_null($region)) {
            $this->selectedRegion = $region;
            $this->companies = Company::where('region_id', $region)->get();
        }
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->selectedCompany = $company;
            $this->pdas = PDAProfile::where('company_id', $company)->get();
        }
    }

    public function addNew()
    {
        $this->reset();
        $this->showEditModal = false;
        $this->regionModals = RegionCode::all();
        $this->dispatchBrowserEvent('show-form');
    }

    public function updatedSelectedRegionModal($region)
    {
        if (!is_null($region)) {
            $this->selectedRegionModal= $region;
            $this->companyModals = Company::all();
        }
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

        $out->writeln("pda_tag:  ". $validatedData['pda_tag']);
        $out->writeln("imei:" . $validatedData['imei']);
        $out->writeln("company:  ". $validatedData['company_id']);
        $out->writeln("status:" . $validatedData['status']);

        if($this->selectedRegionModal){
            $validatedData['region_id'] = $this->selectedRegionModal;
            $validatedData['date_created'] = Carbon::now();
            $validatedData['date_registered'] = Carbon::now();
            $validatedData['pda_key'] = Str::random(60);

            $out->writeln("region:" . $validatedData['region_id']);
            $out->writeln("created:  ". $validatedData['date_created']);
            $out->writeln("registered:" . $validatedData['date_registered']);
            $out->writeln("pda key: " . $validatedData['pda_key']);

            PDAProfile::create($validatedData);

            return redirect()->to('/settings/managePDA')->with(['message' => 'PDA added successfully!']);
        }
        else{
            return redirect()->to('/settings/managePDA')->with(['message' => 'No region selected!']);
        }
    }

    public function edit(PDAProfile $pda)
    {
        //dd($user);
        $this->reset();
        $this->showEditModal = true;
        $this->pdas = $pda;
        $this->state = $pda->toArray();
        $this->regionModals = RegionCode::all();
        $this->dispatchBrowserEvent('show-form');
    }

    public function updatePDA()
    {
        $validatedData = Validator::make($this->state,[
            'pda_tag' => ['required', 'string', 'max:255'],
            'imei' => ['required', 'int'],
            'region_id' => ['required', 'int'],
            'company_id' => ['required', 'int'],
            'status' => ['required', 'int'],
        ])->validate();

        $this->pdas->update($validatedData);

        return redirect()->to('/settings/managePDA')->with(['message' => 'PDA updated successfully!']);
    }
}
