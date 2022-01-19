<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartialSalesTransaction extends Model
{
    use HasFactory;

    protected $table = 'partial_sales_transaction';

    function Bus() {
        return $this->belongsTo(Bus::class, 'bus_id', 'id');
    }

    function ParentSales(){
        return $this->belongsTo(PartialSalesTransaction::class, 'parent_transaction_id', 'id');
    }

    function TripDetail() {
        return $this->belongsTo(TripDetail::class, 'trip_id', 'id');
    }

    function PDAProfile() {
        return $this->belongsTo(PDAProfile::class, 'pda_id', 'id');
    }

    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

    function BusDriver() {
        return $this->belongsTo(BusDriver::class, 'driver_id', 'id');
    }

    function Sector() {
        return $this->belongsTo(Sector::class, 'sector_id', 'id');
    }

    function Stage() {
        return $this->belongsTo(Stage::class, 'stage_id', 'id');
    }
}
