<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PDO;

class Route extends Model
{
    use HasFactory;

    protected $table = 'route';

    //public $timestamps = false;

    protected $fillable = [
        'route_name',
        'route_number',
        'route_target',
        'distance',
        'inbound_distance',
        'outbound_distance',
        'company_id',
        'sector_id',
        'status',
        'updated_by',
        'created_by'
    ];

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
    function Trip(){
        return $this->hasMany(TripDetail::class);
    }
}
