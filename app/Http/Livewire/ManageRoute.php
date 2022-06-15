<?php

namespace App\Http\Livewire;

use App\Models\BusStand;
use App\Models\Company;
use App\Models\Route;
use App\Models\RouteMap;
use App\Models\Sector;
use App\Models\Stage;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\Console\Output\ConsoleOutput;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExportRoute;

class ManageRoute extends Component
{
    public $companies;
    public $sectors;
    public $routes;
    public $routeMaps;
    public $editedRoutes;
    public $editedCompanies;
    public $removedRouteId;
    public $removedRouteMapId;
    public $removedRoute;
    public $state = [];
    public $selectedCompany = NULL;
    public $showEditModal = false;

    public $file;

    public function mount()
    {
        $this->companies = collect();
        $this->routes = collect();
        $this->routeMaps = collect();
        $this->editedRoutes = collect();
        $this->editedCompanies = collect();
    }

    public function render()
    {
        $this->companies = Company::orderBy('company_name')->get();

        return view('livewire.manage-route');
    }

    public function updatedSelectedCompany($company)
    {
        if (!is_null($company)) {
            $this->routes = Route::where('company_id', $company)
                ->orderBy('route_number')
                ->get();
            $this->routeMaps = RouteMap::select('route_id')->distinct()->get();
        }
    }

    public function edit(Route $selectedRoute)
    {
        $this->routes = Route::where('company_id', $this->selectedCompany)
            ->orderBy('route_number')
            ->get();
        $this->routeMaps = RouteMap::select('route_id')->distinct()->get();

        $this->showEditModal = true;
        $this->editedRoutes = $selectedRoute;
        $this->editedCompanies = Company::all();
        $this->state = $selectedRoute->toArray();
        $this->dispatchBrowserEvent('show-form');
    }

    public function updateRoute()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN updateRoute()");

        $validatedData = Validator::make($this->state,[
            'route_name' => ['required', 'string', 'max:255'],
            'route_number' => ['string', 'max:255'],
            'route_target'=> ['required', 'string', 'max:255'],
            'distance'=> ['between:0,99.99'],
            'inbound_distance'=> ['between:0,99.99'],
            'outbound_distance'=> ['between:0,99.99'],
            'company_id'=> ['required', 'int'],
            'status'=> ['required', 'int'],
        ])->validate();

        $existedRouteNo = Route::where('route_number', $validatedData['route_number'])->first();
        $existRouteName = Route::where('route_name', $validatedData['route_name'])->first();
        $attributes = $this->editedRoutes->getAttributes();

        //$out->writeln("YOU ARE IN existedRouteNo " . $existedRouteNo );
        //$out->writeln("YOU ARE IN existRouteName " . $existRouteName );

        if(!is_null($existedRouteNo)) {
            $out->writeln("YOU ARE IN existedRouteNo");
            if($existedRouteNo->id != $attributes['id']) {
                $out->writeln("YOU ARE IN existedRouteNo != attr[id]");
                $this->dispatchBrowserEvent('failed-add-route-no');
            }
        }
        elseif(!is_null($existRouteName)){
            $out->writeln("YOU ARE IN existedRouteName");
            if($existRouteName->id != $attributes['id']) {
                $out->writeln("YOU ARE IN existedRouteName != attr[id]");
                $this->dispatchBrowserEvent('failed-add-route-name');
            }
        }

        $out->writeln("YOU ARE IN update");
        $success = $this->editedRoutes->update($validatedData);

