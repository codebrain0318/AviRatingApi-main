<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Validator, Auth, DB, Gate, File, Mail, Hash,Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\Response as R;
use App\Helpers\Helper;
use \Carbon\Carbon;
use App\Models\EmailVerification;
use App\Models\ClaimRequest;
use \App\User;
use JWTAuth;
use App\Mail\AccountVerificationEmail;

class RequestsController extends Controller
{
    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }

    public function ClaimRequest()
    {   
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'request_description' => 'required|string|max:500',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
         }

        $inputs['user_id'] = Auth::id();
        ClaimRequest::create($inputs);

        return R::Success(__('Claim request send to Admin for Approval'));
    }

    public function ClaimRequests()
    {   
        $filters = $this->request->all();

        $list = ClaimRequest::with('User.Business');

        if(isset($filters['approval_status'])){
            $list->where('approval_status', $filters['approval_status']);
        }

        $data = $list->get();

        return R::Success('list', $data);
    }

    public function ApproveClaimRequest()
    {

        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $data = ClaimRequest::find($inputs['id']);

        $data->update(['approval_status' => 'approved']);

        return R::Success(__('Request has been approved'));
    }

    public function RejectClaimRequest()
    {

        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
            'response_note' => 'required|string|max:500',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $data = ClaimRequest::find($inputs['id']);

        $inputs['approval_status'] = 'rejected';

        $data->update($inputs);

        return R::Success(__('Request rejected'));
    }

}