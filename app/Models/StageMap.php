<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StageMap extends Model
{
    use HasFactory;

    protected $table = 'stage_map';

    function Stage() {
        return $this->belongsTo(Stage::class, 'stage_stage_id', 'stage_id');
    }
}
