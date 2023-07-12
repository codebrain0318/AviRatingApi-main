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
use App\Models\ServiceCategory;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Business;
use App\Models\BusinessService;
use App\Models\BusinessAmenity;
use App\Models\BusinessCategory;
use App\Models\Amenity;
use App\Models\Feedback;
use App\Mail\UserPasswordEmail;
use App\Models\FeaturedLog;
use App\Models\HomepageBanner;
use App\Models\ClassesList;
use App\Models\PlaygroupClass;
use App\Models\Membership;
use App\Models\TempSubscription;
use App\Models\Subscription;
use App\Models\Setting;
use App\Models\Payment;
use App\Models\ClaimBusiness;
use App\Models\BusinessOwnerReply;
use App\Models\Listing;

use App\Models\BusinessSchedule;
use App\Models\EmailVerification;
use App\Models\MyWebHook;

use App\Mail\AccountVerificationEmail;

use PayPal\Api\ChargeModel;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\Plan;


use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Common\PayPalModel;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;

// use to process billing agreements
use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;

use PayPal\Api\Payer;
use PayPal\Api\ShippingAddress;

//web hooks
use PayPal\Api\VerifyWebhookSignature;
use PayPal\Api\VerifyWebhookSignatureResponse;
use PayPal\Api\Webhook;
use PayPal\Api\WebhookEvent;
use PayPal\Api\WebhookEventList;
use PayPal\Api\WebhookEventType;
use PayPal\Api\WebhookEventTypeList;
use PayPal\Api\WebhookList;
use PayPal\Api\WebProfile;

use App\Mail\ChangePasswordEmail;
use \App\User;
use App\Helpers\Paypal;

class PaymentController extends Controller
{  
    private $apiContext;
    private $mode;
    private $client_id;
    private $secret;
    // Create a new instance with our paypal credentials
    public function __construct(Request $request, Helper $helper, Paypal $paypal)
    {        
        $this->request = $request;
        $this->helper = $helper;
        $this->paypal = $paypal;
        // Detect if we are running in live mode or sandbox
        if(config('paypal.settings.mode') == 'live'){
            $this->client_id = config('paypal.live_client_id');
            $this->secret = config('paypal.live_secret');
        } else {
            $this->client_id = config('paypal.sandbox_client_id');
            $this->secret = config('paypal.sandbox_secret');
        }
        
        // Set the Paypal API Context/Credentials
        $this->apiContext = new ApiContext(new OAuthTokenCredential($this->client_id, $this->secret));
        $this->apiContext->setConfig(config('paypal.settings'));
    }

    public function WebHook()
    {
        
        #web hook url use in paypal: https://api.avirating.com/api/public/web-hook    
        $data = $this->request->all();
        $data = json_encode($data);

        // $headers = getallheaders();
        // $headers = array_change_key_case($headers, CASE_UPPER);

        $newData = MyWebHook::create(['hook_data' => $data]);

        // $payerData = json_decode($newData->hook_data);
        // //$event_name = $payerData->event_type;

        // MyWebHook::find($newData->id)
        // ->update(['event_name' => $event_name, 'headers' => $headers]);

        //$pId = $payerData->resource->payer->payer_info->payer_id;

        // if (!empty($pId)) {
            
        //     Subscription::where('payer_id', $pId)
        //     ->update(['start_date' => now(), 'end_date' => now()->addMonths(1)]);
            
        // }

        // $headersData = MyWebHook::find($newData->id);
        // $newHeder = json_decode($headersData->headers, true);

        // $signatureVerification = new VerifyWebhookSignature();
        // $signatureVerification->setAuthAlgo($newHeder['PAYPAL-AUTH-ALGO']);
        // $signatureVerification->setTransmissionId($newHeder['PAYPAL-TRANSMISSION-ID']);
        // $signatureVerification->setCertUrl($newHeder['PAYPAL-CERT-URL']);
        // $signatureVerification->setWebhookId("0H9292514G4310923"); // Note that the Webhook ID must be a currently valid Webhook that you created with your client ID/secret.
        // $signatureVerification->setTransmissionSig($newHeder['PAYPAL-TRANSMISSION-SIG']);
        // $signatureVerification->setTransmissionTime($newHeder['PAYPAL-TRANSMISSION-TIME']);

        // $signatureVerification->setRequestBody($newData->hook_data);
        // $request = clone $signatureVerification;

        
        // $output = $signatureVerification->post($this->apiContext);
       
        // MyWebHook::find($newData->id)
        // ->update(['last_response' => $output]);
       
       //$this->SendHookResponse($newData->id);
        
    }

