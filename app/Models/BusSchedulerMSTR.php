<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusSchedulerMSTR extends Model
{
    use HasFactory;

    protected $table = 'bus_scheduler_mstr';

    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }
}
