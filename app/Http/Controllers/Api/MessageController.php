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
use App\Models\Education;
use App\Models\Subject;
use App\Models\Message;

use \App\User;
use App\Mail\NewMessageEmail;


class MessageController extends Controller
{
    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }

    Public function SendMessage()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs,[
            'to_id' => 'integer|required',
            'description' => 'required|string|max:1200',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $inputs['from_id'] = Auth::id();

        $id = Message::insertGetId($inputs);

        $to_user = User::find($inputs['to_id']);

        $user_id = Auth::id();

        $sender = User::find($user_id);

        Mail::to($to_user->email)
        ->queue(new NewMessageEmail($to_user, $sender));

        return R::Success(__('Message sent successfully'), $id);
    }

    public function messages()
    {
        $messages = Message::with('FromMessages')
        ->where('to_id', Auth::id())
        ->where('delete_status', 'available')
        ->orderBy('id', 'desc')
        ->get();

        $data = [];
        $sender = 0;

        foreach ($messages as $m) {
            if($sender != $m->from_id ){
                $data[] = $m;
                $sender = $m->from_id;
            }      
        }

        return R::Success('message list', $data);
    }

    public function ChatDetail()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs,[
            'user_id' => 'integer|required'
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $messages = Message::with('ToMessages', 'FromMessages')
        ->where('delete_status', 'available')
        ->where([
            ['to_id', Auth::id()],
            ['from_id', $inputs['user_id']]
        ])
        ->orWhere([
            ['from_id', Auth::id()],
            ['to_id', $inputs['user_id']]
        ])
        ->orderBy('created_at', 'asc')
        ->get();
        
        return R::Success("list", $messages);
    }
   
    public function DeleteMessage()
     {
        $inputs = $this->request->all();
        $v = Validator::make($inputs,[
            'ids' => 'required|array',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $ids = $this->request->ids;

        $messages = Message::whereIn('id', $ids)
        ->get();

        foreach ($messages as $message) {
            if ($message->from_id == Auth::id()) {
                $message->delete();
            }else{
                $message->update(['delete_status' => 'deleted']);
            }
        }

        return R::Success(__('Message deleted successfully'));
    }
}