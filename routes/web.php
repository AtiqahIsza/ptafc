<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware('auth')->group(function () {
    Route::view('about', 'about')->name('about');

    Route::get('users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');

    Route::get('profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    //Wallet
    Route::get('/wallet/view', [\App\Http\Controllers\DriverWalletRecordController::class, 'index'])->name('viewTransaction');
    Route::get('/wallet/topup', [\App\Http\Controllers\DriverWalletRecordController::class, 'topup'])->name('topupWallet');
    Route::get('/settings/manageBusDriver/wallet/{id}', [\App\Http\Controllers\DriverWalletRecordController::class, 'show'])->name('viewWalletTransaction');

    //Vehicle Position
    Route::get('/history', [App\Http\Controllers\VehiclePositionController::class, 'index'])->name('vehicleHistory');
    Route::post('/history/view', [\App\Http\Controllers\VehiclePositionController::class, 'show'])->name('viewGPS');
    Route::get('/realtime', [\App\Http\Controllers\VehiclePositionController::class, 'showRealtime'])->name('vehicleRealtime');
    Route::get('/summary', [\App\Http\Controllers\VehiclePositionController::class, 'viewSummary'])->name('vehicleSummary');
    //Route::get('/realtime/view/{id}', [\App\Http\Controllers\VehiclePositionController::class, 'viewRealtime'])->name('viewRealtime');

    //Card
    Route::get('/cards/manage', [\App\Http\Controllers\TicketCardController::class, 'manageCards'])->name('manageCards');
    Route::get('/cards/view', [\App\Http\Controllers\TicketCardController::class, 'index'])->name('viewCards');
    Route::get('/cards/manageVoucher', [\App\Http\Controllers\TicketCardController::class, 'manageVouchers'])->name('manageVouchers');

    //Setting
    Route::get('/settings/manageCompany', [\App\Http\Controllers\CompanyController::class, 'index'])->name('manageCompany');
    Route::get('/settings/manageSector', [\App\Http\Controllers\SectorController::class, 'index'])->name('manageSector');
    Route::get('/settings/manageBus', [\App\Http\Controllers\BusController::class, 'index'])->name('manageBus');
    Route::get('/settings/manageRoute', [\App\Http\Controllers\RouteController::class, 'index'])->name('manageRoute');
    Route::get('/settings/manageBusDriver', [\App\Http\Controllers\BusDriverController::class, 'index'])->name('manageBusDriver');
    Route::get('/settings/manageStage', [\App\Http\Controllers\StageController::class, 'index'])->name('manageStage');
    Route::get('/settings/manageBusStand', [\App\Http\Controllers\BusStandController::class, 'index'])->name('manageBusStand');
    Route::get('/settings/manageStageFare', [\App\Http\Controllers\StageFareController::class, 'index'])->name('manageStageFare');
    Route::get('/settings/manageScheduler', [\App\Http\Controllers\RouteSchedulerDetailController::class, 'index'])->name('manageScheduler');
    Route::get('/settings/managePDA', [\App\Http\Controllers\PDAProfileController::class, 'index'])->name('managePDA');

    //Report
    Route::get('/report/salesByBus', [\App\Http\Controllers\ReportController::class, 'viewSalesByBus'])->name('viewSalesByBus');
    Route::get('/report/salesByRoute', [\App\Http\Controllers\ReportController::class, 'viewSalesByRoute'])->name('viewSalesByRoute');
    Route::get('/report/salesByDriver', [\App\Http\Controllers\ReportController::class, 'viewSalesByDriver'])->name('viewSalesByDriver');
    Route::get('/report/collectionCompany', [\App\Http\Controllers\ReportController::class, 'viewCollectionByCompany'])->name('viewCollectionByCompany');
    Route::get('/report/monthlySummary', [\App\Http\Controllers\ReportController::class, 'viewMonthlySummary'])->name('viewMonthlySummary');
    Route::get('/report/dailySummary', [\App\Http\Controllers\ReportController::class, 'viewDailySummary'])->name('viewDailySummary');
    Route::get('/report/spad', [\App\Http\Controllers\ReportController::class, 'viewReportSPAD'])->name('viewReportSPAD');
    Route::get('/report/averageSummary', [\App\Http\Controllers\ReportController::class, 'viewAverageSummary'])->name('viewAverageSummary');

    //Report Claim Details GPS
    Route::get('/report/claimDetails/{dateFrom}/{dateTo}/{routeID}/{companyID}', [\App\Http\Controllers\ReportController::class, 'viewClaimDetails'])->name('viewClaimDetails');
    Route::get('/report/claimDetailsGPS/{tripID}', [\App\Http\Controllers\ReportController::class, 'viewClaimDetailsGPS'])->name('viewClaimDetailsGPS');

    //PowerGrid
    //Route::get('/settings/{id}/edit', [\App\Http\Controllers\UserController::class, 'edit'])->name('user.edit');

    //StageFare
    Route::post('/settings/updateStageFare', [\App\Http\Controllers\StageFareController::class, 'update'])->name('updateStageFare');

    //Map
    Route::get('/settings/manageRouteMap/{id}/add', [\App\Http\Controllers\RouteMapController::class, 'index'])->name('addRouteMap');
    Route::post('/settings/manageRouteMap/store', [\App\Http\Controllers\RouteMapController::class, 'store'])->name('storeRouteMap');
    Route::get('/settings/manageRouteMap/{id}/view', [\App\Http\Controllers\RouteMapController::class, 'show'])->name('viewRouteMap');

    Route::get('/settings/manageStage/{id}/addMap', [\App\Http\Controllers\StageMapController::class, 'create'])->name('addStageMap');
    Route::post('/settings/manageStageMap/store', [\App\Http\Controllers\StageMapController::class, 'store'])->name('storeStageMap');
    Route::get('/settings/manageStageMap/{id}/view', [\App\Http\Controllers\StageMapController::class, 'show'])->name('viewStageMap');

    Route::get('/settings/manageBusStand/{id}/addMap', [\App\Http\Controllers\BusStandController::class, 'create'])->name('addBusStand');
    Route::post('/settings/manageBusStand/store', [\App\Http\Controllers\BusStandController::class, 'store'])->name('storeBusStand');
    Route::get('/settings/manageBusStand/{id}/view', [\App\Http\Controllers\BusStandController::class, 'show'])->name('viewBusStand');

    //Upload KML File
    Route::post('/settings/uploadFile', [\App\Http\Controllers\RouteMapController::class, 'uploadFile'])->name('uploadFile');

    //Dashboard
    Route::get('/home/getPassengerType', [\App\Http\Controllers\HomeController::class, 'getPassengerType'])->name('getPassengerType');
    Route::get('/home/getTotalSalesPerDay', [\App\Http\Controllers\HomeController::class, 'getTotalSalesPerDay'])->name('getTotalSalesPerDay');
    Route::get('/home/getTotalSalesPerMonth', [\App\Http\Controllers\HomeController::class, 'getTotalSalesPerMonth'])->name('getTotalSalesPerMonth');
    Route::get('/home/getCollectionByCompany', [\App\Http\Controllers\HomeController::class, 'getCollectionByCompany'])->name('getCollectionByCompany');
    Route::get('/home/getCollectionByCompanyBar', [\App\Http\Controllers\HomeController::class, 'getCollectionByCompanyBar'])->name('getCollectionByCompanyBar');
});
/*//Authentication (Login & Register)
Route::get('login', [AuthController::class, 'index'])->name('login');
Route::post('post-login', [AuthController::class, 'postLogin'])->name('login.post');
Route::get('registration', [AuthController::class, 'registration'])->name('register');
Route::post('post-registration', [AuthController::class, 'postRegistration'])->name('register.post');
//Route::get('dashboard', [AuthController::class, 'dashboard']);
Route::get('logout', [AuthController::class, 'logout'])->name('logout');*/
