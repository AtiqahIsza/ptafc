<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportParam extends Model
{
    use HasFactory;

    protected $table = 'report_param';

    function Report()
    {
        return $this->belongsTo(Report::class, 'report_name', 'report_name');
    }
}
