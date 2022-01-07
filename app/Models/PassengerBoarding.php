<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PassengerBoarding extends Model
{
    use HasFactory;

    protected $table = 'passenger_boarding';

    function Bus() {
        return $this->belongsTo(Bus::class, 'bus_bus_id', 'bus_id');
    }
}
