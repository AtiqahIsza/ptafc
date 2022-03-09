<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketSalesTransaction extends Model
{
    use HasFactory;

    protected $table = 'ticket_sales_transaction';

    function Sector() {
        return $this->belongsTo(Sector::class, 'sector_id', 'id');
    }
    function tostage() {
        return $this->belongsTo(Stage::class, 'tostage_stage_id', 'id');
    }
    function Bus() {
        return $this->belongsTo(Bus::class, 'bus_id', 'id');
    }
    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }
    function TripDetail() {
        return $this->belongsTo(TripDetail::class, 'trip_id', 'id');
    }
    function PDAProfile() {
        return $this->belongsTo(PDAProfile::class, 'pda_id', 'id');
    }
    function BusDriver() {
        return $this->belongsTo(BusDriver::class, 'bus_driver_id', 'id');
    }
    function fromstage() {
        return $this->belongsTo(Stage::class, 'fromstage_stage_id', 'id');
    }
    function TicketTransactionSummary() {
        return $this->belongsTo(TicketTransactionSummary::class, 'summary_id', 'id');
    }
    function TicketCard() {
        return $this->belongsTo(TicketCard::class, 'card_id', 'id');
    }
}
