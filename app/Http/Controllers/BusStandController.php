<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\BusStand;
use App\Models\Company;
use App\Models\Route;
use App\Models\RouteMap;
use App\Models\Stage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\Console\Output\ConsoleOutput;

class BusStandController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function index()
    {
        return view('settings.manageBusStand');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $routes = Route::where('id', $request->route('id'))->first();
        $routeMaps = RouteMap::select('latitude', 'longitude')
            ->where('route_id', $request->route('id'))
            ->orderby('sequence')
            ->get();
        return view('settings.addBusStand', compact( 'routes','routeMaps'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request): JsonResponse
    {
        $out = new ConsoleOutput();
        $busStands = $request->markers;

        try{
            foreach($busStands as $key => $value){
                $out->writeln($value['lat']);
                $out->writeln(round($value['lat'],10));
                $out->writeln($value['long']);
                $out->writeln(round($value['long'],10));
                $out->writeln($value['sequence']);
                $out->writeln($value['route_id']);
                $out->writeln($value['radius']);

                $newMap = new BusStand();
                $newMap->longitude = round($value['long'],10);
                $newMap->latitude = round($value['lat'],10);
                $newMap->sequence = $value['sequence'];
                $newMap->route_id = $value['route_id'];
                $newMap->radius = $value['radius'];
                $newMap->save();
            }
            return $this->returnResponse(1, "Bus Stand Successfully Stored", "Bus Stand Successfully Stored");
        }
        catch(\Exception $e){
            $out->writeln($e);
            $error['error'] =  $e;
            return $this->returnResponse(2, $error, "Error Occurred, see error log");
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BusStand  $busStand
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $routes = Route::where('id', $request->route('id'))->first();
        $routeMaps = RouteMap::select('latitude', 'longitude')
            ->where('route_id', $request->route('id'))
            ->orderby('sequence')
            ->get();
        $busStand = BusStand::select('latitude', 'longitude','radius','description')
            ->where('route_id', $request->route('id'))
            ->orderby('sequence')
            ->get();
        return view('settings.viewBusStand', compact('routes','routeMaps','busStand'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BusStand  $busStand
     * @return \Illuminate\Http\Response
     */
    public function edit(BusStand $busStand)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BusStand  $busStand
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BusStand $busStand)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BusStand  $busStand
     * @return \Illuminate\Http\Response
     */
    public function destroy(BusStand $busStand)
    {
        //
    }
}
