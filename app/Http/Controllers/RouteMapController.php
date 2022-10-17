<?php

namespace App\Http\Controllers;

use App\Models\BusStand;
use App\Models\Route;
use App\Models\RouteMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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

                $newMap = new RouteMap();
                $newMap->longitude = round($value['long'],10);
                $newMap->latitude = round($value['lat'],10);
                $newMap->sequence = $value['sequence'];
                $newMap->route_id = $value['route_id'];
                $newMap->created_by = auth()->user()->id;
                $newMap->updated_by = auth()->user()->id;

                $newMap->save();

                $id = $value['route_id'];
            }
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
        $updatedBy = RouteMap::where('route_id', $request->route('id'))
            ->orderby('sequence','DESC')
            ->first();
        return view('settings.viewRouteMap', compact('route','routeMaps','updatedBy'));
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

    public function uploadFile(Request $request)
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN  uploadFile");

        $validator = Validator::make($request->all(), [
            'file' => 'required', 'mimes:application/vnd.google-earth.kml'
        ]);

        if ($validator->fails()) {
            return redirect()->to('/settings/manageRoute')->with(['message' => $validator->messages()->first()]);
        }

        $coordinates = NULL;
        $gps = [];
        if($validator->passes()){
            $path = $request->file('file')->store('kml');
            $content = Storage::get($path);
            if($content) {
                $xmlObject = simplexml_load_string($content);
                $placemarks = $xmlObject->Document->Placemark;
                for ($i = 0; $i < sizeof($placemarks); $i++) {
                    $out->writeln("YOU ARE IN  loop placemarks");
                    //LineString
                    if($placemarks[$i]->LineString) {
                        $route = $placemarks[$i]->name;
                        $out->writeln("Route Name " . $i . ":" . $route);
                        $routeNo = substr($route, 0, 5);
                        $out->writeln("Route No: " . $routeNo);
                        $routeName = substr($route, 6, -15);
                        $out->writeln("Route Name: " . $routeName);
                        $coordinates[$i] = $placemarks[$i]->LineString->coordinates;
                        //$out->writeln("Polygon " . $i . ":" . $coordinates);
                    } //Point
                    if($placemarks[$i]->Point){
                        $gps['name'][$i] = $placemarks[$i]->name;
                        $out->writeln("Stage Name " . $i . ":" . $gps['name'][$i]);
                        $gps['longitude'][$i] = $placemarks[$i]->LookAt->longitude;
                        $out->writeln("Longitude" . $i . ": " . $gps['longitude'][$i]);
                        $gps['latitude'][$i] = $placemarks[$i]->LookAt->latitude;
                        $out->writeln("Latitude" . $i . ": " . $gps['latitude'][$i]);
                        $gps['altitude'][$i] = $placemarks[$i]->LookAt->altitude;
                        $out->writeln("Altitude " . $i . ": " . $gps['altitude'][$i]);
                    }
                }

                $polygon = [];
                //Sort Polygon
                if(count($coordinates)>0){
                    $countCoordinates = 0;
                    foreach($coordinates as $coordinate){
                        $polyArray = explode(',', $coordinate);
                        $indexLong = 0;
                        for ($k = 0; $k < sizeof($polyArray); $k++) {
                            if ($k + 1 != sizeof($polyArray)) {
                                if ($k % 2 == 0) {
                                    $longitude[$indexLong] = $polyArray[$k];
                                    $indexLong++;
                                }
                            }
                        }
                        //$out->writeln("Size longitude[] " . ":" . sizeof($longitude));
                        $indexLat = 0;
                        for ($m = 0; $m < sizeof($polyArray); $m++) {
                            if ($m % 2 == 1) {
                                $latitude[$indexLat] = $polyArray[$m];
                                $indexLat++;
                            }
                        }
                        //$out->writeln("Size latitude[] " . ":" . sizeof($latitude));
                        for ($p = 0; $p < sizeof($longitude); $p++) {
                            $polygon['longitude'][$countCoordinates][$p] = $longitude[$p];
                            $polygon['latitude'][$countCoordinates][$p] = $latitude[$p];
                            $out->writeln("Polygon " . $p . ":" . $polygon['longitude'][$countCoordinates][$p] . "-" . $polygon['latitude'][$countCoordinates][$p]);
                        }
                        $countCoordinates++;
                    }
                }

                //Save to DB
                $savedRMap = 0;
                $savedMap = 0;
                $checkRoute = Route::where('route_number', $routeNo)->first();
                if ($checkRoute) {
                    $checkRouteMap = RouteMap::where('route_id', $checkRoute->id)->first();
                    if (empty($checkRouteMap)) {
                        if ($polygon!=NULL) {
                            $seq = 0;
                            for ($m = 0; $m < sizeof($polygon['longitude']); $m++) {
                                for ($b = 0; $b < sizeof($polygon['longitude'][$m]); $b++) {
                                    $seq++;
                                    $long = NULL;
                                    if ($b == 0) {
                                        $long = $polygon['longitude'][$m][$b];
                                        $out->writeln("b: " . $long);
                                    } else {
                                        $long = substr($polygon['longitude'][$m][$b], 2);
                                        $out->writeln("Substr long rmap: " . $long);
                                    }
                                    $newRMap = new RouteMap();
                                    $newRMap->longitude = round((float)$long, 15);
                                    $newRMap->latitude = round((float)$polygon['latitude'][$m][$b], 15);
                                    $newRMap->sequence = $seq;
                                    $newRMap->route_id = $checkRoute->id;
                                    $newRMap->created_by = auth()->user()->id;
                                    $newRMap->updated_by = auth()->user()->id;
                                    $successSaveRMap = $newRMap->save();
                                    if ($successSaveRMap) {
                                        $savedRMap++;
                                    }
                                }
                            }
                        }
                        $checkBusStand = BusStand::where('route_id', $checkRoute->id)->first();
                        if (empty($checkBusStand)) {
                            if ($gps!=NULL) {
                                for ($d = 0; $d < sizeof($gps['name']); $d++) {
                                    $newSMap = new BusStand();
                                    $newSMap->longitude = round((float)$gps['longitude'][$d], 15);
                                    $newSMap->latitude = round((float)$gps['latitude'][$d], 15);
                                    $newSMap->altitude = $gps['altitude'][$d];
                                    $newSMap->description = $gps['name'][$d];
                                    $newSMap->route_id = $checkRoute->id;
                                    $newSMap->radius = 50;
                                    $newSMap->sequence = $d;
                                    $newRMap->created_by = auth()->user()->id;
                                    $newRMap->updated_by = auth()->user()->id;
                                    $successSaveSMap = $newSMap->save();
                                    if ($successSaveSMap) {
                                        $savedMap++;
                                    }
                                }
                            }
                        }else{
                            return redirect()->to('/settings/manageRoute')->with(['message' => 'Bus stand already exist in the database, please remove the current one to update']);
                        }
                    }else{
                        return redirect()->to('/settings/manageRoute')->with(['message' => 'Route map already exist in the database, please remove the current one to update']);
                    }
                } else {
                    return redirect()->to('/settings/manageRoute')->with(['message' => 'Route is not exist in the database']);
                }
                return redirect()->to('/settings/manageRoute')->with(['message' => 'File Upload Successfully!']);
            }
            return redirect()->to('/settings/manageRoute')->with(['message' => 'Failed to Read File!']);
        }
        return redirect()->to('/settings/manageRoute')->with(['message' => 'File Upload Failed!']);
    }

    public function uploadFileOld(Request $request)
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN  uploadFile");

        $validator = Validator::make($request->all(), [
            'file' => 'required', 'mimes:application/vnd.google-earth.kml'
        ]);

        if ($validator->fails()) {
            return redirect()->to('/settings/manageRoute')->with(['message' => $validator->messages()->first()]);
        }

        $coordinates = NULL;
        $gps = [];
        if($validator->passes()){
            $path = $request->file('file')->store('kml');
            $content = Storage::get($path);
            if($content) {
                $xmlObject = simplexml_load_string($content);
                $placemarks = $xmlObject->Document->Placemark;
                for ($i = 0; $i < sizeof($placemarks); $i++) {
                    $out->writeln("YOU ARE IN  loop placemarks");
                    //LineString
                    if($placemarks[$i]->LineString) {
                        $route = $placemarks[$i]->name;
                        $out->writeln("Route Name " . $i . ":" . $route);
                        $routeNo = substr($route, 0, 5);
                        $out->writeln("Route No: " . $routeNo);
                        $routeName = substr($route, 6, -15);
                        $out->writeln("Route Name: " . $routeName);
                        $coordinates = $placemarks[$i]->LineString->coordinates;
                        $out->writeln("Polygon " . $i . ":" . $coordinates);
                    } //Point
                    if($placemarks[$i]->Point){
                        $gps['name'][$i] = $placemarks[$i]->name;
                        $out->writeln("Stage Name " . $i . ":" . $gps['name'][$i]);
                        $gps['longitude'][$i] = $placemarks[$i]->LookAt->longitude;
                        $out->writeln("Longitude" . $i . ": " . $gps['longitude'][$i]);
                        $gps['latitude'][$i] = $placemarks[$i]->LookAt->latitude;
                        $out->writeln("Latitude" . $i . ": " . $gps['latitude'][$i]);
                        $gps['altitude'][$i] = $placemarks[$i]->LookAt->altitude;
                        $out->writeln("Altitude " . $i . ": " . $gps['altitude'][$i]);
                    }
                }

                $polygon = [];
                //Sort Polygon
                if($coordinates!=NULL){
                    $polyArray = explode(',', $coordinates);
                    $indexLong = 0;
                    for ($k = 0; $k < sizeof($polyArray); $k++) {
                        if ($k + 1 != sizeof($polyArray)) {
                            if ($k % 2 == 0) {
                                $longitude[$indexLong] = $polyArray[$k];
                                $indexLong++;
                            }
                        }
                    }
                    //$out->writeln("Size longitude[] " . ":" . sizeof($longitude));
                    $indexLat = 0;
                    for ($m = 0; $m < sizeof($polyArray); $m++) {
                        if ($m % 2 == 1) {
                            $latitude[$indexLat] = $polyArray[$m];
                            $indexLat++;
                        }
                    }
                    //$out->writeln("Size latitude[] " . ":" . sizeof($latitude));
                    for ($p = 0; $p < sizeof($longitude); $p++) {
                        $polygon['longitude'][$p] = $longitude[$p];
                        $polygon['latitude'][$p] = $latitude[$p];
                        $out->writeln("Polygon " . $p . ":" . $polygon['longitude'][$p] . "-" . $polygon['latitude'][$p]);
                    }
                }

                //Save to DB
                $savedRMap = 0;
                $savedMap = 0;
                $checkRoute = Route::where('route_number', $routeNo)->first();
                if ($checkRoute) {
                    $checkRouteMap = RouteMap::where('route_id', $checkRoute->id)->first();
                    if (empty($checkRouteMap)) {
                        if ($polygon!=NULL) {
                            for ($b = 0; $b < sizeof($polygon['longitude']); $b++) {
                                $long = NULL;
                                if ($b == 0) {
                                    $long = $polygon['longitude'][$b];
                                    $out->writeln("b: " . $long);
                                } else {
                                    $long = substr($polygon['longitude'][$b], 2);
                                    $out->writeln("Substr long rmap: " . $long);
                                }
                                $newRMap = new RouteMap();
                                $newRMap->longitude = round((float)$long, 15);
                                $newRMap->latitude = round((float)$polygon['latitude'][$b], 15);
                                $newRMap->sequence = $b;
                                $newRMap->route_id = $checkRoute->id;
                                $newRMap->created_by = auth()->user()->id;
                                $newRMap->updated_by = auth()->user()->id;
                                $successSaveRMap = $newRMap->save();
                                if ($successSaveRMap) {
                                    $savedRMap++;
                                }
                            }
                        }
                        $checkBusStand = BusStand::where('route_id', $checkRoute->id)->first();
                        if (empty($checkBusStand)) {
                            if ($gps!=NULL) {
                                for ($d = 0; $d < sizeof($gps['name']); $d++) {
                                    $newSMap = new BusStand();
                                    $newSMap->longitude = round((float)$gps['longitude'][$d], 15);
                                    $newSMap->latitude = round((float)$gps['latitude'][$d], 15);
                                    $newSMap->altitude = $gps['altitude'][$d];
                                    $newSMap->description = $gps['name'][$d];
                                    $newSMap->route_id = $checkRoute->id;
                                    $newSMap->radius = 50;
                                    $newSMap->sequence = $d;
                                    $newRMap->created_by = auth()->user()->id;
                                    $newRMap->updated_by = auth()->user()->id;
                                    $successSaveSMap = $newSMap->save();
                                    if ($successSaveSMap) {
                                        $savedMap++;
                                    }
                                }
                            }
                        }else{
                            return redirect()->to('/settings/manageRoute')->with(['message' => 'Bus stand already exist in the database, please remove the current one to update']);
                        }
                    }else{
                        return redirect()->to('/settings/manageRoute')->with(['message' => 'Route map already exist in the database, please remove the current one to update']);
                    }
                } else {
                    return redirect()->to('/settings/manageRoute')->with(['message' => 'Route is not exist in the database']);
                }
                return redirect()->to('/settings/manageRoute')->with(['message' => 'File Upload Successfully!']);
            }
            return redirect()->to('/settings/manageRoute')->with(['message' => 'Failed to Read File!']);
        }
        return redirect()->to('/settings/manageRoute')->with(['message' => 'File Upload Failed!']);
    }

}
