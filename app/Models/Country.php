<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $guarded = [];

    public function Region(){
    	return $this->hasMany('App\Models\Region');
    }

    public function Product(){
    	return $this->hasMany('App\Models\Product');
    }
}