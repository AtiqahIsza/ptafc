<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusDriver extends Model
{
    use HasFactory;

    protected $table = 'bus_driver';

    function Sector() {
        return $this->belongsTo(Sector::class, 'sector_sector_id', 'sector_id');
    }

    function Route() {
        return $this->belongsTo(Route::class, 'route_route_id', 'route_id');
    }

    function Bus() {
        return $this->belongsTo(Bus::class, 'bus_bus_id', 'bus_id');
    }

    function Hunter() {
        return $this->belongsTo(DriverHunter::class, 'hunter_hunter_id', 'hunter_id');
    }

    function Company() {
        return $this->belongsTo(Company::class, 'company_company_id', 'company_id');
    }
}
