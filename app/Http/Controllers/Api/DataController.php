<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\BusDriver;
use App\Models\BusStand;
use App\Models\Route;
use App\Models\RouteMap;
use App\Models\RouteSchedulerMSTR;
use App\Models\Stage;
use App\Models\StageMap;
use App\Models\TicketSalesTransaction;
use App\Models\TripDetail;
use App\Models\VehiclePosition;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Output\ConsoleOutput;
use function Illuminate\Events\queueable;

class DataController extends Controller
{
    public function loadTripData(Request $request)
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN  loadTripData");

        //dd($request->all());
        $validator = Validator::make($request->all(), [
            'fileToUpload' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->messages()->first(),
            ]);
        }

        $data = $request->file('fileToUpload');
        $reads = file($data);
        $index = 0;
        $saved = 0;
        foreach ($reads as $read) {
            $parse = str_getcsv($read, ',');
            $newTrip = new TripDetail();

            $newTrip->trip_number = $parse[0];
            //$startFormat = Carbon::createFromFormat('d-m-Y H:i', $parse[1])->format('Y-m-d H:i:s');
            $newTrip->start_trip = $parse[1];
            //$endFormat = Carbon::createFromFormat('d/m/Y H:i', $parse[1])->format('Y-m-d H:i:s');
            $newTrip->end_trip = $parse[2];

            $checkSchedule = RouteSchedulerMSTR::where('id', $parse[3])->first();
            if(!empty($checkSchedule)){
                $newTrip->route_schedule_mstr_id = $parse[3];
            }
            $checkBus = Bus::where('id', $parse[4])->first();
            if(!empty($checkBus)){
                $newTrip->bus_id = $parse[4];
            }
            $checkRoute = Route::where('id', $parse[5])->first();
            if(!empty($checkRoute)){
                $newTrip->route_id = $parse[5];
            }
            $checkDriver = BusDriver::where('id', $parse[6])->first();
            if(!empty($checkDriver)){
                $newTrip->driver_id = $parse[6];
            }

            $newTrip->total_adult = $parse[7];
            $newTrip->total_concession = $parse[8];
            $newTrip->total_adult_amount = $parse[9];
            $newTrip->total_concession_amount = $parse[10];
            $newTrip->total_mileage = $parse[11];
            $newTrip->trip_code = $parse[12];
            $successSave = $newTrip->save();
            if($successSave){
                $saved++;
            }
        }
        return response()->json([
            'success' => true,
            'saved' => $saved,
        ]);
    }

    public function loadTicketSalesData(Request $request)
    {
        //dd($request->all());
        $validator = Validator::make($request->all(), [
            'fileToUpload' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->messages()->first(),
            ]);
        }

        $data = $request->file('fileToUpload');
        $reads = file($data);
        //$index = 0;
        $saved = 0;
        foreach ($reads as $read) {
            //$index++;
            //skip  first line
            //if ($index > 1) {
                $parse = str_getcsv($read, ',');
                $newTicket = new TicketSalesTransaction();

                $getTripID = TripDetail::where('trip_number', $parse[0])->first();
                $newTicket->trip_id = $getTripID->id;

                $newTicket->trip_number = $parse[0];
                $newTicket->ticket_number = $parse[1];

                $checkBusStand = BusStand::where('id', $parse[2])->first();
                if(!empty($checkBusStand)){
                    $newTicket->bus_stand_id = $parse[2];
                }

                $checkFromStage = Stage::where('id', $parse[3])->first();
                $checkToStage = Stage::where('id', $parse[4])->first();
                if(!empty($checkFromStage)){
                    $newTicket->fromstage_stage_id = $parse[3];
                }
                if(!empty($checkToStage)){
                    $newTicket->tostage_stage_id = $parse[4];
                }
                $newTicket->passenger_type = $parse[5];
                $newTicket->amount = $parse[6];
                $newTicket->actual_amount = $parse[7];
                $newTicket->fare_type = $parse[8];
                $newTicket->latitude = $parse[9];
                $newTicket->longitude = $parse[10];

                //dd($parse[11]);
                //$salesFormat = Carbon::createFromFormat('d/m/Y H:i:s', $parse[11])->format('Y-m-d H:i:s');
                $newTicket->sales_date = $parse[11];

                /*$newTicket->pda_transaction_id = $parse[2];
                $newTicket->upload_date = $parse[4];
                $newTicket->bus_id = $parse[5];
                $newTicket->bus_driver_id = $parse[6];
                $newTicket->car_id = $parse[7];
                $newTicket->route_id = $parse[9];
                $newTicket->sector_id = $parse[10];
                $newTicket->summary_id = $parse[12];
                $newTicket->pda_id = $parse[13];
                $newTicket->balance_in_card = $parse[14];
                $newTicket->card_trx_sequence = $parse[15];
                $newTicket->trip_number = $parse[16];*/

                $successSave = $newTicket->save();
                if($successSave){
                    $saved++;
                }
            //}
        }
        return response()->json([
            'success' => true,
            'saved' => $saved,
        ]);
    }

    public function loadVehiclePositionData(Request $request)
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN loadVehiclePositionData");

        $validator = Validator::make($request->all(), [
            'fileToUpload' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->messages()->first(),
            ]);
        }

        $data = $request->file('fileToUpload');
        $reads = file($data);
        $index = 0;
        $saved = 0;
        foreach ($reads as $read) {
            $index++;
            //skip  first line
            if ($index > 1) {
                $parse = str_getcsv($read, '|');
                $new = new VehiclePosition();

                $checkBus = Bus::where('id', $parse[0])->first();
                if(!empty($checkBus)){
                    $new->bus_id = $parse[0];
                }
                $checkDriver = BusDriver::where('id', $parse[1])->first();
                if(!empty($checkDriver)){
                    $new->driver_id = $parse[1];
                }
                $new->pda_imei = $parse[2];
                $new->latitude = $parse[3];
                $new->longitude = $parse[4];
                $new->altitude = $parse[5];
                $new->date_time = $parse[6];
                $new->speed = $parse[7];
                $new->satellite_count = $parse[8];
                $new->hdop = $parse[9];
                $new->d2d3= $parse[10];
                $new->rssi = $parse[11];
                $new->cell_id = $parse[12];
                $new->mcc = $parse[13];
                $new->msg_id = $parse[14];
                $new->activity_id = $parse[15];
                $new->addon_json = $parse[16];

                $successSave = $new->save();
                if($successSave){
                    $saved++;
                }
            }
        }
        return response()->json([
            'success' => true,
            'saved' => $saved,
        ]);
    }
    /*public function loadGPSHistoryData(Request $request)
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN  loadGPSHistoryData");

        $validator = Validator::make($request->all(), [
            'fileToUpload' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->messages()->first(),
            ]);
        }

        $xml = simplexml_load_file($request->file('fileToUpload'));
        $placemarks = $xml->Document->Placemark;
        for ($i=0; $i < sizeof($placemarks); $i++) {
            //$out->writeln("YOU ARE IN  loop placemarks");
            //LineString
            if($i+1 == sizeof($placemarks)){
                $route = $placemarks[$i]->name;
                //$out->writeln("Route Name " . $i . ":" . $route);
                $routeNo = substr($route, 0, 4);
                //$out->writeln("Route No: " . $routeNo);
                $routeName = substr($route, 5,-15);
                //$out->writeln("Route Name: " . $routeName);
                $coordinates = $placemarks[$i]->LineString->coordinates;
                //$out->writeln("Polygon " . $i . ":" . $coordinates);
            }
            //Point
            else{
                $gps['name'][$i] = $placemarks[$i]->name;
                //$out->writeln("Stage Name " . $i . ":" . $gps['name'][$i]);
                $gps['longitude'][$i] = $placemarks[$i]->LookAt->longitude;
                //$out->writeln("Longitude" . $i . ": " . $gps['longitude'][$i]);
                $gps['latitude'][$i] = $placemarks[$i]->LookAt->latitude;
                //$out->writeln("Latitude" . $i . ": " . $gps['latitude'][$i]);
                $gps['altitude'][$i] = $placemarks[$i]->LookAt->altitude;
                //$out->writeln("Altitude " . $i . ": " . $gps['altitude'][$i]);
            }
        }

        //Sort Polygon
        $polyArray = explode(',', $coordinates);
        $indexLong = 0;
        for($k=0; $k < sizeof($polyArray); $k++) {
            if($k+1 != sizeof($polyArray)){
                if ($k % 2 == 0) {
                    $longitude[$indexLong] = $polyArray[$k];
                    $indexLong++;
                }
            }
        }
        //$out->writeln("Size longitude[] " . ":" . sizeof($longitude));
        $indexLat = 0;
        for($m=0; $m < sizeof($polyArray); $m++) {
            if ($m % 2 == 1) {
                $latitude[$indexLat] = $polyArray[$m];
                $indexLat++;
            }
        }
        //$out->writeln("Size latitude[] " . ":" . sizeof($latitude));
        for($p=0; $p<sizeof($longitude); $p++) {
            $polygon['longitude'][$p] = $longitude[$p];
            $polygon['latitude'][$p] = $latitude[$p];
            //$out->writeln("Polygon " . $p . ":" . $polygon['longitude'][$p] . "-" . $polygon['latitude'][$p]);
        }

        //Save to DB
        $savedRMap=0;
        $savedMap=0;
        $checkRoute = Route::where('route_number',$routeNo)->first();
        if($checkRoute){
            for($b=0; $b<sizeof($polygon['longitude']); $b++) {
                $long= NULL;
                if($b==0){
                    $long = $polygon['longitude'][$b];
                    //$out->writeln("b: " . $long);
                }else {
                    $long = substr($polygon['longitude'][$b], 2);
                    //$out->writeln("Substr long rmap: " . $long);
                }
                $newRMap = new RouteMap();
                $newRMap->longitude = round((float)$long,15);
                $newRMap->latitude = round((float)$polygon['latitude'][$b],15);
                $newRMap->sequence = $b;
                $newRMap->route_id = $checkRoute->id;
                $successSaveRMap = $newRMap->save();
                if($successSaveRMap){
                    $savedRMap++;
                }
            }
            for($d=0; $d<sizeof($gps['name']); $d++){
                $newSMap = new BusStand();
                $newSMap->longitude = round((float)$gps['longitude'][$d],15);
                $newSMap->latitude = round((float)$gps['latitude'][$d],15);
                $newSMap->altitude =  $gps['altitude'][$d];
                $newSMap->description = $gps['name'][$d];
                $newSMap->route_id = $checkRoute->id;
                $newSMap->radius = 50;
                $successSaveSMap = $newSMap->save();
                if($successSaveSMap){
                    $savedMap++;
                }
            }
        }else{
            return response()->json([
                'success' => false,
                'data' => "Route is not exist in the database",
            ]);
        }

        return response()->json([
            'success' => true,
            'savedRouteMap' => $savedRMap,
            'savedBusStand' => $savedMap
        ]);
    }*/
}
