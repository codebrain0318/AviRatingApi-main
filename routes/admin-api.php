<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
#User
Route::get('user-list', 'AdminController@UserList');
Route::post('delete-user', 'AdminController@DeleteUser');
Route::post('change-password', 'AdminController@ChangePassword');
Route::post('update-status', 'AdminController@UpdateStatus');
Route::post('save-user', 'AdminController@SaveUser');
Route::post('bulk-upload', 'BulkUploadController@BulkUploadUsers');

#Business
Route::get('business-requests', 'AdminController@BusinessRequests');
Route::post('approve-business', 'AdminController@ApprovedBusiness');
Route::post('reject-business', 'AdminController@RejectBusiness');

#ClaimBusinessRequests
Route::get('claim-requests', 'AdminController@ClaimRequests');
Route::post('approve-claim', 'AdminController@ApproveClaimRequest');
Route::post('reject-claim', 'AdminController@RejectClaimRequest');
Route::post('cancel-claim', 'AdminController@CancelClaim');

#replyReviewsPrice
Route::get('review-price-list', 'AdminController@ReviewPriceList');
Route::post('add-review-price', 'AdminController@AddReviewPrice');
Route::post('update-review-price', 'AdminController@UpdateReviewPrice');
Route::post('delete-review-price', 'AdminController@DeleteReviewPrice');

#ClaiMBusiness
Route::get('claim-business-requests', 'AdminController@ClaimBusinessRequests');
Route::post('approve-business-request', 'AdminController@ApproveBusinessRequest');
Route::post('reject-business-request', 'AdminController@RejectBusinessRequest');


#Services
Route::post('add-service', 'AdminController@AddService');
Route::post('update-service', 'AdminController@UpdateService');
Route::post('delete-service', 'AdminController@DeleteService');
Route::get('services', 'AdminController@Services');

#Amenities
Route::post('add-amenity', 'AdminController@AddAmenity');
Route::post('update-amenity', 'AdminController@UpdateAmenity');
Route::post('delete-amenity', 'AdminController@DeleteAmenity');
Route::get('amenities', 'AdminController@Amenities');

#Amenities
Route::post('add-business-category', 'AdminController@AddBusinessCategory');
Route::post('update-business-category', 'AdminController@UpdateBusinessCategory');
Route::post('delete-business-category', 'AdminController@DeleteBusinessCategory');
Route::get('business-categories', 'AdminController@BusinessCategories');

#Business Type
Route::post('add-business-type', 'AdminController@AddBusinessType');
Route::post('update-business-type', 'AdminController@UpdateBusinessType');
Route::post('delete-business-type', 'AdminController@DeleteBusinessType');
Route::get('business-types', 'AdminController@BusinessTypes');

#Listings
Route::get('listings', 'ListingController@Listings');
Route::post('activate-listing', 'AdminController@ActivateListing');
Route::post('deactivate-listing', 'AdminController@DeactivateListing');

Route::get('profile-image/{id}','PublicController@ProfilePicture');

//contactUs
Route::get('contact-us-requests' , 'AdminController@ContactUsRequests'); 
Route::post('contact-us-request-update-status' , 'AdminController@ContactUsRequestUpdateStatus');

//crud of subject category
Route::get('subject-category-list','AdminController@SubjectCategoryList');
Route::post('add-subject-category','AdminController@AddSubjectCategory');
Route::post('edit-subject-category','AdminController@EditSubjectCategory');
Route::post('delete-subject-category','AdminController@DeleteSubjectCategory');

//crud of Level
Route::get('levels','AdminController@Levels');
Route::post('add-level','AdminController@AddLevel');
Route::post('edit-level','AdminController@EditLevel');
Route::post('delete-level','AdminController@DeleteLevel');




Route::get('users', 'AdminController@Users');

// get/delete reviews
Route::get('listing-reviews','ListingReviewController@ListingReviews');
Route::post('delete-listing-review','ListingReviewController@DeleteReview');

Route::get('business-reviews','ListingReviewController@BusinessReviews');
Route::post('delete-business-review','ListingReviewController@DeleteBusinessReview');

//settings
Route::get('settings','AdminController@Settings');
Route::post('update-banner-price','AdminController@UpdataBannerPrice');



//playgroup profile
Route::post('add-palygroup', 'ProfileController@AddPlaygroup');
Route::post('playgroup-update-profile', 'ProfileController@PlaygroupUpdateProfile');

//tutor profile
Route::post('add-tutor', 'ProfileController@AddTutor');
Route::post('tutor-update-profile', 'ProfileController@TutorUpdateProfile');


//student profile
Route::post('add-student', 'ProfileController@AddStudent');
Route::post('student-update-profile', 'ProfileController@StudentUpdateProfile');
Route::get('user-profile/{id}', 'AdminController@UserProfile');

//crud of membership
Route::get('memberships','AdminController@MemberShips');
Route::post('add-membership','PaymentController@AddMemberShip');
//Route::post('edit-membership','AdminController@EditMemberShip');
Route::post('delete-membership','AdminController@DeleteMemberShip');
Route::get('create-paypal-plan', 'PaymentController@create_plan');
Route::post('cancel-subscription', 'AdminController@CancelSubscription');

//Plan status
 Route::post('active-plan', 'PaymentController@ActivePlan');
 Route::post('inactive-plan', 'PaymentController@InactivePlan');

 //Payment History
 Route::get('payment-history/{type}', 'AdminController@PaymentHistory');

//Admins
Route::post('save-admin' , 'ProfileController@SaveAdmin');
Route::post('update-admin' , 'ProfileController@UpdateAdmin');
Route::post('delete-admin' , 'ProfileController@DeleteAdmin');
Route::get('admin-list' , 'ProfileController@AdminList');
