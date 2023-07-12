<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $guarded = [];

    public function Country(){
        return $this->belongsTo('App\Models\Country');
    }

    public function City(){
        return $this->hasMany('App\Models\City');
    }

    public function Business(){
        return $this->hasMany('App\Models\Business');
    }
}
