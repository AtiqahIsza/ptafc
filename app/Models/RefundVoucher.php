<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundVoucher extends Model
{
    use HasFactory;

    protected $table = 'refund_voucher';

    function User()
    {
        return $this->belongsTo(UserOld::class, 'user_id', 'id');
    }

    function ClaimCard()
    {
        return $this->belongsTo(TicketCard::class, 'claim_card_id', 'id');
    }

    function BlacklistCard()
    {
        return $this->belongsTo(TicketCard::class, 'blacklisted_card_id', 'id');
    }

    function Agent()
    {
        return $this->belongsTo(ReloadAgent::class, 'agent_id', 'id');
    }
}
