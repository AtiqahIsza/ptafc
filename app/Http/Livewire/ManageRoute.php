<?php

namespace App\Http\Livewire;

use App\Models\Company;
use App\Models\Route;
use App\Models\RouteMap;
use App\Models\Sector;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class ManageRoute extends Component
{
    public $companies;
    public $sectors;
    public $routes;
    public $routeMaps;
    public $removedRouteId;
    public $state = [];
    public $selectedCompany = NULL;
    public $showEditModal = false;

    public function mount()
    {
        $this->sectors = collect();
        $this->companies = collect();
        $this->routes = collect();
        $this->routeMaps = collect();
    }

    public function render()
    {
        $this->companies = Company::all();
        $this->sectors = Sector::all();
        $this->routeMaps = RouteMap::all();

        return view('livewire.manage-route');
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {

            $this->routes = Route::where('company_id', $company)->get();
        }
    }

    public function edit(Route $route)
    {
        //dd($user);
        $this->reset();
        $this->showEditModal = true;
        $this->routes = $route;
        $this->state = $route->toArray();
        $this->dispatchBrowserEvent('show-form');
    }

    public function updateRoute()
    {
        $validatedData = Validator::make($this->state,[
            'route_name' => ['required', 'string', 'max:255'],
            'route_number' => ['required', 'string', 'max:255'],
            'route_target'=> ['required', 'string', 'max:255'],
            'distance'=> ['required', 'between:0,99.99'],
            'inbound_distance'=> ['required', 'between:0,99.99'],
            'outbound_distance'=> ['required', 'between:0,99.99'],
            'company_id'=> ['required', 'int'],
            'sector_id'=> ['required', 'int'],
            'status'=> ['required', 'int'],
        ])->validate();

        $this->routes->update($validatedData);

        return redirect()->to('/settings/manageRoute')->with(['message' => 'Route updated successfully!']);

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

    public function createRoute()
    {
        $validatedData = Validator::make($this->state, [
            'route_name' => ['required', 'string', 'max:255'],
            'route_number' => ['required', 'string', 'max:255'],
            'route_target'=> ['required', 'string', 'max:255'],
            'distance'=> ['required', 'between:0,99.99'],
            'inbound_distance'=> ['required', 'between:0,99.99'],
            'outbound_distance'=> ['required', 'between:0,99.99'],
            'company_id'=> ['required', 'int'],
            'sector_id'=> ['required', 'int'],
            'status'=> ['required', 'int'],
        ])->validate();

        Route::create($validatedData);

        return redirect()->to('/settings/manageRoute')->with(['message' => 'Route added successfully!']);

        //return Redirect::back()->with(['message' => 'Sector added successfully!']);
        //$this->dispatchBrowserEvent('hide-form', ['message' => 'Sector added successfully!']);
    }

    public function confirmRemoval($id)
    {
        $this->removedRouteId = $id;
        $this->dispatchBrowserEvent('show-delete-modal');
    }

    public function removeRoute()
    {
        $route = Route::findOrFail($this->removedRouteId);
        $route->delete();

        return redirect()->to('/settings/manageRoute')->with(['message' => 'Route removed successfully!']);
    }
}
