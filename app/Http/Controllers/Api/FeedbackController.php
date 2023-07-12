<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Validator, Auth, DB, Gate, File, Mail, Hash,Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\Response as R;
use App\Helpers\Helper;
use \Carbon\Carbon;
use App\Models\Feedback;
use \App\User;

class FeedbackController extends Controller
{
    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }

    public function SaveFeedback()
    {    
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'user_id' => 'required|integer',
            'name' => 'required|string|max:40',
            'rating' => 'required|numeric|max:5',
            'feedback' => 'required|max:1200',

        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        DB::beginTransaction();
        try {

            Feedback::create($inputs);

            $avg_rating = Feedback::where('user_id', $inputs['user_id'])
            ->avg('rating');

            User::find($inputs['user_id'])
            ->update(['avg_rating' => $avg_rating]);

            DB::commit();
            return R::Success(__('Feedaback added successfully'));
            
        } catch (\Exception $e) {
            DB::rollback();
            dd($e);
            return R::SimpleError(__('Some Error !!'));
        }
    }

    public function UserFeedback()
    {
         $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'id' => 'required|integer',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $user_type = User::find($inputs['id']);
        
        $data = User::with(ucfirst($user_type->user_type), 'Feedback')
        ->where('id', $inputs['id'])
        ->first();
        
        return R::Success('feedback', $data);
    }    

    public function UserReviews()
    {
    	$perPage = 3;
        $filters = $this->request->all();
        $feedback = Feedback::with('User.Tutor','User.Playgroup','User.Center');
        
        if(isset($filters['from_date'])){
            $feedback->whereDate('created_at', '>=', $filters['from_date']);
        }

        if(isset($filters['to_date'])){
            $feedback->whereDate('created_at', '<=', $filters['to_date']);
        }

        if(isset($filters['name'])){
            $feedback->whereHas('User', function($q) use ($filters){
                $q->where('first_name', 'like', '%'.$filters['name'])
                ->orWhere('last_name', 'like', '%'.$filters['name']);
            });
        }

        $feedback =  $feedback
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
        $review  = Feedback::find($inputs['id'])
        ->delete();
        return R::Success(__('Deleted successfully'));
    }

    public function MyFeedbacks()
    {
        $data = Feedback::where('user_id', Auth::id())
        ->get();

        return R::Success('data', $data);
    }


    public function UserFeedbacks($id)
    {    
        if($id == null){
            return R::SimpleError('User id is required');
        } 

        $data = Feedback::where('user_id', $id)
        ->paginate(10);

        return R::Success('data', $data);
    }
}