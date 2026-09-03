<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Services\Auth\LoginService;
use App\Services\Auth\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OtpController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly LoginService $loginService,
    ) {}

    public function showOtp(): View|RedirectResponse
    {
        if (session()->has('hr_user_id')) {
            return redirect()->to(
                $this->otpService->dashboardRouteForLevel((int) session('hr_user_level'))
            );
        }

        if (! session('step1')) {
            return redirect()->route('login');
        }

        return view('auth.otp', [
            'remainingSeconds' => $this->otpService->remainingSeconds(),
            'resendInSeconds' => $this->otpService->resendAvailableInSeconds(),
            'maskedDestination' => $this->maskedDestination(),
            'otpLength' => $this->otpService->codeLength(),
            'demoCode' => $this->otpService->demoCode(),
            'otpFields' => array_map(
                static fn (int $i): string => 'n'.$i,
                range(1, $this->otpService->codeLength()),
            ),
        ]);
    }

    public function cancel(): View|RedirectResponse
    {
        $this->loginService->clearPendingOtp();

        return view('auth.redirect-replace', [
            'url' => route('login'),
        ]);
    }

    public function resendOtp(): RedirectResponse
    {
        $user = $this->loginService->pendingUser();

        if ($user === null) {
            return redirect()->route('login');
        }

        $result = $this->otpService->resend($user);

        if (! $result['success']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        return back()->with('otp_resent', true);
    }

    public function verifyOtp(VerifyOtpRequest $request): View|RedirectResponse
    {
        $result = $this->otpService->verify($request, $request->otpCode());

        if (! $result['success']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        return view('auth.redirect-replace', [
            'url' => $result['redirect'],
        ]);
    }

    private function maskedDestination(): string
    {
        if (session('otp_channel') === 'sms') {
            return $this->maskMobile((string) session('mobile'));
        }

        return $this->maskEmail((string) session('email'));
    }

    private function maskMobile(string $mobile): string
    {
        $length = strlen($mobile);

        if ($length < 4) {
            return $mobile;
        }

        return str_repeat('*', max(0, $length - 4)).substr($mobile, -4);
    }

    private function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, min(2, strlen($local)));

        return $visible.str_repeat('*', max(1, strlen($local) - strlen($visible))).'@'.$domain;
    }
}
