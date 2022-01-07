<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionSchemeHistory extends Model
{
    use HasFactory;

    protected $table = 'commission_scheme_history';

    function User() {
        return $this->belongsTo(User::class, 'user_user_id', 'user_id');
    }

    function Company() {
        return $this->belongsTo(Company::class, 'company_company_id', 'company_id');
    }

    function Commission() {
        return $this->belongsTo(CommissionScheme::class, 'scheme_commission_id', 'commission_id');
    }
}
