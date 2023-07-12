<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => ['auth:api', 'auth-customer']], function(){
	
	Route::get('test', function(){
		return Auth::id();
	});	

	#Reviews
	Route::post('save-review', 'ListingReviewController@SaveReview');
	Route::get('my-reviews', 'ListingReviewController@MyReviews');	

	Route::get('my-business-reviews', 'ListingReviewController@MyBusinessReview');	
	#BusinessReviews
	Route::post('save-business-review', 'BusinessReviewController@UpdateBusinessReview');
	Route::get('my-reviews', 'ListingReviewController@MyReviews');
	Route::post('save-review-image', 'BusinessReviewController@SaveReviewImage');
	Route::post('pre-review', 'BusinessReviewController@PreReview');
	Route::post('delete-review-image', 'BusinessReviewController@DeleteReviewImage');




	Route::post('reset-password', 'RegistrationController@ResetPassword');

	Route::get('my-feedbacks', 'FeedbackController@MyFeedbacks');

	Route::post('update-profile', 'RegistrationController@CustomerUpdateProfile');
	
	Route::post('send-message', 'MessageController@SendMessage');

	Route::post('delete-message', 'MessageController@DeleteMessage');

	Route::get('messages', 'MessageController@Messages');

	Route::get('chat-detail', 'MessageController@ChatDetail');

	Route::get('customer-profile','RegistrationController@CustomerProfile');


	//subjects
	Route::post('save-subject','StudentController@SaveSubject');
	
	Route::get('subjects','StudentController@Subjects');

});

Route::get('review-image/{id}','BusinessReviewController@ReviewImage');

Route::post('register', 'RegistrationController@CustomerRegister');

Route::get('profile-picture/{id}','MainController@ProfilePicture');

//Route::get('message','RegController@Message');
  
