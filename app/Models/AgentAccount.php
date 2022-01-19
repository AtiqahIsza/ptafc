<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentAccount extends Model
{
    use HasFactory;

    protected $table = 'agent_account';

    function ParentAgentAccount() {
        return $this->belongsTo(AgentAccount::class, 'parent_transaction_id', 'id');
    }
    function AgentAccount() {
        return $this->belongsTo(AgentAccount::class, 'referenceid_transaction_id', 'id');
    }
    function ReloadAgent() {
        return $this->belongsTo(ReloadAgent::class, 'agent_id', 'id');
    }
    function AccountType() {
        return $this->belongsTo(AccountTransactionType::class, 'agent_id', 'id');
    }
    function User() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    function Company() {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    function TicketCard() {
        return $this->belongsTo(TicketCard::class, 'card_id', 'id');
    }
}
