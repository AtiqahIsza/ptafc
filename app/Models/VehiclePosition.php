<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehiclePosition extends Model
{
    use HasFactory;
    protected $table = 'vehicle_position';

    protected $fillable = [
        'vehicle_reg_no',
        'type',
        'imei',
        'latitude',
        'longitude',
        'altitude',
        'timestamp',
        'speed',
        'bearing',
        'odometer',
        'satellite_count',
        'hdop',
        'd2d3',
        'rssi',
        'lac',
        'cell_id'
    ];
}
