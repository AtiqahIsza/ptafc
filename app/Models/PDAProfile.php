<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PDAProfile extends Model
{
    use HasFactory;

    protected $table = 'pda_profile';

    function Region() {
        return $this->belongsTo(RegionCode::class, 'region_region_id', 'region_id');
    }

    function Company() {
        return $this->belongsTo(Company::class, 'company_company_id', 'company_id');
    }
}
