<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentAccount extends Model
{
    use HasFactory;

    protected $table = 'agent_account';

    function ParentAgentAccount() {
        return $this->belongsTo(AgentAccount::class, 'parent_transaction_id', 'transaction_id');
    }
    function AgentAccount() {
        return $this->belongsTo(AgentAccount::class, 'referenceId_transaction_id', 'transaction_id');
    }
    function ReloadAgent() {
        return $this->belongsTo(ReloadAgent::class, 'agent_agent_id', 'agent_id');
    }
    function AccountType() {
        return $this->belongsTo(AccountTransactionType::class, 'agent_agent_id', 'agent_id');
    }
    function User() {
        return $this->belongsTo(User::class, 'user_user_id', 'user_id');
    }
    function Company() {
        return $this->belongsTo(Company::class, 'company_company_id', 'company_id');
    }
    function TicketCard() {
        return $this->belongsTo(TicketCard::class, 'card_card_id', 'card_id');
    }
}
