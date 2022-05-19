<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketSalesTransaction extends Model
{
    use HasFactory;

    protected $table = 'ticket_sales_transaction';

    public $timestamps = false;

    //passenger_type(0=adult,1=concession)
    //fare_type(0=cash/driver wallet, 1=card,2=tngo)
    protected $fillable = [
        'trip_id',
        'ticket_number',
        'bus_stand_id',
        'fromstage_stage_id',
        'tostage_stage_id',
        'passenger_type',
        'amount',
        'actual_amount',
        'fare_type',
        'latitude',
        'longitude',
        'sales_date'
    ];

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
    function busStand(){
        return $this->belongsTo(BusStand::class, 'bus_stand_id', 'id');
    }
}
