<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketPromotion extends Model
{
    use HasFactory;

    protected $table = 'ticket_promotion';

    function Region() {
        return $this->belongsTo(RegionCode::class, 'region_region_id', 'region_id');
    }
    function ReloadAgent() {
        return $this->belongsTo(ReloadAgent::class, 'createdBy_agent_id', 'agent_id');
    }
}
