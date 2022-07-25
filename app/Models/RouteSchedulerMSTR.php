<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteSchedulerMSTR extends Model
{
    use HasFactory;
    protected $table = 'route_scheduler_mstr';
    public $timestamps = false;

    /** trip_type
     * 1 - weekday
     * 2 - weekend
     * 3 - allday
     * 4 - alldayExceptFriday
     * 5 - alldayExceptSunday
     * 6 - MONDAY to THURSDAY
     * 7 - Friday only
     * 8 - Saturday only
     * 9 - All Day Except Friday & Sunday
     * 10 - All Day (Except Friday and Saturday)
     * 11 - SUNDAY only
     * 
     * trip_code
     * 1 - inbound
     * 0 - outbound
     * 
     * status
     * 1 - ENABLE
     * 2 - DISABLE
     */
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
        'trip_code',
        'bus_id',
    ];

    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

    function Bus() {
        return $this->belongsTo(Bus::class, 'bus_id', 'id');
    }
}
