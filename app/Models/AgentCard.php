<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentCard extends Model
{
    use HasFactory;

    protected $table = 'agent_card';

    function ReloadAgent() {
        return $this->belongsTo(ReloadAgent::class, 'agent_agent_id', 'agent_id');
    }

    function TicketCard() {
        return $this->belongsTo(TicketCard::class, 'card_card_id', 'card_id');
    }
}
