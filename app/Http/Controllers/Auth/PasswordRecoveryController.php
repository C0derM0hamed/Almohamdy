<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\StartPasswordRecoveryRequest;
use App\Http\Requests\Auth\VerifyPasswordRecoveryOtpRequest;
use App\Services\Auth\PasswordRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordRecoveryController extends Controller
{
    public function __construct(
        private readonly PasswordRecoveryService $recovery,
    ) {}

    public function show(): View|RedirectResponse
    {
        if (session()->has('hr_user_id')) {
            return redirect()->route('dashboard');
        }

        return view('auth.forgot-password');
    }

    public function send(StartPasswordRecoveryRequest $request): RedirectResponse
    {
        $this->recovery->begin($request, $request->username(), $request->mobile());

        return redirect()->route('password.otp.show')->with('status', __('password_recovery.sent'));
    }

    public function showOtp(): View|RedirectResponse
    {
        if (! $this->recovery->hasPendingChallenge()) {
            return redirect()->route('password.forgot');
        }

        return view('auth.password-recovery-otp');
    }

    public function verifyOtp(VerifyPasswordRecoveryOtpRequest $request): RedirectResponse
    {
        if (! $this->recovery->verify($request, (string) $request->input('otp'))) {
            return back()->withErrors(['otp' => __('password_recovery.invalid_otp')]);
        }

        return redirect()->route('password.reset.show');
    }

    public function showReset(): View|RedirectResponse
    {
        if (! $this->recovery->isAuthorized()) {
            return redirect()->route('password.forgot');
        }

        return view('auth.reset-password');
    }

    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        if (! $this->recovery->reset((string) $request->input('password'))) {
            return redirect()->route('password.forgot')
                ->withErrors(['username' => __('password_recovery.expired')]);
        }

        return redirect()->route('login')->with('status', __('password_recovery.reset_success'));
    }
}
