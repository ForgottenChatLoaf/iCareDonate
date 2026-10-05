<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return Auth::user()->isAdmin() 
                ? redirect()->route('admin.dashboard') 
                : redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user && ($user->status === 'banned' || $user->status === 'suspended')) {
            return back()->withErrors([
                'email' => 'This account has been ' . $user->status . '. Reason: ' . ($user->suspension_reason ?? 'Violation of community safety policies.'),
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            $user->update(['last_login_at' => now()]);

            AuditLog::log('USER_LOGIN', 'user', $user->id, 'User successfully authenticated.');

            if ($user->isAdmin()) {
                return redirect()->intended(route('admin.dashboard'))->with('success', 'Welcome back, Administrator ' . $user->name);
            }

            return redirect()->intended(route('dashboard'))->with('success', 'Welcome back, ' . $user->name);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'username' => ['nullable', 'string', 'max:50', 'unique:users'],
            'phone' => ['required', 'string', 'max:30'],
            'barangay' => ['nullable', 'string', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'] ?? strtolower(explode('@', $validated['email'])[0]),
            'phone' => $validated['phone'],
            'barangay' => $validated['barangay'] ?? null,
            'city' => $validated['city'] ?? null,
            'province' => $validated['province'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'user', // Public registrations are ALWAYS normal users
            'status' => 'active',
            'email_verified_at' => now(),
            'last_login_at' => now(),
        ]);

        Auth::login($user);

        AuditLog::log('USER_REGISTERED', 'user', $user->id, 'New user account created.');

        return redirect()->route('dashboard')->with('success', 'Your iCareDonate account has been created successfully! Welcome to the community.');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            AuditLog::log('USER_LOGOUT', 'user', $user->id, 'User signed out.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been safely signed out.');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        return back()->with('success', 'If an account exists with this email, recovery instructions have been simulated to your inbox.');
    }
}
