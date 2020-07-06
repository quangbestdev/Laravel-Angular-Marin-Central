<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{	
	protected $table = 'services';	
    protected $fillable = ['service','id', 'category', 'status', 'added_by'];
}

