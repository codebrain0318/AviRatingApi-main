<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Validator, Auth, DB, Gate, File, Mail, Hash,Storage,App;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\Response as R;
use App\Helpers\Helper;
use \Carbon\Carbon;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Feedback;
use App\Models\Service;
use App\Models\BusinessService;
use App\Models\ContactUs;
use App\Models\EmailVerification;
use App\Models\PasswordVerification;
use App\Models\Setting;
use App\Helpers\Paypal;
use App\Models\Listing;
use App\Models\Pratice;
use App\Models\BusinessCategory;
use App\Models\BusinessReview;
use App\Models\HomepageBanner;
use App\Models\ClaimBusiness;
use App\Models\Subscription;
use \App\User;
use JWTAuth;
use \App\Mail\PasswordVerificationEmail;
use \App\Mail\StudentMail\PaymentSuccessfully;
use App\Mail\AccountVerificationEmail;
use App\Mail\ContactUsEmail;

class PublicController extends Controller
{
    public function __construct(Request $request, Helper $helper,  Paypal $paypal)
    {        
        $this->request = $request;
        $this->helper = $helper;
         $this->paypal = $paypal;
    }

    public function Test()
    {
        return 'Check auto pull';
    }

    public function VerifyEmail()
    {
    	$v = Validator::make($this->request->all(), [
            'code' => 'required'
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $code = $this->request->code;
        $verify = EmailVerification::where('code', $code)
        ->where('expiry_time', '>', Carbon::now())
        ->first();

        if($verify == null) {
            return R::SimpleError(__('The verification code has expired.')); 
        }

        DB::beginTransaction();

        try{
            EmailVerification::where('user_id', $verify->user_id)
            ->delete();

            User::where('id',  $verify->user_id)
            ->update([
                'email_verified_at' => Carbon::now()
            ]);

            $data = User::with('Customer', 'Business.BusinessServices.Service',
            'Business.BusinessSchedule', 'Business.BusinessAmenities')
            ->find($verify->user_id);
            $data->api_token = JWTAuth::fromUser($data);
            DB::commit();

            return R::Success(__('Code Verified'), $data);
        } catch(\Exception $e) {
            DB::rollback();
            return R::SimpleError(__('Please try again'));    
        }

    }

    public function Login()
    {   
        $emailCheck = User::where('email', $this->request->email)
        ->where('delete_status', 'deleted')
        ->first();

        if($emailCheck != null){

            return R::SimpleError('Your Profile hasbeen deleted,Please contact admin for further details');
        }

        $v = Validator::make($this->request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $credentials = $this->request->only('email', 'password');
        

        if (!$token = auth()->attempt($credentials)) {
            return R::SimpleError(__('Invalid Username or Password'));
        }

        $userId = auth()->id();

        $data = User::with('Customer', 'Business.BusinessServices.Service',
            'Business.BusinessSchedule', 'Business.BusinessAmenities', 'Business.Subscription.Membership')
        ->find($userId);

        if($data->email_verified_at == null){
            return R::SimpleError(__('Please verifiy your email first!'));
        }

        // if($data->deleted_at != null || $data->delete_status != 'available' || $data->status != 'Approved'){
        //     return R::SimpleError(__('Invalid Username or Password'));
        // }

        $data->api_token = JWTAuth::fromUser($data);
        // $data->expires_in = auth()->factory()->getTTL() * 60;

        return R::Success(__('Login Successful'), $data);
    }

    public function ResendCode()
    {
        $v = Validator::make($this->request->all(), [
            'code' => 'required'
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $code = $this->request->code;
        $verify = EmailVerification::where('code', $code)
        ->first();


        DB::beginTransaction();

        try{
            
            EmailVerification::where('user_id', $verify->user_id)
            ->delete();

            $user_data = User::find($verify->user_id);

            do {
                
                $code = mt_rand(1000, 9999);
            }

            while (EmailVerification::where('code', $code)->first());

            EmailVerification::create([
                'code' => $code,
                'user_id' => $verify->user_id,
                'expiry_time' => Carbon::now()->addMinutes(30), 
                //date('Y-m-d H:i:s', strtotime("+$expiry minutes"))
            ]);

            $subject = __('E-mail Verification');
            $emailData = [
                'name' => $user_data->first_name,
                'link' => env('ANGULAR_BASE_URL').'/verify-email/'.$code
            ];
            Mail::to($user_data->email)
            ->queue(new AccountVerificationEmail($subject, $emailData));

           DB::commit();
            return R::Success(__('Code Verified'), $code);
        } catch(\Exception $e) {
            DB::rollback();
        dd($e);
            return R::SimpleError(__('Please try again'));    
        }

        
    }

    public function ForgotPassword()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'email' => 'required'
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }
        
        $user = User::where('email', $inputs['email'])
        ->first();

        if ($user == null) {
            return R::SimpleError(__('The email you entered is not registered, please check!')); 
        }

        if ($user->email_verified_at == null) {
            return R::SimpleError(__('Please verify your email first!')); 
        }

        DB::beginTransaction();

        try{
            
            do {
                
                $code = mt_rand(1000, 9999);
            }
            while (PasswordVerification::where('code', $code)->first());

            PasswordVerification::create(['code' => $code, 'user_id' => $user->id, 'expiry_time' =>  Carbon::now()->addMinutes(25)]);

            DB::commit();
            $url = env('ANGULAR_BASE_URL').'/reset-password/'.$code;
            $msg = '<a href="'.$url.'">'.__('Reset your password').'</a>';
            Mail::to($inputs['email'])->send(new PasswordVerificationEmail(['name' => __('A password reset has been requested, if this is not you, please email contact@3finders.com. If you did make the request, click on the below link to reset your password.')],__('Reset Password'),$msg));


            return R::Success(__('Check your Email to Reset Password'));

        } catch(\Exception $e){
            DB::rollback();
            dd($e);
            return R::SimpleError(__('Internal server error, please try again later')); 
        }
    }

    public function VerifyCode()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'code' => 'required'
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $verify = PasswordVerification::where('code', $inputs['code'])
        ->where('expiry_time', '>', date('Y-m-d H:i:s'))
        ->first();

        if($verify == null) {
            return R::SimpleError(__('Code is not valid OR expired, please try again')); 
        }
        $user_email = User::where('id', $verify->user_id)
        ->pluck('email');

        return R::Success(__('User code is valid'), $user_email);
    }

    public function ResetPassword()
    {   
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'code' => 'required',
            'new_password' => 'required|confirmed',
            'new_password_confirmation' => 'required',
        ],
        [
            'password.confirmed' => 'The passwords does not match!', 
        ]);    
        

        if($v->fails()){
            return R::ValidationError($v->errors());
         }

        $verify = PasswordVerification::where('code', $this->request->code)
        ->where('expiry_time', '>', date('Y-m-d H:i:s'))
        ->first();

        if($verify == null) {
            return R::SimpleError(__('Code is not valid OR expired, please try again')); 
        }


        DB::beginTransaction();

        try{
            PasswordVerification::where('user_id', $verify->user_id)
            ->delete();
           
           $newPassword = Hash::make($inputs['new_password']);

            User::where('id', $verify->user_id)
            ->update([
                'password' => $newPassword
            ]);

            $data = User::with('Customer', 'Business.BusinessServices.Service',
            'Business.BusinessSchedule', 'Business.BusinessAmenities', 'Business.Subscription.Membership')
            ->find($verify->user_id);

            $data->api_token = JWTAuth::fromUser($data);
            DB::commit();

            return R::Success(__('Password reset successfully'), $data);
        } catch(\Exception $e) {
            DB::rollback();
            return R::SimpleError(__('Please try again'));    
        }

    }

