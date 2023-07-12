<?php

namespace App\Http\Middleware;
use App;
use Closure;
use Auth;

class LanguageMiddleware
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
        $lang = $request->header('lang');
        App::setLocale($lang);
        return $next($request);
        
    }
}