    public function create_plan(){

        // Create a new billing plan
        $plan = new Plan();
        $plan->setName('Silver Membership')
          ->setDescription('Monthly Silver Subscription to the AviRating to post Maximum 5 listings.')
          ->setType('fixed');

        // Set billing plan definitions
        $paymentDefinition = new PaymentDefinition();
        $paymentDefinition->setName('Regular Payments')
          ->setType('REGULAR')
          ->setFrequency('Month')
          ->setFrequencyInterval('1')
          ->setCycles('12')
          ->setAmount(new Currency(array('value' => 5, 'currency' => 'USD')));

        // Set merchant preferences
        $merchantPreferences = new MerchantPreferences();

        $merchantPreferences->setReturnUrl(env('APP_URL').'/api/business/subscription-status')
          ->setCancelUrl(env('APP_URL').'/api/business/subscription-canceled')
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

                Membership::where('id', 1)
                ->update(['paypal_plan_id' => $planId]);

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

    public function AddMemberShip()
    {
        $inputs = $this->request->except('membership_image');
        $v = Validator::make($inputs, [
            'title' => 'required|max:100|string',
            'number_of_listings' => 'required|integer',
            'monthly_price' => 'required|numeric'
        ]);

        if($v->fails()) 
        {
           return R::ValidationError($v->errors()); 
        }

        $plainId = $this->CreatePlan($inputs);

        $inputs['paypal_plan_id'] = $plainId;

        $data = Membership::create($inputs);

        if ($this->request->hasFile('membership_image')) {
            $file = $this->request->file('membership_image');
            $result = $file->storeAs('images/membership-images/',$data->id);
        }
        return R::Success('Added successfully',$data);
    }

    private function CreatePlan($data)
    {
       // Create a new billing plan
        $plan = new Plan();
        $plan->setName($data['title'])
          ->setDescription('Monthly '.$data['title'].' Subscription to the AviRating to post Maximum'.$data['number_of_listings']  .'listings.')
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
        $merchantPreferences->setReturnUrl('https://api.avirating.com/api/business/subscription-status')
          ->setCancelUrl('https://api.avirating.com/api/business/subscription-canceled')
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

    public function Subscribe(){
        
        $inputs = $this->request->all();

        $v = Validator::make($inputs, [
            'membership_id' => 'required|integer',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
         }

        $membership = Membership::find($inputs['membership_id']);

        $sub_check = Subscription::where('membership_id', $inputs['membership_id'])
        ->where('user_id', Auth::id())
        ->where('status', 'active')
        ->first();

        if ($sub_check != NULL) {
            return R::SimpleError('You are already subscribed to this Plan');
        }
        
        DB::beginTransaction();
        try {
            // Create new agreement
            $agreement = new Agreement();
            $agreement->setName($membership->title)
              ->setDescription('Basic Subscription')
              ->setStartDate(\Carbon\Carbon::now()->addMinutes(5)->toIso8601String());

            // Set plan id
            $plan = new Plan();

            $result = $plan->get($membership->paypal_plan_id, $this->apiContext);

            $state = $result->getState();

            if ($state != 'ACTIVE') {
                return R::SimpleError('Requested membership is currently not available, please try later.');
            }

            $plan->setId($membership->paypal_plan_id);

            $agreement->setPlan($plan);

            // Add payer type
            $payer = new Payer();
            $payer->setPaymentMethod('paypal');
            $agreement->setPayer($payer);
            // Create agreement
            $agreement = $agreement->create($this->apiContext);

            // session(['user_id'=>Auth::id()]);

            // Extract approval URL to redirect user
            $approvalUrl = $agreement->getApprovalLink();
            $url_components = parse_url($approvalUrl);
            parse_str($url_components['query'], $params);

            $temp_data = [
                'user_id' =>    Auth::id(),
                'membership_id' => $membership->id,
                'monthly_price' => $membership->monthly_price,
                'token' => $params['token'],
            ];
            
            TempSubscription::create($temp_data);
            $data['redirect_url'] = $approvalUrl;

            $data['prev_subscription'] = Subscription::where('user_id', Auth::id())
            ->where('status', 'active')
            ->where('end_date', '>=', date('Y-m-d H:i:s'))
            ->first();

            DB::commit();
            return R::Success('Redirect URL', $data);
        }   catch (PayPal\Exception\PayPalConnectionException $ex) {
            DB::rollback();
            echo $ex->getCode();
            echo $ex->getData();
            die($ex);
        }   catch (Exception $ex) {
            DB::rollback();
            return $ex;
        }

    }

    public function UnSubscribe()
    {
        $subscription = Subscription::where('user_id', Auth::id())
        ->where('status', 'active')
        ->where('subscription_status', 'active')
        ->first();

        DB::beginTransaction();
        try{
            if (!empty($subscription) && $subscription != null) {
                $agrId = $subscription->agreement_id;

                $data = Agreement::get($agrId, $this->apiContext);
                $createdAgreement = $data;

                if ($createdAgreement->state != 'Active') {
                    return R::SimpleError('Sorry, you dont have any active subscriptions.');
                }

                $agreementStateDescriptor = new AgreementStateDescriptor();
                $agreementStateDescriptor = $agreementStateDescriptor
                ->setNote("Canceling the agreement");

                $resp =  $createdAgreement->cancel($agreementStateDescriptor, $this->apiContext);

                $subscription->update(['status' => 'cancelled', 'subscription_status' => 'inactive']);

                $listing = Listing::where('user_id', Auth::id());

                if (!empty($listing) && $listing != null) {
                    Listing::where('user_id', Auth::id())
                    ->update(['status' => 'inactive', 'subscription_status' => 'inactive']);
                }

                DB::commit();

                return R::Success('Subscription cancelled successfully');
            }else{
                    return R::SimpleError('Sorry, you dont have any active subscriptions.');
                }
        }
        catch(\Exception $e){
            DB::rollback();
            return $e->getMessage();
        }
    }

    public function SubscriptionStatus(Request $request)
    {

        $token = $request->token;
        $agreement = new \PayPal\Api\Agreement();

        DB::beginTransaction();
        try {
            // Execute agreement
            $result = $agreement->execute($token, $this->apiContext);
            
            $temp_sub = TempSubscription::where('token', $token)
            ->first();
            $name = 'Free';
            if ($temp_sub == null) {
                return redirect()->away(env('ANGULAR_BASE_URL').'/business-owner/subscription-status/failed'.$name);
            }

            $membership = Membership::find($temp_sub->membership_id);

            $sub_data = [
                'user_id'       => $temp_sub->user_id,
                'start_date'    => Carbon::now(),
                'end_date'      => Carbon::now()->addDays(32),
                'price'         => $temp_sub->monthly_price, 
                'membership_id' => $temp_sub->membership_id,
                'agreement_id'  => $result->id,
                'payer_id'      => $result->payer->payer_info->payer_id,
                'allow_listing'  => $membership->number_of_listings,
            ];

            $payment_data = [
                'user_id'       => $temp_sub->user_id,
                'payer_id'    => $result->payer->payer_info->payer_id,
                'price'         => $temp_sub->monthly_price,
                'payment_type'  => 'subscription',
            ];

            $user = User::find($temp_sub->user_id);
            //$total_listing = $user->allow_listing + $membership->number_of_listings;
            
            $user->update(['allow_listing' => $membership->number_of_listings]);

            $checkFreeSubscription = Subscription::where('membership_id', 1)
            ->where('user_id', $temp_sub->user_id)
            ->where('subscription_status', 'active');

            if ($checkFreeSubscription != null) {
                $checkFreeSubscription->update(['subscription_status' =>  'inactive']);
            }

            

            $checkListing = Listing::where('user_id', $temp_sub->user_id)
            ->where('delete_status', 'available');
            if ($checkListing != null) {
                $checkListing->update(['subscription_status' => 'inactive']);    
            }

            Subscription::where('user_id', $temp_sub->user_id)
            ->where('status', 'active')
            ->where('subscription_status', 'active')
            ->update(['status' => 'inactive', 'subscription_status' => 'inactive']);

            Subscription::create($sub_data);
            Payment::create($payment_data);

            TempSubscription::where('token', $token)
            ->delete();

            DB::commit();
            
            // return R::Success('New Subscriber Created and Billed');
            return redirect()->away(env('ANGULAR_BASE_URL').'/business-owner/subscription-status/success/'.$membership->title);

        } catch (\PayPal\Exception\PayPalConnectionException $ex) {
            DB::rollback();
            TempSubscription::where('token', $token)
            ->delete();
            //env('ANGULAR_BASE_URL').
            $name = 'Free';
            return redirect()->away(env('ANGULAR_BASE_URL').'/business-owner/subscription-status/failed/'.$name);
            // return R::Success('You have either cancelled the request or your session has expired');
        }
    }

    public function SubscriptionCanceled()
    {
        $token = $this->request->token;

        TempSubscription::where('token', $token)
        ->delete();

        return redirect()->away(env('ANGULAR_BASE_URL').'/business-owner/subscriptions');
    }


    private function UpdatePlan($plainId,$status)
    {
        $createdPlan = new Plan();     
        try {
        $patch = new Patch();
        //$plainId = 'P-0RL77434HX265573SKQQOBGI';

        $plan = $createdPlan->get($plainId, $this->apiContext);
        
        $createdPlan->setPaymentDefinitions($plan);
       
        $paymentDefinitions = $createdPlan->getPaymentDefinitions();
        
        $paymentDefinitionId = $paymentDefinitions->payment_definitions[0]->getId();
        
        $createdPlan->setId($plainId);
        $data = ["state" => $status];
        $data = json_encode($data);

        $patch->setOp('replace')
            ->setPath('/')
            ->setValue(json_decode($data)); 
        $patchRequest = new PatchRequest();
        $patchRequest->addPatch($patch);

        $createdPlan->update($patchRequest, $this->apiContext);

        $plan = Plan::get($createdPlan->getId(), $this->apiContext);
        } catch (Exception $ex) {
                return $ex;
            }
        return $plan;
    }

    private function GetToken()
    {
        $url = "https://api.sandbox.paypal.com/v1/oauth2/token ";
        $headers = [
            'Accept: application/json',
            'Accept-Language: en_US',
            //"Authorization :".env('FCM_KEY'),
        ];

        $secret ="EA7mPkSP-jm5-y138Gr3WOn4ioC1F46hq2oy5XVaW8ei3rJjcqwOfc8FQSxKrYzRjsS6mvXlsew4NO02";
        $cId =  "AW38bXctmKL8mJ3M5HfYl9CiDGph64o5V5uLN42BiNugnAdCWjpZWc1OaeRUtPVjxDmSDyjBrGdR2aQ_";    
        
        //$data = $this->request->all();

        //$data = json_encode($data);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_USERPWD, $cId.":".$secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
       
        


        $result = curl_exec($ch);
        $respCode = curl_getinfo($ch, CURLINFO_HTTP_CODE );
        curl_close($ch);
        $d=json_decode($result);
        $token = $d->access_token;

        return $token;
    }

    public function GetPlan()
    {
        $token = $this->GetToken();
        // $plainId = 'P-0RL77434HX265573SKQQOBGI';
        // $url = "https://api.sandbox.paypal.com/v1/payments/billing-plans/".$plainId."/";
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer $token",
        ];
              
        $ch = curl_init("https://api.sandbox.paypal.com/v1/payments/billing-plans/P-0RL77434HX265573SKQQOBGI");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
       curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        //curl_setopt($ch, CURLOPT_POST, 0);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $respCode = curl_getinfo($ch, CURLINFO_HTTP_CODE );
        
            //dd($respCode);
        curl_close($ch);
        $d=json_decode($result);
        //return $result;
        return R::Success('data' , $d);

    }

    // public function UpdatePlan()
    // {
    //     $token = $this->GetToken();
    //     dd($token);
    //     // $plainId = 'P-0RL77434HX265573SKQQOBGI';
    //     // $url = "https://api.sandbox.paypal.com/v1/payments/billing-plans/".$plainId."/";
    //     $headers = [
    //         "Content-Type: application/json",
    //         "Authorization: Bearer $token",
    //     ];
       
    //     //dd($headers);
    //     $data = [ 
    //         "pricing_schemes" => [
    //             "billing_cycle_sequence" => 2,
    //             "pricing_scheme" => [
    //                 "fixed_price" => [
    //                     "value" => "50",
    //                     "currency_code" => "USD"
    //                 ]
    //             ]
    //         ]
    //     ];
           

    //      // $data ='{ "op": "replace", "path": "/payment-definitions/PD-1SE709902U814050UKQQOBGI", 
    //      //       "value":{"state":"ACTIVE"}}';
    //     $data = json_encode($data);
    //     $ch = curl_init("https://api.sandbox.paypal.com/v1/billing/plans/P-0RL77434HX265573SKQQOBGI/update-pricing-schemes");
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    //     //curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    //     curl_setopt($ch, CURLOPT_POST, 0);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //     curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    //     $result = curl_exec($ch);
    //     $respCode = curl_getinfo($ch, CURLINFO_HTTP_CODE );
        
    //     //dd($respCode);
    //     curl_close($ch);
    //     $d=json_decode($result);
    //     //return $result;
    //     return R::Success('data' , $d);
    // }


    public function ActivePlan()
    {
        $inputs = $this->request->all();
        $v = Validator::make($inputs, [
            'id' => 'integer|required'
        ]);
        
        if ($v->fails()) {
            return R::ValidationError($v->errors());
        }

        $status = 'ACTIVE';
        $data = Membership::find($inputs['id']);
        $resp = $this->UpdatePlan($data->paypal_plan_id, $status);

        if($resp->state == $status){
            $data->update(['status' => $status]);
            return R::Success('Plan active successfully');
        }

        return R::SimpleError('Some Error!!!');
        return $resp->state;
    }

    public function InactivePlan()
    {   
        $inputs = $this->request->all();    
        $v = Validator::make($inputs, [
            'id' => 'integer|required'
        ]);
        
        if ($v->fails()) {
            return R::ValidationError($v->errors());
        }

        $status = 'INACTIVE';
        $data = Membership::find($inputs['id']);
        $resp = $this->UpdatePlan($data->paypal_plan_id, $status);
        
        if($resp->state == $status){
            $data->update(['status' => $status]);
            return R::Success('Plan Inactive successfully');
        }

        return R::SimpleError('Some Error!!!');
        return $resp->state;
    }

    public function CancelPaypal($banner_id)
    {   
        $file = HomepageBanner::find($banner_id);
            
        $path = storage_path('app/images/product-images/'.$banner_id);

        if (File::exists($path) && $file != null) {
            HomepageBanner::find($banner_id)
            ->where('payment_status', 'unpaid')
            ->delete();

            Storage::delete('images/product-images/'.$inputs['id']);
            $file->delete();  
        }

        return redirect()->away(env('ANGULAR_BASE_URL').'/business-owner/banners?page=1');
    }

    public function BannerPaymentStatus($banner_id)
    {
        // Get payment object by passing paymentId
        $paymentId = $_GET['paymentId'];
        $payerId = $_GET['PayerID'];

        $result = $this->paypal->processPayment($paymentId, $payerId);
        $result = json_decode($result);

        if($result->state == 'approved'){
            return $this->ConfirmBannerPayment($result, $banner_id);
        } else {
            $file = HomepageBanner::find($banner_id);
                
            $path = storage_path('app/images/product-images/'.$banner_id);

            if (File::exists($path) && $file != null) {
                
                HomepageBanner::find($banner_id)
                ->where('payment_status', 'unpaid')
                ->delete();

                Storage::delete('images/product-images/'.$inputs['id']);
                $file->delete();  
            }
            
            return redirect()->away(env('ANGULAR_BASE_URL').'/business-owner/banners-status/failed');
        }
    }

    public function ConfirmBannerPayment($paymentResults, $banner_id)
    {
        $paymentId = $_GET['paymentId'];
        $payerId = $_GET['PayerID'];
        $result = $paymentResults->state;

        $data['payment_id'] = $paymentId;
        $data['payer_id'] = $payerId;
        $data['payment_status'] = 'paid';
        $data['status'] = 'active';

        HomepageBanner::find($banner_id)
        ->update($data);

        // return R::Success('Banner added successfully', $id);
        return redirect()->away(env('ANGULAR_BASE_URL').'/business-owner/banners-status/success');
    }

    // public function Webhook()
    // {
    //     return 'success';
    // }

    public function CancelBusinessPayment($user_id)
    {   
        DB::beginTransaction();
        try {
                User::find($user_id)
                ->delete();
        
                Business::find($user_id)
                ->delete();

                BusinessService::where('business_id', $user_id)
                ->delete();

                BusinessSchedule::where('business_id', $user_id)
                ->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return $e;
        }

        // return redirect()->away(env('ANGULAR_BASE_URL').'/business-owner/banners-status/failed');
        return redirect()->away(env('ANGULAR_BASE_URL').'/registration');
    }

    public function BusinessPaymentStatus($user_id)
    {
        // Get payment object by passing paymentId
        $paymentId = $_GET['paymentId'];
        $payerId = $_GET['PayerID'];

        $result = $this->paypal->processPayment($paymentId, $payerId);
        $result = json_decode($result);

        if($result->state == 'approved'){
            return $this->ConfirmBusinessPayment($result, $user_id);
        } else {
            
            DB::beginTransaction();
            try {
                    User::find($user_id)
                    ->delete();
            
                    Business::find($user_id)
                    ->delete();

                    BusinessService::whreIn('business_id', $user_id)
                    ->delete();

                    BusinessHoliday::whereIn('business_id', $user_id)
                    ->delete();

                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                return $e;
            }

            return redirect()->away(env('ANGULAR_BASE_URL').'/registration-status/failed');
        }
    }

    public function ConfirmBusinessPayment($paymentResults, $user_id)
    {
        $paymentId = $_GET['paymentId'];
        $payerId = $_GET['PayerID'];
        $result = $paymentResults->state;

        $data['payment_id'] = $paymentId;
        $data['payer_id'] = $payerId;
        $data['payment_status'] = 'paid';
        $data['payment_type'] = 'business';
        // $data['status'] = 'active';



        $userData = User::find($user_id);
        $businessData = Business::find($user_id);

        $data['user_id'] = $userData->id;
        $data['price'] = $businessData->BusinessType->price;
        Payment::create($data);

         do {
                
                $code = mt_rand(1000, 9999);
            } 
            while (EmailVerification::where('code', $code)->first());

            $expiry = 120;
            
            EmailVerification::create([
                'code' => $code, 
                'user_id' => $userData->id,
                'expiry_time' => Carbon::now()->addMinutes(25)
            ]);

            $subject = 'E-mail Verification';
            $emailData = [
                'name' => $userData->first_name,
                'link' => env('ANGULAR_BASE_URL').'/verify-email/'.$code
            ];

            Mail::to($userData->email)
            ->queue(new AccountVerificationEmail($subject, $emailData));
        

        // return R::Success('Banner added successfully', $id);
        return redirect()->away(env('ANGULAR_BASE_URL').'/registration-status/success');
    }

    public function CancelClaim($claim_id)
    {   
        $data = ClaimBusiness::find($claim_id);
        $business_id = $data->business_id;
        $data->delete();

        // return redirect()->away(env('ANGULAR_BASE_URL').'/business-owner/banners-status/failed');
        return redirect()->away(env('ANGULAR_BASE_URL').'/business-profile/'.$business_id);
    }

    public function ClaimBusinessStatus($claim_id)
    {
        // Get payment object by passing paymentId
        $paymentId = $_GET['paymentId'];
        $payerId = $_GET['PayerID'];

        $result = $this->paypal->processPayment($paymentId, $payerId);
        $result = json_decode($result);

        if($result->state == 'approved'){
            return $this->ConfirmClaimPayment($result, $claim_id);
        } else {
                ClaimBusiness::find($claim_id)
                ->delete();
            return redirect()->away(env('ANGULAR_BASE_URL').'/claim-status/failed');
        }
    }

    public function ConfirmClaimPayment($paymentResults, $claim_id)
    {   

        $clData = ClaimBusiness::find($claim_id);
        $userData = User::find($clData->business_id);

        $paymentId = $_GET['paymentId'];
        $payerId = $_GET['PayerID'];
        $result = $paymentResults->state;

        $data['payment_id'] = $paymentId;
        $data['payer_id'] = $payerId;
        $data['payment_status'] = 'paid';
        $data['payment_type'] = 'claim';
        // $data['status'] = 'active';


        $data['price'] = $clData->business_price;
        
        $data['user_id'] = $userData->id;
        
        Payment::create($data);

        $clData->update(['payment_status' => 'paid']);

        // return R::Success('Banner added successfully', $id);
        return redirect()->away(env('ANGULAR_BASE_URL').'/claim-status/success');
    }

    public function ReplyPaymentCancel($businessReplyId)
    {
        BusinessOwnerReply::find($businessReplyId)
        ->delete();

        return redirect()->away(env('ANGULAR_BASE_URL').'/business-owner/reply-status/canceled');
    }

    public function ReplyPaymentStatus($businessReplyId)
    {
        // Get payment object by passing paymentId
        $paymentId = $_GET['paymentId'];
        $payerId = $_GET['PayerID'];

        $result = $this->paypal->processPayment($paymentId, $payerId);
        $result = json_decode($result);

        if($result->state == 'approved'){
            return $this->ConfirmReplyPayment($result, $businessReplyId);
        } else {
            BusinessOwnerReply::find($businessReplyId)
            ->delete();

            return redirect()->away(env('ANGULAR_BASE_URL').'/business-owner/reply-status/failed');
        }
    }

    public function ConfirmReplyPayment($paymentResults, $businessReplyId)
    {
        $paymentId = $_GET['paymentId'];
        $payerId = $_GET['PayerID'];
        $result = $paymentResults->state;

        $data['payment_id'] = $paymentId;
        $data['payer_id'] = $payerId;
        $data['payment_status'] = 'paid';
        $data['status'] = 'active';
        $data['purchased_date'] = Carbon::now();

        BusinessOwnerReply::find($businessReplyId)
        ->update($data);

        // return R::Success('Banner added successfully', $id);
        return redirect()->away(env('ANGULAR_BASE_URL').'/business-owner/reply-status/success');
    }

    public function WhebHookList()
    {
        $list = new WebhookList();
        $webhookId = '52E86222JG838235K'; 
        $params = array(
           'start_time'=>'2014-12-06T11:00:00Z',
           'end_time'=>'2014-12-12T11:00:00Z'
        );

        try {
                $output = \PayPal\Api\WebhookEventType::subscribedEventTypes($webhookId, $this->apiContext);
        } catch (Exception $ex) {

            ResultPrinter::printError("Get a Webhook", "Webhook", null, $webhookId, $ex);
            exit(1);
        }    

        return $output;

    }

    public function AllPlans()
    {
        $params = array('page_size' => '2');
    $planList = Plan::all($params, $this->apiContext);
        return $planList;
    }

    public function SingelPlan($planId)
    {
        $plan = Plan::get($planId,$this->apiContext);

        return $plan;
    }

    public function GetWebhook()
    {
        $data = Webhook::getAll($this->apiContext);
        return $data->webhooks[0]->id;
    }

    public function SendHookResponse($id)
    {       
        $newData = MyWebHook::find($id);
        

        $headersData = MyWebHook::find($id);
        $newHeder = json_decode($newData->headers, true);

        $signatureVerification = new VerifyWebhookSignature();
        $signatureVerification->setAuthAlgo($newHeder['PAYPAL-AUTH-ALGO']);
        $signatureVerification->setTransmissionId($newHeder['PAYPAL-TRANSMISSION-ID']);
        $signatureVerification->setCertUrl($newHeder['PAYPAL-CERT-URL']);
        $signatureVerification->setWebhookId("0H9292514G4310923"); // Note that the Webhook ID must be a currently valid Webhook that you created with your client ID/secret.
        $signatureVerification->setTransmissionSig($newHeder['PAYPAL-TRANSMISSION-SIG']);
        $signatureVerification->setTransmissionTime($newHeder['PAYPAL-TRANSMISSION-TIME']);

        $signatureVerification->setRequestBody($newData->hook_data);
        $request = clone $signatureVerification;

        try {
            /** @var \PayPal\Api\VerifyWebhookSignatureResponse $output */
            $output = $signatureVerification->post($this->apiContext);
            
            MyWebHook::find($id)
            ->update(['last_response' => $output]);
        } catch (Exception $ex) {
            MyWebHook::find($id)
            ->update(['last_response' => json_encode($ex)]);
        }

        return $output;
    }

    public function Agreement($agrId)
    {
        $createdAgreement = $agrId;

        $agreement = Agreement::get($createdAgreement, $this->apiContext);
        
        return $agreement;    
    }

    public function SuspendAgreement($agrId)
    {
        $data = Agreement::get($agrId, $this->apiContext);
        $createdAgreement = $data;

        $agreementStateDescriptor = new AgreementStateDescriptor();
        $agreementStateDescriptor = $agreementStateDescriptor->setNote("Suspending the agreement");

        $resp =  $createdAgreement->suspend($agreementStateDescriptor, $this->apiContext);

        $agreement = Agreement::get($agrId, $this->apiContext);    
        return $agreement;
    }

    public function ReactiveAgreement($agrId)
    {
        $data = Agreement::get($agrId, $this->apiContext);
        $createdAgreement = $data;

        $agreementStateDescriptor = new AgreementStateDescriptor();
        $agreementStateDescriptor = $agreementStateDescriptor->setNote("Reactivating the agreement");

        $resp =  $createdAgreement->reActivate($agreementStateDescriptor, $this->apiContext);

        $agreement = Agreement::get($agrId, $this->apiContext);    
        return $agreement;
    }
}