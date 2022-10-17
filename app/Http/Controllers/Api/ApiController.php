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
use App\Models\VehiclePosition;
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

        $data = BusDriver::where('driver_number', $request->driver_number)
        ->where('status', 1)
        ->where('driver_role', 1)
        ->first();

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

    public function getDriverByCompany(Request $request)
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

       $data = BusDriver::where('company_id', $request->company_id)->where('status',1)->get();

        if($data){
            return response()->json([
                'success' => true,
                'driverByCompany' => $data,
            ]);
        }
        else{
            return response()->json([
                'success' => false,
                'data' => 'No data',
            ]);
        }
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

        $data = Bus::where('company_id', $request->company_id)->where('status', 1)->get();

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

        $data = Route::where('company_id', $request->company_id)->where('status', 1)->get();

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

        $routePerCompany = Route::where('company_id', $request->company_id)->where('status', 1)->get();

        $routeScheduleCollection = collect();

        foreach($routePerCompany as $route) {
            $routeSchedules = RouteSchedulerMSTR::where('route_id', $route->id)
            ->where('status',1)->get();

            if($routeSchedules) {
                $data = $routeSchedules;
                $routeScheduleCollection->add($data);
            }
            else {
                $data = 'No Schedule';
                $routeScheduleCollection->add($data);
            }
        }
        $collapsed = $routeScheduleCollection->collapse();

        return response()->json([
            'success' => true,
            'routeScheduleByCompany' => $collapsed,
        ]);
    }

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
            $checkPDA= PDAProfile::where('imei', $request->imei)->where('status', 1)->first();
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

    public function saveVehiclePosition(Request $request){
        $validator = Validator::make($request->all(), [
            'pda_imei' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'altitude' => 'required',
            'date_time' => 'required',
            'speed' => 'required',
            'satellite_count' => 'required',
            'hdop' => 'required',
            'd2d3' => 'required',
            'rssi' => 'required',
            'cell_id' => 'required',
            'mcc' => 'required',
            'msg_id' => 'required',
            'activity_id' => 'required',
            'addon_json' => 'required',
            'bus_id' => 'required',
            'driver_id' => 'required',
            'trip_id' => 'required',
            'phms_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->messages()->first(),
            ]);
        }

        $checkBus = Bus::where('id', $request->bus_id)->first();
        if(empty($checkBus)) {
            return response()->json([
                'success' => false,
                'message' => "Bus ID is not existed in the database"
            ]);
        }

        $checkDriver = BusDriver::where('id', $request->driver_id)->first();
        if(empty($checkDriver)){
            return response()->json([
                'success' => false,
                'message' => "Driver ID is not existed in the database"
            ]);
        }
       
        $new = new VehiclePosition();
        // $new->pda_imei = $request->pda_imei;
        // $new->latitude = $request->latitude;
        // $new->longitude = $request->longitude;
        // $new->altitude = $request->altitude;
        // $new->date_time = $request->date_time;
        // $new->speed = $request->speed;
        // $new->satellite_count = $request->satellite_count;
        // $new->hdop = $request->hdop;
        // $new->d2d3 = $request->d2d3;
        // $new->rssi = $request->rssi;
        // $new->cell_id = $request->cell_id;
        // $new->mcc = $request->mcc;
        // $new->msg_id = $request->msg_id;
        // $new->activity_id = $request->activity_id;
        // $new->addon_json = $request->addon_jsond;
        // $new->bus_id = $request->bus_id;
        // $new->driver_id = $request->driver_id;
        // $new->trip_id = $request->trip_id;
        // $new->phms_id = $request->phms_id;
       
        $success = $new->create($request->all());
        if($success){
            return response()->json([
                'success' => true,
                'message' => "Successfully added the vechicle position!"
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => "Failed to add the vechicle position!"
        ]);
    }

    public function updatePDA(Request $request){
        $validator = Validator::make($request->all(), [
            'imei' => 'required',
            'app_version' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->messages()->first(),
            ]);
        }

        $checkPDA= PDAProfile::where('imei', $request->imei)->where('status', 1)->first();
        if($checkPDA){
            $checkPDA->app_version = $request->app_version;
            $checkPDA->save();

            return response()->json([
                'success' => true,
                'message' => 'Successfully updated app_version'
            ]);
        }else{
            return response()->json([
                'success' => true,
                'message' => 'Failed to update app_version'
            ]);
        }
    }

    /*public function saveTicketSalesTransaction(Request $request){
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
    }*/
}
