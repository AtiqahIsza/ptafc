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
        'schedule_start_time',
        'schedule_end_time',
        'route_id',
        'inbound_bus_id',
        'outbound_bus_id',
        'inbound_distance',
        'outbound_distance',
        'status',
        'trip_type',
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
