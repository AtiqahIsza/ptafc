<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusSchedulerHistory extends Model
{
    use HasFactory;

    protected $table = 'bus_scheduler_history';

    function Bus1() {
        return $this->belongsTo(Bus::class, 'bus1_id', 'id');
    }
    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }
    function Bus2() {
        return $this->belongsTo(Bus::class, 'bus2_id', 'id');
    }
}
