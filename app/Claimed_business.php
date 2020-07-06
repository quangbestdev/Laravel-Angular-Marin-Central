<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Claimed_business extends Model
{
    //
    protected $table = 'claimed_business';
    protected $fillable = ['email','stepscompleted','name', 'services','city','state','country','zipcode','contact','stage','businessemail','websiteurl','primaryimage'];

    protected $hidden = [];
}
