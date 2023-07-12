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
use App\Models\TutorSchedule;
use App\Models\Playgroup;
use App\Models\Student;
use App\Models\Center;
use App\Models\Education;
use App\Models\TutorEducation;
use App\Models\Document;
use App\Models\EmailVerification;
use App\Models\CenterSchedule;
use App\Models\PlaygroupSchedule;
use \App\User;
use JWTAuth;
use App\Mail\AccountVerificationEmail;
use App\Mail\AdminEmailVerification;

class ProfileController extends Controller
{
    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }
    
    public function ResetPassword()
    {   
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [

            'new_password' => 'required',
            'old_password' => 'required'
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
         }

        $user_id = Auth::id();
        $user = User::find($user_id);
        $old_password = $user->password;
        $match = Hash::check($inputs['old_password'] , $old_password);
        
        if($match == 'true'){
           
            $newPassword = Hash::make($inputs['new_password']);
            $data = $user->update(['password' => $newPassword]);
            return R::Success(__('Password reset successfully'));   
        }
        return R::SimpleError(__('Incorrect old password entered'));
    }

    public function SaveAdmin()
    {   
        $token = Str::random(60);
        
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'first_name' => 'required|max:125',
            'last_name' => 'required|max:125',
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $check_user = User::where('email', $inputs['email'])
        ->where('delete_status', 'available')
        ->count();


        if($check_user > 0) {
            return R::SimpleError("Email  Already Exist");
        }

        $data = [
            'first_name' => $inputs['first_name'],
            'last_name' => $inputs['last_name'],
            'email' => $inputs['email'],
            'password' => Hash::make($inputs['password']),
            'api_token' => hash('sha256', $token),
            'user_type' => 'admin',
            'email_verified_at' => Carbon::now(),
        ];

        
        DB::beginTransaction();
        try{    
            $user = User::create($data);

            

            $msg['password'] = $inputs['password'];
            $msg['email'] = $inputs['email'];
            $msg['first_name'] = $inputs['first_name'];
            $msg['last_name'] = $inputs['last_name'];

            Mail::to($inputs['email'])->send(new AdminEmailVerification(
                ['name' => 'click on  below link to verify your Account'],'Register as Admin',$msg));

            $user->api_token = $token;
            DB::commit();
        } catch(\Exception $e) {
            DB::rollback();
            return $e;
            return R::SimpleError("Can't save data");
        }
        
        return R::Success('Saved Successfully', $user->id); 
    }

    public function UpdateAdmin()
    {   
        $inputs = $this->request->all();
        
        $v = Validator::make($inputs, [
            'id' => 'required|integer',
            'first_name' => 'required|max:125',
            'last_name' => 'required|max:125'
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }
            
        $user = User::where('id' ,$inputs['id'])
        ->update($inputs);

        return R::Success('Updated Successfully', $user); 
    }

    public function AdminList()
    {
        $data = User::where('user_type', 'admin')
        ->where('id', '>', 1)
        ->where('delete_status', 'available')
        ->get();

        return R::Success('List', $data);
    }

    public function DeleteAdmin()
    {
        $inputs = $this->request->all();
        
        $v = Validator::make($inputs, [
            'id' => 'required|integer',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        if($inputs['id'] == 1){
            return R::SimpleError('Main admin cannot be deleted');
        }

        User::find($inputs['id'])
        ->delete();

        return R::Success('Deleted Successfully'); 
    }


    
}