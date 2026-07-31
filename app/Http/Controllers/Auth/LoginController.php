<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\LoginService;
use App\Services\Auth\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        private readonly LoginService $loginService,
        private readonly OtpService $otpService,
    ) {}

    public function showLogin(): View|RedirectResponse
    {
        if ($this->loginService->isAuthenticated()) {
            return redirect()->to(
                $this->otpService->dashboardRouteForLevel((int) session('hr_user_level'))
            );
        }

        if ($this->loginService->hasPendingOtp()) {
            $this->loginService->clearPendingOtp();
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request): View|RedirectResponse
    {
        $result = $this->loginService->attemptCredentials(
            $request,
            $request->username(),
            $request->password(),
        );

        if (! $result['success']) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => $result['message']]);
        }

        return view('auth.redirect-replace', [
            'url' => route('otp.show'),
        ]);
    }
}