    public function Business($id)
    {
        $check = Business::find($id);
        if($check->delete_status == 'deleted'){
            return R::SimpleError('business deleted');
        }

        $data['business'] = Business::with(['User.Listing.Review.User','BusinessType', 'BusinessAmenities.Amenity','BusinessSchedule', 'BusinessServices.Service',
            'BusinessCategory', 'BusinessReviews.ReviewImages', 'ReplyImages']) 
        ->where('id', $id)
        ->first();

        $data['listings'] = Listing::with('ListingImages', 'User.Business.BusinessCategory','Review')
        ->where('status', 'active')
        ->where('delete_status', 'available')
        ->where('user_id', $id)
        ->inRandomOrder()
        ->take(5)
        ->get();

        return R::Success(__('business'), $data);
    }

    public function Customer($id)
    {
        $data = Customer::with('User.Review.Listing.User')
        ->where('id', $id)
        ->get();

        return R::Success(__('Customer'), $data);
    }

    
    Public function TutorAvgRating($tutor_id = null)
    {
        if($tutor_id == null ){
            return R::Success(__('No record found'));
        }

        $tutor_rating = ClassFeedback::whereHas('StudentClass', function($q) use ($tutor_id) {
            $q->where('tutor_id', $tutor_id);
        })->avg('student_rating');

        return R::Success(__('Success'), $tutor_rating);        
    }

   
    Public function TutorFeedback($tutor_id){

        $tutor_feedbacks = ClassFeedback::whereHas('StudentClass', function($q) use ($tutor_id) {
            $q->where('tutor_id', $tutor_id);
        })->orderBy('created_at', 'desc')->take(5)->get('student_feedback');

        return R::Success(__('Success'), $tutor_feedbacks);        
    }



