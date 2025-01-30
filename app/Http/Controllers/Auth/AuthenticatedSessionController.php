<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the user login request including the captcha
        $validatedData = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'g-recaptcha-response' => 'required',
        ]);
    
        // Validate the captcha with Google reCAPTCHA
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $request->input('g-recaptcha-response'),
        ]);
    
        // If the captcha verification fails, return back with an error message
        if (! $response['success']) {
            return redirect()->back()
                             ->withErrors(['g-recaptcha-response' => 'Captcha verification failed.'])
                             ->withInput();
        }
    
        // Attempt to authenticate the user
        if (Auth::guard('web')->attempt($validatedData)) {
            // $request->session()->regenerate(); // Uncomment if you need to regenerate the session
    
            return redirect()->route('two-factor.index');
        }
    
        // If the authentication fails, return back with an error message
        return redirect()->back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput();
    }
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Invalidate the token and logout the user
        if($token = $request->cookie('token')) {
            JWTAuth::setToken($token)->invalidate();
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();
        
        // Redirect to the home page and remove the token cookie
        return redirect('/')->withCookie(cookie()->forget('token'));
    }
}
