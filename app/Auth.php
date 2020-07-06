<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Auth extends Authenticatable
{
    use HasApiTokens, Notifiable;
    //
    protected $fillable = ['email', 'password','usertype','status','ipaddress'];

    protected $hidden = ['password'];
    
}



