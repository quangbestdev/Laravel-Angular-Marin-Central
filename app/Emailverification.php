<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Emailverification extends Model
{
     //
    protected $table = 'emailverification';
    protected $fillable = ['email','otp','status'];
    protected $hidden = [];
    public $timestamps = false;
}
