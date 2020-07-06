<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Companydetail extends Model
{	
    protected $fillable = ['name', 'services','city','state','country','zipcode','contact','stage','businessemail','websiteurl','primaryimage'];

    protected $hidden = [];
}
