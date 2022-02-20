<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteSchedule extends Model
{
    use HasFactory;

    protected $table = 'route_scheduler';
    public $timestamps = false;
    protected $fillable = [
        'route_id',
        'inbus_id',
        'outbus_id',
        'sequence',
        'start',
        'time',
        'title'
    ];

    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }
    function Inbus() {
        return $this->belongsTo(Bus::class, 'inbus_id', 'id');
    }
    function Outbus() {
        return $this->belongsTo(Bus::class, 'outbus_id', 'id');
    }
}
