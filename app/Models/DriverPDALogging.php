<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverPDALogging extends Model
{
    use HasFactory;

    protected $table = 'driver_pda_logging';

    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

    function Sector() {
        return $this->belongsTo(Sector::class, 'sector_id', 'id');
    }

    function Bus() {
        return $this->belongsTo(Bus::class, 'bus_id', 'id');
    }

    function BusDriver() {
        return $this->belongsTo(BusDriver::class, 'driver_id', 'id');
    }

    function PDAProfile() {
        return $this->belongsTo(PDAProfile::class, 'pda_id', 'id');
    }
}
