<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteSchedulerDetail extends Model
{
    use HasFactory;

    protected $table = 'route_scheduler_details';

    function RouteScheduleMSTR() {
        return $this->belongsTo(RouteSchedulerMSTR::class, 'route_scheduler_mstr_id', 'id');
    }
}
