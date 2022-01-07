<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartialSalesTransaction extends Model
{
    use HasFactory;

    protected $table = 'partial_sales_transaction';

    function Bus() {
        return $this->belongsTo(Bus::class, 'bus_bus_id', 'bus_id');
    }

    function ParentSales(){
        return $this->belongsTo(PartialSalesTransaction::class, 'parent_transaction_id', 'transaction_id');
    }

    function TripDetail() {
        return $this->belongsTo(TripDetail::class, 'trip_trip_id', 'trip_id');
    }

    function PDAProfile() {
        return $this->belongsTo(PDAProfile::class, 'pda_pda_id', 'pda_id');
    }

    function Route() {
        return $this->belongsTo(Route::class, 'route_route_id', 'route_id');
    }

    function BusDriver() {
        return $this->belongsTo(BusDriver::class, 'driver_driver_id', 'driver_id');
    }

    function Sector() {
        return $this->belongsTo(Sector::class, 'sector_sector_id', 'sector_id');
    }

    function Stage() {
        return $this->belongsTo(Stage::class, 'stage_stage_id', 'stage_id');
    }
}
