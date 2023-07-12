<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessOwnerReply extends Model
{
    protected $guarded = [];
    
    public function User()
    {
    	return $this->hasOne('App\Models\User', 'id', 'user_id');
    }
}
