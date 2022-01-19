<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverCard extends Model
{
    use HasFactory;

    protected $table = 'driver_card';

    function BusDriver() {
        return $this->belongsTo(BusDriver::class, 'driver_id', 'id');
    }

    function TicketCard() {
        return $this->belongsTo(TicketCard::class, 'card_id', 'id');
    }
}
