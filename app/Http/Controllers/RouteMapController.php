<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\RouteMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Support\Facades\Redirect;

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
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $out = new ConsoleOutput();
        $routeMaps = $request->markers;


        try{
            foreach($routeMaps as $key => $value){
                /*$out->writeln($value['lat']);
                $out->writeln(round($value['lat'],10));
                $out->writeln($value['long']);
                $out->writeln(round($value['long'],10));
                $out->writeln($value['sequence']);
                $out->writeln($value['route_id']);*/

                $newMap = new RouteMap();
                $newMap->longitude = round($value['long'],10);
                $newMap->latitude = round($value['lat'],10);
                $newMap->sequence = $value['sequence'];
                $newMap->route_id = $value['route_id'];
                $newMap->save();

                $id = $value['route_id'];
            }
            $out->writeln($id);
            //return redirect()->route('profile', ['id' => 1]);
            //$this->show($id);
            //return redirect()->route('viewRouteMap', $id)->with('message', 'Route Map Successfully Stored!');
            //return redirect()->route('viewRouteMap', ['id' => $id])->with('message', 'Route Map Successfully Stored!');
            //return route('viewRouteMap', $id)->with(['message' => 'Route Map Successfully Stored!']);
            //return redirect()->to('/settings/manageRouteMap/'.$id.'/view')

            //return view('settings.viewRouteMap', compact('route','routeMaps'));
            //route('unreconcil', ['cat' => $cat])
            return $this->returnResponse(1, route('viewRouteMap', ['id' => $id]), "Route Map Successfully Stored");
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
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        //dd($request);
        $route = Route::where('id', $request->route('id'))->first();
        $routeMaps = RouteMap::select('latitude', 'longitude')
            ->where('route_id', $request->route('id'))
            ->orderby('sequence')
            ->get();
        return view('settings.viewRouteMap', compact('route','routeMaps'));
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
