<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\RouteMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\Console\Output\ConsoleOutput;

class RouteMapController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $route = Route::where('id', $request->route('id'))->first();

        return view('settings.addRouteMap', compact('route'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
        $routeMaps = $request->all();
        $out->writeln($routeMaps);
        try{
            foreach($routeMaps as $routeMap)
            {
                $newMap = new RouteMap();
                $newMap->longitude = $routeMap['lng'];
                $newMap->latitute = $routeMap['lat'];
                $newMap->sequence = $routeMap['sequence'];
                $newMap->route_id = $routeMap['route_id'];
                $newMap->save();
            }

            $log['current_route_map'] = $newMap;

            return $this->returnResponse(1, $log, "Route Map Successfully Stored");
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
     * @param  \App\Models\RouteMap  $routeMap
     * @return \Illuminate\Http\Response
     */
    public function show(RouteMap $routeMap)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RouteMap  $routeMap
     * @return \Illuminate\Http\Response
     */
    public function edit(RouteMap $routeMap)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RouteMap  $routeMap
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RouteMap $routeMap)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RouteMap  $routeMap
     * @return \Illuminate\Http\Response
     */
    public function destroy(RouteMap $routeMap)
    {
        //
    }

    public function returnResponse ($statusCode, $payload, $statusDescription) : JsonResponse
    {
        $response['statusCode'] = $statusCode ;
        $response['payload'] = $payload;
        $response['statusDescription'] = $statusDescription;

        return response()->json($response);
    }
}
