<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Hash, Validator, Helper, Auth, DB, Gate, File;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\Response as R;
use \Carbon\Carbon;
use \Log;

use App\Models\User;

class MainController extends Controller
{
    public function __construct(Request $request)
    {        
        $this->request = $request;
    }

    public function index() 
    {
    	if(isAdmin()){
    		return redirect('admin/');
    	}

    	return view('website.index');
    }
}