<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageBanner extends Model
{
    protected $guarded = [];

    public function User()
    {
        return $this->belongsTo('App\User');
    }   
}
