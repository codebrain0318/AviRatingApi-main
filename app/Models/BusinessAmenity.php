<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessAmenity extends Model
{
    protected $guarded = [];

   

    public function Amenity()
    {
    	return $this->belongsTo('App\Models\Amenity');
    }
}
