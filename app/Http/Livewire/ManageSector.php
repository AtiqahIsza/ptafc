<?php

namespace App\Http\Livewire;

use App\Models\Company;
use App\Models\RegionCode;
use App\Models\Sector;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class ManageSector extends Component
{
    public $companies;
    public $sectors;
    public $removedSectorId;
    public $state = [];
    public $selectedCompany = NULL;
    public $showEditModal = false;

    public function mount()
    {
        $this->sectors = collect();
        $this->companies = collect();
    }

    public function render()
    {
        $this->companies = Company::all();
        return view('livewire.manage-sector');
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->sectors = Sector::where('company_id', $company)->get();
        }
    }

    public function edit(Sector $sector)
    {
        //dd($user);
        $this->reset();
        $this->showEditModal = true;
        $this->sectors = $sector;
        $this->state = $sector->toArray();
        $this->dispatchBrowserEvent('show-form');
    }

    public function updateSector()
    {
        $validatedData = Validator::make($this->state,[
            'sector_name' => ['required', 'string', 'max:255'],
            'company_id' => ['required', 'int'],
        ])->validate();

        $this->sectors->update($validatedData);

        return redirect()->to('/settings/managesector')->with(['message' => 'Sector updated successfully!']);

        //return Redirect::back()->with(['message' => 'Sector updated successfully!']);
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

    public function createSector()
    {
        $validatedData = Validator::make($this->state, [
            'sector_name' => ['required', 'string', 'max:255'],
            'company_id' => ['required', 'int'],
        ])->validate();

        Sector::create($validatedData);

        return redirect()->to('/settings/managesector')->with(['message' => 'Sector removed successfully!']);

        //return Redirect::back()->with(['message' => 'Sector added successfully!']);
        //$this->dispatchBrowserEvent('hide-form', ['message' => 'Sector added successfully!']);
    }

    public function confirmRemoval($sectorId)
    {
        $this->removedSectorId = $sectorId;
        $this->dispatchBrowserEvent('show-delete-modal');
    }

    public function removeSector()
    {
        $sector = Sector::findOrFail($this->removedSectorId);
        $sector->delete();

        return redirect()->to('/settings/managesector')->with(['message' => 'Sector removed successfully!']);
    }
}
