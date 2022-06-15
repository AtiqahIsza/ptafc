<?php

namespace App\Http\Livewire;

use App\Models\Bus;
use App\Models\Route;
use App\Models\RouteSchedulerMSTR;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Symfony\Component\Console\Output\ConsoleOutput;

class ViewRouteSchedule extends Component
{
    public $schedules;
    public $editSchedules;
    public $editButton = false;
    public $state = [];
    public $routes;
    public $buses;

    public $removedId;
    public $removedSchedule;

    protected $listeners = ['viewEvent'];

    public function render()
    {
        return view('livewire.view-route-schedule');
    }

    public function mount(){
        $this->routes = collect();
        $this->buses = collect();
        $this->state = collect();
        $this->removedSchedule = collect();
        $this->schedules = collect();
    }

    public function viewEvent(Route $route)
    {
        $this->schedules = RouteSchedulerMSTR::where('route_id', $route->id)->orderBy('schedule_start_time')->get();
    }

    public function edit(RouteSchedulerMSTR $schedule){
        $this->routes = Route::all();
        $this->buses = Bus::all();
        $this->editSchedules = $schedule;
        $this->state = $schedule->toArray();
        $this->editButton = true;
    }

    public function updateRouteSchedule()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE");

        $validatedData = Validator::make($this->state, [
            'schedule_start_time'=> ['required'],
            'schedule_end_time'=> ['required'],
            'inbound_distance'=> ['required', 'between:0,99.99'],
            'outbound_distance'=> ['required', 'between:0,99.99'],
            'inbound_bus_id'=> ['required', 'int'],
            'outbound_bus_id'=> ['required', 'int'],
            'status'=> ['required', 'int'],
            'trip_type'=> ['required', 'int'],
            'route_id' => ['required', 'int']
        ])->validate();

        $out->writeln($validatedData['schedule_start_time']);
        $out->writeln($validatedData['route_id']);
        $out->writeln($validatedData['inbound_distance']);
        $out->writeln($validatedData['outbound_distance']);
        $out->writeln($validatedData['inbound_bus_id']);
        $out->writeln($validatedData['outbound_bus_id']);
        $out->writeln($validatedData['status']);
        $out->writeln($validatedData['trip_type']);

        $this->editSchedules->update($validatedData);

        //return redirect()->to('/settings/manageScheduler')->with(['message' => 'Route Schedule added successfully!']);
    }

    public function confirmRemoval($id)
    {
        $this->removedId = $id;
        $this->removedSchedule = RouteSchedulerMSTR::where('id', $id)->first();
        //dd($this->removedSchedule);
    }

    public function removeSchedule()
    {
        $remove = RouteSchedulerMSTR::findOrFail($this->removedId);
        $remove->delete();
        return redirect()->to('/settings/manageScheduler')->with(['message' => 'Route Schedule removed successfully!']);
    }
}
