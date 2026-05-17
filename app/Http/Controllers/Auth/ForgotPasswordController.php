<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    /**
     * Show the forgot password form.
     */
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a reset link to the given user.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['We can\'t find a user with that email address.'],
            ]);
        }

        // Delete any existing tokens for this email
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Create a new token
        $token = Str::random(64);

        // Store the token in the database
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // Build the reset URL
        $baseUrl = config('app.url', 'https://phidlms.co.tz');
        $resetUrl = $baseUrl . '/reset-password/' . $token . '?email=' . urlencode($user->email);

        // Send the reset email
        try {
            $this->sendResetEmail($user, $token);
            return back()
                ->with('status', 'We have emailed your password reset link!');
        } catch (\Exception $e) {
            \Log::error('Password reset email failed for: ' . $user->email . '. Error: ' . $e->getMessage());

            // Provide a development fallback link so users can still reset
            $baseUrl = config('app.url', 'https://phidlms.co.tz');
            $resetUrl = $baseUrl . '/reset-password/' . $token . '?email=' . urlencode($user->email);

            if (config('app.debug')) {
                return back()
                    ->with('error', 'Failed to send reset email. Please try again later.');
            }

            // In production, show generic success message to prevent email enumeration
            return back()->with('status', 'If your email exists in our system, you will receive a password reset link.');
        }
    }

    /**
     * Show the password reset form.
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Reset the given user's password.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Find the token record
        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$tokenRecord || !Hash::check($request->token, $tokenRecord->token)) {
            throw ValidationException::withMessages([
                'email' => ['This password reset token is invalid.'],
            ]);
        }

        // Check if token is expired (24 hours)
        if (now()->diffInHours($tokenRecord->created_at) > 24) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            throw ValidationException::withMessages([
                'email' => ['This password reset token has expired.'],
            ]);
        }

        // Update the user's password
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Delete the token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('status', 'Your password has been reset!');
    }

    /**
     * Send the password reset email.
     */
    private function sendResetEmail($user, $token)
    {
        // Build the reset URL using config app.url
        $baseUrl = config('app.url', 'https://phidlms.co.tz');
        $resetUrl = $baseUrl . '/reset-password/' . $token . '?email=' . urlencode($user->email);
        
        \Log::info('Generated reset URL: ' . $resetUrl);

        try {
            // Send the password reset notification
            $user->notify(new ResetPasswordNotification($token, $resetUrl));
            \Log::info('Password reset email sent successfully to: ' . $user->email . ' with URL: ' . $resetUrl);
        } catch (\Exception $e) {
            // Log the error but don't fail the request
            \Log::error('Failed to send password reset email to: ' . $user->email . '. Error: ' . $e->getMessage());
            
            // For development/debugging, also log the reset URL
            \Log::info('Password reset URL (email failed): ' . $resetUrl);
            
            // If this is a Gmail authentication error, provide specific guidance
            if (strpos($e->getMessage(), 'Application-specific password required') !== false) {
                \Log::warning('Gmail SMTP requires an App Password. Please generate one at: https://myaccount.google.com/apppasswords');
            }
            
            // Re-throw the exception so the user knows there was an issue
            throw $e;
        }
    }
}