<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionScheme extends Model
{
    use HasFactory;

    protected $table = 'commission_scheme';

    function User() {
        return $this->belongsTo(User::class, 'user_user_id', 'user_id');
    }

    function Company() {
        return $this->belongsTo(Company::class, 'company_company_id', 'company_id');
    }
}
