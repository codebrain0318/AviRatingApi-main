<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Validator, Auth, DB, Gate, File, Mail, Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\Response as R;
use App\Helpers\Helper;
use \Carbon\Carbon;
use App\Models\ContactUs;
use App\Models\ReviewReplyPrice;
use App\Models\ServiceCategory;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Review;
use App\Models\Business;
use App\Models\BusinessService;
use App\Models\BusinessAmenity;
use App\Models\BusinessReview;
use App\Models\BusinessCategory;
use App\Models\BusinessType;
use App\Models\Amenity;
use App\Models\Feedback;
use App\Mail\UserPasswordEmail;
use App\Models\FeaturedLog;
use App\Models\HomepageBanner;
use App\Models\ClassesList;
use App\Models\PlaygroupClass;
use App\Models\Membership;
use App\Models\Listing;
use App\Models\Subscription;
use App\Models\ClaimRequest;
use App\Models\Setting;
use App\Models\Payment;
use App\Models\ClaimBusiness;
use App\Models\BusinessSchedule;
use App\Mail\ChangePasswordEmail;
use App\Mail\BusinessApproveEmail;

use \App\User;

class AdminController extends Controller
{  
   

    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }

    public function UserList()
    {   
        $filters = $this->request->all();

        $user = User::with(['Subscription' => function($q){
                    $q->where('status', 'active')
                    ->where('end_date', '>', Carbon::now());
                }, 'Subscription.Membership'])
        ->where('user_type','!=', 'admin')
        ->where('delete_status', 'available');
        
        if(isset($filters['id'])){
            $user->where('id', $filters['id']);
        }

        if(isset($filters['user_type'])){
            $user->where('user_type', $filters['user_type']);
        }

        if (isset($filters['name']) && $filters['name'] != null) {
            
            $user->where('first_name', 'like', '%'.$filters['name'].'%')
            ->orWhere('last_name', 'like', '%'.$filters['name'].'%');
        }

        if(isset($filters['email'])){
            $user->where('email', $filters['email']);
        }

        $data = $user->paginate(10);
        return R::Success('User', $data);
    }

    public function BusinessRequests()
    {
        $status = $this->request->status;
        $data = User::where('user_type', 'business');
       
        if ($status != null) {

            $data->where('admin_approved', $status);
        }

        $data = $data->get();

        return R::Success('data', $data);
    }

    public function ApprovedBusiness()
    {
        
        $inputs = $this->request->all();
        
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        User::find($inputs['id'])
        ->update([
            'admin_approved' => 'approved',
        ]);

        return R::Success('Business approved successfully');
    }

    public function RejectBusiness($value='')
    {
        
        $inputs = $this->request->all();
        
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        User::find($inputs['id'])
        ->update(['admin_approved' => 'rejected']);

        return R::Success('Business Rejected successfully');
    }

    public function DeleteUser()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }
        
        $user = User::find($inputs['id']);
        
        DB::beginTransaction();
        try {

            $user->update(['delete_status' => 'deleted']);

            if($user->user_type == 'customer'){
                Customer::find($user->id)
                ->update(['delete_status' => 'deleted']);
            }

            if($user->user_type == 'business'){
                Business::find($user->id)
                ->delete();
                Listing::where('user_id', $inputs['id'])
                ->delete();
                ClaimBusiness::where('business_id', $inputs['id'])
                ->delete();
                Review::where('user_id', $inputs['id'])
                ->delete();
                BusinessReview::where('user_id', $inputs['id'])
                ->delete();
                ClaimRequest::where('user_id', $inputs['id'])
                ->delete();
                Subscription::where('user_id', $inputs['id'])
                ->delete();
                $user->delete();
            }

           DB::commit();

           return R::Success('User deleted successfully');
        } catch (\Exception $e) {
           DB::rollback();
           return $e;
}
    }



    public function AddService()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs , [
            'full_name' => 'required|max:50|string',
            'short_name' => 'required|max:50|string',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $id = Service::insertGetId($inputs);

        return R::Success('Service added successfully', $id);
    }

    public function UpdateService()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs , [
            'id' => 'required|integer',
            'full_name' => 'required|max:50|string',
            'short_name' => 'required|max:50|string',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $id = Service::find($inputs['id'])
        ->update($inputs);

        return R::Success('Service updated successfully');
    }

    public function DeleteService()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs , [
            'id' => 'required|integer',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $id = Service::find($inputs['id'])
        ->update(['delete_status' => 'deleted']);

        return R::Success('Service deleted successfully');
    }

    public function Services()
    {
        $servicesList = Service::where('delete_status', 'available')
        ->get();

        return R::Success('list', $servicesList);
    }

    public function AddAmenity()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs , [
            'full_name' => 'required|max:50|string',
            'short_name' => 'required|max:50|string',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $id = Amenity::insertGetId($inputs);

        return R::Success('Amenity added successfully', $id);
    }

    public function UpdateAmenity()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs , [
            'id' => 'required|integer',
            'full_name' => 'required|max:50|string',
            'short_name' => 'required|max:50|string',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $id = Amenity::find($inputs['id'])
        ->update($inputs);

        return R::Success('Amenity updated successfully');
    }

    public function DeleteAmenity()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs , [
            'id' => 'required|integer',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $id = Amenity::find($inputs['id'])
        ->update(['delete_status' => 'deleted']);

        return R::Success('Amenity deleted successfully');
    }

    public function Amenities()
    {
        $amenitiesList = Amenity::where('delete_status', 'available')
        ->get();

        return R::Success('list', $amenitiesList);
    }

    public function AddBusinessCategory()
    {
        $inputs = $this->request->except('category_image');

        $v = Validator::make($inputs , [
            'full_name' => 'required|max:1000|string',
            'short_name' => 'required|max:50|string',
            'description' => 'nullable|max:1000|string',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $data = BusinessCategory::create($inputs);

        if($this->request->hasFile('category_image')){
            $file = $this->request->file('category_image');
            $result = $file->storeAs('images/business_category-images/',$data->id);
        }

        return R::Success('Added successfully', $data);
    }

    public function UpdateBusinessCategory()
    {
        $inputs = $this->request->except('category_image');

        $v = Validator::make($inputs , [
            'id' => 'required|integer',
            'full_name' => 'required|max:100|string',
            'short_name' => 'required|max:50|string',
            'description' => 'nullable|max:1000|string',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $id = BusinessCategory::find($inputs['id'])
        ->update($inputs);

        if($this->request->hasFile('category_image')){
            $file = $this->request->file('category_image');
            $result = $file->storeAs('images/business_category-images/',$inputs['id']);
        }

        return R::Success('Updated successfully');
    }

    public function DeleteBusinessCategory()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs , [
            'id' => 'required|integer',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $id = BusinessCategory::find($inputs['id'])
        ->update(['delete_status' => 'deleted']);

        return R::Success('Deleted successfully');
    }

    public function BusinessCategories()
    {
        $categoriesList = BusinessCategory::where('delete_status', 'available')
        ->get();

        return R::Success('list', $categoriesList);
    }

    public function AddBusinessType()
    {
        $inputs = $this->request->all();    
        $v = Validator::make($inputs , [
            'full_name' => 'required|max:1000|string',
            'short_name' => 'required|max:50|string',
            'price' => 'required',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $data = BusinessType::create($inputs);
        return R::Success('Added successfully', $data);
    }

    public function UpdateBusinessType()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
            'full_name' => 'required|max:100|string',
            'short_name' => 'required|max:50|string',
            'price' => 'required',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $id = BusinessType::find($inputs['id'])
        ->update($inputs);

        
        return R::Success('Updated successfully');
    }

    public function DeleteBusinessType()
    {
        $inputs = $this->request->all();

        $v = Validator::make($inputs , [
            'id' => 'required|integer',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $id = BusinessType::find($inputs['id'])
        ->update(['delete_status' => 'deleted']);

        return R::Success('Deleted successfully');
    }

    public function BusinessTypes()
    {
        $categoriesList = BusinessType::where('delete_status', 'available')
        ->get();

        return R::Success('list', $categoriesList);
    }

    public function ActivateListing()
    {
        
        $inputs = $this->request->all();
        
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        Listing::find($inputs['id'])
        ->update(['status' => 'active']);

        return R::Success('Listing activated successfully');
    }

    public function DeactivateListing()
    {
        
        $inputs = $this->request->all();
        
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        Listing::find($inputs['id'])
        ->update(['status' => 'inactive']);

        return R::Success('Listing deactivated successfully');
    }

    public function ContactUsRequests()
    {
        $list = ContactUs::get();
        return R::Success('list' , $list);
    }

    public function ContactUsRequestUpdateStatus()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
            'status' => 'required|in:read,unread',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }   
        
        ContactUs::find($inputs['id'])
        ->update($inputs);

        $data = ContactUs::where('id', $inputs['id'])->first();

        return R::Success('updated successfully', $data);
    }

    public function SaveFeaturedUser()
    {   
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'id' => 'integer|required|exists:users',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $log_data = [
            'user_id' => $inputs['id'],
            'action' => 'UserAsFeatured',
        ];

        $user = User::find($inputs['id']);
        
        DB::beginTransaction();
        try {

            $data = $user->update(['featured' => 1]);
            FeaturedLog::create($log_data);

            DB::commit(); 
            return R::Success(__('User successfully added as featured user'), $user->id);  
        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            return R::SimpleError(__('Some Error !!'));
            
        }   
        
    }

    public function CancelFeaturedUser()
    {   
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'id' => 'integer|required|exists:users',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $log_data = [
            'user_id' => $inputs['id'],
            'action' => 'CancelFeaturedUser'
        ];

        $user = User::find($inputs['id']);
        
        DB::beginTransaction();
        try {
            
            $data = $user->update(['featured' => 0]);
            FeaturedLog::create($log_data);

            DB::commit(); 
            return R::Success(__('User removed as featured user'));  
        } catch (\Exception $e) {
            DB::rollback();
            return R::SimepleError('Some Error !!');
            
        }  
    }

    //Crud of Subject Start
    public function SubjectList()
    {
        $subject_list = Subject::with('SubjectCategory')
        ->where('delete_status' , 'available')
        ->orderBy('name_en', 'asc')
        ->orderBy('name_ch', 'asc')
        ->get();
        return R::Success(__('Subject list') , $subject_list);

    }

    public function AddSubject()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'name_ch' => 'required|max:50|string',
            'name_en' => 'required|max:50|string',
            'description_en' => 'nullable|max:1000',
            'description_ch' => 'nullable|max:1000',
            'subject_category_id' => 'required|integer',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }
        $subject_data = [
            'name_ch' => $inputs['name_ch'],
            'name_en' => $inputs['name_en'],
            'description_en' => @$inputs['description_en'],
            'description_ch' => @$inputs['description_ch'],
            'subject_category_id' => $inputs['subject_category_id'],
        ]; 
    

        $subject  = Subject::create($subject_data);
        if($this->request->hasFile('subject_image')){
            $file = $this->request->file('subject_image');
            $result = $file->storeAs('images/subject-images/',$subject->id);
        }
        return R::Success(__('Added successfully'), $subject->id);
    }

    public function EditSubject()
    {
        $inputs = $this->request->all();
        $id = $inputs['id'];
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
            'name_ch' => 'required|max:50|string',
            'name_en' => 'required|max:50|string',
            'description_en' => 'nullable|max:1000',
            'description_ch' => 'nullable|max:1000',
            'subject_category_id' => 'required|integer',
            
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $subject_data = [
            'id' => $inputs['id'],
            'name_ch' => $inputs['name_ch'],
            'name_en' => $inputs['name_en'],
            'description_en' => @$inputs['description_en'],
            'description_ch' => @$inputs['description_ch'],
            'subject_category_id' => $inputs['subject_category_id'],
        ]; 
        if($this->request->hasFile('subject_image')){
            $file = $this->request->file('subject_image');
            $result = $file->storeAs('images/subject-images/',$id);
        }
        $subject  = Subject::where('id' , $inputs['id'])
        ->update($subject_data);
        return R::Success(__('Updated successfully'), $inputs['id']);
    }

    public function DeleteSubject()
    {

        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|numeric',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }
        $subject  = Subject::where('id' , $inputs['id'])
        ->update(['delete_status' => 'deleted']);
        return R::Success(__('Deleted successfully'));
    }
    //Crud of Subject end

    //Crud of Subject-category Start
    public function SubjectCategoryList()
    {
        $subject_list = SubjectCategory::where('delete_status' , 'available')
        ->get();
        return R::Success(__('Subject list') , $subject_list);

    }

    public function AddSubjectCategory()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'name_ch' => 'required|max:50|string',
            'name_en' => 'required|max:50|string',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $subject  = SubjectCategory::create($inputs);
        return R::Success(__('Added successfully'), $subject->id);
    }

    public function EditSubjectCategory()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
            'name_ch' => 'required|max:50|string',
            'name_en' => 'required|max:50|string',
            
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $subject  = SubjectCategory::find($inputs['id'])
        ->update($inputs);
        return R::Success(__('Updated successfully'), $inputs['id']);
    }

    public function DeleteSubjectCategory()
    {

        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|numeric',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }
        $subject  = SubjectCategory::find($inputs['id'])
       ->update(['delete_status' => 'deleted']);
        return R::Success(__('Deleted successfully'));
    }
    //Crud of Subject-category end
    
    //Crud of member ships
    
    public function MemberShips()
    {
        $data = Membership::where('delete_status','available')
        ->where('id', '>', 1)
        ->get();
        return R::Success('data',$data);
    }

    // public function AddMemberShip()
    // {
    //     $inputs = $this->request->except('membership_image');
    //     $v = Validator::make($inputs, [
    //         'title' => 'required|max:100|string',
    //         'number_of_listings' => 'required|integer',
    //         'monthly_price' => 'required|numeric'
    //     ]);

    //     if($v->fails()) 
    //     {
    //        return R::ValidationError($v->errors()); 
    //     }
        
    //     $data = Membership::create($inputs);

    //     if ($this->request->hasFile('membership_image')) {
    //         $file = $this->request->file('membership_image');
    //         $result = $file->storeAs('images/membership-images/',$data->id);
    //     }
    //     return R::Success('Added successfully',$data);
    // }

    // public function EditMemberShip(){
    //     $inputs = $this->request->except('membership_image');
    //     $v = Validator::make($inputs, [
    //         'id' => 'required|integer',
    //         'title' => 'required|max:100|string',
    //         'number_of_listings' => 'required|integer',
    //         'monthly_price' => 'required|numeric'
    //     ]);

    //     if($v->fails()) {
    //         return R::ValidationError($v->errors());
    //     }

    //     $data  = Membership::find($inputs['id'])
    //     ->update($inputs);
        
    //     if ($this->request->hasFile('membership_image')) {
    //         $file = $this->request->file('membership_image');
    //         $result = $file->storeAs('images/membership-images/',$inputs['id']);
    //     }
    //     return R::Success('Updated successfully', $inputs['id']);
    // }

    public function DeleteMemberShip() {
        $inputs = $this->request->all();
        $v = Validator::make($inputs, [
            'id' => 'required|integer'
        ]);

        if($v->fails()) 
        {
            return R::ValidationError($v->errors());
        }

        $check = Subscription::where('membership_id', $inputs['id'])
        ->where('status', 'active')
        ->count();

        if($check > 0){
            return R::SimpleError($check.' Users are Subcripe this membership Not deleted yet!!');
        }


        $data = Membership::find($inputs['id'])
        ->update(['delete_status' => 'deleted']);
        return R::Success('Deleted successfully');
    }
    
    //Crud of member ships end
    
    //Crud of Level
    public function Levels()
    {
        $subject_list = Level::where('status' , 'Active')
        ->where('delete_status', 'available')
        ->get();
        return R::Success(__('Education levels list') , $subject_list);

    }

    public function AddLevel()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'name_ch' => 'required|max:50|string',
            'name_en' => 'required|max:50|string',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $subject  = Level::create($inputs);
        return R::Success(__('Added successfully'), $subject->id);
    }

    public function EditLevel()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
            'name_ch' => 'required|max:50|string',
            'name_en' => 'required|max:50|string',
            
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $subject  = Level::find($inputs['id'])
        ->update($inputs);
        return R::Success(__('Updated successfully'), $inputs['id']);
    }

    public function DeleteLevel()
    {

        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|numeric',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }
        $subject  = Level::find($inputs['id'])
        ->update(['delete_status' => 'deleted']);
        return R::Success(__('Deleted successfully'));
    }
    //Crud of Level end

    public function Regions()
    {
        $list = Region::where('status' , 'Active')
        ->where('delete_status', 'available')
        ->get();
        return R::Success(__('List') , $list);

    }

    public function AddRegion()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'name_ch' => 'required|max:50|string',
            'name_en' => 'required|max:50|string',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $data  = Region::create($inputs);
        return R::Success(__('Added successfully'), $data->id);
    }

    public function EditRegion()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
            'name_ch' => 'required|max:50|string',
            'name_en' => 'required|max:50|string',
            
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $data  = Region::find($inputs['id'])
        ->update($inputs);
        return R::Success(__('Updated successfully'), $inputs['id']);
    }

    public function DeleteRegion()
    {

        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|numeric',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }
        $data  = Region::find($inputs['id'])
        ->update(['delete_status' => 'deleted']);
        return R::Success(__('Deleted successfully'));
    }

    public function Districts()
    {
        $list = District::with('Region')
        ->where('status' , 'Active')
        ->where('delete_status', 'available')
        ->get();
        return R::Success(__('List' ), $list);

    }

    public function AddDistrict()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'name_ch' => 'required|max:50|string',
            'name_en' => 'required|max:50|string',
            'region_id' => 'required|integer',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $data  = District::create($inputs);
        return R::Success(__('Added successfully'), $data->id);
    }

    public function EditDistirct()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
            'name_ch' => 'required|max:50|string',
            'name_en' => 'required|max:50|string',
            'region_id' => 'required|integer',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $data  = District::find($inputs['id'])
        ->update($inputs);
        return R::Success(__('Updated successfully'), $inputs['id']);
    }

    public function DeleteDistrict()
    {

        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|numeric',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }
        $data  = District::find($inputs['id'])
        ->update(['delete_status' => 'deleted']);
        return R::Success(__('Deleted successfully'));
    }



    public function ChangePassword()
    {   
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'id' => 'integer|required|exists:users,id',
            'new_password' => 'required|min:8',
            
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $user = User::find($inputs['id']);
        
        $newPassword = Hash::make($inputs['new_password']);
        $data = $user->update(['password' => $newPassword]);

        Mail::to($user->email)
        ->send(new ChangePasswordEmail($user, $inputs['new_password']));
            
        return R::Success(__('Changed password successfully'));
    }

    public function SaveUser()
    {
        
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'email' => 'required|unique:users,email',
            'name' => 'required|string|max:40',
            'user_type' => 'required|in:accountant,support',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $password = str::random(8);
        $hasPassword = Hash::make($password);
        $inputs['password'] = $hasPassword;
        $user = User::create($inputs);

        Mail::to($inputs['email'])
        ->send(new UserPasswordEmail($user, $password));
        
        return R::Success(__('User added successfully'),$user->id); 
    }
    
    public function Users()
    {
        $filters = $this->request->all();

        $perPage = 20;
        $data  = User::with('Student','Tutor.Level', 'Center.Level', 'Playgroup.Level')
        ->where('user_type', '!=', 'admin')
        ->where('status', '!=', 'Inactive')
        ->where('delete_status', 'available');
        
        if(isset($filters['id'])){
            $data->where('id', $filters['id']);
        }

        if(isset($filters['user_type'])){
            $data->where('user_type', $filters['user_type']);
        }

        if (isset($filters['name']) && $filters['name'] != null) {
            
            $data->where('first_name', 'like', '%'.$filters['name'].'%')
            ->orWhere('last_name', 'like', '%'.$filters['name'].'%');
        }

        if(isset($filters['email'])){
            $data->where('email', $filters['email']);
        }

        if(isset($filters['featured'])){
            $data->where('featured', $filters['featured']);
        }

        if(isset($filters['registration_date'])){
            $data->orderBy('created_at', $filters['registration_date']);
        }

        if(isset($filters['name_order'])){
            $data->orderByRaw("CONCAT(first_name,' ',last_name)", $filters['name_order']);
        }


        $data = $data->paginate($perPage);

        return R::Success('data', $data);
    }

   //crud of Home[age Banners Start
   public function HomepageBanners()
   {
       $homepage_banners_list = HomepageBanner::get();
       return R::Success('homepage banners list' , $homepage_banners_list);

   }

   public function AddHomepageBanner()
   {
       $inputs = $this->request->all();
       $v = Validator::make($inputs , [
           'banner_link' => 'required|max:50|string',
           'status' => 'required|in:active,inactive'
       ]);
       if($v->fails()){
           return R::ValidationError($v->errors());
       }

       $banner_data = [
           'banner_link' => $inputs['banner_link'],
           'status' => $inputs['status']
       ];

    

       $homepage_banner  = HomepageBanner::create($banner_data);
       
       if($this->request->hasFile('banner_image')){
        $file = $this->request->file('banner_image');
        $result = $file->storeAs('images/banner-images/',$homepage_banner->id);
       }
       return R::Success(__('Added successfully'), $homepage_banner->id);
   }

   public function EditHomepageBanner()
   {
       $inputs = $this->request->all();
       $v = Validator::make($inputs , [
           'id' => 'required|integer',
           'banner_link' => 'required|max:50|string',   
           'status' => 'required|in:active,inactive'
       ]);
       if($v->fails()){
           return R::ValidationError($v->errors());
       }

       $banner_data = [
        'id' => $inputs['id'],
        'banner_link' => $inputs['banner_link'],
        'status' => $inputs['status']
    ];

       $id = $inputs['id'];

       $homepage_banner  = HomepageBanner::find($inputs['id'])
       ->update($banner_data);

        if($this->request->hasFile('banner_image')){
            $file = $this->request->file('banner_image');
            $result = $file->storeAs('images/banner-images/',$id);
        }
       return R::Success(__('Updated successfully'), $inputs['id']);
    }
   
    public function DeleteHomepageBanner()
    {
       $inputs = $this->request->all();
       $v = Validator::make($inputs , [
           'id' => 'required|numeric',
       ]);
       if($v->fails()){
           return R::ValidationError($v->errors());
       }
       $homepage_banner  = HomepageBanner::find($inputs['id'])
      ->delete();
       return R::Success(__('Deleted successfully'));
    }
   //crud of Homepage Banners end


   //user profile

    public function UserProfile($id = null)
    {
        if($id == null){
            return R::SimpleError('Tutor id is required');
        }

        $data = User::find($id);
        $user = ucfirst($data->user_type);
                
        $resp = User::where('id', $id);

        if($user != 'Student'){
            $resp->with($user.'.CoverImages', 
                $user.'.TeacherSubjects.Subject', 
                $user.'.TeachingLocations.District'
            );
        }else{
            $resp->with('Student');
        }    
        $resp = $resp->first();

        return R::Success('data' , $resp);
    }

    public function ClassList()
    {
        $list = ClassesList::where('delete_status' , 'available')
        ->get();
        return R::Success('list' , $list);

    }

    public function AddClass()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'name_ch' => 'required|max:50|string',
            'name_en' => 'required|max:50|string',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }
      

        $list  = ClassesList::create($inputs);
       
        return R::Success(__('Added successfully'), $list->id);
    }

    public function EditClass()
    {
        $inputs = $this->request->all();
        $id = $inputs['id'];
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
            'name_ch' => 'required|max:50|string',
            'name_en' => 'required|max:50|string',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        
        $list  = ClassesList::where('id' , $inputs['id'])
        ->update($inputs);
        return R::Success(__('Updated successfully'), $inputs['id']);
    }

    public function DeleteClass()
    {

        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|numeric',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }
        ClassesList::where('id' , $inputs['id'])
        ->update(['delete_status' => 'deleted']);
        
        return R::Success(__('Deleted successfully'));
    }
    

    public function EducationList()
    {
        $education_list = Education::where('status' , 'Enable')
        ->get();
        return R::Success('education list' , $education_list);

    }

    public function AddEducation()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'name_ch' => 'required|max:50|string',
            'name_en' => 'required|max:50|string',
            'description' => 'nullable|max:1000',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $education  = Education::create($inputs);
        return R::Success(__('Added successfully'), $education->id);
    }

    public function UpdateEducation()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'name_ch' => 'required|max:50|string',
            'name_en' => 'required|max:50|string',
            'description' => 'nullable|max:1000',
            'id' => 'required|integer',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $education  = Education::where('id' , $inputs['id'])
        ->update($inputs);
        return R::Success(__('Updated successfully'),$inputs['id']);
    }

    public function DeleteEducation()
    {

        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|numeric',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }
        $education  = Education::find($inputs['id'])
        ->delete();
        return R::Success(__('Deleted successfully'));
    }
    //Crud of Educatinon end

    public function SaveSuperPassword()
    {   
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'password' => 'required|min:8',
            'password_confirmation' => 'required|min:8',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $password = Hash::make($inputs['password']);
        $user = Setting::where('id','>', 0)
        ->update(['super_password' => $password]);

        return R::Success(__('Pasword Save successfully')); 
    }

    public function ClaimBusinessRequests()
    {   
        $list = ClaimBusiness::with('User')
        ->where('status', 'pending')
        ->where( function($q){
            $q->where('payment_status', 'paid')
            ->orWhereNull('payment_status');
        })
        ->get();

        return R::Success('list', $list);

    }
    public function ApproveBusinessRequest()
    {

        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'ids' => 'required|array'
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $data = ClaimBusiness::whereIn('id', $this->request->ids)
        ->pluck('business_id');


        $unique = array_unique($data->toArray());
        
        if (count($data) != count($unique)) {
            return R::SimpleError("Youre trying to approve multiple claim requests against single business. Please review & try again");
        }

        $data = ClaimBusiness::whereIn('id', $this->request->ids)
        ->get();


        
        foreach ($data as $d) {
           $checkUser = User::where('email', $d->email)
            ->count();

            if($checkUser>0) {
                $errors = [
                    'general'   =>  'email already exist',
                ];
                return \Response::json([
                    'success'   =>  false, 
                    'errors'    =>  $errors,
                    'data'      =>  $d->id,
                ], 200);
            }//end if
        }

        foreach ($data as $r) {
            $code = mt_rand(1000, 9999);

            User::find($r->business_id)
            ->update([
                'email' => $r->email,
                'password'=>Hash::make($code),
                'email_verified_at' => date('Y-m-d H:i:s'),
                'claim_business' => '1',
            ]);

            ClaimBusiness::find($r->id)
            ->update(['status' => 'approved']);

            $business_holidays = [];

            for($i=0; $i<=6; $i++) {
                $business_holidays[] = [
                    'business_id' => $r->business_id,
                    'week_day' => $i,
                    'holiday' => 0
                ];
            }

            BusinessSchedule::insert($business_holidays);

            $emailData = [
                'name' => $r->first_name, 
                'email' => $r->email,
                'password' => $code
            ];
        
             Mail::to($r->email)
            ->queue(new BusinessApproveEmail($emailData));
        }
        
        return R::Success(__('Request has been approved'));
    }

    public function RejectBusinessRequest()
    {

        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'ids' => 'required|array'
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $data = ClaimBusiness::whereIn('id', $this->request->ids)
        ->update(['status' => 'rejected']);

        return R::Success(__('Request has been rejected'));
    }

    public function ClaimRequests()
    {   
        $filters = $this->request->all();

        $list = ClaimRequest::with('User.Business')
        ->where('approval_status', 'pending');

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

        User::find($data->user_id)
        ->update(['claim_business' => '1', 'email_verified_at' => Carbon::now()]);


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

        User::find($data->user_id)
        ->update(['claim_business' => '0']);

        $data->update($inputs);

        return R::Success(__('Request rejected'));
    }

    public function CancelClaim()
    {

        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|integer',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $data = User::find($inputs['id']);

        $inputs['claim_business'] = '0';

        $data->update($inputs);

        return R::Success(__('Claim has been canceled successfully'));
    }

    public function Settings()
    {
        $data = Setting::where('id', '>', 0)
        ->get();

        return R::Success('data', $data);
    }

    public function UpdataBannerPrice()
    {
        $inputs = $this->request->all();
        
        $v = Validator::make($inputs, [
            'id' => 'required|integer',
            'home_banner_price' => 'required|integer',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $data = Setting::find($inputs['id'])
        ->update($inputs);

        return R::Success(__('updated successfully'));
    }

    private function CreatePlan($data){

        // Create a new billing plan
        $plan = new Plan();
        $plan->setName($data['title'])
          ->setDescription('Monthly Copper Subscription to the AviRating to post Maximum'.$data['number_of_listings']  .'listings.')
          ->setType('fixed');

        // Set billing plan definitions
        $paymentDefinition = new PaymentDefinition();
        $paymentDefinition->setName('Regular Payments')
          ->setType('REGULAR')
          ->setFrequency('Month')
          ->setFrequencyInterval('1')
          ->setCycles('12')
          ->setAmount(new Currency(array('value' => $data['monthly_price'], 'currency' => 'USD')));

        // Set merchant preferences
        $merchantPreferences = new MerchantPreferences();
        $merchantPreferences->setReturnUrl('http://avirating.omairusaf.com/success')
          ->setCancelUrl('http://avirating.omairusaf.com/cancel')
          ->setAutoBillAmount('yes')
          ->setInitialFailAmountAction('CONTINUE')
          ->setMaxFailAttempts('0');

        $plan->setPaymentDefinitions(array($paymentDefinition));
        $plan->setMerchantPreferences($merchantPreferences);

        //create the plan
        try {
            $createdPlan = $plan->create($this->apiContext);

            try {
                $patch = new Patch();
                $value = new PayPalModel('{"state":"ACTIVE"}');
                $patch->setOp('replace')
                  ->setPath('/')
                  ->setValue($value);
                $patchRequest = new PatchRequest();
                $patchRequest->addPatch($patch);
                $createdPlan->update($patchRequest, $this->apiContext);
                $plan = Plan::get($createdPlan->getId(), $this->apiContext);

                $planId = $plan->getId();

                // Membership::where('id', 1)
                // ->update(['paypal_plan_id' => $planId]);

                return $planId;

            } catch (PayPal\Exception\PayPalConnectionException $ex) {
                echo $ex->getCode();
                echo $ex->getData();
                die($ex);
            } catch (Exception $ex) {
                die($ex);
            }
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            echo $ex->getCode();
            echo $ex->getData();
            die($ex);
        } catch (Exception $ex) {
            die($ex);
        }

    }

    public function PaymentHistory($type)
    {
        $data = Payment::with('User')
        ->where('payment_type', $type)
        ->get();

        return R::Success('data', $data);
    }

   public function CancelSubscription()
   {
       $inputs = $this->request->all();
        
        $v = Validator::make($inputs, [
            'subscription_id' => 'required|integer',
            'user_id' => 'required|integer',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        Subscription::where('id', $inputs['subscription_id'])
        ->where('user_id', $inputs['user_id'])
        ->update(['status' => 'inactive']);

        return R::Success('Subscription canceled successfully');
   }

   public function ReviewPriceList()
    {
        $list = ReviewReplyPrice::get();
        return R::Success('list' , $list);
    }

    public function AddReviewPrice()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'no_of_replies' => 'required|integer',
            'price' => 'required|numeric',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $data  = ReviewReplyPrice::create($inputs);
        return R::Success('Added successfully', $data->id);
    }

    public function UpdateReviewPrice()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required',
            'no_of_replies' => 'required|integer',
            'price' => 'required|numeric',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $data  = ReviewReplyPrice::find($inputs['id'])
        ->update($inputs);

        return R::Success('updated successfully');
    }

    public function DeleteReviewPrice()
    {

        $inputs = $this->request->all();
        $v = Validator::make($inputs , [
            'id' => 'required|numeric',
        ]);
        if($v->fails()){
            return R::ValidationError($v->errors());
        }
        
        ReviewReplyPrice::find($inputs['id'])
        ->delete();

        return R::Success('Deleted successfully');
    }
}