<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentStaffTransaction extends Model
{
    use HasFactory;

    protected $table = 'agent_staff_transaction';

    function AgentStaff() {
        return $this->belongsTo(AgentStaff::class, 'staff_staff_id', 'staff_id');
    }
    function AgentAccount() {
        return $this->belongsTo(AgentAccount::class, 'referenceId_transaction_id', 'transaction_id');
    }
}
