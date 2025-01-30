<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'confirmed',
                Password::min(8) // must be at least 8 characters long
                    ->mixedCase() // must contain uppercase and lowercase letters
                    ->letters() // must contain letters
                    ->numbers() // must contain numbers
                    ->symbols() // must contain symbols
                    ->uncompromised(), // verify if the password has been compromised in a data breach
            ],
            'g-recaptcha-response' => 'required',
        ]);
    
        // Verify the captcha
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $request->input('g-recaptcha-response'),
        ]);
    
        // If the captcha verification fails, redirect back with an error message
        if (! $response['success']) {
            return redirect()->back()
                ->withErrors(['g-recaptcha-response' => 'Captcha verification failed.'])
                ->withInput();
        }
    
        // Generate a verification token
        $verification_token = Crypt::encryptString(Str::random(16));
    
        // Create a new user
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'verify_token' => $verification_token,
        ]);
    
        // Trigger the Registered event
        event(new Registered($user));
    
        // Send the verification email
        Mail::send('emails.verify', ['user' => $user, 'verification_token' => $verification_token], function($message) use ($user) {
            $message->to($user->email);
            $message->subject('Please verify your email address');
        });
    
        // Redirect to the login page with a success message
        return redirect()->route('login')->with('success', 'We have sent you a verification email.');
    }

    public function verify($token)
    {
        // Find the user with the given token
        $user = User::where('verify_token', $token)->first();
        
        // If the user exists, verify the email
        if($user) {
            $user->email_verified_at = now();
            $user->verify_token = null;
            $user->active = 'A';
            $user->save();

            return redirect()->route('login')->with('success', 'Your email has been verified.');
        } else {
            return redirect()->route('login')->with('error', 'Verification failed. Please try again.');
        }
    }
}
