<?php

namespace App\Http\Livewire;

use App\Models\RouteSchedule;
use Livewire\Component;

class ViewRouteSchedule extends Component
{
    public $schedules;

    public function render()
    {
        return view('livewire.view-route-schedule');
    }
}
