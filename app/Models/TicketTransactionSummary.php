<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketTransactionSummary extends Model
{
    use HasFactory;

    protected $table = 'ticket_transaction_summary';

    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }
    function Bus() {
        return $this->belongsTo(Bus::class, 'bus_id', 'id');
    }
}
