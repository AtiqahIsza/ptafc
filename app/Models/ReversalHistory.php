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
        return $this->belongsTo(AgentAccount::class, 'debitted_transaction_id', 'transaction_id');
    }

    function Creditted()
    {
        return $this->belongsTo(AgentAccount::class, 'creditted_transaction_id', 'transaction_id');
    }

    function CreditReversal()
    {
        return $this->belongsTo(AgentAccount::class, 'creditReversal_transaction_id', 'transaction_id');
    }

    function DebitReversal()
    {
        return $this->belongsTo(AgentAccount::class, 'debitReversal_transaction_id', 'transaction_id');
    }

    function User()
    {
        return $this->belongsTo(User::class, 'user_user_id', 'user_id');
    }
}
