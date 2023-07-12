<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    protected $guarded = [];

    public function ListingImages()
    {
    	return $this->hasMany('App\Models\ListingImage','listing_id','id');
    }

    public function User()
    {
    	return $this->belongsTo('App\User');
    }

    public function Review()
    {
    	return $this->hasMany('App\Models\Review', 'listing_id', 'id');
    }

    public function Business()
    {
    	return $this->hasOne('App\Models\Business', 'id', 'user_id');
    }

}
