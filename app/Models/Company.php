<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $table = 'company';

    public $timestamps = false;

    protected $fillable = [
        'company_name',
        'company_type',
        'region_id',
        'address1',
        'address2',
        'postcode' ,
        'city',
        'state',
        'minimum_balance',
    ];

    function Region() {
        return $this->belongsTo(RegionCode::class, 'region_id', 'id');
    }
}
