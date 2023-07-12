<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessService extends Model
{
    protected $guarded = [];

   

    public function Service()
    {
    	return $this->belongsTo('App\Models\Service');
    }
}
