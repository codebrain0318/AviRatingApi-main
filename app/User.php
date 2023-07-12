<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function isGuest()
    {
        return \Auth::check();
    }

    public function isCustomer()
    {
        return $this->user_type == 'customer' ? true : false;
    }

    public function isBusiness()
    {
        return $this->user_type == 'business' ? true : false;
    }
   

    public function isAdmin()
    {
        return $this->user_type == 'admin' ? true : false;
    }

    public function Business(){
        return $this->hasOne('App\Models\Business' , 'id' , 'id');
    }

    public function Customer(){
        return $this->hasOne('App\Models\Customer' , 'id' , 'id');
    }

    public function Feedback(){
        return $this->hasMany('App\Models\Feedback' , 'user_id' , 'id');
    }

    public function Listing(){
        return $this->hasMany('App\Models\Listing','user_id', 'id');
    }

    public function Review(){
        return $this->hasMany('App\Models\Review');
    }

    public function Subscription(){
        return $this->hasOne('App\Models\Subscription', 'user_id', 'id');
    }
}
