<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    use HasFactory;

    protected $table = 'route';

    function Sector() {
        return $this->belongsTo(Sector::class, 'sector_company_id', 'sector_id');
    }
    function Company() {
        return $this->belongsTo(Company::class, 'company_company_id', 'company_id');
    }
}
