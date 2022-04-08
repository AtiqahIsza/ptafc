<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BusDriverController;
use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\BusDriver;
use App\Models\BusStand;
use App\Models\Company;
use App\Models\DriverWalletRecord;
use App\Models\PDAProfile;
use App\Models\Route;
use App\Models\RouteMap;
use App\Models\RouteSchedulerDetail;
use App\Models\RouteSchedulerMSTR;
use App\Models\Stage;
use App\Models\StageFare;
use App\Models\StageMap;
use App\Models\TicketSalesTransaction;
use App\Models\TripDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
// use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Response;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\Output;

class ApiController extends Controller
{
    //DriveLogin
    public function Auth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_password' => 'required',
            'driver_number' => 'required',
            'remember_token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->messages()->first(),
            ]);
        }

        $data = BusDriver::where('driver_number', $request->driver_number)->where('driver_role', 1)->first();

        if ($data) {
            $match = Hash::check($request->driver_password, $data->driver_password);

            if ($match) {
                return response()->json([
                    'success' => true,
                    'auth' => $data,
                ]);
            }
            else {
                return response()->json([
                    'success' => false,
                    'auth' => 'Wrong password, please try again',
                ]);
            }
        }
        else {
            return response()->json([
                'success' => false,
                'auth' => 'Invalid credentials, please try again',
            ]);
        }
    }

    public function getCompanyList()
    {
        $companyList = Company::all();

        return response()->json([
            'success' => true,
            'companyList' => $companyList,
        ]);
    }

    public function getBusByCompany(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->messages()->first(),
            ]);
        }

        $data = Bus::where('company_id', $request->company_id)->get();

        if($data){
            return response()->json([
                'success' => true,
                'busByCompany' => $data,
            ]);
        }
        else{
            return response()->json([
                'success' => false,
                'data' => 'No data',
            ]);
        }
    }

    public function getRouteByCompany(Request $request){
        $validator = Validator::make($request->all(), [
            'company_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->messages()->first(),
            ]);
        }

        $data = Route::where('company_id', $request->company_id)->get();

        if($data){
            return response()->json([
                'success' => true,
                'routeByCompany' => $data,
            ]);
        }
        else{
            return response()->json([
                'success' => false,
                'data' => 'No data',
            ]);
        }
    }

    public function getStageByCompany(Request $request){
        $validator = Validator::make($request->all(), [
            'company_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->messages()->first(),
            ]);
        }
        $routes = Route::select('id')->where('company_id', $request->company_id)->get();

        $stagePerCompany = collect();

        foreach($routes as $route) {
            $stages = Stage::where('route_id', $route->id)->get();

            if($stages){
                foreach ($stages as $stage){
                    $data = $stage;
                    $stagePerCompany->add($data);
                }
            }
            else{
                return response()->json([
                    'success' => false,
                    'data' => 'No stage data',
                ]);
            }
        }
        return response()->json([
                'success' => true,
                'data' => $stagePerCompany,
            ]);
    }

    public function getRouteMapByCompany(Request $request){
        $validator = Validator::make($request->all(), [
            'company_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->messages()->first(),
            ]);
        }
        $routes = Route::select('id')->where('company_id', $request->company_id)->get();

        //$routeMaps = RouteMap::where('company_id', $request->company_id)->get();

        $routeMapPerCompany = collect();
        $merged_collection = collect();

        foreach($routes as $route)
        {
            $routeMap = RouteMap::where('route_id', $route->id)->get();
            if($routeMap){
                $data = $routeMap;
                $routeMapPerCompany->add($data);
            }
            else{
                $data = 'No Route Map';
                $routeMapPerCompany->add($data);
            }
        }
        $collapsed = $routeMapPerCompany->collapse();

        return response()->json([
            'success' => true,
            'routeMapByCompany' => $collapsed,
            ]);
    }

    public function getStageMapByCompany(Request $request){
        $validator = Validator::make($request->all(), [
            'company_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->messages()->first(),
            ]);
        }
        $routes = Route::select('id')->where('company_id', $request->company_id)->get();

        $stageMapPerCompany = collect();

        foreach($routes as $route)
        {
            $stages = Stage::select('id')->where('route_id', $route->id)->get();
            foreach ($stages as $stage){
                $stageMap = StageMap::where('stage_id', $stage->id)->get();
                if($stageMap){
                    $data = $stageMap;
                    $stageMapPerCompany->add($data);
                }
                else{
                    $data = 'No Stage Map';
                    $stageMapPerCompany->add($data);
                }
            }

        }
        $collapsed = $stageMapPerCompany->collapse();

        return response()->json([
            'success' => true,
            'stageMapByCompany' =>  $collapsed,
        ]);
    }

    public function getStageFareByCompany(Request $request){
        $validator = Validator::make($request->all(), [
            'company_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->messages()->first(),
            ]);
        }

        $routes = Route::select('id')->where('company_id', $request->company_id)->get();

        $stageFarePerCompany = collect();

        foreach($routes as $route)
        {
            $fares = StageFare::where('route_id', $route->id)->get()->toArray();
            if($fares) {
                $data = $fares;
                $stageFarePerCompany->add($data);
            }
            else {
                $data = 'No Stage Fare';
                $stageFarePerCompany->add($data);
            }
        }

        $collapsed = $stageFarePerCompany->collapse();

        return response()->json([
            'success' => true,
            'stageFareByCompany' => $collapsed,
        ]);
    }

    public function getRouteScheduleByCompany(Request $request){
        $validator = Validator::make($request->all(), [
            'company_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->messages()->first(),
            ]);
        }

        $routePerCompany = Route::where('company_id', $request->company_id)->get();

        $routeScheduleCollection = collect();

        foreach($routePerCompany as $route) {
            $routeSchedules = RouteSchedulerMSTR::where('route_id', $route->id)->get();

            if($routeSchedules) {
                $data = $routeSchedules;
                $routeScheduleCollection->add($data);
            }
            else {
                $data = 'No Stage Fare';
                $routeScheduleCollection->add($data);
            }
        }
        $collapsed = $routeScheduleCollection->collapse();

        return response()->json([
            'success' => true,
            'routeScheduleByCompany' => $collapsed,
        ]);
    }

    /*public function saveTripDetails(Request $request){
        $validator = Validator::make($request->all(), [
//            'end_trip' => 'required',
//             'first_ticket_number' => 'required',
//             'last_ticket_number' => 'required',
//             'number_of_pass' => 'required',
//             'number_of_ticker' => 'required',
            'start_trip' => 'required',
//            'total_collection' => 'required',
//            'trip_number' => 'required',
            'bus_id' => 'required',
            'pda_id' => 'required',
            'route_id' => 'required',
            'route_schedule_mstr_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->messages()->first(),
            ]);
        }

        //Check pda_id exist in db
        $existedPDA = PDAProfile::where('id',$validator['pda_id'])->first();
        if($existedPDA){
            //Check route_schedule_mstr_id exist in db
            $existedSchedule = RouteSchedulerMSTR::where('id',$validator['route_schedule_mstr_id'])->first();
            if($existedSchedule){
                //Check route_id exist in db
                $existedRoute = Route::where('id', $validator['route_id'])->first();
                if($existedRoute){
                    //Compare $existedRoute id with route_id in $existedSchedule
                    if($validator['route_id'] == $existedSchedule->route_id){
                        //Check bus_id exist in db
                        $existedBus = Bus::where('id', $validator['bus_id'])->first();
                        if($existedBus){
                            //Check inbound bus
                            if($existedSchedule->inbound_bus_id == $existedBus->id){
                                $validator['trip_code'] = "IB";
                            }
                            //Check outbound bus
                            if($existedSchedule->outbound_bus_id == $existedBus->id){
                                $validator['trip_code'] = "OB";
                            }
                            $new = new TripDetail();
                            $new->create($validator);
                            return response()->json([
                                'success' => true,
                                'data' => $new,
                            ]);
                        }else{
                            return response()->json([
                                'success' => false,
                                'data' => "Bus ID is not exist in database",
                            ]);
                        }
                    }else{
                        return response()->json([
                            'success' => false,
                            'data' => "Wrong route specified in schedule",
                        ]);
                    }
                }else{
                    return response()->json([
                        'success' => false,
                        'data' => "Route ID is not exist in database",
                    ]);
                }
            }else{
                return response()->json([
                    'success' => false,
                    'data' => "Schedule ID is not exist in database",
                ]);
            }
        }
        return response()->json([
                'success' => false,
                'data' => "PDA ID is not exist in database",
        ]);
    }*/

    /*public function saveTripDetails(Request $request){
        $successSave = false;

        $validator = Validator::make($request->all(), [
            'trip' => 'required',
            'tickets' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->messages()->first(),
            ]);
        }

        $trips = json_decode($request->trip);
        $tickets = json_decode($request->tickets);

        if (count($trips) > 0) {
            for ($i = 0; $i < count($trips); $i++) {
                //Check trip_id exist in db
                $existedTrip = TripDetail::where('id', $trips[$i]->id)->first();
                if (!$existedTrip) {
                    //Check pda_id exist in db
                    $existedPDA = PDAProfile::where('id', $trips[$i]->pda_id)->first();
                    if ($existedPDA) {
                        //Check route_schedule_mstr_id exist in db
                        $existedSchedule = RouteSchedulerMSTR::where('id', $trips[$i]->route_schedule_mstr_id)->first();
                        if ($existedSchedule) {
                            //Check driver exist in db
                            $existedDriver = BusDriver::where('id', $trips[$i]->driver_id)->first();
                            if ($existedDriver) {
                                //Check route_id exist in db
                                $existedRoute = Route::where('id', $trips[$i]->route_id)->first();
                                if ($existedRoute) {
                                    //Compare $existedRoute id with route_id in $existedSchedule
                                    if ($validator['route_id'] == $existedSchedule->route_id) {
                                        //Check bus_id exist in db
                                        $existedBus = Bus::where('id', $trips[$i]->bus_id)->first();
                                        if ($existedBus) {
                                            $newTrip = new TripDetail();
                                            //Check inbound bus
                                            if ($existedSchedule->inbound_bus_id == $existedBus->id) {
                                                $newTrip->trip_code = "IB";
                                            }
                                            //Check outbound bus
                                            if ($existedSchedule->outbound_bus_id == $existedBus->id) {
                                                $newTrip->trip_code = "OB";
                                            }
                                            $newTrip->end_trip = $trips[$i]->end_trip;
                                            $newTrip->start_trip = $trips[$i]->start_trip;
                                            $newTrip->bus_id = $trips[$i]->bus_id;
                                            $newTrip->pda_id = $trips[$i]->pda_id;
                                            $newTrip->route_id = $trips[$i]->route_id;
                                            $newTrip->driver_id = $trips[$i]->driver_id;
                                            $newTrip->route_schedule_mstr_id = $trips[$i]->route_schedule_mstr_id;
                                            $successSave = $newTrip->save();
                                            if($successSave){
                                                $this->saveTicketSales($request->tickets);
                                            }
                                        } else {
                                            return response()->json([
                                                'success' => false,
                                                'data' => "Bus ID is not exist in database",
                                            ]);
                                        }
                                    } else {
                                        return response()->json([
                                            'success' => false,
                                            'data' => "Wrong route specified in schedule",
                                        ]);
                                    }
                                } else {
                                    return response()->json([
                                        'success' => false,
                                        'data' => "Route ID is not exist in database",
                                    ]);
                                }
                            } else {
                                return response()->json([
                                    'success' => false,
                                    'data' => "Bus Driver ID is not exist in database",
                                ]);
                            }
                        } else {
                            return response()->json([
                                'success' => false,
                                'data' => "Schedule ID is not exist in database",
                            ]);
                        }
                    }else {
                        return response()->json([
                            'success' => false,
                            'data' => "PDA ID is not exist in database",
                        ]);
                    }
                }else {
                    return response()->json([
                        'success' => false,
                        'data' => "Trip ID is already exist in database",
                    ]);
                }
            }
        }

        return response()->json([
            'success' => false,
            'data' => "Failed to save into the database",
        ]);
    }

    public function saveTicketSales(Request $request){
        $walletDeduct = 0.0;
        $tickets = json_decode($request->tickets);

        if (count($tickets) > 0) {
            for ($i = 0; $i < count($tickets); $i++) {
                //Check pda_id exist in db
                $existedPDA = PDAProfile::where('id', $tickets[$i]->pda_id)->first();
                if($existedPDA){
                    //check bus stand_in_id
                    $existedBusStandIn = BusStand::where('id', $tickets[$i]->bus_stand_out_id)->first();
                    if($existedBusStandIn){
                        //check bus stand_out_id
                        $existedBusStandOut = BusStand::where('id', $tickets[$i]->bus_stand_out_id)->first();
                        if($existedBusStandOut){
                            //check route_id
                            $existedRoute = Route::where('id', $tickets[$i]->route_id)->first();
                            if($existedRoute) {
                                //check bus_driver_id
                                $existedBus = BusDriver::where('id', $tickets[$i]->bus_driver_id)->first();
                                if ($existedBus) {
                                    //check fromstage_stage_id
                                    $existedStageFrom = Stage::where('id', $tickets[$i]->fromstage_stage_id)->first();
                                    if ($existedStageFrom) {
                                        //check tostage_stage_id
                                        $existedStageTo = Stage::where('id', $tickets[$i]->tostage_stage_id)->first();
                                        if ($existedStageTo) {
                                            //check trip_id
                                            $existedTrip = TripDetail::where('id', $tickets[$i]->trip_id)->first();
                                            if ($existedTrip) {
                                                $newTicket = new TicketSalesTransaction();
                                                $newTicket->amount = $tickets[$i]->amount ;
                                                $newTicket->fare_type = $tickets[$i]->fare_type ;
                                                $newTicket->pda_transaction_id = $tickets[$i]->pda_transaction_id;
                                                $newTicket->sales_date = $tickets[$i]->sales_date;
                                                $newTicket->upload_date = $tickets[$i]->upload_date;
                                                $newTicket->bus_id = $tickets[$i]->bus_id;
                                                $newTicket->bus_driver_id = $tickets[$i]->bus_driver_id ;
                                                $newTicket->fromstage_stage_id = $tickets[$i]->fromstage_stage_id;
                                                $newTicket->route_id = $tickets[$i]->route_id;
                                                $newTicket->sector_id = $tickets[$i]->sector_id ;
                                                $newTicket->tostage_stage_id = $tickets[$i]->tostage_stage_id;
                                                $newTicket->ticket_number = $tickets[$i]->ticket_number;
                                                $newTicket->actual_amount = $tickets[$i]->actual_amount;
                                                $newTicket->bus_stand_in_id = $tickets[$i]->bus_stand_in_id;
                                                $newTicket->bus_stand_out_id = $tickets[$i]->bus_stand_out_id;
                                                $newTicket->passenger_type = $tickets[$i]->passenger_type;
                                                $successSave = $newTicket->save();

                                                if($successSave) {
                                                    if ($tickets[$i]->fare_type == 1){
                                                        $walletDeduct += $tickets[$i]->actual_amount;
                                                    }
                                                }

                                            }else {
                                                return response()->json([
                                                    'success' => false,
                                                    'data' => "Trip ID is not exist in database",
                                                ]);
                                            }
                                        }else {
                                            return response()->json([
                                                'success' => false,
                                                'data' => "To Stage ID is not exist in database",
                                            ]);
                                        }
                                    }else {
                                        return response()->json([
                                            'success' => false,
                                            'data' => "From Stage ID is not exist in database",
                                        ]);
                                    }
                                }else {
                                    return response()->json([
                                        'success' => false,
                                        'data' => "Bus ID is not exist in database",
                                    ]);
                                }
                            }else {
                                return response()->json([
                                    'success' => false,
                                    'data' => "Route ID is not exist in database",
                                ]);
                            }
                        }else {
                            return response()->json([
                                'success' => false,
                                'data' => "Bus Stand Out ID is not exist in database",
                            ]);
                        }
                    }else {
                        return response()->json([
                            'success' => false,
                            'data' => "Bus Stand In ID is not exist in database",
                        ]);
                    }
                }else {
                    return response()->json([
                        'success' => false,
                        'data' => "PDA ID is not exist in database",
                    ]);
                }
            }
            $driverWallet = new DriverWalletRecord();
            $driverWallet->driver_id = $
            $driverWallet = DriverWalletRecord::

        }
        return response()->json([
            'success' => false,
            'data' => "Failed to save into the database",
        ]);
    }*/

    public function checkPDA(Request $request){
        $validator = Validator::make($request->all(), [
            'imei' => 'required',
            'company_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'pda_key' => $validator->messages()->first(),
            ]);
        }

        $checkCompany = Company::find($request->company_id);
        if($checkCompany){
            $checkPDA= PDAProfile::where('imei', $request->imei)->first();
            if($checkPDA){
                $token = Str::random(60);

                $checkPDA->pda_key = $token;
                $checkPDA->save();

                $getID = PDAProfile::select('id')->where('pda_key', $token)->first();

                return response()->json([
                    'success' => true,
                    'pda_key' => $token,
                    'pda_id' => $getID->id,
                ]);
            }else{
                return response()->json([
                    'success' => false,
                    'pda_key' => 'PDA not exist in database',
                    'pda_id' => false,
                ]);
            }
        }else{
            return response()->json([
                'success' => false,
                'pda_key' => 'Company not exist in database',
                'pda_id' => false,
            ]);
        }
    }

    public function saveTicketSalesTransaction(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'fare_type' => 'required',
            'passenger_type' => 'required',
            'sales_date' => 'required',
            'upload_date' => 'required',
            'trip_number' => 'required',
            'ticket_number' => 'required',
            'bus_id' => 'required',
            'bus_driver_id' => 'required',
            'fromstage_stage_id' => 'required',
            'tostage_stage_id' => 'required',
            'route_id' => 'required',
            'sector_id' => 'required',
            'pda_id' => 'required',
            'pda_transaction_id' => 'required',
            'trip_id' => 'required',
            'bus_stand_id' => 'required',
            'actual_amount' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->messages()->first(),
            ]);
        }

        $new = new TicketSalesTransaction();
        $new->create($validator);

        return response()->json([
            'success' => true,
            'saved' => $new,
        ]);
    }

    public function saveDriverWallet(Request $request){
        $validator = Validator::make($request->all(), [
            'value' => 'required',
            'driver_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->messages()->first(),
            ]);
        }

        $findDriver = BusDriver::where('id', $validator['driver_id'])->first();

        if($findDriver){
            $findDriver->update(['wallet_balance' => $validator['value']]);
        }
        else{
            return response()->json([
                'success' => false,
                'message' => "Given ID is not existed in the database"
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully updated the wallet balance!"
        ]);
    }


}
