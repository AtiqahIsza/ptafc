<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PDATrackingHistory extends Model
{
    use HasFactory;

    protected $table = 'pda_tracking_history';

    function Route() {
        return $this->belongsTo(Company::class, 'route_route_id', 'route_id');
    }
    function Bus() {
        return $this->belongsTo(Bus::class, 'bus_bus_id', 'bus_id');
    }
    function PDAProfile() {
        return $this->belongsTo(PDAProfile::class, 'pda_pda_id', 'pda_id');
    }
}
