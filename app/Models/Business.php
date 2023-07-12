<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Carbon\Carbon;

class Business extends Model
{
    protected $guarded = [];

    public function User()
    {
        return $this->belongsTo('App\User' , 'id' , 'id');
    }

    public function BusinessServices()
    {
        return $this->hasMany('App\Models\BusinessService' , 'business_id' , 'id');
    }

   public function BusinessAmenities()
    {
        return $this->hasMany('App\Models\BusinessAmenity' , 'business_id' , 'id');
    }

    public function BusinessCategory()
    {
        return $this->belongsTo('App\Models\BusinessCategory');
    }

     public function BusinessType()
    {
        return $this->hasOne('App\Models\BusinessType', 'id', 'business_type_id');
    }
    
    public function BusinessSchedule()
    {
        return $this->hasMany('App\Models\BusinessSchedule');
    }

    public function Listing()
    {
        return $this->hasMany('App\Models\Listing', 'user_id', 'id');
    }

    public function ReplyImages()
    {
        return $this->hasMany('App\Models\ReplyImage', 'business_id', 'id');
    }

    public function BusinessReviews()
    {
        return $this->hasMany('App\Models\BusinessReview', 'business_id', 'id')
        ->where('status', 'active');
    }

    public function Country(){
        return $this->belongsTo('App\Models\Country');
    }

    public function Region(){
        return $this->belongsTo('App\Models\Region');
    }

    public function City(){
        return $this->belongsTo('App\Models\City');
    }

    public function Subscription(){
        return $this->hasOne('App\Models\Subscription', 'user_id', 'id')
        ->where('status', 'active')
        ->where('subscription_status', 'active');
       
    }

    public function BusinessOwnerReply()
    {
        return $this->hasOne('App\Models\BusinessOwnerReply', 'user_id', 'id');
    }
}