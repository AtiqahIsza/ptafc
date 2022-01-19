<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SummaryGenerator extends Model
{
    use HasFactory;

    protected $table = 'summary_generator';

    function Route() {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }
}
