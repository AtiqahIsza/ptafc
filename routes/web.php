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
});

//Card
Route::get('/cards/manage', [\App\Http\Controllers\TicketCardController::class, 'manageCards'])->name('manageCards');
Route::get('/cards/view', [\App\Http\Controllers\TicketCardController::class, 'index'])->name('viewCards');
Route::get('/cards/managevoucher', [\App\Http\Controllers\TicketCardController::class, 'manageVouchers'])->name('manageVouchers');

//Setting
Route::get('/settings/managecompany', [\App\Http\Controllers\CompanyController::class, 'index'])->name('manageCompany');
Route::get('/settings/managesector', [\App\Http\Controllers\SectorController::class, 'index'])->name('manageSector');
Route::get('/settings/managebus', [\App\Http\Controllers\BusController::class, 'index'])->name('manageBus');
Route::get('/settings/manageroute', [\App\Http\Controllers\RouteController::class, 'index'])->name('manageRoute');
Route::get('/settings/managebusdriver', [\App\Http\Controllers\BusDriverController::class, 'index'])->name('manageBusDriver');
Route::get('/settings/managestage', [\App\Http\Controllers\StageController::class, 'index'])->name('manageStage');
Route::get('/settings/managebusstand', [\App\Http\Controllers\BusStandController::class, 'index'])->name('manageBusStand');
Route::get('/settings/managestagefare', [\App\Http\Controllers\StageFareController::class, 'index'])->name('manageStageFare');

//PowerGrid
Route::get('/settings/{id}/edit', [\App\Http\Controllers\UserController::class, 'edit'])->name('user.edit');

//StageFare
Route::post('/settings/updatestagefare', [\App\Http\Controllers\StageFareController::class, 'update'])->name('updateStageFare');


/*//Authentication (Login & Register)
Route::get('login', [AuthController::class, 'index'])->name('login');
Route::post('post-login', [AuthController::class, 'postLogin'])->name('login.post');
Route::get('registration', [AuthController::class, 'registration'])->name('register');
Route::post('post-registration', [AuthController::class, 'postRegistration'])->name('register.post');
//Route::get('dashboard', [AuthController::class, 'dashboard']);
Route::get('logout', [AuthController::class, 'logout'])->name('logout');*/
