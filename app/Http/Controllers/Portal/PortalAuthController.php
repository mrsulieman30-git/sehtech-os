<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PortalAuthController extends Controller
{
    public function show()
    {
        return Inertia::render('Portal/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $credentials['email'] = strtolower($credentials['email']);

        // Attempt login using the 'portal' guard (defined in auth.php)
        if (Auth::guard('portal')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/portal/dashboard');
        }

        throw ValidationException::withMessages([
            'email' => 'Invalid credentials for client portal.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('portal')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/portal/login');
    }
}
