<?php

namespace App\Http\Livewire;

use App\Models\Company;
use App\Models\Route;
use App\Models\Sector;
use App\Models\Stage;
use App\Models\StageMap;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class ManageStage extends Component
{
    public $companies;
    public $stages;
    public $routes;
    public $stageMaps;
    public $removedStageMapId;
    public $removedStageId;
    public $state = [];
    public $selectedCompany = NULL;
    public $selectedRoute = NULL;
    public $showEditModal = false;

    public function mount()
    {
        $this->stages= collect();
        $this->companies = collect();
        $this->routes = collect();
    }

    public function render()
    {
        $this->companies = Company::all();
        $this->stageMaps = StageMap::all();

        //$this->routes = Route::all();
        return view('livewire.manage-stage');
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->routes = Route::where('company_id', $company)->get();
        }
    }

    public function updatedSelectedRoute($route)
    {
        if (!is_null($route)) {
            $this->routes = Route::all();
            $this->stages = Stage::where('route_id', $route)->get();
        }
    }

    public function edit(Stage $stage)
    {
        //dd($user);
        $this->reset();
        $this->showEditModal = true;
        $this->stages= $stage;
        $this->state = $stage->toArray();
        $this->dispatchBrowserEvent('show-form');
    }

    public function updateStage()
    {
        $validatedData = Validator::make($this->state,[
            'stage_name' => ['required', 'string', 'max:255'],
            'stage_number' => ['string', 'max:255'],
            'stage_order'=> ['required', 'int'],
            'no_of_km'=> ['between:0,99.99'],
            'route_id' => ['required', 'int']
        ])->validate();

        $existedOrder = Stage::where([
            ['route_id', $validatedData['route_id']],
            ['stage_order',$validatedData['stage_order']]
        ])->first();

        if($existedOrder){
            return Redirect::back()->with(['message' => 'Existed sequence of order for this route!']);
        }

        $this->stages->update($validatedData);

        return redirect()->to('/settings/manageStage')->with(['message' => 'Stage updated successfully!']);

        //return Redirect::back()->with(['message' => 'Sector updated successfully!']);
        //$this->emit('hide-form');
        //session()->flash('message', 'Sector successfully updated!');
        //$this->dispatchBrowserEvent('hide-form', ['message' => 'Sector updated successfully!']);
    }

    public function addNew()
    {
        $this->reset();
        $this->routes = Route::all();
        $this->showEditModal = false;
        $this->dispatchBrowserEvent('show-form');
    }

    public function createStage()
    {
        $validatedData = Validator::make($this->state, [
            'stage_name' => ['required', 'string', 'max:255'],
            'stage_number' => ['string', 'max:8'],
            'stage_order'=> ['required', 'int'],
            'no_of_km'=> ['required', 'between:0,99.99'],
            'route_id'=> ['required', 'int'],
        ])->validate();

        $existedOrder = Stage::where([
            ['route_id', $validatedData['route_id']],
            ['stage_order',$validatedData['stage_order']]
        ])->first();

        if($existedOrder){
            return Redirect::back()->with(['message' => 'Existed sequence of order for this route!']);
        }

        Stage::create($validatedData);

        return redirect()->to('/settings/manageStage')->with(['message' => 'Stage added successfully!']);

        //return Redirect::back()->with(['message' => 'Sector added successfully!']);
        //$this->dispatchBrowserEvent('hide-form', ['message' => 'Sector added successfully!']);
    }

    public function confirmRemoval($id)
    {
        $this->removedStageId = $id;
        $this->dispatchBrowserEvent('show-delete-modal');
    }

    public function removeStage()
    {
        $stage= Stage::findOrFail($this->removedStageId);
        $stage->delete();

        return redirect()->to('/settings/manageStage')->with(['message' => 'Stage removed successfully!']);
    }

    public function confirmRemovalMap($stage)
    {
        // $routeId = Route::select('id')->where('id',$route->id)->first();
        $this->removedStageMapId = $stage;
        //$this->dispatchBrowserEvent('show-delete-modal');
    }

    public function removeStageMap()
    {
        $stageMap = StageMap::where('stage_id',$this->removedStageMapId);
        $stageMap->delete();

        return redirect()->to('/settings/manageStage')->with(['message' => 'Stage Map removed successfully!']);
    }
}
