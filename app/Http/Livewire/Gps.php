<?php

namespace App\Http\Livewire;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Route;
use App\Models\VehiclePosition;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Symfony\Component\Console\Output\ConsoleOutput;

class Gps extends Component
{
    public $companies;
    public $buses;
    public $selectedCompany = NULL;

    public function mount()
    {
        $this->companies = collect();
        $this->buses = collect();
    }

    public function render()
    {
        $this->companies = Company::orderBy('company_name')->get();
        return view('livewire.gps');
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->buses = Bus::where('company_id', $company)->orderBy('bus_registration_number')->get();
        }
    }
}
