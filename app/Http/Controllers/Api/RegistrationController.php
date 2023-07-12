<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Validator, Auth, DB, Gate, File, Mail, Hash,Storage;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\Response as R;
use App\Helpers\Helper;
use \Carbon\Carbon;
use App\Models\EmailVerification;
use App\Models\Business;
use App\Models\Customer;
use App\Models\BusinessService;
use App\Models\BusinessCategory;
use App\Models\BusinessType;
use App\Models\BusinessAmenity;
use App\Models\BusinessSchedule;
use App\Models\BusinessReview;
use App\Helpers\Paypal;
use \App\User;
use JWTAuth;
use App\Mail\AccountVerificationEmail;

class RegistrationController extends Controller
{
    public function __construct(Request $request, Helper $helper, Paypal $paypal)
    {        
        $this->request = $request;
        $this->helper = $helper;
        $this->paypal = $paypal;
    }

    public function BusinessRegister()
    {
        $inputs = $this->request->all();

        $emailCheck = User::where('email', $inputs['email'])
        ->where('delete_status', 'deleted')
        ->first();

        if($emailCheck != null){

            return R::SimpleError('Your Profile hasbeen deleted,Please contact admin for further details');
        }

        $v = Validator::make($inputs, [
            'email' => 'required|string|max:50|unique:users,email',
            'first_name' => 'required|string|max:100',
            'password' => 'required|confirmed|min:8',
            'password_confirmation' => 'required',
            'opening_time' => 'required|date',
            'closing_time' => 'required|date',
            'contact' => 'required|string|max:15',
            'zip_code' => 'required|string|max:10',
            'state' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'business_category_id' => 'required|integer',
            'services' => 'required|array',
            'holidays' => 'required|array',
            'address' => 'required|string|max:500',
            'airport_id' => 'nullable|max:255',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'business_type_id' => 'required|integer',
            'website' => 'nullable|string|max:100|url',
        ], [
            'lat.required' => 'Please select the valid address from the options given.',
            'lng.required' => 'Please select the valid address from the options given.',
            'website.url' => 'Website format should be like http://yourwebsite.com'
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $user_data = [
            'email' => $inputs['email'],
            'first_name' => $inputs['first_name'],
            //'last_name' => $inputs['last_name'],
            'password' => Hash::make($inputs['password']),
            'user_type'=>'business',
            'claim_business' => '1',
        ];

        $role_data = [
            'opening_time' => $inputs['opening_time'],
            'closing_time' => $inputs['closing_time'],
            'contact' => $inputs['contact'],
            'business_category_id' => $inputs['business_category_id'],
            'address' => $inputs['address'],
            'description' => @$inputs['description'],
            'zip_code' => $inputs['zip_code'],
            'state' => $inputs['state'],
            'city' => $inputs['city'],
            'lat' => @$inputs['lat'],
            'lng' => @$inputs['lng'],
            'airport_id' => @$inputs['airport_id'],
            'business_type_id' => $inputs['business_type_id'],
            'website' => @$inputs['website']
        ];
        
        DB::beginTransaction();
        try{
            $user = User::create($user_data);
            
            $role_data['id'] = $user->id;
            $business_services = [];
            $business_holidays = [];
            $holidays = $this->request->holidays;

            for($i=0; $i<=6; $i++) {
                $business_holidays[] = [
                    'business_id' => $user->id,
                    'week_day' => $i,
                    'holiday' => $holidays[$i]
                ];
            }

            foreach ($this->request->services as $s) {
                $business_services[] = [
                    'business_id' => $user->id,
                    'service_id' => $s,
                ];
            }

            BusinessService::insert($business_services);
            BusinessSchedule::insert($business_holidays);
            Business::create($role_data);
            
            if($inputs['business_price'] == 0){
                do {
                    
                    $code = mt_rand(1000, 9999);
                } 
                while (EmailVerification::where('code', $code)->first());

                $expiry = 120;
                EmailVerification::create([
                    'code' => $code, 
                    'user_id' => $user->id,
                    'expiry_time' => Carbon::now()->addMinutes(25)
                ]);

                $subject = 'E-mail Verification';
                $emailData = [
                    'name' => $inputs['first_name'],
                    'link' => env('ANGULAR_BASE_URL').'/verify-email/'.$code
                ];
            }    
            
            DB::commit();
            //$user->api_token = $token;
            if($this->request->hasFile('profile_image')){
                $file = $this->request->file('profile_image');
                $imageFile = Image::make($file)->encode('jpg', 100);
                $profileImage = $imageFile->resize(240, 200);
                $profileImage->save(storage_path("app/images/profile-images/$user->id"), 90, 'jpg');
                // $result = $profileImage->storeAs('images/profile-images/',$user->id);
            }
        
            
        } catch(\Exception $e) {
            DB::rollback();
            return $e;
        }

        $check = BusinessType::find($inputs['business_type_id']);

        if($check->price > 0){

            $options = [
                'process-url' => env('APP_URL').'/api/business/business-payment-status/'.$user->id,
                'cancel-url' => env('APP_URL').'/api/business/business-payment-cancel/'.$user->id,
            ];

            $url = $this->paypal->definePayment($check->price, 'USD', $options);
            return R::Success('Verification Code Sent to your Email address!', $url); 
        }

        Mail::to($inputs['email'])
         ->queue(new AccountVerificationEmail($subject, $emailData));
        return R::Success('Verification Code Sent to your Email address!'); 
    }

    public function BusinessUpdateProfile()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'first_name' => 'required|string|max:100',
            'opening_time' => 'required',
            'closing_time' => 'required',
            'contact' => 'required|string|max:15',
            'business_category_id' => 'required|integer',
            'services' => 'required|array',
            'zip_code' => 'required|string|max:10',
            'state' => 'required|string|max:100',
            'website' => 'nullable|string|max:100|url',
            'city' => 'required|string|max:100',
            'amenities' => 'required|array',
            'holidays' => 'required|array',
            'address' => 'required|string|max:500',
            'airport_id' => 'nullable|max:325',
            'description' => 'nullable|string|max:500',
            'lat' => 'nullable',
            'lng' => 'nullable'
        ], [
            'lat.required' => 'Please select the valid address from the options given.',
            'lng.required' => 'Please select the valid address from the options given.',
            'website.url' => 'Website format should be like http://yourwebsite.com'
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $user_data = [
            'first_name' => $inputs['first_name'],
        ];

        $role_data = [
            'opening_time' => $inputs['opening_time'],
            'closing_time' => $inputs['closing_time'],
            'contact' => $inputs['contact'],
            'business_category_id' => $inputs['business_category_id'],
            'address' => $inputs['address'],
            'description' => $inputs['description'],
            'zip_code' => $inputs['zip_code'],
            'state' =>  $inputs['state'],
            'city' => $inputs['city'],
            'lat' => @$inputs['lat'],
            'lng' => @$inputs['lng'],
            'airport_id' => @$inputs['airport_id'],
            'website' => @$inputs['website'],
        ];
        
        DB::beginTransaction();
        try{
            $id = Auth::id();

            $user = User::find($id)
            ->update($user_data);

            $holidays = $this->request->holidays;

            for($i=0; $i<=6; $i++) {
                BusinessSchedule::where('business_id', $id)
                ->where('week_day', $i)
                ->update(['holiday' => $holidays[$i]]);
            }
            
            Business::find($id)
            ->update($role_data);
            
            BusinessService::where('business_id', $id)
            ->whereNotIn('service_id', $inputs['services'])
            ->delete();

            BusinessAmenity::where('business_id', $id)
            ->whereNotIn('amenity_id', $inputs['amenities'])
            ->delete();

            foreach ($this->request->services as $service) {
                BusinessService::firstOrCreate(['service_id' => $service, 'business_id' => $id]);
            }

            foreach ($this->request->amenities as $amenity) {
                BusinessAmenity::firstOrCreate(['amenity_id' => $amenity, 'business_id' => $id]);
            }
            
            DB::commit();
            //$user->api_token = $token;
            if($this->request->hasFile('profile_image')){
                $file = $this->request->file('profile_image');
                $imageFile = Image::make($file)->encode('jpg', 100);
                $profileImage = $imageFile->resize(240, 200);
                $profileImage->save(storage_path("app/images/profile-images/$id"), 90, 'jpg');
                // $result = $profileImage->storeAs('images/profile-images/',$user->id);
            }

            $data = User::with('Business.BusinessServices.Service',
            'Business.BusinessAmenities.Amenity', 'Business.BusinessSchedule', 
            'Business.BusinessCategory')
            ->find(Auth::id());
        
            return R::Success('Data updated successfully', $data); 
        } catch(\Exception $e) {
            DB::rollback();
            return $e;
            return R::SimpleError("Can't save data");
        }
    }

    public function ResetPassword()
    {   
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [

            'new_password' => 'required|min:8',
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
            return R::Success('Password changed successfully');   
        }
        return R::SimpleError('Old password doesnt match');
    }

    public function CustomerRegister()
    {
        $inputs = $this->request->all();

        $emailCheck = User::where('email', $inputs['email'])
        ->where('delete_status', 'deleted')
        ->first();

        if($emailCheck != null){

            return R::SimpleError('Your Profile hasbeen deleted,Please contact admin for further details');
        }

        $v = Validator::make($inputs, [
            'email' => 'required|email|max:50|unique:users,email',
            'password' => 'required|confirmed|min:8',
            'password_confirmation' => 'required',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'contact_1' => 'required|string|max:15',
            'contact_2' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:500',
            'dob' => 'required|date',
            'title' => 'required|in:Mr,Miss,Ms,Mrs',
            'home_airport' => 'nullable|max:150'
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $user_data = [
            'email' => $inputs['email'],
            'first_name' => $inputs['first_name'],
            'last_name' => $inputs['last_name'],
            'password' => Hash::make($inputs['password']),
            'user_type'=>'customer',
        ];

        $role_data = [
            'contact_1' => $inputs['contact_1'],
            'contact_2' => @$inputs['contact_2'],
            'address' => @$inputs['address'],
            'dob' => $inputs['dob'],
            'title' => $inputs['title'],
            'home_airport' => $inputs['home_airport']
        ];
       DB::beginTransaction();     
       try {
            $user = User::create($user_data);
            
            $role_data['id'] = $user->id;
            Customer::create($role_data);

            do {
                
                $code = mt_rand(1000, 9999);
            } 
            while (EmailVerification::where('code', $code)->first());

            $expiry = 120;
            EmailVerification::create([
                'code' => $code, 
                'user_id' => $user->id,
                'expiry_time' => Carbon::now()->addMinutes(25)
            ]);

            $subject = 'E-mail Verification';
            $emailData = [
                'name' => $inputs['first_name'],
                'link' => env('ANGULAR_BASE_URL').'/verify-email/'.$code
            ];
            Mail::to($inputs['email'])
            ->queue(new AccountVerificationEmail($subject, $emailData));

            DB::commit();

            if($this->request->hasFile('profile_image')){
                $file = $this->request->file('profile_image');
                $result = $file->storeAs('images/profile-images/',$user->id);
            }

            return R::Success('Verification Code Sent to your Email address!');

       } catch (Exception $e) {
         DB::rollback();
         return R::SimpleError("Can't save data");  
       }
    }

    public function CustomerUpdateProfile()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'contact_1' => 'required|string|max:15',
            'contact_2' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:500',
            'dob' => 'required|date',
            'title' => 'required|in:Mr,Miss,Ms,Mrs',
            'home_airport' => 'nullable|max:150',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $user_data = [
            'first_name' => $inputs['first_name'],
            'last_name' => $inputs['last_name'],
        ];

        $role_data = [
            'contact_1' => $inputs['contact_1'],
            'contact_2' => @$inputs['contact_2'],
            'address' => @$inputs['address'],
            'dob' => $inputs['dob'],
            'title' => $inputs['title'],
            'home_airport' => $inputs['home_airport'],
        ];
       DB::beginTransaction();     
       try {
            User::find(Auth::id())
            ->update($user_data);

            Customer::find(Auth::id())
            ->update($role_data);

        DB::commit();

        $user = User::with('Customer')
        ->find(Auth::id());

        if ($this->request->has('profile_image')) {
            $file = $this->request->file('profile_image');
            $result = $file->storeAs('images/profile-images',Auth::Id());
        }

        return R::Success('Updated successfully', $user);

       } catch (Exception $e) {
         DB::rollback();
         return $e;  
       }
    }

    public function BusinessProfile()
    {
        $data = Business::with(['User.Listing.Review.User',
        'BusinessSchedule', 'BusinessServices.Service', 'BusinessCategory', 'BusinessOwnerReply' =>function($q){
            $q->where('status', 'active')
            ->where('payment_status', 'paid');
        }]) 
        ->where('id', Auth::id())
        ->first();

        return R::Success(__('business'), $data);
    }

    public function CustomerProfile()
    {
        $data = Customer::with('User.Review.Listing.User')
        ->where('id', Auth::id())
        ->first();

        return R::Success(__('Customer'), $data);
    }

    public function AddBusiness()
    {
        if (!Auth::check()) {
            return R::SimpleError("Please login to add a business");
        }

        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'first_name' => 'required|string|max:100',
            'contact' => 'required|string|max:15',
            'zip_code' => 'required|string|max:10',
            'business_category_id' => 'required|integer',
            'address' => 'required|string|max:500',
            'city' => 'nullable|string|max:100',
            'lat' => 'nullable|numeric',
            'lng' => 'numeric|nullable',
            'state' => 'nullable|string|max:100',
            'description' => 'nullable|max:500',
            'rating' => 'required|numeric|max:5',
            'feedback' => 'required|max:1200',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $user_data = [
            'first_name' => $inputs['first_name'],
            'user_type'=>'business',
        ];

        $role_data = [
            'contact' => $inputs['contact'],
            'business_category_id' => $inputs['business_category_id'],
            'address' => $inputs['address'],
            'description' => @$inputs['description'],
            'city' => @$inputs['city'],
            'state' => @$inputs['state'],
            'lat' => @$inputs['lat'],
            'lng' => @$inputs['lng'],
            'zip_code' => $inputs['zip_code'],
            'business_type_id' => @$inputs['business_type_id'],
        ];
        
        DB::beginTransaction();
        try{
            $user = User::create($user_data);
            
            $role_data['id'] = $user->id;
            
            Business::create($role_data);

            $review_data = [
                'rating' => $inputs['rating'],
                'feedback' => $inputs['feedback'],
                'business_id' => $user->id,
                'user_id' => Auth::id(),
            ];

            BusinessReview::create($review_data);

            $avg_rating = BusinessReview::where('business_id', $user->id)
            ->avg('rating');
            
            User::find($user->id)
            ->update(['avg_rating' => $avg_rating]);
            
            DB::commit();
            return R::Success('Review and Business has been added successfully '); 
        } catch(\Exception $e) {
            DB::rollback();
            return R::SimpleError("Can't save data");
        }
    
        
    }
}