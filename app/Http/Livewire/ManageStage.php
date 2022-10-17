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
use Symfony\Component\Console\Output\ConsoleOutput;

class ManageStage extends Component
{
    public $companies;
    public $stages;
    public $routes;
    public $stageMaps;
    public $editedStages;
    public $editedRoutes;
    public $editedCompanies;
    public $removedStageMapId;
    public $removedStageId;
    public $removedStage;
    public $state = [];
    public $selectedCompany = NULL;
    public $selectedRoute = NULL;
    public $selectedEditCompany = NULL;
    public $showEditModal = false;

    public function mount()
    {
        $this->stages= collect();
        $this->companies = collect();
        $this->routes = collect();
        $this->stageMaps = collect();
        $this->editedStages = collect();
        $this->editedCompanies = collect();
        $this->editedRoutes = collect();
        $this->removedStage = collect();
        $this->selectedEditCompany = collect();
    }

    public function render()
    {
        $this->companies = Company::orderBy('company_name')->get();
        return view('livewire.manage-stage');
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->routes = Route::where('company_id', $company)->orderBy('route_number')->get();
        }
    }

    public function updatedSelectedRoute($route)
    {
        if (!is_null($route)) {
            $this->stages = Stage::where('route_id', $route)->orderBy('stage_order')->get();
            $this->stageMaps = StageMap::select('stage_id')->distinct()->get();
        }
    }

    public function updatedSelectedEditCompany($company)
    {
        if (!is_null($company)) {
            $this->editedRoutes = Route::where('company_id', $company)->orderBy('route_number')->get();
        }
    }

    public function edit(Stage $stage)
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN edit()");
        $this->state = $stage->toArray();
        $companyId = $stage->Route->company_id;
        $this->selectedEditCompany =  $companyId;
        $this->editedCompanies = Company::all();
        $this->editedRoutes = Route::where('company_id', $companyId)->orderBy('route_number')->get();
        $this->showEditModal = true;
        $this->editedStages = $stage;
        $this->dispatchBrowserEvent('show-form');
    }

    public function updateStage()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN updateStage()");

        $validatedData = Validator::make($this->state,[
            'stage_name' => ['required', 'string', 'max:255'],
            'stage_number' => ['nullable','string', 'max:255'],
            'stage_order'=> ['required', 'int'],
            'no_of_km'=> ['required', 'between:0,99.99'],
            'route_id' => ['required', 'int']
        ])->validate();

        $out->writeln("YOU ARE IN after validation");

        $existedOrder = Stage::where([
            ['route_id', $validatedData['route_id']],
            ['stage_order',$validatedData['stage_order']]
        ])->first();

        $attributes = $this->editedStages->getAttributes();

        if($existedOrder) {
            $out->writeln("YOU ARE IN check existedOrder()");
            if ($existedOrder->id != $attributes['id']) {
                $out->writeln("YOU ARE IN popup existedOrder()");
                $this->dispatchBrowserEvent('show-error-existed');
            }else{
                $out->writeln("YOU ARE IN not existedOrder()");
                $validatedData['updated_by'] = auth()->user()->id;
                $success = $this->editedStages->update($validatedData);

                if($success){
                    $out->writeln("YOU ARE IN success");
                    $this->stages = Stage::where('route_id', $this->selectedRoute)->orderBy('stage_order')->get();
                    $this->stageMaps = StageMap::select('stage_id')->distinct()->get();
                    $this->dispatchBrowserEvent('hide-form-edit');
                }else{
                    $this->dispatchBrowserEvent('hide-form-failed');
                }
            }
        }else{
            $out->writeln("YOU ARE IN not existedOrder()");
            $validatedData['updated_by'] = auth()->user()->id;
            $success = $this->editedStages->update($validatedData);

            if($success){
                $out->writeln("YOU ARE IN success");
                $this->stages = Stage::where('route_id', $this->selectedRoute)->orderBy('stage_order')->get();
                $this->stageMaps = StageMap::select('stage_id')->distinct()->get();
                $this->dispatchBrowserEvent('hide-form-edit');
            }else{
                $this->dispatchBrowserEvent('hide-form-failed');
            }
        }
    }

    public function addNew()
    {
        $this->state = [];
        $this->selectedEditCompany = NULL;

        $this->editedCompanies = Company::all();
        $this->editedRoutes = Route::all();
        $this->showEditModal = false;
        $this->dispatchBrowserEvent('show-form');
    }

    public function createStage()
    {
        $validatedData = Validator::make($this->state, [
            'stage_name' => ['required', 'string', 'max:255'],
            'stage_number' => ['nullable', 'string', 'max:8'],
            'stage_order'=> ['required', 'int'],
            'no_of_km'=> ['required', 'between:0,99.99'],
            'route_id'=> ['required', 'int'],
        ])->validate();

        $existedOrder = Stage::where([
            ['route_id', $validatedData['route_id']],
            ['stage_order',$validatedData['stage_order']]
        ])->first();

        if($existedOrder){
            $this->dispatchBrowserEvent('show-error-existed');
        }else{
            $validatedData['created_by'] = auth()->user()->id;
            $validatedData['updated_by'] = auth()->user()->id;
            $success = Stage::create($validatedData);

            if($success){
                $this->stages = Stage::where('route_id', $this->selectedRoute)->orderBy('stage_order')->get();
                $this->stageMaps = StageMap::select('stage_id')->distinct()->get();
                $this->dispatchBrowserEvent('hide-form-add');
            }else{
                $this->dispatchBrowserEvent('hide-form-failed');
            }
        }
    }

    public function confirmRemoval($id)
    {
        $this->removedStageId = $id;
        $selectedRemoved = Stage::where('id', $this->removedStageId)->first();
        $this->removedStage = $selectedRemoved->stage_name;
        $this->stages = Stage::where('route_id', $this->selectedRoute)->orderBy('stage_order')->get();
        $this->stageMaps = StageMap::select('stage_id')->distinct()->get();
        $this->dispatchBrowserEvent('show-delete-modal');
    }

    public function removeStage()
    {
        $stage= Stage::findOrFail($this->removedStageId);
        $successRemove = $stage->delete();

        if($successRemove) {
            $removeMap = StageMap::where('stage_id', $this->removedStageId)->get();

            if (!empty($removeMap)) {
                $successRemoveMap = StageMap::where('stage_id', $this->removedStageId)->delete();
            }
            $this->stages = Stage::where('route_id', $this->selectedRoute)->orderBy('stage_order')->get();
            $this->stageMaps = StageMap::select('stage_id')->distinct()->get();
            $this->dispatchBrowserEvent('hide-form-edit');
        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }
    }

    public function confirmRemovalMap($id)
    {
        $this->removedStageMapId = $id;
        $selectedRemoved = Stage::where('id', $this->removedStageMapId)->first();
        $this->removedStage = $selectedRemoved->stage_name;
        $this->stages = Stage::where('route_id', $this->selectedRoute)->orderBy('stage_order')->get();
        $this->stageMaps = StageMap::select('stage_id')->distinct()->get();
        $this->dispatchBrowserEvent('show-delete-map-modal');
    }

    public function removeStageMap()
    {
        $stageMap = StageMap::where('stage_id',$this->removedStageMapId);
        $successRemoveMap = $stageMap->delete();

        if($successRemoveMap){
            $this->stages = Stage::where('route_id', $this->selectedRoute)->orderBy('stage_order')->get();
            $this->stageMaps = StageMap::select('stage_id')->distinct()->get();
            $this->dispatchBrowserEvent('hide-delete-modal');
        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }
    }
}
