<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardBlacklistHistory extends Model
{
    use HasFactory;

    protected $table = 'card_blacklist_history';

    public $timestamps = false;

    protected $fillable = [
        'reason',
        'card_id',
        'blacklisted_date'
    ];

    function TicketCard() {
        return $this->belongsTo(TicketCard::class, 'card_id', 'id');
    }
}
