<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StageFare extends Model
{
    use HasFactory;

    protected $table = 'stage_fare';

    public $timestamps = false;

    protected $fillable = [
        'fare',
        'consession_fare',
        'fromstage_stage_id',
        'tostage_stage_id',
        'route_id'
    ];

    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'route_id');
    }
    function tostage() {
        return $this->belongsTo(Stage::class, 'tostage_stage_id', 'id');
    }
    function fromstage() {
        return $this->belongsTo(Stage::class, 'fromstage_stage_id', 'id');
    }
}
