<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Support\Facades\DB;
use App\Models\Bus;
use App\Models\TripDetail;
use App\Models\VehiclePosition;
use Carbon\Carbon;

class GpsHistory extends Component
{
    public $vehiclePosition;
    public $buses;
    public $date;
    public $exist;
    public $trips;
    public $tripDetails;
    public $selectedTrip = NULL;
    public $allTripByBus;

    public function mount($buses, $date)
    {
        $this->vehiclePosition = collect();
        $this->date = $date;
        $this->buses = $buses;
        $this->chosenBus = collect();
        $this->exist = collect();
        $this->trips = collect();
    }

    public function render()
    {
        // dd($this->date);
        $dateFrom = new Carbon($this->date);
        $dateTo = new Carbon($this->date . '23:59:59');

        $this->trips = VehiclePosition::select('trip_id')
            ->where('bus_id', $this->buses)
            ->whereBetween('date_time', [$dateFrom, $dateTo])
            ->groupBy('trip_id')
            ->get();

        $this->chosenBus = Bus::where('id', $this->buses)->first();

        //dd($this->trips);

        //$getId = $this->allTripByBus;
        //dd($this->trips);
        //dd($this->allTripByBus);
        //dd($this->vehiclePosition);
        //$this->vehiclePosition = collect();
        //$this->tripDetails = TripDetail::where('trip_number', $this->trips)->get();
        return view('livewire.gps-history');
    }

    public function updatedSelectedTrip($trip)
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE  updatedSelectedTrip{}: " . $trip);
        $out->writeln("selectedTrip: " . $this->selectedTrip);
        
        if ($this->selectedTrip!=NULL){
            if ($this->selectedTrip=='ALL'){
                $dateFrom = new Carbon($this->date);
                $dateTo = new Carbon($this->date . '23:59:59');

                $allTrips = VehiclePosition::select('trip_id')
                    ->where('bus_id', $this->buses)
                    ->whereBetween('date_time', [$dateFrom, $dateTo])
                    ->groupBy('trip_id')
                    ->get();

                $allTripsArr = [];
                foreach($allTrips as $allTrip){
                    $allTripsArr[] = $allTrip->trip_id;
                }

                $this->tripDetails = TripDetail::whereIn('trip_number', $allTripsArr)->get();

                $this->vehiclePosition = DB::table('vehicle_position as a')
                    ->join('bus as c', 'c.id', '=', 'a.bus_id')
                    ->whereIn('trip_id', $allTripsArr)
                    ->get(['a.*', 'c.bus_registration_number']);
            }else{
                $this->vehiclePosition = DB::table('vehicle_position as a')
                    ->join('bus as c', 'c.id', '=', 'a.bus_id')
                    ->where('trip_id', $this->selectedTrip)
                    ->get(['a.*', 'c.bus_registration_number']);

                $this->tripDetails = TripDetail::where('trip_number',  $this->selectedTrip)->get();
            }
        }else{
            $this->vehiclePosition = collect();
        }
        $dateFrom = new Carbon($this->date);
        $dateTo = new Carbon($this->date . '23:59:59');
        $this->trips = VehiclePosition::select('trip_id')
            ->where('bus_id', $this->buses)
            ->whereBetween('date_time', [$dateFrom, $dateTo])
            ->groupBy('trip_id')
            ->get();
        $this->dispatchBrowserEvent('livewire:load', ['vehiclePosition' => $this->vehiclePosition]);
    }
}
