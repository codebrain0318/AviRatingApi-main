<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessReview extends Model
{
	
    protected $guarded = [];

    public function User()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function Business()
    {
        return $this->hasOne('App\User', 'id', 'business_id');
    }

    public function ReviewImages()
    {
        return $this->hasMany('App\Models\ReviewImage', 'business_review_id', 'id');
    }

    public function ReplyImages()
    {
        return $this->hasMany('App\Models\ReplyImage', 'review_id', 'id');
    }

    public function Listing()
    {
        return $this->belongsTo('App\Models\Listing');
    }


}