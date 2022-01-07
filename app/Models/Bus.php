<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    use HasFactory;

    protected $table = 'bus';

    function BusType() {
        return $this->belongsTo(BusType::class, 'busType_id', 'id');
    }
    function Route() {
        return $this->belongsTo(Route::class, 'route_route_id', 'route_id');
    }
    function Sector() {
        return $this->belongsTo(Sector::class, 'sector_sector_id', 'sector_id');
    }
    function Company() {
        return $this->belongsTo(Company::class, 'company_company_id', 'company_id');
    }
}
