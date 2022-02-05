<?php

namespace App\Http\Livewire;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\Gate;
use Livewire\Component;
use LivewireUI\Modal\ModalComponent;

class EditUser extends ModalComponent
{
    public $users;
    public $companies;
    public $showModal = false;

    /*public function mount($id)
    {
        $user = User::findOrFail($id);
        Gate::authorize('update', $user);

        $this->user = $user;
    }*/

    public function showModal()
    {
        $this->showModal = true;

    }

    public function render()
    {
        /*$this->reset();
        //$this->showEditModal = true;
        //$this->users = $user;
        $this->companies= Company::all();*/
        //$this->state = $user->toArray();
        return view('livewire.edit-user');
    }
}
