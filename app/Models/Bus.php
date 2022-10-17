<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    use HasFactory;

    protected $table = 'bus';

    //public $timestamps = false;

    /** Status:
     * 1 - Active
     * 2 - Inactive
     */
    protected $fillable = [
        'bus_registration_number',
        'bus_series_number',
        'company_id',
        'sector_id',
        'route_id',
        'bus_type_id',
        'mac_address',
        'bus_manufacturing_date',
        'bus_age',
        'updated_by',
        'created_by',
        'status',
        'terminal_id'
    ];

    function BusType() {
        return $this->belongsTo(BusType::class, 'bus_type_id', 'id');
    }
    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }
    function Sector() {
        return $this->belongsTo(Sector::class, 'sector_id', 'id');
    }
    function Company() {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    function UpdatedBy() {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
    function CreatedBy() {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
