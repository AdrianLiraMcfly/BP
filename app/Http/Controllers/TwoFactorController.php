<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Crypt;

class TwoFactorController extends Controller
{

    // Show the two factor authentication view
    public function index()
    {
        $user = auth()->user();
        if(!$user->two_factor_secret) {
            $this->genereteAndResend($user);
        }
        return view('auth.2fa');
    }
    
    // Verify the two factor authentication code
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|numeric|digits:6'
        ]);

        $user = auth()->user();
        // Decrypt the two factor secret and check if the code is valid
        $descrypted = Crypt::decryptString($user->two_factor_secret);
        
        if ($descrypted == $request->code) {
            $user->two_factor_secret = null;
            $user->save();
            session(['two_factor_verified' => true]);
            
            // Generate a new JWT token and redirect to the dashboard
            $token = JWTAuth::fromUser($user);
            
            return redirect()->route('dashboard')->withCookie(cookie('token', $token, 60));
        }

        return back()->with('error', 'Invalid code, please try again with a valid code.');
    }
    
    // Resend the two factor authentication code
    public function resend(){
        $user = auth()->user();
        // Check if the user has already requested a new code in the last 60 seconds
        if(Carbon::now()->lessThan($user->two_factor_secret_expires_at)){
            return back()->with('error', 'You can only resend the code once every 60 seconds.');
        }

        // Generate and resend the two factor authentication code
        $this->genereteAndResend($user);
        return back()->with('success', 'Code resent successfully.');
    }

    // Generate and resend the two factor authentication code
    public function genereteAndResend($user)
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
