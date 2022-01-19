<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardMonthlyBalance extends Model
{
    use HasFactory;

    protected $table = 'card_monthly_balance';

    function TicketCard() {
        return $this->belongsTo(TicketCard::class, 'card_id', 'id');
    }
}
