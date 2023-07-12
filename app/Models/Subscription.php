<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $guarded = [];

    public function Membership()
    {
    	return $this->belongsTo('App\Models\Membership');
    }

    public function Business()
    {
    	return $this->belongsTo('App\Models\Subscription');
    }
}
