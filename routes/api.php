<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\DataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('auth',[ApiController::class, 'auth']);
Route::post('getCompanyList',[ApiController::class, 'getCompanyList']);
Route::post('getBusByCompany',[ApiController::class, 'getBusByCompany']);
Route::post('getRouteByCompany',[ApiController::class, 'getRouteByCompany']);
Route::post('getStageByCompany',[ApiController::class, 'getStageByCompany']);
Route::post('getRouteMapByCompany',[ApiController::class, 'getRouteMapByCompany']);
Route::post('getStageMapByCompany',[ApiController::class, 'getStageMapByCompany']);
Route::post('getStageFareByCompany',[ApiController::class, 'getStageFareByCompany']);
Route::post('getRouteScheduleByCompany',[ApiController::class, 'getRouteScheduleByCompany']);
Route::post('checkPDA',[ApiController::class, 'checkPDA']);

//save to db
Route::post('saveDriverWallet',[ApiController::class, 'saveDriverWallet']);
Route::post('saveTicketSalesTransaction',[ApiController::class, 'saveTicketSalesTransaction']);

//load 3 file data
Route::post('loadTripData', [DataController::class, 'loadTripData']);
Route::post('loadTicketSalesData', [DataController::class, 'loadTicketSalesData']);
Route::post('loadGPSHistoryData', [DataController::class, 'loadGPSHistoryData']);
Route::post('loadVehiclePositionData', [DataController::class, 'loadVehiclePositionData']);
