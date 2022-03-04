<?php

namespace App\Http\Livewire;

use App\Models\Company;
use App\Models\Route;
use Livewire\Component;

class ReportSpad extends Component
{
    public $companies;
    public $routes;
    public $selectedCompany = NULL;

    public function render()
    {
        return view('livewire.report-spad');
    }

    public function mount()
    {
        $this->companies=Company::all();
        $this->routes=Route::all();
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->selectedCompany = $company;
            $this->routes = Route::where('company_id', $company)->get();
        }
    }
}
