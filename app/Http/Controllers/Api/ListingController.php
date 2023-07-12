<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Intervention\Image\ImageManagerStatic as Image;

use Validator, Auth, DB, Gate, File, Mail, Hash, Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\Response as R;
use App\Helpers\Helper;
use \Carbon\Carbon;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Subscription;

use \App\User;

class ListingController extends Controller
{  
   

    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }

    public function Listings()
    {   
        $filters = $this->request->all();

        $data = Listing::with('ListingImages', 'User.Business.BusinessCategory','Review')
        ->where('status', 'active')
        ->where('subscription_status', 'active')
        ->where('delete_status', 'available')
        ->whereHas('User', function($q){
            $q->where('delete_status', 'available');
        });

        if(isset($filters['rating'])  &&  $filters['rating'] > 0){
            $data->where('avg_rating', '>=', $filters['rating']);
        }

        if(isset($filters['user_id'])  &&  $filters['user_id'] > 0){
            $data->where('user_id', $filters['user_id']);
        }

        if(isset($filters['title'])){
            $data->where('title', 'like', '%'.$filters['title'].'%');
        }

        if(isset($filters['business_name'])){
            $data->whereHas('User', function($q) use($filters){
                $q->where('first_name', 'like', '%'.$filters['business_name'].'%');
            });
        }
        
        $data = $data->paginate(10);
        
        return R::Success('data', $data);
    }

    public function ChangeListingStatus()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs,[
            'id' => 'required|integer|exists:listings,id',
            'subscription_status' => 'required|in:active,inactive',
        ]);

        if ($v->fails()) {
            return R::ValidationError($v->errors());
        }

        if($inputs['subscription_status'] == 'inactive'){
            Listing::find($inputs['id'])
            ->update(['subscription_status' => 'inactive']);
            return R::Success('status updated successfully');
        }

        $allowActiveListing = Subscription::where('user_id', Auth::id())
        ->where('subscription_status', 'active')
        ->first();

        $activeListing = Listing::where('user_id',Auth::id())
        ->where('delete_status', 'available')
        ->where('subscription_status', 'active')
        ->where('status', 'active')
        ->count();

        if ($allowActiveListing== null) {
            return R::SimpleError('Your dont have any active  Subscription');
        }

        if($allowActiveListing->allow_listing == $activeListing){
            return R::SimpleError('Your membership listing limt is'.$allowActiveListing->allow_listing);
        }

        DB::beginTransaction();
        try {
            
            Listing::find($inputs['id'])
            ->update(['subscription_status' => 'active']);
            
           $user =  User::find(Auth::id());
           if ($user->allow_listing != 0) {
               $user->update(['allow_listing', $user->allow_listing-1]);
           }

            DB::commit(); 
        } catch (Exception $e) {
            DB::rollback();
            return $e;
        }

        return R::Success('activated successfully');

    }

    public function MyListings()
    {   
        $filters = $this->request->all();

        $data = Listing::with('ListingImages', 'User.Business')
        ->where('user_id', Auth::id())
        ->where('status', 'active')
        ->where('delete_status', 'available');

        $data = $data->paginate(10);
        
        return R::Success('data', $data);
    }

    public function ListingDetail()
    {   
        $inputs = $this->request->all();
        $v = Validator::make($inputs, [
            'id' => 'required|integer',
        ]);
        
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $data = Listing::with('ListingImages', 'User.Business', 'Review.User')
        ->where('id', $inputs['id'])
        ->where('delete_status', 'available')
        ->first();

        return R::Success('data', $data);
    }
   

    public function AllowListing()
    {
        $allowListing = User::find(Auth::id());

        if($allowListing->allow_listing <= 0){
            return R::SimpleError('You are out of maximum Listing Limit');
        }

        return R::Success('max listing allow', $allowListing->allow_listing);
    }
    public function PreListing()
    {
        // $checkSubscription = Subscription::where('user_id', Auth::id())
        // ->whereDate('end_date', '>', now())
        // ->first();

        // $listing = Listing::where('user_id', Auth::id())
        // ->count();

        // if($checkSubscription == null || $checkSubscription->allow_listing == $listing){
        //     return R::SimpleError('Your are Out of listing Limit');
        // }

        $id = Listing::insertGetId(['status' => 'inactive', 'user_id' => Auth::id()]);
        return R::Success('id', $id);
    }

    public function SaveListingImage()
    {   
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'listing_id' => 'required|integer',
            'image' => 'required|image',
        ]);
        
        if($v->fails()){
            return R::ValidationError($v->errors());
        }
        $data = $this->request->except('image');
        

        $id = ListingImage::insertGetId($data);

        if($this->request->hasFile('image')){
            $file = $this->request->file('image');
            $result = $file->storeAs('images/listing-images/',$id);
        }

        return R::Success('Image saved successfully', $id);//image save successfully
    }

    public function UpdateListingImage()
    {   
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'id' => 'required|integer',
            'image' => 'required|image',
            
        ]);
        
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $check_image = ListingImage::find($inputs['id']);
       
        if($this->request->hasFile('image') && $check_image != null){
            $file = $this->request->file('image');
            $result = $file->storeAs('images/listing-images/',$inputs['id']);

            return R::Success(__('Image updated successfully'), $inputs['id']);
        }
        
        return R::SimpleError(__('Some Error !!'));
    }

    public function ListingImage($id)
    {
        $path = storage_path("app/images/listing-images/$id");

        if (!File::exists($path)) {
            $path = public_path("images/avatars/no-image.jpg");
        }
        
        $file = File::get($path);
        $type = File::mimeType($path);

        $response = \Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    }

    public function ListingSmallThumbnail($id)
    {
        $path = storage_path("app/images/thumbnail-small/$id");

        if (!File::exists($path)) {
            $path = public_path("images/avatars/no-image.jpg");
        }
        
        $file = File::get($path);
        $type = File::mimeType($path);

        $response = \Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    }

    public function ListingLargeThumbnail($id)
    {
        $path = storage_path("app/images/thumbnail-large/$id");

        if (!File::exists($path)) {
            $path = public_path("images/avatars/no-image.jpg");
        }
        
        $file = File::get($path);
        $type = File::mimeType($path);

        $response = \Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    }

    public function UpdateListing()
    {
        $inputs = $this->request->except('listing_thumbnail');
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
            'title' => 'required|max:255|string',
            'description' => 'required|max:1000|string',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $id = $inputs['id'];

        $inputs['status'] = 'active';

        $resp  = Listing::find($id)
        ->update($inputs);

        if ($resp == true) {
            $user = User::find(Auth::id());
            $allow_listing = $user->allow_listing - 1;
            $user->update(['allow_listing' => $allow_listing]); 
        }

        if($this->request->hasFile('listing_thumbnail')){
            $thumbnailFile = $this->request->file('listing_thumbnail');
            $imageFile = Image::make($thumbnailFile)->encode('jpg', 100);

            $small = $imageFile->resize(240, 200);
            $small->save(storage_path("app/images/thumbnail-small/$id"), 90, 'jpg');

            // $large = $imageFile->resize(500, 500);
            $imageFile->save(storage_path("app/images/thumbnail-large/$id"), 90, 'jpg');
       }
       
        return R::Success(__('Updated successfully'), $inputs['id']);
    }

    public function SaveListing()
    {
        $inputs = $this->request->except('listing_thumbnail');
        $v = Validator::make($inputs , [
            'title' => 'required|max:255|string',
            'description' => 'required|max:1000|string',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }
        

        $id = Listing::insertGetId($inputs);

        if($this->request->hasFile('listing_thumbnail')){
            $thumbnailFile = $this->request->file('listing_thumbnail');
            $imageFile = Image::make($thumbnailFile)->encode('jpg', 100);

            $small = $imageFile->resize(250, 250);
            $small->save(storage_path("app/images/thumbnail-small/$id"), 90, 'jpg');

            $large = $imageFile->resize(500, 500);
            $large->save(storage_path("app/images/thumbnail-large/$id"), 90, 'jpg');
       }
       
        return R::Success(__('Saved Successfully'), $id);
    }

    public function UpdateThumbnail()
    {   
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
            'listing_thumbnail' => 'required|image',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }
         
        $id = $inputs['id'];

        if($this->request->hasFile('listing_thumbnail')){
            $thumbnailFile = $this->request->file('listing_thumbnail');
            $imageFile = Image::make($thumbnailFile)->encode('jpg', 100);

            $small = $imageFile->resize(250, 250);
            $small->save(storage_path("app/images/thumbnail-small/$id"), 90, 'jpg');

            $large = $imageFile->resize(500, 500);
            $large->save(storage_path("app/images/thumbnail-large/$id"), 90, 'jpg');

            return R::Success(__('update image successfully'));
        }
       
    }

    public function DeleteListingImage()
    {       
       $inputs = $this->request->all();
        $v = Validator::make($inputs, [
            'id' => 'required|integer',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $file = ListingImage::find($inputs['id']);
            
        $path = storage_path('app/images/listing-images/'.$inputs['id']);

        if (File::exists($path) && $file != null) {

            Storage::delete('images/listing-images/'.$inputs['id']);
            $file->delete();  
            
            return R::Success(__('Deleted Successfully'));
        }

        return R::SimpleError(__('Some Error !!'));
    }


    public function DeleteListing()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|numeric',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $checkMembership = Subscription::where('user_id', Auth::id())
        ->where('subscription_status', 'active')
        ->first();

        if($checkMembership != null){
            $user = User::find(Auth::id());

            $user->update(['allow_listing' => $user->allow_listing + 1]);

        }

        $listing  = Listing::where('id' , $inputs['id'])
        ->update(['delete_status' => 'deleted']);
        return R::Success(__('Deleted Successfully'));
    }

    public function ProductComplain()
    {
        $inputs = $this->request->except('product_thumbnail');
        $v = Validator::make($inputs , [
            'product_url' => 'required|max:150|string',
            'seller_profile_url' => 'required|max:150|string',
            'title' => 'required|max:100|string',
            'issue_detail' => 'required|max:1000|string',
            'date_of_purchase' => 'required',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $seller_profile_url = explode('/', $inputs['seller_profile_url']);

        $seller_id_index = count($seller_profile_url)- 1;

        $seller_id = $seller_profile_url[$seller_id_index];

        $product_url = explode('/', $inputs['product_url']);

        $product_id_index = count($product_url)- 1;

        $product_id = $product_url[$product_id_index];
        
        
        $inputs['user_id'] = Auth::id();
        $inputs['product_id'] = $product_id;
        $inputs['seller_id'] = $seller_id;
        
        $id = ProductComplain::insertGetId($inputs);

        return R::Success('Your complain submit Successfully');
    }

    public function ProductComplains()
    {
        $filters = $this->request->all();
        $data = ProductComplain::with('User', 'Product.Seller');
        if(isset($filters['created_at']) &&  $filters['created_at'] != ''){
           
            $data->whereDate('created_at', $filters['created_at']);

        }
        $data = $data->get();

        return R::Success('data', $data);
    }

    public function Pay()
    {
        MercadoPago\SDK::setAccessToken("MP_ACCESS_TOKEN");

        $payment = new MercadoPago\Payment();
        $payment->transaction_amount = 100;
        $payment->token = "61897fddca3f1ad2fd23ac28ae87fe76";
        $payment->description = "Ergonomic Silk Shirt";
        $payment->installments = 1;
        $payment->payment_method_id = "visa";
        $payment->payer = array(
            "email" => "test_user_27957448@testuser.com"
        );

        $payment->save();
        
        return R::Success(__('Payment Requent Processed Successfully!'), $payment);
    }

    public function UploadQualityCertificate()
    {   
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'id' => 'required|integer',
            'file' => 'required',
            
        ]);
        
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        if($this->request->hasFile('file')){
            
            Product::find($inputs['id'])
            ->update(['quality_certificate' => 1]); 

            $file = $this->request->file('file');
            $result = $file->storeAs('images/quality-certificates/',$inputs['id']);

            return R::Success(__('file Save Successfully'), $inputs['id']);
        }
    }

    public function DownloadQualityCertificate($id)
    {
        $path = storage_path("app/images/quality-certificates/$id");

        if (!File::exists($path)) {
            $path = public_path("images/avatars/no-image.jpg");
        }
        
        $file = File::get($path);
        $type = File::mimeType($path);

        $response = \Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    }

     public function DeleteQualityCertificate()
    {       
       $inputs = $this->request->all();
        $v = Validator::make($inputs, [
            'id' => 'required|integer',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }
            
        $path = storage_path('app/images/quality-certificates/'.$inputs['id']);

        if (File::exists($path)) {

            Storage::delete('images/quality-certificates/'.$inputs['id']);
            
            Product::find($inputs['id'])
            ->update(['quality_certificate' => 0]);  
            
            return R::Success(__('Delete Successfully'));
        }

        return R::SimpleError(__('Some Error !!'));
    }
}