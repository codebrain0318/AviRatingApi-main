<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessType extends Model
{
    protected $guarded = [];

    public function Business()
    {
        return $this->hasMany('App\Models\Business' , 'id' , 'business_category_id');
    }
    
}