        if($success){
            $this->routes = Route::where('company_id', $this->selectedCompany)
                ->orderBy('route_number')
                ->get();
            $this->routeMaps = RouteMap::select('route_id')->distinct()->get();
            $this->dispatchBrowserEvent('hide-form-edit');
        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }

    }

    public function addNew()
    {
        $this->state = [];
        $this->routes = Route::where('company_id', $this->selectedCompany)
            ->orderBy('route_number')
            ->get();
        $this->routeMaps = RouteMap::select('route_id')->distinct()->get();

        $this->showEditModal = false;
        $this->editedCompanies = Company::all();
        $this->dispatchBrowserEvent('show-form');
    }

    public function createRoute()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN createRoute()");

        $validatedData = Validator::make($this->state, [
            'route_name' => ['required', 'string', 'max:255'],
            'route_number' => ['string', 'max:255'],
            'route_target'=> ['required', 'string', 'max:255'],
            'distance'=> ['between:0,99.99'],
            'inbound_distance'=> ['between:0,99.99'],
            'outbound_distance'=> ['between:0,99.99'],
            'company_id'=> ['required', 'int'],
            'status'=> ['required', 'int'],
        ])->validate();

        $existRouteNumber = Route::where('route_number', $validatedData['route_number'])->first();
        $existRouteName = Route::where('route_name', $validatedData['route_name'])->first();

        if(!empty($existRouteNumber)){
            $this->dispatchBrowserEvent('failed-add-route-no');
        }
        elseif(!empty($existRouteName->id)){
            $this->dispatchBrowserEvent('failed-add-route-name');
        }else{
            $create = Route::create($validatedData);

            if($create){
                $this->routes = Route::where('company_id', $this->selectedCompany)
                    ->orderBy('route_number')
                    ->get();
                $this->routeMaps = RouteMap::select('route_id')->distinct()->get();
                $this->dispatchBrowserEvent('hide-form-add');
            }else{
                $this->dispatchBrowserEvent('hide-form-failed');
            }
        }
    }

    public function confirmRemoval($id)
    {
        $this->removedRouteId = $id;
        $selectedRemoved = Route::where('id', $this->removedRouteId)->first();
        $this->removedRoute = $selectedRemoved->route_name;
        $this->routes = Route::where('company_id', $this->selectedCompany)
            ->orderBy('route_number')
            ->get();
        $this->routeMaps = RouteMap::select('route_id')->distinct()->get();
        $this->dispatchBrowserEvent('show-delete-modal');
    }

    public function removeRoute()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN removeRoute()");

        $route = Route::findOrFail($this->removedRouteId);
        $successRemove = $route->delete();

        if($successRemove) {
            $out->writeln("YOU ARE IN succesRemove");

            $removeMap = RouteMap::where('route_id', $this->removedRouteId)->get();
            if (!empty($removeMap)) {
                $out->writeln("YOU ARE IN removeMap not empty ");
                $successRemoveMap = RouteMap::where('route_id', $this->removedRouteId)->delete();
            }

            $removeStage = Stage::where('route_id',$this->removedRouteId)->get();
            if (!empty($removeStage)) {
                $out->writeln("YOU ARE IN removeStage not empty ");
                $successRemoveStage = Stage::where('route_id',$this->removedRouteId)->delete();
            }

            $removeBusStand = BusStand::where('route_id',$this->removedRouteId)->get();
            if (!empty($removeBusStand)) {
                $out->writeln("YOU ARE IN removeBusStand not empty ");
                $successBusRemoveStand = Stage::where('route_id',$this->removedRouteId)->delete();
            }

            $this->routes = Route::where('company_id', $this->selectedCompany)
                ->orderBy('route_number')
                ->get();
            $this->routeMaps = RouteMap::select('route_id')->distinct()->get();
            $this->dispatchBrowserEvent('hide-delete-modal');

        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }
    }

    public function confirmRemovalMap($id)
    {
        $this->removedRouteMapId = $id;
        $selectedRemoved = Route::where('id', $this->removedRouteMapId)->first();
        $this->removedRoute = $selectedRemoved->route_name;
        $this->routes = Route::where('company_id', $this->selectedCompany)
            ->orderBy('route_number')
            ->get();
        $this->routeMaps = RouteMap::select('route_id')->distinct()->get();
        $this->dispatchBrowserEvent('show-delete-map-modal');
    }

    public function removeRouteMap()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN  removeRouteMap()");

        $routeMap = RouteMap::where('route_id',$this->removedRouteMapId);
        $successRemoveMap = $routeMap->delete();

        if($successRemoveMap){
            $busStand = BusStand::where('route_id',$this->removedRouteMapId);
            $successBusRemoveStand = $busStand->delete();
            if($successBusRemoveStand){
                $this->routes = Route::where('company_id', $this->selectedCompany)
                    ->orderBy('route_number')
                    ->get();
                $this->routeMaps = RouteMap::select('route_id')->distinct()->get();
                $this->dispatchBrowserEvent('hide-delete-modal');
            }else{
                $this->dispatchBrowserEvent('hide-form-failed');
            }
        }else{
            $this->dispatchBrowserEvent('hide-form-failed');
        }
    }

    public function extractExcel(){
        if ($this->selectedCompany==NULL) {
            $allCompanies = Company::all();
            if(count($allCompanies)>0){
                foreach($allCompanies as $allCompany){
                    $routePerCompanies = Route::where('company_id',  $allCompany->id)->get();
                    $route = [];
                    if(count($routePerCompanies)>0){
                        foreach($routePerCompanies as $routePerCompany){
                            $route[$routePerCompany->route_number] = $routePerCompany;
                        }
                    }
                    $data[$allCompany->company_name] = $route;
                }
            }
        }else{
            $companyDetails = Company::where('id', $this->selectedCompany)->first();
            if($companyDetails){
                $routePerCompanies = Route::where('company_id',  $companyDetails->id)->get();
                $route = [];
                if(count($routePerCompanies)>0){
                    foreach($routePerCompanies as $routePerCompany){
                        $route[$routePerCompany->route_number] = $routePerCompany;
                    }
                }
                $data[$companyDetails->company_name] = $route;
            }
        }
        return Excel::download(new ExportRoute($data), 'Route_Details.xlsx');
    }
}
