<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusInspectorLog extends Model
{
    use HasFactory;

    function Bus() {
        return $this->belongsTo(Bus::class, 'bus_bus_id', 'bus_id');
    }
    function Route() {
        return $this->belongsTo(Route::class, 'route_route_id', 'route_id');
    }
    function PDAProfile() {
        return $this->belongsTo(PDAProfile::class, 'pda_pda_id', 'pda_id');
    }
    function BusDriver() {
        return $this->belongsTo(BusDriver::class, 'driver_driver_id', 'driver_id');
    }
    function BusInspector() {
        return $this->belongsTo(BusDriver::class, 'inspector_driver_id', 'driver_id');
    }
    function InspectorTicketCard() {
        return $this->belongsTo(TicketCard::class, 'inspectorCard_card_id', 'card_id');
    }
}
