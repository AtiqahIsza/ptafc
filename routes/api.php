<?php

use App\Http\Controllers\Api\ApiController;
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
