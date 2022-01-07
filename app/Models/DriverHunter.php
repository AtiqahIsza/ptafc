<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverHunter extends Model
{
    use HasFactory;

    protected $table = 'driver_hunter';

    function Region() {
        return $this->belongsTo(RegionCode::class, 'region_region_id', 'region_id');
    }
}
