<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    use HasFactory;

    protected $table = 'sector';

    public $timestamps = false;

    protected $fillable = [
        'sector_name',
        'company_id',
        ];

    function Company() {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
