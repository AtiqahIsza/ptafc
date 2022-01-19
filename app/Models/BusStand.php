<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusStand extends Model
{
    use HasFactory;

    protected $table = 'bus_stand';

    public $timestamps = false;

    protected $fillable = [
        'driver_name',
        'employee_number',
        'id_number',
        'driver_role',
        'status',
        'target_collection',
        ];

    function Stage() {
        return $this->belongsTo(Stage::class, 'stage_id', 'id');
    }
}
