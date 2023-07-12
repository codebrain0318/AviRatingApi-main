<?php

/*
|--------------------------------------------------------------------------
| Website Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix'=>'lov'], function(){
    Route::get('countries', 'LovController@Countries');
    Route::get('cities/{id}', 'LovController@Cities');
    Route::get('subjects', 'LovController@Subjects');
    Route::get('educations', 'LovController@Educations');
    Route::get('major-degrees', 'LovController@MajorDegrees');
});

Route::group(['prefix'=>'public'], function(){

    Route::get('test-auto', 'PublicController@Test');
    #test
    Route::get('testhook1', 'PaymentController@WhebHookList');
    Route::any('web-hook', 'PaymentController@WebHook');

    Route::post('user-membership', 'PublicController@UserMembership');

    Route::post('bulk-upload', 'BulkUploadController@BulkUploadUsers');

    Route::post('buy-review-reply', 'BusinessOwnerController@BuyReviewReply');

    Route::get('listings', 'ListingController@Listings');
    Route::post('listing-detail', 'ListingController@ListingDetail');
    Route::get('listing-image/{id}', 'ListingController@ListingImage');
    Route::get('listing-small-thumbnail/{id}','ListingController@ListingSmallThumbnail');
    Route::get('listing-large-thumbnail/{id}','ListingController@ListingLargeThumbnail');

    #Services
    Route::get('services', 'AdminController@Services');

    #Amenities
    Route::get('amenities', 'AdminController@Amenities');

    Route::get('business/{id}', 'PublicController@Business'); 
    Route::get('business-categories', 'AdminController@BusinessCategories');
    Route::get('business-requests', 'AdminController@BusinessRequests');
    Route::get('listing-reviews/{id}', 'ListingReviewController@ListingReview');   
    Route::get('customer/{id}', 'PublicController@Customer');
    Route::post('add-business', 'RegistrationController@AddBusiness');
    Route::post('claim-business', 'PublicController@ClaimBusiness');

    #Reviews
    Route::get('business-reviews/{id}', 'BusinessReviewController@BusinessReviews');

    #Homepage Banners
    Route::get('homepage-banners','PublicController@HomepageBanners');
    Route::get('banner-image/{id}','PublicController@HomepageBannerImage');


    Route::post('verify-email', 'PublicController@VerifyEmail');
    Route::post('resend-code', 'PublicController@ResendCode');
    Route::post('contact-us', 'PublicController@ContactUs');

    Route::post('login', 'PublicController@Login');
    Route::post('forgot-password', 'PublicController@ForgotPassword');
    Route::post('verify-code', 'PublicController@VerifyCode');
    Route::post('reset-password', 'PublicController@ResetPassword');
    Route::get('tutor-avg-rating/{tutor_id}', 'PublicController@TutorAvgRating');
    Route::get('student-avg-rating/{student_id}', 'PublicController@StudentAvgRating');
    Route::get('tutor-feedback/{tutor_id}', 'PublicController@TutorFeedback');
    Route::get('student-feedback/{student_id}', 'PublicController@StudentFeedback');
    Route::post('tutor-search', 'PublicController@TutorSearch');
    
    #profile
    Route::get('user-profile/{id}', 'PublicController@UserProfile');
    Route::get('center/{id}', 'PublicController@CenterProfile');
    Route::get('playgroup/{id}', 'PublicController@PlaygroupProfile');
   
    #FeedBack
    Route::post('save-feedback', 'FeedbackController@SaveFeedback');
    Route::get('user-feedback', 'FeedbackController@UserFeedback');
    Route::get('user-feedbacks/{id}','FeedbackController@UserFeedbacks');

    #Business Type List
    Route::get('business-types', 'AdminController@BusinessTypes');
    #Paypal

    // Route::get('test', 'PaymentController@create_plan');
   

    // Route::get('update-plan', 'PaymentController@UpdatePlan');
    // Route::get('get-plan', 'PaymentController@GetPlan');
    
    Route::get('all-plan', 'PaymentController@AllPlans');
    Route::get('plan/{planId}', 'PaymentController@SingelPlan');
    Route::get('web-hooks', 'PaymentController@GetWebhook');
    Route::get('hresponse/{id}', 'PaymentController@SendHookResponse');

    Route::get('agreement-detail/{agrId}', 'PaymentController@Agreement');
    Route::get('suspend-agreement/{agrId}', 'PaymentController@SuspendAgreement');
    Route::get('reactive-agreement/{agrId}', 'PaymentController@ReactiveAgreement');

    //claim business return urls
    Route::any('claim-business-payment-cancel/{claim_id}', 'PaymentController@CancelClaim');
    Route::any('claim-business-payment-status/{claim_id}', 'PaymentController@ClaimBusinessStatus');

    Route::get('create-paypal-plan', 'PaymentController@create_plan');
    Route::get('banner-payment', 'PaymentController@Pay');


    Route::get('membership-image/{id}','PublicController@MembershipImage');
    Route::get('profile-image/{id}','PublicController@ProfilePicture');
    Route::get('businesses-list','BusinessOwnerController@BusinessesList');
    Route::get('business-category-image/{id}', 'PublicController@BusinessCategoryImage');
    Route::get('home-page-items', 'PublicController@HomePageItems');
    Route::get('settings','AdminController@Settings');
    Route::get('review-image/{id}','BusinessReviewController@ReviewImage');
    Route::get('reply-image/{id}','ListingReviewController@ReplyImage');
    Route::any('tes-resp', 'PublicController@TestResp');
    
});

