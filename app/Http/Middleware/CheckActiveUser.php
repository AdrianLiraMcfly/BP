<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckActiveUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if ($user && $user->active === 'A') {
            return $next($request);
        }

        //Invalidate the jwt token and redirect to login page
        if($token = JWTAuth::getToken()){
            JWTAuth::setToken($token)->invalidate();
            session()->flush();
        }
        
        auth()->logout();
        return redirect('/login')->with('error', 'Your account is not active.');
    }
}
