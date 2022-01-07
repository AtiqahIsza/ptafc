<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardBlacklistHistory extends Model
{
    use HasFactory;

    protected $table = 'card_blacklist_history';

    function TicketCard() {
        return $this->belongsTo(TicketCard::class, 'card_card_id', 'card_id');
    }
}
