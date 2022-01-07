<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    use HasFactory;

    protected $table = 'sector';

    function Company() {
        return $this->belongsTo(Company::class, 'company_company_id', 'company_id');
    }
}
