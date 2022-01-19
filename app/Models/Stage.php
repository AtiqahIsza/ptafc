<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    use HasFactory;

    protected $table = 'stage';

    public $timestamps = false;

    protected $fillable = [
        'stage_name',
        'stage_number',
        'stage_order',
        'no_of_km',
        'route_id',
    ];

    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }
}
