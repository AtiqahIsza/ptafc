<?php

namespace App\Http\Livewire;

use App\Models\Company;
use Livewire\Component;

class Users extends Component
{
    public $companies;
    public $users;

    public $selectedCompany = NULL;

    public function mount()
    {
        $this->companies = Company::pluck('company_id','company_name');
        $this->users = collect();
    }

    public function render()
    {
        $this->companies = Company::all();
        return view('livewire.users');
    }

    public function updatedSelectedCompany($companies)
    {
        if (!is_null($companies)) {
            $this->users = Users::where('company_company_id', $companies->company_id)->get();
        }
    }
}
