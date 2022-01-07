<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyPromoHistory extends Model
{
    use HasFactory;

    protected $table = 'company_promo_history';

    function User() {
        return $this->belongsTo(User::class, 'user_user_id', 'user_id');
    }

    function Company() {
        return $this->belongsTo(Company::class, 'company_company_id', 'company_id');
    }

    function Promo() {
        return $this->belongsTo(CompanyPromo::class, 'promo_promo_id', 'promo_id');
    }
}
