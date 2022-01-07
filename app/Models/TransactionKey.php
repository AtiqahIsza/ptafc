<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionKey extends Model
{
    use HasFactory;

    protected $table = 'transaction_key';

    function ReloadAgent() {
        return $this->belongsTo(ReloadAgent::class, 'agent_agent_id', 'agent_id');
    }
}
