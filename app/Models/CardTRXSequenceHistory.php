<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardTRXSequenceHistory extends Model
{
    use HasFactory;

    protected $table = 'card_trx_sequence_history';

    function PDAProfile() {
        return $this->belongsTo(PDAProfile::class, 'pda_pda_id', 'pda_id');
    }
    function Sale() {
        return $this->belongsTo(TicketSalesTransaction::class, 'sales_sales_id', 'sales_id');
    }

    function Agent() {
        return $this->belongsTo(AgentAccount::class, 'transaction_transaction_id', 'transaction_id');
    }

    function TicketCard() {
        return $this->belongsTo(TicketCard::class, 'card_card_id', 'card_id');
    }


}

