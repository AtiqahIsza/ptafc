<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stage extends Model
{
    use HasFactory;

    protected $table = 'stage';

    //public $timestamps = false;

    protected $fillable = [
        'stage_name',
        'stage_number',
        'stage_order',
        'no_of_km',
        'route_id',
        'created_by',
        'updated_by'
    ];

    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

    function FromStage(){
        return $this->hasMany(StageFare::class, 'id', 'fromstage_stage_id');
    }

    function ToStage() {
        return $this->hasMany(StageFare::class, 'id','tostage_stage_id');
    }
    function UpdatedBy() {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
    function CreatedBy() {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
