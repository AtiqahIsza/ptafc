<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusSchedulerHistory extends Model
{
    use HasFactory;

    protected $table = 'bus_scheduler_history';

    function Bus1() {
        return $this->belongsTo(Bus::class, 'bus1_bus_id', 'bus_id');
    }
    function Route() {
        return $this->belongsTo(Route::class, 'route_route_id', 'route_id');
    }
    function Bus2() {
        return $this->belongsTo(Bus::class, 'bus2_bus_id', 'bus_id');
    }
}