    public function TutorSearch()
    {   
        $filters = $this->request->all();
        $user_type = strtolower($filters['user_type']);
       
        $user = User::with('TeacherSubjects.Subject', 'TeachingLocations.District')
        ->withCount('Feedback')
        ->where('user_type',  $user_type)
        ->where('user_type', '!=', 'student');

        if(isset($filters['rating']) && $filters['rating'] > 0){
            $user->where('avg_rating','>=', $filters['rating']);
        }

        if(isset($filters['min_fee']) && $filters['min_fee'] > 0  && isset($filters['max_fee']) && $filters['max_fee'] > 0) {
            $user->whereBetween('fee', [
                $filters['min_fee'], $filters['max_fee']
            ]);
        }

        if (isset($filters['name']) && $filters['name'] != null) {
            
            $user->where('first_name', 'like', '%'.$filters['name'].'%')
            ->orWhere('last_name', 'like', '%'.$filters['name'].'%');
        }
        
        if(isset($filters['user_type'])){
            $user->where('user_type', $user_type)
            ->with($user_type);
        }


         if(isset($filters['level_ids']) && count($filters['level_ids']) > 0) {
            $user->whereHas($filters['user_type'], function($q) use($filters){
                $q->where('level_id',$filters['level_ids']);
            });
        }
        

        if(isset($filters['subject_ids']) && count($filters['subject_ids']) > 0){
            $user->whereHas('TeacherSubjects', function($q) use($filters){
                $q->whereIn('subject_id', $filters['subject_ids'])
                ->where('delete_status','available');
            });
        }

        if(isset($filters['districts_ids']) && count($filters['districts_ids']) > 0){
            $user->whereHas('TeachingLocations', function($q) use($filters){
                $q->whereIn('district_id', $filters['districts_ids'])
                ->where('delete_status','available');
            });
        }

        $validSortBys = ['avg_rating', 'fee'];
        if(isset($filters['sort_by']) && in_array($filters['sort_by'], $validSortBys)) {
            $sortBy = explode(',', $filters['sort_by']);
            
            $user->orderBy($sortBy[0], $sortBy[1])
            ->orderBy('created_at', 'desc');
        } else {
            $user->orderBy('created_at', 'desc');
        }

        $perPage = 25;
        $featured = [];
        if ($this->request->has('page') && $this->request->page == 1) {
            $featured = clone($user);
            $featured = $featured->where('featured', 1)
            ->take(3)
            ->get();
        }

        $data = $user->paginate($perPage);
        $results['featured'] = $featured;
        $results['other'] = $data;
        return R::Success('all tutors' , $results);
    }

    public function UserMembership()
    {
        $data = Subscription::with('Membership')
        ->where('user_id', Auth::id())
        ->where('status', 'active')
        ->where('subscription_status', 'active')
        ->first();

        return R::Success('data',$data);
        
    }

    public function UserProfile($id = null)
    {
        if($id == null){
            return R::SimpleError('id is required');
        }   

        $user = User::find($id);
        $data;
        
        if ($user->user_type == 'customer') {
            $data = Customer::with('User')
            ->find($id);
        }

        if ($user->user_type == 'business') {
            $data = Business::with('User')
            ->find($id);
        }

        return R::Success('data' , $data);
    }


