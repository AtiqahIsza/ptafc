<?php

namespace App\Http\Livewire;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Livewire\Component;
use Illuminate\Support\Facades\Validator;

class Users extends Component
{
    public $companies;
    public $users;
    public $userEdit;
    public $removedUserId;
    public $state = [];
    public $selectedCompany = NULL;
    public $showEditModal = false;

    public function mount()
    {
        $this->companies = Company::pluck('id','company_name');
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
            $this->users = User::where('company_id', $companies)->get();
        }
    }

    public function editUser(User $user)
    {
        //dd($user);
        $this->reset();
        $this->showEditModal = true;
        $this->users = $user;
        $this->state = $user->toArray();
        $this->dispatchBrowserEvent('show-form');
    }

    public function updateUser()
    {
        $validatedData = Validator::make($this->state,[
            'full_name' => ['required', 'regex:/^[a-zA-Z\s]*$/'],
            'email' => ['required', 'email', 'max:255'],
            'ic_number' => ['required', 'string', 'max:14', 'regex:/([0-9][0-9])((0[1-9])|(1[0-2]))((0[1-9])|([1-2][0-9])|(3[0-1]))\-([0-9][0-9])\-([0-9][0-9][0-9][0-9])/'],
            'phone_number' => ['required', 'string', 'min:10', 'max:11', 'regex:/^(\+?6?01)[0|1|2|3|4|5|6|7|8|9][0-9]{7,8}$/'],
            'company_id' => ['required', 'int'],
            'user_role' => ['required', 'int'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8','confirmed']
        ])->validate();

        $this->users->update($validatedData);

        return redirect()->to('users')->with(['message' => 'User updated successfully!']);
        //$this->dispatchBrowserEvent('hide-form', ['message' => 'User updated successfully!']);
    }

    public function addNew()
    {
        $this->reset();
        $this->showEditModal = false;
        $this->dispatchBrowserEvent('show-form');
    }

    public function createUser()
    {
        $validatedData = Validator::make($this->state, [
            'full_name' => ['required', 'regex:/^[a-zA-Z\s]*$/'],
            'email' => ['required', 'email', 'max:255'],
            'ic_number' => ['required', 'string', 'max:14', 'regex:/([0-9][0-9])((0[1-9])|(1[0-2]))((0[1-9])|([1-2][0-9])|(3[0-1]))\-([0-9][0-9])\-([0-9][0-9][0-9][0-9])/'],
            'phone_number' => ['required', 'string', 'min:10', 'max:11', 'regex:/^(\+?6?01)[0|1|2|3|4|5|6|7|8|9][0-9]{7,8}$/'],
            'company_id' => ['required', 'int'],
            'user_role' => ['required', 'int'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8','confirmed']
        ])->validate();

        /*if($validatedData->fails()) {
            return Redirect::back()->withErrors( $validatedData);
        }*/

        $validatedData['password'] = bcrypt($validatedData['password']);

        User::create($validatedData);

        return redirect()->to('users')->with(['message' => 'User added successfully!']);

        //$this->dispatchBrowserEvent('hide-form', ['message' => 'User added successfully!']);
    }

    public function confirmUserRemoval($userId)
    {
        $this->removedUserId = $userId;
        $this->dispatchBrowserEvent('show-delete-modal');
    }

    public function removeUser()
    {
        $user = User::findOrFail($this->removedUserId);
        $user->delete();

        return redirect()->to('users')->with(['message' => 'User removed successfully!']);

        //$this->dispatchBrowserEvent('hide-delete-modal', ['message' => 'User deleted successfully!']);
    }

}
