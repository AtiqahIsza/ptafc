<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionScheme extends Model
{
    use HasFactory;

    protected $table = 'commission_scheme';

    function User() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    function Company() {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
