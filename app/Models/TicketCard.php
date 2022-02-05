<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketCard extends Model
{
    use HasFactory;

    protected $table = 'ticket_card';
    public $timestamps = false;
    protected $fillable = [
        'current_balance',
        'region_id'
    ];

    function Region() {
        return $this->belongsTo(RegionCode::class, 'region_id', 'id');
    }
}
