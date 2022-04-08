<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteSchedulerMSTR extends Model
{
    use HasFactory;
    protected $table = 'route_scheduler_mstr';
    public $timestamps = false;

    //trip_code(out = 0/inbound=1)
    protected $fillable = [
        'id',
        'start_trip',
        'end_trip',
        'route_schedule_mstr_id',
        'bus_id',
        'route_id',
        'driver_id',
        'total_adult',
        'total_concession',
        'total_adult_amount',
        'total_concession_amount',
        'total_mileage',
        'trip_code'
    ];

    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

    function inbus() {
        return $this->belongsTo(Bus::class, 'inbound_bus_id', 'id');
    }

    function outbus() {
        return $this->belongsTo(Bus::class, 'outbound_bus_id', 'id');
    }
}
