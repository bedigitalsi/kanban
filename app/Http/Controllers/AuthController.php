<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('authenticated')) {
            return redirect()->route('taskboard');
        }
        
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required'
        ]);

        $appPassword = config('app.password', env('APP_PASSWORD', 'taskboard123'));

        if ($request->password === $appPassword) {
            session(['authenticated' => true]);
            return redirect()->route('taskboard');
        }

        return back()->withErrors([
            'password' => 'Invalid password.'
        ]);
    }

    public function logout(): RedirectResponse
    {
        session()->forget('authenticated');
        return redirect()->route('login');
    }
}
