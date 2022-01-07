<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffPromotion extends Model
{
    use HasFactory;

    protected $table = 'staff_promotion';

    function Agent() {
        return $this->belongsTo(Company::class, 'agent_company_id', 'company_id');
    }
    function Company() {
        return $this->belongsTo(Company::class, 'company_company_id', 'company_id');
    }
}
