<?php

namespace App\Http\Controllers;

use App\Models\BusDriver;
use App\Models\DriverWalletRecord;
use Illuminate\Http\Request;

class DriverWalletRecordController extends Controller
{
    public function index()
    {
        return view('wallet.index');
    }

    public function topup()
    {
        return view('wallet.topup');
    }

    public function show(Request $request)
    {
        $records = DriverWalletRecord::where('driver_id', $request->route('id'))->get();
        $drivers = BusDriver::where('id', $request->route('id'))->first();
        return view('wallet.view', compact('records','drivers'));
    }

}
