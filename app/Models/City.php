<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $guarded = [];

    public function Region(){
        return $this->belongsTo('App\Models\Region');
    }

    public function Product(){
    	return $this->hasMany('App\Models\Product');
    }
}