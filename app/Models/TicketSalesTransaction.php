<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketSalesTransaction extends Model
{
    use HasFactory;

    protected $table = 'ticket_sales_transaction';

    function Sector() {
        return $this->belongsTo(Sector::class, 'sector_sector_id', 'sector_id');
    }
    function toStage() {
        return $this->belongsTo(Stage::class, 'toStage_stage_id', 'stage_id');
    }
    function Bus() {
        return $this->belongsTo(Bus::class, 'bus_bus_id', 'bus_id');
    }
    function Route() {
        return $this->belongsTo(Route::class, 'route_route_id', 'route_id');
    }
    function TripDetail() {
        return $this->belongsTo(TripDetail::class, 'trip_trip_id', 'trip_id');
    }
    function PDAProfile() {
        return $this->belongsTo(PDAProfile::class, 'pda_pda_id', 'pda_id');
    }
    function BusDriver() {
        return $this->belongsTo(BusDriver::class, 'busDriver_driver_id', 'driver_id');
    }
    function fromStage() {
        return $this->belongsTo(Stage::class, 'fromStage_stage_id', 'stage_id');
    }
    function TicketTransactionSummary() {
        return $this->belongsTo(TicketTransactionSummary::class, 'summary_summary_id', 'summary_id');
    }
    function TicketCard() {
        return $this->belongsTo(TicketCard::class, 'card_card_id', 'card_id');
    }
}
