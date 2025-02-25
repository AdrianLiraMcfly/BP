<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Crypt;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TwoFactorController extends Controller
{
    protected $user;
    
    // Show the two factor authentication view
    public function index(Request $request)
    {
        // Check if the URL signature is valid
        if (!$request->hasValidSignature()) {
            return back()->with('error', 'Invalid URL.');
        }
    
        // Get the user ID from the signed route
        $user = User::findOrFail($request->route('user'));
        
        // Log the found user
        Log::info('User found:', ['user' => $user->toArray()]);
    
        // Check if the user has a two factor authentication secret
        if (!$user->two_factor_secret) {
            $this->generateAndResend($user); // Generate and resend the 2FA code if necessary
        }
        
        // Return the two factor authentication view
        return view('auth.2fa', ['userId' => $user->id]);
    }
    
    public function verify(Request $request)
    {
        // Validar el código y el reCAPTCHA
        $request->validate([
            'code' => 'required|numeric|digits:6',
            'user' => 'required|exists:users,id', // Validar que el ID del usuario existe
            'g-recaptcha-response' => 'required', // Validar que el reCAPTCHA está presente
        ]);
    
        // Validar el reCAPTCHA con Google reCAPTCHA
        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $request->input('g-recaptcha-response'),
            ])->json();
        } catch (\Exception $e) {
            Log::error('reCAPTCHA verification failed:', ['error' => $e->getMessage()]);
            return redirect()->back()
                     ->withErrors(['g-recaptcha-response' => 'Captcha verification failed.'])
                     ->withInput();
        }
    
        if (! $response['success']) {
            return redirect()->back()
                             ->withErrors(['g-recaptcha-response' => 'Captcha verification failed.'])
                             ->withInput();
        }
    
        // Recuperar el usuario del ID enviado en el formulario
        $user = User::findOrFail($request->user);
    
        // Verificar si el secreto de dos factores ha expirado
        if (Carbon::now()->greaterThan(Carbon::parse($user->two_factor_secret_expires_at)->addMinutes(3))) {
            // Invalidar el token JWT actual y redirigir
            $user->two_factor_secret = null;
            $user->two_factor_secret_expires_at = null;
            $user->save();
            return back()->with('error', 'The two factor code has expired. Please request a new code.');
        }
    
        // Desencriptar el secreto de dos factores y verificar si el código es válido
        $decrypted = Crypt::decryptString($user->two_factor_secret);
    
        if ($decrypted == $request->code) {
            $user->two_factor_secret = null;
            $user->two_factor_secret_expires_at = null;
            $user->save();
    
            // Generar un nuevo token JWT y redirigir al dashboard
            $newToken = JWTAuth::fromUser($user);
            
            return redirect()->route('dashboard')->withCookie(cookie('token', $newToken, 60));
        }
    
        return back()->with('error', 'Invalid code, please try again with a valid code.');
    }
    
    public function resend(Request $request)
    {
        $request->validate([
            'user' => 'required|exists:users,id', // Validate that the user ID exists
        ]);
    
        // Retrieve the user from the ID sent in the form
        $user = User::findOrFail($request->user);
    
        // Check if the user has already requested a new code in the last 60 seconds
        if (Carbon::now()->lessThan($user->two_factor_secret_expires_at)) {
            return back()->with('error', 'You can only resend the code once every 60 seconds.');
        }
    
        // Generate and resend the two factor authentication code
        $this->generateAndResend($user);
    
        return back()->with('success', 'Code resent successfully.');
    }
    
    // Generate and resend the two factor authentication code
    public function generateAndResend($user)
    {
        $code = rand(100000, 999999);
        
        // Encrypt the two factor secret and save it in the database
        $encrypted = Crypt::encryptString($code);
        $user->two_factor_secret = $encrypted;
        
        // Set the expiration time to 60 seconds
        $user->two_factor_secret_expires_at = Carbon::now()->addSeconds(60);
        $user->save();
        
        // Send the two factor code to the user
        Mail::raw("Your two factor code is: $code", function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Two Factor Code');
        });
    }
}