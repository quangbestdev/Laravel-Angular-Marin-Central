<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Yachtdetail extends Model
{
    protected $table = 'yachtdetail';
    protected $fillable = ['firstname', 'lastname','contact','address','city','state','country','zipcode','yachtdetail','homeport','primaryimage'];
}
