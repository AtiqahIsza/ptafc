<?php

namespace App\Http\Livewire;

use App\Models\Bus;
use App\Models\Route;
use App\Models\RouteSchedule;
use App\Models\RouteSchedulerMSTR;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Symfony\Component\Console\Output\ConsoleOutput;

class AddRouteSchedule extends Component
{
    public $routes;
    public $buses;
    public $state = [];

    public function render()
    {
        $this->routes=Route::all();
        $this->buses=Bus::all();
        return view('livewire.add-route-schedule');
    }

    public function addRouteSchedule()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE");

        $validatedData = Validator::make($this->state, [
            'schedule_time'=> ['required', 'date_format:H:i'],
            'route_id'=> ['required', 'int'],
            'inbound_distance'=> ['required', 'between:0,99.99'],
            'outbound_distance'=> ['required', 'between:0,99.99'],
            'inbound_bus_id'=> ['required', 'int'],
            'outbound_bus_id'=> ['required', 'int'],
            'status'=> ['required', 'int'],
            'trip_type'=> ['required', 'int'],
        ])->validate();

        $out->writeln($validatedData['schedule_time']);
        $out->writeln($validatedData['route_id']);
        $out->writeln($validatedData['inbound_distance']);
        $out->writeln($validatedData['outbound_distance']);
        $out->writeln($validatedData['inbound_bus_id']);
        $out->writeln($validatedData['outbound_bus_id']);
        $out->writeln($validatedData['status']);
        $out->writeln($validatedData['trip_type']);

        RouteSchedulerMSTR::create($validatedData);

        return redirect()->to('/settings/manageScheduler')->with(['message' => 'Route Schedule added successfully!']);
    }
}
