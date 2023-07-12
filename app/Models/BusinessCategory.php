<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessCategory extends Model
{
    protected $guarded = [];

    public function Business()
    {
        return $this->hasMany('App\Models\Business' , 'id' , 'business_category_id');
    }
    
}