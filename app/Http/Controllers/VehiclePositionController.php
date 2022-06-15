<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\VehiclePosition;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Support\Facades\DB;

class VehiclePositionController extends Controller
{
    public function index()
    {
        return view('gps.index');
    }

    public function show(Request $request)
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN HERE show");

        $validatedData = Validator::make($request->all(), [
            'dateChoose' => ['required', 'date'],
            'bus_id' => ['required', 'int'],
        ])->validate();

        $buses = $validatedData['bus_id'];
        $date = $validatedData['dateChoose'];

        return view('gps.view', compact('buses', 'date'));
    }

    public function showRealtime()
    {
        return view('gps.realtime');
    }
    
    public function viewRealtime(Request $request)
    {
        $allData = VehiclePosition::where('id', $request->route('id'))->first();
        $vehiclePosition = VehiclePosition::select('latitude', 'longitude', 'speed')
            ->where('id', $request->route('id'))
            ->first();
        $viewedBus = Bus::where('id', $allData->bus_id)->first();
        $viewedDate = $allData->date_time;

        if($vehiclePosition){
            $exist = true;
        }else{
            $exist = false;
        }

        return view('gps.view-realtime', compact('vehiclePosition','viewedBus','viewedDate','exist'));
    }

    public function viewSummary()
    {
        $currentDate = Carbon::now();

        $join = DB::table('vehicle_position')
                ->select('bus_id', DB::raw('MAX(id) as last_id'))
                ->groupBy('bus_id');

        $allBus = DB::table('vehicle_position as a')
            ->joinSub($join, 'b', function ($join) {
                $join->on('a.id', '=', 'b.last_id');
            })
            ->count();

        $onlineBus = DB::table('vehicle_position as a')
            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) < '00:10:00'")
            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
            ->joinSub($join, 'b', function ($join) {
                $join->on('a.id', '=', 'b.last_id');
            })
            ->count();

        $stationaryBus = DB::table('vehicle_position as a')
            ->whereRaw("TIMEDIFF(" . " '$currentDate' " . ", a.date_time) >=  '00:10:00'")
            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) <  1")
            ->joinSub($join, 'b', function ($join) {
                $join->on('a.id', '=', 'b.last_id');
            })
            ->count();

        $offlineBus = DB::table('vehicle_position as a')
            ->whereRaw("DATEDIFF(" . " '$currentDate' " . ", a.date_time) >=  1")
            ->joinSub($join, 'b', function ($join) {
                $join->on('a.id', '=', 'b.last_id');
            })
            ->count();

        return view('gps.summary', compact('allBus','onlineBus','stationaryBus','offlineBus'));
    }
}
