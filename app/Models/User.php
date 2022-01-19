<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    public $timestamps = false;

//    User_Role
//    1 - Administrator
//    2 - Report User

    protected $fillable = [
        'full_name',
        'ic_number',
        'password',
        'phone_number',
        'username',
        'company_id',
        'user_role',
        'email',
    ];
    protected $hidden = [
        'password',
    ];

    function Company() {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
