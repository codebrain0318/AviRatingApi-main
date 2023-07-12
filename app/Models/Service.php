<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $guarded = [];

   

    public function BusinessService()
    {
    	return $this->hasMany('App\Models\BusinessService');
    }
}
