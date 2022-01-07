<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StageFare extends Model
{
    use HasFactory;

    protected $table = 'stage_fare';

    function Route() {
        return $this->belongsTo(Route::class, 'route_route_id', 'route_id');
    }
    function toStage() {
        return $this->belongsTo(Stage::class, 'toStage_stage_id', 'stage_id');
    }
    function fromStage() {
        return $this->belongsTo(Stage::class, 'fromStage_stage_id', 'stage_id');
    }
}
