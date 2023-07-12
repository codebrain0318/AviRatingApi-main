<?php

namespace App\Http\Controllers\AdminPanel;

use App\Http\Controllers\Controller;
use Hash, Validator, Helper, Auth, DB, Gate, File;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\Response as R;
use \Carbon\Carbon;

use App\Models\User;


class AdminController extends Controller
{
    public function __construct(Request $request)
    {        
        $this->request = $request;
    }

    public function Dashboard() {
    	return view('admin-panel.dashboard');
    }

    public function ManageUser(){
    	$data = User::get();
    	return view('admin-panel.users.manage-users',compact('data'));
    }

    public function UserStatus($id,$status)
    {
    	User::find($id)
    	->Update(['status'=>$status]);
    	return R::Success('Status Updated Successfully');
    }

    public function AddUsers(){
    	$data = null;
    	return view('admin-panel.users.add-users',compact('data'));

    } 



}



