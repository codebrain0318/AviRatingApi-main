<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = [];

   

    public function User()
    {
    	return $this->hasOne('App\User', 'id', 'user_id');
    }
}
