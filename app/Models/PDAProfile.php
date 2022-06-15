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
        'company_id',
        'pda_key',
        'status'
    ];

    function Company() {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
