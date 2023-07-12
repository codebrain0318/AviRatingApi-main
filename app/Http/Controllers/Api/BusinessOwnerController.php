<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Intervention\Image\ImageManagerStatic as Image;
use MercadoPago;

use Validator, Auth, DB, Gate, File, Mail, Hash, Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\Response as R;
use App\Helpers\Helper;
use \Carbon\Carbon;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Business;
use App\Models\ClaimRequest;
use App\Models\Setting;
use App\Models\HomepageBanner;
use App\Models\Membership;
use App\Models\BusinessOwnerReply;
use App\Models\ReviewReplyPrice;
use App\Models\Subscription;
use \App\User;
use App\Helpers\Paypal;

class BusinessOwnerController extends Controller
{  
   
    public function __construct(Request $request, Helper $helper, Paypal $paypal)
    {        
        $this->request = $request;
        $this->helper = $helper;
        $this->paypal = $paypal;
    }

    public function BusinessesList()
    {
        $filters = $this->request->all();

        $data = Business::with([
            'User',
            'BusinessServices.Service',
            'BusinessCategory',
            'BusinessType',
        ])->whereHas('User', function($q) {
            $q->where('delete_status', 'available');
        });

        if(isset($filters['airport_id']) && $filters['airport_id'] != "null"){

            $data->where('airport_id','like', '%'.$filters['airport_id'].'%');
        }

        if(isset($filters['state']) && $filters['state'] != 'null'){

            $data->where('state', 'like', '%'.$filters['state'].'%');
        }

        if(isset($filters['city']) && $filters['city'] != "null"){

            $data->where('city', 'like', '%'.$filters['city'].'%');
        }

        if(isset($filters['rating'])  &&  $filters['rating'] > 0){
            $data->whereHas('User', function($q) use($filters){
                $q->where('avg_rating', '>=', $filters['rating']);
            });
        }

        if(isset($filters['business_name']) && $filters['business_name'] != 'null'){
            $data->whereHas('User', function($q) use($filters){
                $q->where('first_name', 'like', '%'.$filters['business_name'].'%');
            });
        }

        if(isset($filters['zip_code']) && $filters['zip_code'] != 'null'){
            $data->where('zip_code', $filters['zip_code']);
        }

        if(isset($filters['cat_name']) && $filters['cat_name'] != 'null'){
            $data->whereHas('BusinessCategory', function($q) use($filters){
                $q->where('full_name', 'like', '%'.$filters['cat_name'].'%');
            });
        }

        if(isset($filters['lat']) && isset($filters['lng']) &&
            ($filters['lat'] != 'null') && ($filters['lat'] != 'null')) {
            $lat = $filters['lat'];
            $lng = $filters['lng'];
            $radius = $filters['radius'];
            $kmCenter = 6371;
            $milesCenter = 3959;
            // Haversine Formula
            $data->whereNotNull('lat')->whereNotNull('lng');
            // $distanceQuery = "$milesCenter * 2 * ASIN(SQRT(POWER(SIN(($lat - abs(lat)) * pi()/180 / 2), 2) + COS($lat * pi()/180 ) * COS(abs(lat) * pi()/180) * POWER(SIN(($lng - lng) * pi()/180 / 2), 2) ))";
            $distanceQuery = "( $milesCenter * acos( cos( radians($lat) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians($lng) ) + sin( radians($lat) ) * sin(radians(lat)) ) )";
            $data->selectRaw("*, $distanceQuery as distance")
            ->whereRaw("$distanceQuery <= $radius");
        }
        
        $data = $data
        ->where('delete_status', 'available')
        ->paginate(10);
        
        return R::Success('data', $data);
    }

    public function ClaimBusiness()
    {   
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'claim_description' => 'required|string|max:500',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
         }

        $inputs['user_id'] = Auth::id();
        ClaimRequest::create($inputs);

        User::find($inputs['user_id'])
        ->update(['claim_business' => 2]);

