<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PassengerDetail extends Model
{
    use HasFactory;

    protected $table = 'passenger_details';

    function TicketCard() {
        return $this->belongsTo(TicketCard::class, 'card_id', 'id');
    }
}
