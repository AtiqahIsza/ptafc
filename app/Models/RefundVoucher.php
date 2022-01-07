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
        return $this->belongsTo(UserOld::class, 'user_user_id', 'user_id');
    }

    function ClaimCard()
    {
        return $this->belongsTo(TicketCard::class, 'claimCard_card_id', 'card_id');
    }

    function BlacklistCard()
    {
        return $this->belongsTo(TicketCard::class, 'blacklistedCard_card_id', 'card_id');
    }

    function Agent()
    {
        return $this->belongsTo(ReloadAgent::class, 'agent_agent_id', 'agent_id');
    }
}
