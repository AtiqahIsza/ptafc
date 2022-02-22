<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteSchedulerMSTR extends Model
{
    use HasFactory;
    protected $table = 'route_scheduler_mstr';
    public $timestamps = false;

    protected $fillable = [
        'schedule_time',
        'route_id',
        'inbound_distance',
        'outbound_distance',
        'inbound_bus_id',
        'outbound_bus_id',
        'status',
        'trip_type'
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
