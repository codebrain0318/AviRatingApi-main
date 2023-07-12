<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimBusiness extends Model
{
    protected $guarded = [];

    

    public function User(){
    	return $this->hasOne('App\User','id','business_id');
    }
}