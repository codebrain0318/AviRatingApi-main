<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Validator, Auth, DB, Gate, File, Mail, Hash,Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\Response as R;
use App\Helpers\Helper;
use \Carbon\Carbon;
use App\Models\BusinessReview;
use App\Models\ReviewImage;

use App\Models\Listing;
use App\Models\BusinessOwnerReply;

use \App\User;

class BusinessReviewController extends Controller
{
    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }

    public function PreReview()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'business_id' => 'required|integer',
            
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $businessReviewIds = BusinessReview::whereDate('created_at', '<=', now()->subDays(1))
        ->where('status', 'inactive')
        ->pluck('id');

        $reviewImagesIds = ReviewImage::whereIn('business_review_id', $businessReviewIds)
        ->pluck('id');

        foreach ($reviewImagesIds as $id) {
            Storage::delete('images/review-images/'.$id);
        }

        BusinessReview::whereIn('id', $businessReviewIds)
        ->delete();
        ReviewImage::whereIn('id', $reviewImagesIds)
        ->delete();

        $id = BusinessReview::insertGetId($inputs);
        return R::Success('id', $id);
    }

    public function SaveBusinessReview()
    {    
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'business_id' => 'required|integer',
            'rating' => 'required|numeric|max:5',
            'feedback' => 'required|max:1200',

        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        DB::beginTransaction();
        try {

            $inputs['user_id'] = Auth::id();
            $inputs['status'] = 'active';
            BusinessReview::create($inputs);

            $avg_rating = BusinessReview::where('business_id', $inputs['business_id'])
            ->where('status', 'active')
            ->avg('rating');
            
            User::find($inputs['business_id'])
            ->update(['avg_rating' => $avg_rating]);

            DB::commit();
            return R::Success(__('Review saved successfully'));
            
        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            return R::SimpleError(__('Some Error !!'));
        }
    }

    public function UpdateBusinessReview()
    {    
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'id' => 'required|integer',    
            // 'business_id' => 'required|integer',
            'rating' => 'required|not_in:0|numeric|max:5',
            'feedback' => 'required|max:1200',

        ],
        [
            'rating.not_in' => 'Please select rating',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        DB::beginTransaction();
        try {

            $inputs['user_id'] = Auth::id();
            $inputs['status'] = 'active';

            BusinessReview::find($inputs['id'])
            ->update($inputs);

            $avg_rating = BusinessReview::where('business_id', $inputs['business_id'])
            ->where('status', 'active')
            ->avg('rating');
            
            User::find($inputs['business_id'])
            ->update(['avg_rating' => $avg_rating]);

            if($this->request->hasFile('image')){
                $file = $this->request->file('image');
                $result = $file->storeAs('images/review-images',$id);
            }

            DB::commit();
            return R::Success(__('Review saved successfully'));
            
        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            return R::SimpleError(__('Some Error !!'));
        }
    }

    public function SaveReviewImage()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'business_review_id' => 'required|integer', 
            'image' => 'required|image',

        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        unset($inputs['image']);

        $id = ReviewImage::insertGetId($inputs);

        if($this->request->hasFile('image')){
            $file = $this->request->file('image');
            $result = $file->storeAs('images/review-images',$id);
        }

        return R::Success('image save successfully',$id);
    }

    public function DeleteReviewImage()
    {
       $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'id' => 'required|integer',  
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        ReviewImage::find($inputs['id'])
        ->delete();

        Storage::delete('images/review-images/'.$inputs['id']);

        return R::Success('deleted successfully');
    }

    public function ReviewImage($id)
    {
        $path = storage_path("app/images/review-images/$id");

        if (!File::exists($path)) {
            $path = public_path("images/avatars/no-image.jpg");
        }
        
        $file = File::get($path);
        $type = File::mimeType($path);

        $response = \Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    } 

    public function BusinessReviews($id)
    {
       //$id = $this->request->id;
        
        $data = BusinessReview::with('User.Customer', 'ReviewImages', 'ReplyImages')
        ->where('status', 'active')
        ->where('business_id', $id)
        ->paginate(3);
        
        return R::Success('Review', $data);
    }    

    public function BusinessesReviews()
    {
        $perPage = 3;
        $filters = $this->request->all();
        $reviews = BusinessReview::with('Business')
        ->where('status', 'active');
        
        if(isset($filters['date'])){
            $reviews->whereDate('created_at', $filters['date']);
        }

        if(isset($filters['first_name'])){
            $reviews->where('name', 'like', '%'.$filters['name']);
        }

        $reviews =  $reviews->orderBy('id', 'desc')
        ->paginate($perPage);

        return R::Success('list' , $reviews);
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

        $reviewImagesIds = ReviewImage::where('business_review_id', $inputs['id'])
        ->pluck('id');

        foreach ($reviewImagesIds as $id) {
            Storage::delete('images/review-images/'.$id);
        }

        ReviewImage::where('business_review_id', $inputs['id'])
        ->delete();
        BusinessReview::find($inputs['id'])
        ->delete();
        
        return R::Success(__('Review deleted successfully'));
    }

    public function MyReviews()
    {
        $data = BusinessReview::with('Business.User')
        ->where('status', 'active')
        ->where('user_id', Auth::id())
        ->paginate(10);

        return R::Success('data', $data);
    }

    public function ReplyReview()
    {
        $inputs = $this->request->all();
        
        $v = Validator::make($inputs, [
            'reply' => 'required|string',
            'id' => 'required|integer', 
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $businessReply = BusinessOwnerReply::where('user_id', Auth::id())
        ->where('payment_status', 'paid')
        ->where('status', 'active')
        ->first();

        if (empty($businessReply) && $businessReply == null) {
            return R::SimpleError('You have to purchase replies in order to reply to any review.');
        }

        if ($businessReply->max_replies == $businessReply->used_replies) {
            return R::SimpleError('You have reached your replies limit, please purchase more replies to continue replying.');
        }

        $usedReplies = $businessReply->used_replies+1;
        $businessReply->update(['used_replies' => $usedReplies]);

        $inputs['reply_date'] = Carbon::now();

        $data = BusinessReview::find($inputs['id'])
        ->update($inputs);

        return R::Success('Replied successfully', $inputs);
    }

    public function BusinessOwnerReviews()
    {
        $data = BusinessReview::with('User.Customer', 'ReviewImages', 'ReplyImages')
        ->where('status', 'active')
        ->where('business_id', Auth::id())
        ->paginate(10);

        return R::Success('data', $data);
    }


    public function UserReviews($id)
    {    
        if($id == null){
            return R::SimpleError('User id is required');
        } 

        $data = BusinessReview::where('user_id', $id)
        ->where('status', 'active')
        ->paginate(10);

        return R::Success('data', $data);
    }
}