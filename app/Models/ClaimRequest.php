<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimRequest extends Model
{
    protected $guarded = [];

    
    public function User()
    {
        return $this->belongsTo('App\User' , 'user_id' , 'id');
    }

   
}