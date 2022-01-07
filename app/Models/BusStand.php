<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusStand extends Model
{
    use HasFactory;

    protected $table = 'bus_stand';

    function Stage() {
        return $this->belongsTo(Stage::class, 'stage_stage_id', 'stage_id');
    }
}
