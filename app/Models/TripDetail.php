<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripDetail extends Model
{
    use HasFactory;

    protected $table = 'trip_details';

    public $timestamps = false;

    //trip_code(out = 0/inbound=1)
    protected $fillable = [
        'id',
        'start_trip',
        'end_trip',
        'route_schedule_mstr_id',
        'bus_id',
        'route_id',
        'total_adult',
        'total_concession',
        'total_adult_amount',
        'total_concession_amount',
        'total_mileage',
        'trip_code',
    ];

    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
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
    function RouteScheduleMSTR() {
        return $this->belongsTo(RouteSchedulerMSTR::class, 'route_schedule_mstr_id', 'id');
    }

}
