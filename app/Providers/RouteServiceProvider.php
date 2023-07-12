<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();
    }

    protected function mapApiRoutes()
    {
        Route::prefix('api/customer')
             ->namespace($this->namespace.'\Api')
             ->group(base_path('routes/customer-api.php'));

        Route::prefix('api/business')
             ->namespace($this->namespace.'\Api')
             ->group(base_path('routes/business-api.php')); 
                 
        Route::prefix('api/admin')
            ->middleware('auth-admin')
             ->namespace($this->namespace.'\Api')
             ->group(base_path('routes/admin-api.php'));

        Route::prefix('api')
             ->namespace($this->namespace.'\Api')
             ->group(base_path('routes/public-api.php'));
       
        Route:: middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }
}
