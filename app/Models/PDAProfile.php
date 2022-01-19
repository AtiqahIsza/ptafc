<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PDAProfile extends Model
{
    use HasFactory;

    protected $table = 'pda_profile';

    function Region() {
        return $this->belongsTo(RegionCode::class, 'region_id', 'id');
    }

    function Company() {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
