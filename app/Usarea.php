<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Usarea extends Model
{
    protected $fillable = ['zipcode', 'state','statename','status','city'];
    protected $hidden = ['password'];
    public $timestamps = false;
}
