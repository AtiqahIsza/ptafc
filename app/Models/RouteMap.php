<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteMap extends Model
{
    use HasFactory;

    protected $table = 'route_scheduler_details';

    function Route() {
        return $this->belongsTo(Route::class, 'route_route_id', 'route_id');
    }
}