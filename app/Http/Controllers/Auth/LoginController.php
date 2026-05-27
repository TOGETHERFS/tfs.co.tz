<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     */
    public function login(Request $request)
    {
        \Log::info('Login attempt started', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'session_driver' => config('session.driver')
        ]);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            \Log::warning('Login validation failed', [
                'email' => $request->input('email'),
                'errors' => $validator->errors()->toArray()
            ]);
            
            return back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        // Normalize email to lowercase to prevent case-sensitivity issues
        $credentials = [
            'email' => strtolower(trim($request->input('email'))),
            'password' => $request->input('password')
        ];
        $remember = $request->boolean('remember');

        \Log::info('Attempting authentication', ['email' => $credentials['email']]);

        if (Auth::attempt($credentials, $remember)) {
            \Log::info('Authentication successful', [
                'email' => $credentials['email'], 
                'user_id' => Auth::id(),
            ]);

            try {
                $sessionStore = app('session.store');
                // Manually attach session to request if StartSession middleware didn't (shared hosting fix)
                if (!$request->hasSession()) {
                    $request->setLaravelSession($sessionStore);
                }
                $request->session()->regenerate();
                $request->session()->put('locale', 'sw');
                $request->session()->save();
                \Log::info('Session regenerated and saved', ['user_id' => Auth::id(), 'session_id' => $request->session()->getId()]);
            } catch (\Exception $e) {
                \Log::error('Session handling error after login', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            }

            if (function_exists('activity')) {
                try {
                    activity()
                        ->causedBy(Auth::user())
                        ->log('User logged in');
                } catch (\Exception $e) {
                    \Log::warning('Activity logging failed', ['error' => $e->getMessage()]);
                }
            }

            $user = Auth::user();
            
            // Check if user is superadmin based on role or position
            $superAliases = ['super_admin', 'superadmin', 'super-admin', 'super admin'];
            $isSuperAdmin = in_array(strtolower($user->role ?? ''), $superAliases) || 
                           strtolower($user->position ?? '') === 'superadmin';
            
            if ($isSuperAdmin) {
                \Log::info('Redirecting super admin to admin dashboard', ['user_id' => $user->id, 'role' => $user->role, 'position' => $user->position]);
                return redirect()->route('admin.dashboard');
            }
            
            // Check if user is admin staff member (has admin_role_id)
            if ($user->admin_role_id) {
                \Log::info('Redirecting admin staff to admin dashboard', ['user_id' => $user->id, 'admin_role_id' => $user->admin_role_id]);
                return redirect()->route('admin.dashboard');
            }

            \Log::info('Redirecting user to dashboard', ['user_id' => $user->id, 'tenant_id' => $user->tenant_id]);
            return redirect()->intended(route('dashboard'));
        }

        \Log::warning('Login failed - invalid credentials', [
            'email' => $credentials['email'],
            'ip' => $request->ip()
        ]);

        throw ValidationException::withMessages([
            'email' => __('These credentials do not match our records.'),
        ]);
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        // Log logout activity
        if (Auth::check()) {
            if (function_exists('activity')) {
                activity()
                    ->causedBy(Auth::user())
                    ->log('User logged out');
            }
        }

        Auth::logout();

        // Use global session helper for consistency
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    }
}