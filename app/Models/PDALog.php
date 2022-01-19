<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PDALog extends Model
{
    use HasFactory;

    protected $table = 'pda_log';

    function PDAProfile() {
        return $this->belongsTo(PDAProfile::class, 'pda_id', 'id');
    }
}
