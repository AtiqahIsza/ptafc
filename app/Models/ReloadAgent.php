<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReloadAgent extends Model
{
    use HasFactory;

    protected $table = 'reload_agent';

    function ParentAgent() {
        return $this->belongsTo(ReloadAgent::class, 'parent_agent_id', 'id');
    }

    function Company() {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
