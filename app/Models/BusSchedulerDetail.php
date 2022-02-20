<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusSchedulerDetail extends Model
{
    use HasFactory;
    protected $table = 'bus_scheduler_details';
    public $timestamps = false;
    protected $fillable = [
        'time',
        'bus1_id',
        'bus2_id',
        'route_id',
        'sequence',
    ];

    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }
}
