<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class WebAuthController extends Controller
{
    public function showLogin() { return view('auth.login'); }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if (!$user->is_approved) {
                Auth::logout();
                return back()->withErrors(['email' => 'Your account is pending approval by a Superadmin.'])->onlyInput('email');
            }
            $request->session()->regenerate();
            return redirect()->intended('dashboard');
        }

        return back()->withErrors(['email' => 'Invalid credentials'])->onlyInput('email');
    }

    public function showRegister() { return view('auth.register'); }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'admin_key' => 'nullable|string', // Optional key for super admin
        ]);

        $role = 'admin'; // Default role
        $isApproved = false; // Admins need approval

        if ($request->filled('admin_key')) {
            if ($request->admin_key === config('app.super_admin_key')) {
                $role = 'super_admin';
                $isApproved = true; // Super Admins are auto-approved
            } else {
                return back()->withErrors(['admin_key' => 'Invalid super admin key. Leave blank for normal Admin.'])->withInput();
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
            'is_approved' => $isApproved,
        ]);

        if (!$isApproved) {
            return redirect()->route('login')->with('success', 'Registration successful! Your account is pending Superadmin approval.');
        }

        Auth::login($user);
        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
