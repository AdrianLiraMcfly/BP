<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

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
            'password' => 'required',
            'g-recaptcha-response' => 'required',
        ]);
    
        // Validate the captcha with Google reCAPTCHA
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $request->input('g-recaptcha-response'),
        ]);
    
        if (! $response['success']) {
            return redirect()->back()
                             ->withErrors(['g-recaptcha-response' => 'Captcha verification failed.'])
                             ->withInput();
        }

        // Find the user in the database
        $user = User::where('email', $validatedData['email'])->first();
    
        // Verify if the user exists, is active, and the password is valid
        if ($user && $user->active==='A' && Hash::check($validatedData['password'], $user->password)) {
            // Generate a signed URL for two-factor verification
            $signedUrl = URL::signedRoute('two-factor.index', ['user' => $user->id],);
    
            return redirect($signedUrl);
        }
    
        return redirect()->back()->withErrors([
            'email' => 'The provided credentials do not match our records or the account is inactive.',
        ])->withInput();
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($token = JWTAuth::getToken()) {
            JWTAuth::invalidate($token);
        }

        // Clear the token cookie explicitly
        $deleteCookie = cookie('token', null, -1);

        // Redirect to login with success message and without the token in the cookie
        return redirect()->route('login')
                         ->with('success', 'You have been logged out successfully.')
                         ->withCookie($deleteCookie);
    }
}
