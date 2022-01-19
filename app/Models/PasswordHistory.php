<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordHistory extends Model
{
    use HasFactory;

    protected $table = 'password_history';

    function User() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
