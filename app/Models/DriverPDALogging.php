<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverPDALogging extends Model
{
    use HasFactory;

    protected $table = 'driver_pda_logging';

    function Route() {
        return $this->belongsTo(Route::class, 'route_route_id', 'route_id');
    }

    function Sector() {
        return $this->belongsTo(Sector::class, 'sector_sector_id', 'sector_id');
    }

    function Bus() {
        return $this->belongsTo(Bus::class, 'bus_bus_id', 'bus_id');
    }

    function BusDriver() {
        return $this->belongsTo(BusDriver::class, 'driver_driver_id', 'driver_id');
    }

    function PDAProfile() {
        return $this->belongsTo(PDAProfile::class, 'pda_pda_id', 'pda_id');
    }
}
