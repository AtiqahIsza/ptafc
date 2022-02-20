<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use App\Models\Bus;
use App\Models\BusSchedulerDetail;
use App\Models\Route;
use App\Models\RouteSchedule;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Symfony\Component\Console\Output\ConsoleOutput;

class ManageRouteScheduler extends Component
{
    public $events = '';
    public $buses;
    public $routes;
    public $schedule;
    public $startDate;
    public $state=[];


    public function render()
    {
        $this->buses = Bus::all();
        $this->routes = Route::all();
        $this->schedule = RouteSchedule::all();
        //$events = Event::select('id','title','start')->get();
        $events = RouteSchedule::select('id','title','start')->get();
        $this->events = json_encode($events);
        return view('livewire.manage-route-scheduler');
    }

    public function modalAdd($startDate)
    {
        $this->startDate = $startDate;
        $this->buses = Bus::all();
        $this->routes = Route::all();
        $this->dispatchBrowserEvent('add-form');
    }

    public function modalView($id)
    {
        $this->state = [];
        $schedule = RouteSchedule::where('id', $id)->first();
        $this->schedule = $schedule;
        $this->state = $schedule->toArray();
        $this->dispatchBrowserEvent('view-modal');
    }

    public function updateSchedule()
    {
        $validatedData = Validator::make($this->state, [
            'title'=> ['required', 'string', 'max:255'],
            'sequence'=> ['required', 'int'],
            'time'=> ['required', 'date_format:H:i'],
            'start'=> ['required', 'date_format:Y-m-d'],
            'inbus_id'=> ['required', 'int'],
            'outbus_id'=> ['required', 'int'],
            'route_id'=> ['required', 'int'],
        ])->validate();

        $this->schedule->update($validatedData);

        return redirect()->to('/settings/manageRoute')->with(['message' => 'Route updated successfully!']);

        //return Redirect::back()->with(['message' => 'Sector updated successfully!']);
        //$this->emit('hide-form');
        //session()->flash('message', 'Sector successfully updated!');
        //$this->dispatchBrowserEvent('hide-form', ['message' => 'Sector updated successfully!']);
    }

    //ADD NEW DB FOR THIS (route_schedule)
    public function addScheduleRoute()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE");

        $validatedData = Validator::make($this->state, [
            'title'=> ['required', 'string', 'max:255'],
            'sequence'=> ['required', 'int'],
            'time'=> ['required', 'date_format:H:i'],
            'inbus_id'=> ['required', 'int'],
            'outbus_id'=> ['required', 'int'],
            'route_id'=> ['required', 'int'],
        ])->validate();

        $validatedData['start'] = $this->startDate;

        $out->writeln($validatedData['title']);
        $out->writeln($validatedData['schedule_date']);
        $out->writeln($validatedData['sequence']);
        $out->writeln($validatedData['time']);
        $out->writeln($validatedData['inbus_id']);
        $out->writeln($validatedData['outbus_id']);
        $out->writeln($validatedData['route_id']);

        /*$input  = $validatedData['schedule_date'];
        $out->writeln($input);
        $format = 'd-m-Y';
        $date = Carbon::parse($this->startDate);
        $out->writeln($date);*/

        //check existed sequence on route_id and startDate
        $existedSeq = RouteSchedule::where([
            ['start',  $validatedData['start']],
            ['route_id', $validatedData['route_id']],
            ['sequence',$validatedData['sequence']]
        ])->first();

        if($existedSeq){
            return Redirect::back()->with(['message' => 'Existed sequence for this route on selected date!']);
        }

        RouteSchedule::create($validatedData);

        return redirect()->to('/settings/manageScheduler')->with(['message' => 'Route Schedule added successfully!']);

        //return Redirect::back()->with(['message' => 'Sector added successfully!']);
        //$this->dispatchBrowserEvent('hide-form', ['message' => 'Sector added successfully!']);
    }

    public function getevent()
    {
        $events = BusSchedulerDetail::all();
        return  json_encode($events);
    }

    public function addevent($event)
    {
        $input['route_id'] = $event['title'];
        $input['start'] = $event['start'];
        Event::create($input);
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function eventDrop($event, $oldEvent)
    {
        $eventdata = Event::find($event['id']);
        $eventdata->start = $event['start'];
        $eventdata->save();
    }
}
