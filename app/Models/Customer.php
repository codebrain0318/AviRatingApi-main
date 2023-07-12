<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = [];

    public function User()
    {
        return $this->belongsTo('App\User' , 'id' , 'id');
    }

    public function District()
    {
        return $this->hasOne('App\Models\District' , 'id' , 'district_id');
    }

   
    
}