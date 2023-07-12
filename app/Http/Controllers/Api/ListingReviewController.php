<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Validator, Auth, DB, Gate, File, Mail, Hash,Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\Response as R;
use App\Helpers\Helper;
use \Carbon\Carbon;
use App\Models\Review;
use App\Models\ReplyImage;
use App\Models\BusinessReview;
use App\Models\Listing;
use \App\User;

class ListingReviewController extends Controller
{
    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }

    public function SaveReview()
    {    
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'listing_id' => 'required|integer|max:40',
            'rating' => 'required|numeric|max:5|not_in:0',
            'feedback' => 'required|max:1200',
        ],[
            'rating.not_in' => 'Please select rating',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        DB::beginTransaction();
        try {

            $inputs['user_id'] = Auth::id();
            Review::create($inputs);

            $avg_rating = Review::where('listing_id', $inputs['listing_id'])
            ->avg('rating');



            $listing = Listing::find($inputs['listing_id']);
            $user_id = $listing->user_id; 
            
            User::find($user_id)
            ->update(['avg_rating' => $avg_rating]);

            $listing->update(['avg_rating' => $avg_rating]);

            DB::commit();
            return R::Success(__('Review saved successfully'));
            
        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            return R::SimpleError(__('Some Error !!'));
        }
    }

    public function ListingReview($id)
    {
       //$id = $this->request->id;
        
        $data = Review::with('User')
        ->where('listing_id', $id)
        ->paginate(10);
        
        return R::Success('Review', $data);
    }    

    public function ListingReviews()
    {
        $perPage = 10;
        $filters = $this->request->all();
        $feedback = Review::with('User', 'Listing.User');
        
        if(isset($filters['from_date']) && isset($filters['to_date'])){
            $feedback->whereBetween('created_at', [$filters['from_date'], $filters['to_date']]);
        }

        if(isset($filters['first_name'])){
            $feedback->where('name', 'like', '%'.$filters['name']);
        }

        $feedback =  $feedback
        ->orderBy('id', 'desc')
        ->paginate($perPage);

        return R::Success('list' , $feedback);
    }

    public function BusinessReviews()
    {
        $perPage = 10;
        $filters = $this->request->all();
        $feedback = BusinessReview::with('User','Business','ReviewImages', 'ReplyImages');
        
        if(isset($filters['from_date']) && isset($filters['to_date'])){
            $feedback->whereBetween('created_at', [$filters['from_date'], $filters['to_date']]);
        }

        

        if(isset($filters['first_name'])){
            $feedback->where('name', 'like', '%'.$filters['name']);
        }

        $feedback =  $feedback->where('status', 'active')
        ->orderBy('id', 'desc')
        ->paginate($perPage);

        return R::Success('list' , $feedback);
    }

    public function DeleteReview()
    {
        
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|numeric',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }
        $review  = Review::find($inputs['id']);
        $listingId = $review->listing_id;
        $review->delete();
        
        $avg_rating = Review::where('listing_id', $listingId)
        ->avg('rating');

        Listing::find($listingId)
        ->update(['avg_rating' => $avg_rating]);

       
        return R::Success(__('Review deleted successfully'));
    }

    public function DeleteBusinessReview()
    {
        
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|numeric',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }
        $review  = BusinessReview::find($inputs['id']);
        $businessId = $review->business_id;
        $review->delete();

        $avg_rating = BusinessReview::where('business_id', $businessId)
        ->avg('rating');

        User::find($businessId)
        ->update(['avg_rating' => $avg_rating]);

        
        return R::Success(__('Review deleted successfully'));
    }

    public function MyReviews()
    {
        $data = Review::with('Listing.User.Business')
        ->whereHas('Listing.User', function($q){
            $q->where('delete_status', 'available');
        })
        ->where('user_id', Auth::id())
        ->paginate(10);

        return R::Success('data', $data);
    }

    public function ListingOwnerReviews()
    {
        $listingIds = Listing::where('user_id', Auth::id())
        ->pluck('id');

        $data = Review::with('Listing.User.Business','User.Customer')
        ->whereHas('Listing.User', function($q){
            $q->where('delete_status', 'available');
        })
        ->whereIn('listing_id', $listingIds)
        ->paginate(10);

        return R::Success('data', $data);
    }

    public function ReplyListingReview()
    {   
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'id' => 'required|integer',
            'reply' => 'required|string',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $inputs['reply_date'] = Carbon::now();

        $data = Review::find($inputs['id'])
        ->update($inputs);

        return R::Success('replied successfully', $inputs);
    }


    public function MyBusinessReview()
    {
        $data = BusinessReview::with('Business', 'ReviewImages', 'ReplyImages')
        ->where('status', 'active')
        ->where('user_id', Auth::id())
        ->paginate(10);

        return R::Success('data', $data);
    }

    public function UserReviews($id)
    {    
        if($id == null){
            return R::SimpleError('User id is required');
        } 

        $data = Review::where('user_id', $id)
        ->paginate(10);

        return R::Success('data', $data);
    }

    public function SaveReplyImage()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'review_id' => 'required|integer', 
            'image' => 'required|image',

        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        unset($inputs['image']);
        $inputs['business_id'] = Auth::id();

        $id = ReplyImage::insertGetId($inputs);

        if($this->request->hasFile('image')){
            $file = $this->request->file('image');
            $result = $file->storeAs('images/reply-images',$id);
        }

        return R::Success('image save successfully',$id);
    }

    public function DeleteReplyImage()
    {
       $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'id' => 'required|integer',  
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        ReplyImage::find($inputs['id'])
        ->delete();

        Storage::delete('images/reply-images/'.$inputs['id']);

        return R::Success('deleted successfully');
    }

    public function ReplyImage($id)
    {
        $path = storage_path("app/images/reply-images/$id");

        if (!File::exists($path)) {
            $path = public_path("images/avatars/no-image.jpg");
        }
        
        $file = File::get($path);
        $type = File::mimeType($path);

        $response = \Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    }
}