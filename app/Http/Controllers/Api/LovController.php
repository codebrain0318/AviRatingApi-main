<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Validator, Auth, DB, Gate, File, Mail, Hash,Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\Response as R;
use App\Helpers\Helper;
use \Carbon\Carbon;
use App\Models\Tutor;
use App\Models\Education;
use App\Models\Subject;
use App\Models\Country;
use App\Models\City;
use App\Models\TutorEducation;
use App\Models\Document;
use App\Models\MajorDegree;
use App\Models\Region;
use \App\User;

class LovController extends Controller
{
    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }

    Public function Countries(){
        $educationList = Country::get();
        return R::Success(' Country List', $educationList);        
    }

    Public function Cities($id){
        $educationList = City::Where('country_id' , $id)
        ->get();
        return R::Success(' Citiy List', $educationList);        
    }

    Public function Subjects(){
        $educationList = Subject::get();
        return R::Success(' Subject List', $educationList);        
    }

    Public function Educations(){
        $educationList = Education::get();
        return R::Success(' Education List', $educationList);        
    }

    Public function MajorDegrees(){
        $data = MajorDegree::where('delete_status', 'available')
        ->get();
        return R::Success('List', $data);        
    }
}