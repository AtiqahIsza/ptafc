<?php

namespace App\Http\Livewire;

use App\Models\Bus;
use App\Models\Route;
use App\Models\RouteSchedulerMSTR;
use Livewire\Component;

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


    public function render()
    {
        return view('livewire.view-route-schedule');
    }

    public function mount(){
        $this->routes = collect();
        $this->buses = collect();
        $this->state = collect();
        $this->removedSchedule = collect();
    }

    public function edit(RouteSchedulerMSTR $schedule){
        //$this->reset();
        $this->routes = Route::all();
        $this->buses = Bus::all();
        //$this->schedules = RouteSchedulerMSTR::all();
        //$this->schedules = $schedule;
        $this->state = $schedule->toArray();
        $this->editButton = true;
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
