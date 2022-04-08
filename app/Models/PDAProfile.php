<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PDAProfile extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'pda_profile';

    protected $fillable = [
        'pda_tag',
        'imei',
        'date_created',
        'date_registered',
        'region_id',
        'company_id',
        'pda_key',
        'status'
    ];

    function Region() {
        return $this->belongsTo(RegionCode::class, 'region_id', 'id');
    }

    function Company() {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
