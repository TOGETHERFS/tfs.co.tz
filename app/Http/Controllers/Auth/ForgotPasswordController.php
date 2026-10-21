<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\Sms\BeemAfricaService;
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

    // ─────────────────────────────────────────────────────────────────────────
    // SMS OTP PASSWORD RESET
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Show the phone number entry form for SMS OTP reset.
     */
    public function showSmsResetForm()
    {
        return view('auth.forgot-password-sms');
    }

    /**
     * Send a 6-digit OTP to the user's registered phone number.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = $this->normalizePhone($request->phone);

        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return back()->withErrors(['phone' => 'No account found with that phone number.'])->withInput();
        }

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Delete any existing OTPs for this phone
        DB::table('sms_otp_codes')->where('phone', $phone)->delete();

        // Store hashed OTP (expires in 10 minutes)
        DB::table('sms_otp_codes')->insert([
            'phone'      => $phone,
            'code'       => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Send OTP via Beem Africa SMS
        try {
            $beem    = app(BeemAfricaService::class);
            $message = "TFS Password Reset: Your OTP is {$otp}. Valid for 10 minutes. Do not share.";
            $result  = $beem->sendSms('TFS', $phone, $message);

            if (!$result['success']) {
                \Log::warning('OTP SMS failed', ['phone' => $phone, 'error' => $result['error'] ?? '']);
                return back()->with('error', 'Failed to send OTP SMS. Please try again or contact support.')->withInput();
            }
        } catch (\Exception $e) {
            \Log::error('OTP SMS exception', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to send OTP SMS: ' . $e->getMessage())->withInput();
        }

        return redirect()->route('password.sms.verify', ['phone' => $phone])
            ->with('status', 'OTP sent to your phone. Enter it below.');
    }

    /**
     * Show OTP verification form.
     */
    public function showOtpVerify(Request $request)
    {
        return view('auth.verify-otp', [
            'phone' => $request->query('phone'),
        ]);
    }

    /**
     * Verify OTP and redirect to new-password form.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp'   => 'required|string|size:6',
        ]);

        $phone  = $this->normalizePhone($request->phone);
        $record = DB::table('sms_otp_codes')->where('phone', $phone)->first();

        if (!$record) {
            return back()->withErrors(['otp' => 'OTP not found. Please request a new one.'])->withInput();
        }

        if (now()->isAfter($record->expires_at)) {
            DB::table('sms_otp_codes')->where('phone', $phone)->delete();
            return back()->withErrors(['otp' => 'OTP has expired. Please request a new one.'])->withInput();
        }

        if (!Hash::check($request->otp, $record->code)) {
            return back()->withErrors(['otp' => 'Invalid OTP. Please check and try again.'])->withInput();
        }

        // OTP valid — store a single-use token for the reset form
        $token = Str::random(64);
        DB::table('sms_otp_codes')->where('phone', $phone)->update([
            'code'       => 'VERIFIED:' . Hash::make($token),
            'expires_at' => now()->addMinutes(15),
            'updated_at' => now(),
        ]);

        return redirect()->route('password.sms.reset.form', [
            'phone' => $phone,
            'token' => $token,
        ]);
    }

    /**
     * Show the new password form after OTP verification.
     */
    public function showSmsPasswordResetForm(Request $request)
    {
        return view('auth.reset-password-sms', [
            'phone' => $request->query('phone'),
            'token' => $request->query('token'),
        ]);
    }

    /**
     * Set the new password after OTP verification.
     */
    public function resetViaSms(Request $request)
    {
        $request->validate([
            'phone'    => 'required|string',
            'token'    => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $phone  = $this->normalizePhone($request->phone);
        $record = DB::table('sms_otp_codes')->where('phone', $phone)->first();

        if (!$record || !str_starts_with($record->code, 'VERIFIED:')) {
            return back()->withErrors(['token' => 'Session expired. Please start the reset process again.']);
        }

        if (now()->isAfter($record->expires_at)) {
            DB::table('sms_otp_codes')->where('phone', $phone)->delete();
            return redirect()->route('password.sms.request')
                ->with('error', 'Session expired. Please request a new OTP.');
        }

        $storedHash = substr($record->code, strlen('VERIFIED:'));
        if (!Hash::check($request->token, $storedHash)) {
            return back()->withErrors(['token' => 'Invalid reset token. Please start again.']);
        }

        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Account not found.');
        }

        $user->update(['password' => Hash::make($request->password)]);

        DB::table('sms_otp_codes')->where('phone', $phone)->delete();

        return redirect()->route('login')->with('status', 'Password reset successfully. You can now log in.');
    }

    /**
     * Normalise phone number to 255XXXXXXXXX format.
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 9) return '255' . $phone;
        if (strlen($phone) === 10 && str_starts_with($phone, '0')) return '255' . substr($phone, 1);
        return $phone;
    }
}