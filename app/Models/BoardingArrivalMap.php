<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoardingArrivalMap extends Model
{
    use HasFactory;

    protected $table = 'boarding_arrival_map';

    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }
}
