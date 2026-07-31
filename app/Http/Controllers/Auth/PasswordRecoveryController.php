<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordRecoveryController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (session()->has('hr_user_id')) {
            return redirect()->route('dashboard');
        }

        return view('auth.forgot-password');
    }

    public function send(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        return back()->with('status', __('password_recovery.sent'));
    }
}
