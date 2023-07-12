<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSchedule extends Model
{
    protected $guarded = [];

    public function Business()
    {
    	return $this->belongTo('App\Models\Business');
    }
}
