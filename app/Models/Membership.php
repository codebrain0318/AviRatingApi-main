<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    protected $guarded = [];

   

    public function Subscription()
    {
    	return $this->hasMany('App\Models\Subscription');
    }
}
