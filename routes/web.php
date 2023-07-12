<?php

/*
|--------------------------------------------------------------------------
| Website Routes
|--------------------------------------------------------------------------
*/

Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
Route::get('401', function(){
	$message = 'You are not Authorized!!';
	$response = [
        'success' => false,
        'error' => [
            'general' => $message
        ]
    ];

    return response()->json($response, 401, []);
})->name('401');

Route::get('404', function(){
	$message = 'Endpoint does not exist!!';
	$response = [
        'success' => false,
        'error' => [
            'general' => $message
        ]
    ];

    return response()->json($response, 404, []);
})->name('404');

Route::fallback(function() {
    return redirect('404');
});

#PUBLIC URLS
Route::get('/', function(){
	return view('website.index');
});
