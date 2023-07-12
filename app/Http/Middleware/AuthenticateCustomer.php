<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class AuthenticateCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(Auth::check() && isCustomer()){
            return $next($request);
        } else {
            return redirect('401');
        }
    }
}
