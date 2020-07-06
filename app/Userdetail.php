<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Userdetail extends Model
{
    protected $fillable = ['firstname', 'lastname','city','state','country','zipcode','address','mobile'];

    protected $hidden = [];
    protected $primaryKey = 'authid';

    public function serviceRequests() {
    	return $this->hasMany('App\User_request_services','authid','authid');
    }
}
