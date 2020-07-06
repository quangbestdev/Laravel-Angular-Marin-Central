<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User_request_services extends Model
{
    protected $table = 'users_service_requests'; 
    public function users(){
        return $this->belongsTo('App\Userdetail');
    }
}