        return R::Success(__('Claim send to Admin for Approval'));
    }

    //crud of Homepage Banners Start
   public function MyHomepageBanners()
   {
        $homepage_banners_list = HomepageBanner::where('user_id', Auth::id())
        ->where('payment_status', 'paid')
        ->paginate(10);

        return R::Success('homepage banners list' , $homepage_banners_list);
   }

   public function AddHomepageBanner()
   {
        $inputs = $this->request->all();

        $v = Validator::make($inputs , [
           'banner_link' => 'required|max:250|string',
           'title' => 'required|max:150|string',
           'description' => 'nullable|max:500|string',
           'start_date' => 'required|date',
           'end_date' => 'required|date|after:start_date',
        ]);

        if($v->fails()){
           return R::ValidationError($v->errors());
        }

        $settings = Setting::where('home_banner_price', '>', 0)
        ->where('id', '>', 0)
        ->first();

        if ($settings == NULL) {
          return R::SimpleError('Some error');
        }

        $startDate = Carbon::createFromFormat('Y-m-d', $inputs['start_date']);
        $endDate = Carbon::createFromFormat('Y-m-d', $inputs['end_date']);
        $daysDiff = $endDate->diffInDays($startDate);

        $totalPrice = $settings->home_banner_price * $daysDiff;


        $banner_data = [
            'banner_link' => $inputs['banner_link'],
            'title' => $inputs['title'],
            'user_id' => Auth::id(),
            'price' => $totalPrice,
            'description' => @$inputs['description'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'payment_status' => 'unpaid',
            'status' => 'inactive',
        ];

        $banner_id = HomepageBanner::insertGetId($banner_data);

        if($this->request->hasFile('banner_image')){
            $file = $this->request->file('banner_image');
            $result = $file->storeAs('images/banner-images/', $banner_id);
        }

        $options = [
            'process-url' => env('APP_URL').'/api/business/banner-payment-status/'.$banner_id,
            'cancel-url' => env('APP_URL').'/api/business/banner-payment-cancel/'.$banner_id,
        ];

        $url = $this->paypal->definePayment($totalPrice, 'USD', $options);

        return R::Success('Redirect URL', $url);
   }

   public function EditHomepageBanner()
   {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
            'banner_link' => 'required|max:250|string',
            'title' => 'required|max:150|string',
            'description' => 'nullable|max:500|string',
            'start_date' => 'required|date|after_or_equal:now',
            'end_date' => 'required|date|after:start_date',
        ]);

        if($v->fails()){
           return R::ValidationError($v->errors());
        }

        $banner_data = [
            'id' => $inputs['id'],
            'banner_link' => $inputs['banner_link'],
            'title' => $inputs['title'],
            'description' => @$inputs['description'],
            'start_date' => $inputs['start_date'],
            'end_date' => $inputs['end_date'],
        ];

        $id = $inputs['id'];

        $homepage_banner  = HomepageBanner::find($inputs['id'])
        ->update($banner_data);

        if($this->request->hasFile('banner_image')){
            $file = $this->request->file('banner_image');
            $result = $file->storeAs('images/banner-images/',$id);
        }
        return R::Success('Updated successfully', $inputs['id']);
    }
   
    public function DeleteHomepageBanner()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
           'id' => 'required|integer',
        ]);

        if($v->fails()){
           return R::ValidationError($v->errors());
        }

        $homepage_banner  = HomepageBanner::find($inputs['id'])
        ->delete();

        return R::Success('Deleted successfully');
    }

    public function HomepageBannerStatus()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs , [
           'id' => 'required|integer',
        ]);

        if($v->fails()){
           return R::ValidationError($v->errors());
        }

        $data = HomepageBanner::find($inputs['id']);

        if ($data->status == 'active') {
            $data->update(['status' => 'inactive']);
        }else {
            $data->update(['status' => 'active']);
        }

        return R::Success('Status changed successfully', $data);
    }
   //crud of Homepage Banners end

    public function ActiveMemberShips()
    {   
        $checkUserMembership = Subscription::where('user_id', Auth::id())
        ->where('subscription_status', 'active')
        ->first();

        $data = Membership::where('delete_status','available')
        ->where('status', 'active');


        if ($checkUserMembership != null) {
            $data->where('id', '>', 1 );
        }

        $data = $data->get();
        return R::Success('data',$data);
    }
    public function FreeSubscription()
    {   
        $checkFreeMembership = Subscription::where('user_id', Auth::id())
        ->where('subscription_status', 'active')
        ->where('membership_id', 1)
        ->first();

        if ($checkFreeMembership != null) {
            return R::SimpleError('You already subscribed free membership');
        }

        Subscription::create(
            [
                'user_id' => Auth::id(),
                'membership_id' => 1,
                'allow_listing' => 2,
                'price' =>  0.0,
                'subscription_status' => 'active',

            ]
        );

        User::find(Auth::id())
        ->update(['allow_listing' => 2]);

        return R::Success('Free subscription Activated');
        
    }

    public function ReviewReplyCount()
    {
        $businessReply = BusinessOwnerReply::where('user_id', Auth::id())
        ->where('payment_status', 'paid')
        ->where('status', 'active')
        ->first();

        if (!empty($businessReply) && $businessReply != null) {
            if ($businessReply->used_replies < $businessReply->max_replies) {
                return R::Success('your Have reply limit', );
            }else{
                $businessReply->delete();
                return R::SimpleError('Buy the reply Reviews');
            }
        }
        return R::SimpleError('Buy the reply Reviews');
    }

    public function BuyReviewReply()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs , [
           'id' => 'required|integer',
        ]);

        if($v->fails()){
           return R::ValidationError($v->errors());
        }

        $businessReply = BusinessOwnerReply::where('user_id', Auth::id())
        ->where('payment_status', 'paid')
        ->where('status', 'active')
        ->first();

        if (!empty($businessReply) && $businessReply != null) {
            if ($businessReply->used_replies < $businessReply->max_replies) {
                return R::SimpleError('You have not yet reached your replies limit.');
            }else{
                $businessReply->delete();
            }
        }

        $reviewReplyPrice = ReviewReplyPrice::find($inputs['id']);
        $totalPrice = $reviewReplyPrice->price;

        $businessReplyData = [
            'max_replies' => $reviewReplyPrice->no_of_replies,
            'user_id' => Auth::id(),
            'purchase_amount' => $totalPrice,
        ];

        $businessReplyId = BusinessOwnerReply::insertGetId($businessReplyData);

        $options = [
            'process-url' => env('APP_URL').'/api/business/reply-payment-status/'.$businessReplyId,
            'cancel-url' => env('APP_URL').'/api/business/reply-payment-cancel/'.$businessReplyId,
        ];

        $url = $this->paypal->definePayment($totalPrice, 'USD', $options);

        return R::Success('Redirect URL', $url);
   }
}