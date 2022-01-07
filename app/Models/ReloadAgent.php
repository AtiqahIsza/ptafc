<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReloadAgent extends Model
{
    use HasFactory;

    protected $table = 'reload_agent';

    function ParentAgent() {
        return $this->belongsTo(ReloadAgent::class, 'parentAgent_agent_id', 'agent_id');
    }

    function Company() {
        return $this->belongsTo(Company::class, 'company_company_id', 'company_id');
    }
}
