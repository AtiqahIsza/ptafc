<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    use HasFactory;

    protected $table = 'stage';

    function Route() {
        return $this->belongsTo(Route::class, 'route_route_id', 'route_id');
    }
}
