<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
	
    protected $guarded = [];

    public function Listing()
    {
        return $this->belongsTo('App\Models\Listing');
    }

    public function User()
    {
        return $this->belongsTo('App\User');
    }

}