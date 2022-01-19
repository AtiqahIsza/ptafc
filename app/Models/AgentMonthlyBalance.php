<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentMonthlyBalance extends Model
{
    use HasFactory;

    protected $table = 'agent_monthly_balance';

    function ReloadAgent() {
        return $this->belongsTo(ReloadAgent::class, 'agent_id', 'id');
    }
}
