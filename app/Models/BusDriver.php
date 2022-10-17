<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusDriver extends Model
{
    use HasFactory;

    protected $table = 'bus_driver';

    //public $timestamps = false;

    protected $fillable = [
        'driver_name',
        'employee_number',
        'id_number',
        'driver_role',
        'status',
        'target_collection',
        'driver_number',
        'driver_password',
        'company_id',
        'sector_id',
        'route_id',
        'bus_id',
        'wallet_balance',
        'updated_by',
        'created_by'
    ];

    function Sector() {
        return $this->belongsTo(Sector::class, 'sector_id', 'id');
    }

    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

    function Bus() {
        return $this->belongsTo(Bus::class, 'bus_id', 'id');
    }

    function Hunter() {
        return $this->belongsTo(DriverHunter::class, 'hunter_id', 'id');
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
