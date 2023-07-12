<?php

/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
*/

Route::group(['middleware'=>['auth', 'auth-admin']], function(){
	Route::get('/', 'AdminController@Dashboard');

	Route::get('manage-users','AdminController@ManageUser');

	Route::get('add-user/{id?}','AdminController@AddUsers');

	Route::get('user-status/{id}/{status}','AdminController@UserStatus');
});