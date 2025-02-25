<?php
/**
 * Middleware to handle user authentication.
 *
 * This middleware checks if the user is authenticated by verifying the JWT token
 * stored in the request's cookies. If the token is not present or invalid, the user
 * is redirected to the login route with a session expired message.
 *
 * @package App\Http\Middleware
 */
namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Tymon\JWTAuth\Facades\JWTAuth;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        $token = $request->cookie('token');
        if (!$token||!JWTAuth::setToken($token)->authenticate()) {
            return route('login')->with('message', 'Your session has expired');
        }

    }
}
