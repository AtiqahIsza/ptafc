<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopupPromo extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'topup_promo';

    protected $fillable = [
        'promo_value',
        'created_at',
        'created_by',
    ];

    function createdBy() {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
