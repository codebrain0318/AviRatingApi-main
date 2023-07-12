<?php

namespace App\Http\Controllers\Api;
ini_set('max_execution_time', 0);
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
use App\Models\BusinessAmenity;
use App\Models\BusinessSchedule;
use App\Models\BusinessReview;
use App\Models\BusinessType;
use \App\User;

class BulkUploadController extends Controller
{  
	private $data = [];
	private $errorsData = [];
	private $error_list;

    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
    }
    
    
    public function BulkUploadUsers()
    {   
    	$inputs = $this->request->all();
        $v = Validator::make($inputs,[
            'file' => 'required|file|mimes:csv,txt'
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $delimiter = ',';
        $handle = fopen($this->request->file('file'), 'r');//$this->request->file('csv_file')->getRealPath();
        //$data =  fgetcsv($handle, 1000, $delimiter);//array_map('str_getcsv', file($path));
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== false)
        {
            $data[] = $row;
        }
        fclose($handle);
        $this->data = array_slice($data, 1, count($data));
        
            $result = $this->BulkUploadData();
           
            if($result) {
           		return R::Success('Data uploaded successfully', $this->error_list);
           	} else {
           		$msg = ('We encounter some issues while uploading your data');
           		return R::SimpleError($msg, json_encode($this->error_list));
           	}
        
        
    }

    private function BulkUploadData()
    {
        $db_users = User::where('id', '>', 0);
        
        $db_email = $db_users->pluck('email')->toArray();
        
        $error_list = [];
        $error_list['csv_duplicates'] = [];
        $existing_email_users = [];
        $missing_lat_lng= [];

        DB::beginTransaction();
        try{
        	
            $results = $this->CheckDuplicates();
        	
            $error_list['csv_duplicates'] = $results['duplicateData'];
        	$data = $results['validData'];

            foreach ($data as $value) {

                $email;
                if($this->TK($value, 'email') == "" || $this->TK($value, 'email') == "N/A" ){
                    $email = Null;
                }else{
                    $email = $this->TK($value, 'email');
                }
                
                $user_data = [
					'email' => $email, 
					'first_name' => $this->TK($value, 'first_name'),
					'password' => Hash::make($this->TK($value, 'password')),
					'user_type' => 'business',
                ];
                
                $existing_email;
                if($this->TK($value, 'email') == "" || $this->TK($value, 'email') == "N/A" ){
                    $existing_email = [];
                }else{
                    $existing_email = array_intersect(['email' => $user_data['email']], $db_email);
                }    
                //dd($this->TK($value, 'lat'));
                if(count($existing_email) > 0) {
                        $existing_email_users [] = $value;
                }
                // elseif($value[10] == "" || $value[11]  == "" ){
                    
                //     $missing_lat_lng [] = $value;
                // }  
                else { 
                    $business_cat = BusinessCategory::where('full_name', trim($this->TK($value, 'business_category_name')))
                    ->first();

                    $business_type = BusinessType::where('full_name', trim($this->TK($value, 'business_type_name')))
                    ->first();
                   
                    $user_id = User::insertGetId($user_data);
                    
                    $profile_data = [
                        'id' 				            => 	$user_id,
                        'contact' 		                => 	$this->TK($value, 'contact'),
                        'opening_time' 		            => 	$this->TK($value, 'opening_time'),
                        'closing_time'                  =>  $this->TK($value, 'closing_time'),
                        'business_category_id' 			=> 	@$business_cat['id'],
                        'business_type_id'              =>  @$business_type['id'], 
                        'address'                       =>  $this->TK($value, 'address'),
                        'zip_code'                      =>  $this->TK($value, 'zip_code'),
                        'state'                         =>  $this->TK($value, 'state'),
                        'city'                          =>  $this->TK($value, 'city'),    
                        'lat'                           =>  $this->TK($value, 'lat'),
                        'lng'                           =>  $this->TK($value, 'lng'),
                        'airport_id'                    =>  $this->TK($value, 'airport_id'),
                    ];

                    
                    $business_schedule = [];
                    for ($i=0; $i < 7 ; $i++) { 
                        $business_schedule[]=[
                          'week_day' => $i,
                          'business_id' => $user_id,
                          'holiday' => $this->TK($value, "{$i}_holiday") == 'Yes'? 1 : 0, 
                        ];
                    }
                    Business::create($profile_data);    
                    BusinessSchedule::insert($business_schedule);
                }
            }        

            $error_list['existing_users'] =  $existing_email_users;
            $error_list['missing_lat_lng'] = $missing_lat_lng;
            $error_list['total_users'] = count($this->data);
            $error_list['total_success_users'] = count($results['validData']) - (count($existing_email_users) + count($missing_lat_lng));
            $error_list['total_failed_users'] = count($existing_email_users) + count($results['duplicateData']) + + count($missing_lat_lng);

            DB::commit();
            $this->error_list = $error_list;
            
            return true;
        } catch (\Exception $e){
            DB::rollback();

            $this->error_list = $e;
            dd($e);
        }
    }

    private function TK($row, $field) {
    	$mappings = [
    		'email','first_name','opening_time', 'closing_time', 'zip_code',
    		'password', 'contact', 'state', 'city', 'address', 'lat', 'lng','business_category_name', 
    		'0_holiday', // SUNDAY
    		'1_holiday',
    		'2_holiday',
    		'3_holiday',
    		'4_holiday',
    		'5_holiday',
    		'6_holiday', // SATURDAY
    	    'business_type_name',
            'airport_id'
        ];

    	return $row[array_search($field, $mappings)];
    }

    private function CheckDuplicates() {
    	$validData = [];
    	$duplicateData = [];

    	foreach ($this->data as $k => $row) {
            
            if($row[0] == "" || $row[0] == "N/A"){
                 
                $validData[] = $row;

               
            }else{
                 
                  $resp = $this->emailUsernameCount($row);
            
              if($resp['email'] > 1 || $resp['user_name'] > 1) {
                    $duplicateData[] = $row;
                } else {
                    $validData[] = $row;
                }
            }
            
    	}

    	return [ 
    		'validData' => $validData,
			'duplicateData' => $duplicateData
    	];
    }

    private function emailUsernameCount($r) {
    	$arr = [
    		'email' => 0,
    		'user_name' => 0
    	];

    	foreach ($this->data as $row) {
    		if($this->TK($row, 'email') == $this->TK($r, 'email')) {
    			$arr['email']++;
    		}
    	}

    	return $arr;
    }
}