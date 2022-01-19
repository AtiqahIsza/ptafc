<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReversalHistory extends Model
{
    use HasFactory;

    protected $table = 'reversal_history';

    function Debitted()
    {
        return $this->belongsTo(AgentAccount::class, 'debitted_transaction_id', 'id');
    }

    function Creditted()
    {
        return $this->belongsTo(AgentAccount::class, 'creditted_transaction_id', 'id');
    }

    function CreditReversal()
    {
        return $this->belongsTo(AgentAccount::class, 'credit_reversal_transaction_id', 'id');
    }

    function DebitReversal()
    {
        return $this->belongsTo(AgentAccount::class, 'debit_reversal_transaction_id', 'id');
    }

    function User()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
