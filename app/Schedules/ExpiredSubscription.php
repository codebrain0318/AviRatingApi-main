<?php
namespace App\Schedules;
use \Carbon\Carbon;
use\App\Models\Subscription;
use\App\Models\Listing;
use\App\User;
use DB;

class ExpiredSubscription
{

	public function __construct(){
		$this->ExpireSubscription();			
	}

	public function ExpireSubscription(){
		$subscriptionIds = Subscription::whereDate('end_date', '<', date('Y-m-d'))
		->pluck('id');
		
		$userIds = Subscription::whereDate('end_date', '<', date('Y-m-d'))
		->pluck('user_id');

		DB::beginTransaction();

		try {

			Listing::whereIn('user_id', $userIds)
			->update(['subscription_status' => 'inactive']);

			User::whereIn('id', $userIds)
			->update(['allow_listing' => 0]);

			Subscription::whereIn('id', $subscriptionIds)
			->update(['status' => 'inactive', 'subscription_status' => 'inactive']);

			DB::commit();
		} catch (Exception $e) {
			DB::rollback();

			return $e;
		}

		
	}
}