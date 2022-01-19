<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardTRXSequenceHistory extends Model
{
    use HasFactory;

    protected $table = 'card_trx_sequence_history';

    function PDAProfile() {
        return $this->belongsTo(PDAProfile::class, 'pda_id', 'id');
    }
    function Sale() {
        return $this->belongsTo(TicketSalesTransaction::class, 'sales_id', 'id');
    }

    function Agent() {
        return $this->belongsTo(AgentAccount::class, 'transaction_id', 'id');
    }

    function TicketCard() {
        return $this->belongsTo(TicketCard::class, 'card_id', 'id');
    }


}

