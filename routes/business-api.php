<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => ['auth:api', 'auth-business']], function(){
	
	Route::get('test', function(){
		//return 'good';
		 return Auth::id();
	});

	Route::get('dashboard', 'MainController@DashBoard');

	#Listings
	Route::post('save-listing', 'ListingController@SaveListing');
	Route::get('my-listings', 'ListingController@MyListings');
	Route::post('listing-detail', 'ListingController@ListingDetail');
	Route::post('check-allow-listing', 'ListingController@AllowListing');
	Route::post('pre-listing', 'ListingController@PreListing');
	Route::post('save-listing-image', 'ListingController@SaveListingImage');
	Route::post('update-listing-image', 'ListingController@UpdateListingImage');
	Route::post('update-listing', 'ListingController@UpdateListing');
	Route::post('update-thumbnail', 'ListingController@UpdateThumbnail');
	Route::post('delete-listing-image', 'ListingController@DeleteListingImage');
	Route::post('delete-listing', 'ListingController@DeleteListing');
	Route::get('listing-image/{id}', 'ListingController@ListingImage');

	Route::post('update-profile', 'RegistrationController@BusinessUpdateProfile');

	Route::get('memberships','BusinessOwnerController@ActiveMemberShips');

	#ClaimBusiness
	Route::post('claim-business', 'BusinessOwnerController@ClaimBusiness');

	#FreeSubscription
    Route::post('free-subscription', 'BusinessOwnerController@FreeSubscription');

    Route::post('change-status',  'ListingController@ChangeListingStatus');

	// crud of homepage Banners
	Route::get('my-homepage-banners','BusinessOwnerController@MyHomepageBanners');
	Route::post('add-homepage-banner','BusinessOwnerController@AddHomepageBanner');
	Route::post('update-homepage-banner','BusinessOwnerController@EditHomepageBanner');
	Route::post('delete-homepage-banner','BusinessOwnerController@DeleteHomepageBanner');
	Route::post('change-banner-status','BusinessOwnerController@HomepageBannerStatus');

	Route::get('business-profile','RegistrationController@BusinessProfile');
	Route::post('save-location' , 'MainController@SaveLocation');
	Route::post('reset-password', 'RegistrationController@ResetPassword');
	Route::get('my-feedbacks', 'FeedbackController@MyFeedbacks');

	//documents
	Route::post('save-document', 'MainController@SaveDocument');
	Route::get('document-list' , 'MainController@DocumentList');

	#messages:
	Route::post('send-message', 'MessageController@SendMessage');

	Route::post('delete-message', 'MessageController@DeleteMessage');

	Route::get('messages', 'MessageController@Messages');

	Route::get('chat-detail', 'MessageController@ChatDetail');


	#coverimge
	Route::post('save-cover-image' , 'TeacherController@SaveCoverImage');
	Route::post('delete-cover-image' , 'TeacherController@DeleteCoverImage');
	Route::get('cover-image-list' , 'TeacherController@CoverImageList');

	#Subscription
	Route::post('subscribe', 'PaymentController@Subscribe');
	Route::post('unsubscribe', 'PaymentController@UnSubscribe');

	#reply Image
	Route::post('save-reply-image', 'ListingReviewController@SaveReplyImage');
	Route::post('delete-reply-image', 'ListingReviewController@DeleteReplyImage');	

	#buyreplyReviews
	Route::post('buy-review-reply', 'BusinessOwnerController@BuyReviewReply');
	Route::post('review-reply-count', 'BusinessOwnerController@ReviewReplyCount');


	#business reviews
	Route::get('my-business-reviews', 'BusinessReviewController@BusinessOwnerReviews');
	Route::post('reply-business-review', 'BusinessReviewController@ReplyReview');

	Route::get('my-listing-reviews', 'ListingReviewController@ListingOwnerReviews');		
	Route::post('reply-listing-review', 'ListingReviewController@ReplyListingReview');

	Route::get('agreement-detail/{agrId}', 'PaymentController@Agreement');
    Route::get('suspend-agreement/{agrId}', 'PaymentController@SuspendAgreement');
    Route::get('reactive-agreement/{agrId}', 'PaymentController@ReactiveAgreement');
});

#list of review-price
Route::get('review-price-list', 'AdminController@ReviewPriceList');

Route::post('register', 'RegistrationController@BusinessRegister');

#WebHooks
Route::any('webhook', 'PaymentController@Webhook');

#Membership Subscription
Route::any('subscription-status', 'PaymentController@SubscriptionStatus');
Route::any('subscription-canceled', 'PaymentController@SubscriptionCanceled');

Route::any('banner-payment-cancel/{banner_id}', 'PaymentController@CancelPaypal');
Route::any('banner-payment-status/{banner_id}', 'PaymentController@BannerPaymentStatus');
 
#Buy Replies  
Route::any('reply-payment-cancel/{businessReplyId}', 'PaymentController@ReplyPaymentCancel');
Route::any('reply-payment-status/{businessReplyId}', 'PaymentController@ReplyPaymentStatus');

Route::any('business-payment-cancel/{user_id}', 'PaymentController@CancelBusinessPayment');
Route::any('business-payment-status/{user_id}', 'PaymentController@BusinessPaymentStatus');

Route::get('download-document/{id}', 'MainController@DownloadDocument');

Route::post('delete-document', 'MainController@DeleteDocument');

//Route::get('education-list', 'MainController@EducationList');

Route::get('profile-picture/{id}','PublicController@ProfilePicture');

Route::get('cover-image/{id}','TeacherController@CoverImage');









