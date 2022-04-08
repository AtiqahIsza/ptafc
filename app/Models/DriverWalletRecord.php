<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverWalletRecord extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'driver_wallet_record';

    protected $fillable = [
        'driver_id',
        'value',
        'value_after_promo',
        'created_at',
        'created_by',
        'topup_promo_id'
    ];

    function BusDriver() {
        return $this->belongsTo(BusDriver::class, 'driver_id', 'id');
    }
    function CreatedBy() {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    function TopupPromo() {
        return $this->belongsTo(TopupPromo::class, 'topup_promo_id', 'id');
    }
}
