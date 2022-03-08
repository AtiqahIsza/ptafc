<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BusDriverController;
use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\BusDriver;
use App\Models\Company;
use App\Models\Route;
use App\Models\RouteMap;
use App\Models\RouteSchedulerMSTR;
use App\Models\Stage;
use App\Models\StageFare;
use App\Models\StageMap;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
// use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
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
            //'remember_token' => 'required',
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
                    $data['stage'] = $stage;
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

        $routeMapPerCompany = collect();

        foreach($routes as $route)
        {
            $routeMap = RouteMap::where('route_id', $route->id)->get();
            if($routeMap){
                $data['route_id'] = $route->id;
                $data['route_map'] = $routeMap;
                $routeMapPerCompany->add($data);
            }
            else{
                $data['route_id'] = $route->id;
                $data['route_map'] = 'No Route Map';
                $routeMapPerCompany->add($data);
            }
        }

        return response()->json([
            'success' => true,
            'routeMapByCompany' => $routeMapPerCompany,
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
                    $data['stage_id'] = $stage->id;
                    $data['stage_map'] = $stageMap;
                    $stageMapPerCompany->add($data);
                }
                else{
                    $data['stage_id'] = $stage->id;
                    $data['stage_map'] = 'No Stage Map';
                    $stageMapPerCompany->add($data);
                }
            }

        }
        return response()->json([
            'success' => true,
            'stageMapByCompany' => $stageMapPerCompany,
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
                $data['route_id'] = $route->id;
                $data['stage_fare'] = $fares;
                $stageFarePerCompany->add($data);
            }
            else {
                $data['route_id'] = $route->id;
                $data['stage_fare'] = 'No Stage Fare';
                $stageFarePerCompany->add($data);
            }
        }

        return response()->json([
            'success' => true,
            'stageFareByCompany' => $stageFarePerCompany,
        ]);
    }
}