    public function ContactUs()
    {
        $data = $this->request->all();
        $v = Validator::make($data, [
            'name' => 'required|string|max:200',
            'email' => 'required|email',
            'contact_no' => 'required|string|max:16',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:200',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $adminEmail = env('adminEmail');

        Mail::to("avir8tor@gmail.com")->queue(new ContactUsEmail($data));
        
        $data = ContactUs::create($data);

        return R::Success(__('Request sent successfully')); 
    }


    public function ProfilePicture($id)
    {
        $path = storage_path("app/images/profile-images/$id");

        if (!File::exists($path)) {
            $path = public_path("images/avatars/no-image.jpg");
        }
        
        $file = File::get($path);
        $type = File::mimeType($path);

        $response = \Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    }

    public function BusinessCategoryImage($id)
    {
        $path = storage_path("app/images/business_category-images/$id");

        if (!File::exists($path)) {
            $path = public_path("images/avatars/no-image.jpg");
        }
        
        $file = File::get($path);
        $type = File::mimeType($path);

        $response = \Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    }

    public function MembershipImage($id)
    {
        $path = storage_path("app/images/membership-images/$id");

        if (!File::exists($path)) {
            $path = public_path("images/avatars/no-image.jpg");
        }
        
        $file = File::get($path);
        $type = File::mimeType($path);

        $response = \Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    }



    public function FearturedUsers()
    {
       $users = [];

       $users['tutors'] = User::with(
            'Tutor.District',
            'Tutor.District', 
            'Tutor.TeacherSubjects.Subject'
        )
       ->where('user_type', 'tutor')
       ->where('featured', 1)
       ->orderBy('last_shown', 'asc')
       ->take(3)
       ->get();

        $users['playgroup'] = User::with(
            'Playgroup.District',
            'Playgroup.District',
            'Playgroup.TeacherSubjects.Subject'
        )
        ->where('user_type', 'playgroup')
        ->where('featured', 1)
        ->orderBy('last_shown', 'asc')
        ->take(3)
        ->get();

        $users['center'] = User::with(
            'Center.District',
            'Center.District',
            'Center.TeacherSubjects.Subject'
        )
        ->where('user_type', 'center')
        ->where('featured', 1)
        ->orderBy('last_shown', 'asc')
        ->take(3)
        ->get();
            
        $tutor = collect($users['tutors'])->pluck('id')->toArray();
        $playgroup = collect($users['playgroup'])->pluck('id')->toArray();
        $center = collect($users['center'])->pluck('id')->toArray();

        $ids = array_merge($tutor, $playgroup, $center);
        
        User::whereIn('id',$ids)
        ->update(['last_shown' => Carbon::now()]);
        
        return R::Success('data',$users);
    }

    public function RegionsDistricts() {
    	$list = DB::table('regions as r')
    	->join('districts as d', 'r.id', '=', 'd.region_id')
        ->where('r.status' , 'Active')
        ->where('d.status' , 'Active')
        ->where('r.delete_status', 'available')
        ->where('d.delete_status', 'available')
        ->selectRaw("d.id as id,
        	CONCAT(r.name_en, ' - ', d.name_en) as name_en,
        	CONCAT(r.name_ch, ' - ', d.name_ch) as name_ch")
        ->get();

        return R::Success('Region & Districts' , $list);
    }

    public function HomePageItems()
    {
        $data['businesses'] = Business::with([
            'User',
            'BusinessReviews',
            'BusinessCategory'
        ])->whereHas('User', function($q) {
            $q->where('delete_status', 'available');
        })
        ->where('delete_status', 'available')
        ->inRandomOrder()
        ->take(8)
        ->get();

        $data['listings'] = Listing::with('ListingImages', 'User.Business.BusinessCategory','Review')
        ->where('status', 'active')
        ->where('subscription_status', 'active')
        ->where('delete_status', 'available')
        ->whereHas('User', function($q){
            $q->where('delete_status', 'available');
        })
        ->inRandomOrder()
        ->take(8)
        ->get();

        $data['business_categories'] = BusinessCategory::where('delete_status', 'available')
        ->inRandomOrder()
        ->take(6)
        ->get();

        $data['business_reviews'] = BusinessReview::with('User.Customer', 'Business.Business')
        ->where('status', 'active')
        ->orderBy('created_at', 'desc')
        ->take(6)
        ->get();

        $data['homepage_banners'] = HomepageBanner::where('status', 'active')
        ->where('start_date', '<=', Carbon::now())
        ->where('end_date', '>=', Carbon::now())
        ->where('payment_status', 'paid')
        ->inRandomOrder()
        ->take(20)
        ->get();

        return R::Success('Home page items' , $data);
    }

    public function HomepageBanners()
   {
       $homepage_banners_list = HomepageBanner::where('status', 'active')
       ->where('payment_status', 'paid')
       ->get();

       return R::Success('homepage banners list' , $homepage_banners_list);
   }

   public function HomepageBannerImage($id)
    {
        $path = storage_path("app/images/banner-images/$id");

        if (!File::exists($path)) {
            $path = public_path("images/avatars/no-image.jpg");
        }
        
        $file = File::get($path);
        $type = File::mimeType($path);

        $response = \Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    }

     public function ClaimBusiness()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'email' => 'required|email|max:100|unique:users,email',
            'name' => 'required|string|max:100',
            'contact_no' => 'required|string|max:15',
            "business_id" =>'required|string|max:100'
        ], [
            'email.unique' => 'This email is already associated with some other account'
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $check  = ClaimBusiness::where('email', $inputs['email'])
        ->where('status', '=', 'approved')
        ->where('payment_status', 'paid')
        ->count();

        if($check > 0){
            return R::SimpleError('You have already claimed this business');
        }
            
        if ($inputs['business_price'] > 0) {
            
            $inputs['payment_status'] = 'in_progress';
            $id = ClaimBusiness::insertGetId($inputs);
            
            $options = [
                'process-url' => env('APP_URL').'/api/public/claim-business-payment-status/'.$id,
                'cancel-url' => env('APP_URL').'/api/public/claim-business-payment-cancel/'.$id,
            ];

            $url = $this->paypal->definePayment($inputs['business_price'], 'USD', $options);
            return R::Success('Send successfully', $url); 
        }

        ClaimBusiness::create($inputs);

        return R::Success('Claim Request Submitted');
       
    }

    public function TestResp()
    {
        $data = $this->request->all();
        return $data;
    }
    
}