<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\VehiclePosition;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Output\ConsoleOutput;

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

        $dateFrom = new Carbon($validatedData['dateChoose']);
        $dateTo = new Carbon($validatedData['dateChoose'] . '23:59:59');

        $out->writeln("bus: " . $validatedData['bus_id']);
        $out->writeln("datefrom: " . $dateFrom);
        $out->writeln("dateto: " . $dateTo);

        $buses = Bus::where('id',$validatedData['bus_id'])->first();
        $date = $validatedData['dateChoose'];
        $vehiclePosition = VehiclePosition::select('latitude', 'longitude', 'speed')
            ->where('bus_id', $validatedData['bus_id'])
            ->whereBetween('date_time', [$dateFrom, $dateTo])
            ->orderby('date_time')
            ->get();

        if(count($vehiclePosition)>0){
            $exist = true;
        }else{
            $exist = false;
        }

        return view('gps.view', compact('vehiclePosition','buses','date','exist'));
    }
}
