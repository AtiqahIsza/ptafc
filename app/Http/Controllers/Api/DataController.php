<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketSalesTransaction;
use App\Models\TripDetail;
use App\Models\VehiclePosition;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Output\ConsoleOutput;

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
            $newTrip->id = $parse[0];

            //$startFormat = Carbon::createFromFormat('d-m-Y H:i', $parse[1])->format('Y-m-d H:i:s');
            $newTrip->start_trip = $parse[1];

            //$endFormat = Carbon::createFromFormat('d/m/Y H:i', $parse[1])->format('Y-m-d H:i:s');
            $newTrip->end_trip = $parse[2];

            $newTrip->route_schedule_mstr_id = $parse[3];
            $newTrip->bus_id = $parse[4];
            $newTrip->route_id = $parse[5];
            $newTrip->driver_id = $parse[6];
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
        $index = 0;
        $saved = 0;
        foreach ($reads as $read) {
            $index++;
            //skip  first line
            if ($index > 1) {
                $parse = str_getcsv($read, ',');
                $newTicket = new TicketSalesTransaction();
                $newTicket->trip_id = $parse[0];
                $newTicket->ticket_number = $parse[1];
                $newTicket->bus_stand_id = $parse[2];
                $newTicket->fromstage_stage_id = $parse[3];
                $newTicket->tostage_stage_id = $parse[4];
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
            }
        }
        return response()->json([
            'success' => true,
            'saved' => $saved,
        ]);
    }

    public function loadGPSHistoryData(Request $request)
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
        $index = 0;
        $saved = 0;
        foreach ($reads as $read) {
            $index++;
            //skip  first line
            if ($index > 1) {
                $parse = str_getcsv($read, ',');
                $newGPS = new VehiclePosition();
                $newGPS->vehicle_reg_no = $parse[0];
                $newGPS->type = $parse[1];
                $newGPS->imei = $parse[2];
                $newGPS->latitude = $parse[3];
                $newGPS->longitude = $parse[4];
                $newGPS->altitude = $parse[5];
                $newGPS->timestamp = $parse[6];
                $newGPS->speed = $parse[7];
                $newGPS->bearing = $parse[8];
                $newGPS->odometer = $parse[9];
                $newGPS->satellite_count = $parse[10];
                $newGPS->hdop = $parse[11];
                $newGPS->d2d3 = $parse[12];
                $newGPS->rssi = $parse[13];
                $newGPS->lac = $parse[14];
                $newGPS->cell_id = $parse[15];
                $successSave = $newGPS->save();
                if ($successSave) {
                    $saved++;
                }
            }
        }
        return response()->json([
            'success' => true,
            'saved' => $saved,
        ]);
    }
}
