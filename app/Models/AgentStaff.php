<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentStaff extends Model
{
    use HasFactory;

    protected $table = 'agent_staff';

    function ReloadAgent() {
        return $this->belongsTo(ReloadAgent::class, 'agent_id', 'id');
    }
}
