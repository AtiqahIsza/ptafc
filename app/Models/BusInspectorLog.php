<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusInspectorLog extends Model
{
    use HasFactory;

    function Bus() {
        return $this->belongsTo(Bus::class, 'bus_id', 'id');
    }
    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }
    function PDAProfile() {
        return $this->belongsTo(PDAProfile::class, '_pda_id', 'id');
    }
    function BusDriver() {
        return $this->belongsTo(BusDriver::class, 'driver_id', 'id');
    }
    function BusInspector() {
        return $this->belongsTo(BusDriver::class, 'inspector_driver_id', 'id');
    }
    function InspectorTicketCard() {
        return $this->belongsTo(TicketCard::class, 'inspector_card_id', 'id');
    }
}
