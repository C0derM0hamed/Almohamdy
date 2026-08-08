@extends('layouts.auth')

@section('auth_title', __('otp.title'))

@section('content')
    @php $isRtl = app()->getLocale() === 'ar'; @endphp

    @component('auth.partials.hope-login-shell', [
        'authImage' => asset('assets/images/auth/03.png'),
        'centered' => true,
    ])
        <div class="hm-hope-otp">
            <div class="hm-hope-auth-logos" dir="ltr">
                <img src="{{ asset('images/brand/vision-2030.png') }}" alt="{{ __('login.vision_2030') }}" class="hm-hope-auth-logos__vision">
                <img src="{{ asset('images/brand/hh-logo-horizontal.png') }}" alt="{{ __('dashboard.brand_name') }}" class="hm-hope-auth-logos__hospital">
            </div>

            <div class="hm-hope-otp__intro">
                <h2 class="hm-hope-otp__title">{{ __('otp.heading') }}</h2>
                <p class="hm-hope-otp__instruction">{{ __('otp.instruction') }}</p>
                @if (! empty($maskedDestination))
                    <p class="hm-hope-otp__destination-wrap">
                        <span class="hm-hope-otp-destination">{{ $maskedDestination }}</span>
                    </p>
                @endif
            </div>

            @if (session('otp_resent'))
                <div class="hm-auth-alert hm-auth-alert--success" role="status">
                    <i class="bi bi-check-circle" aria-hidden="true"></i>
                    <span>{{ __('otp.resent_success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="hm-auth-alert hm-auth-alert--danger" role="alert">
                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                    <span>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </span>
                </div>
            @endif

            <form method="POST" action="{{ route('otp.verify') }}" id="otp-form" class="hm-hope-otp__form">
                @csrf

                <div class="hm-hope-otp-inputs" dir="ltr">
                    @foreach ($otpFields as $field)
                        <input
                            type="text"
                            name="{{ $field }}"
                            id="{{ $field }}"
                            class="form-control hm-hope-otp-digit @error($field) is-invalid @enderror"
                            maxlength="1"
                            inputmode="numeric"
                            pattern="[0-9]"
                            autocomplete="one-time-code"
                            dir="ltr"
                            @if ($loop->first) autofocus @endif
                            required
                            aria-label="{{ __('otp.digit', ['number' => $loop->iteration]) }}"
                        >
                    @endforeach
                </div>
            </form>

            <div class="hm-hope-otp__resend">
                <div class="hm-hope-otp-timer" id="otpTimerWrap" role="timer" aria-live="polite">
                    <i class="bi bi-clock" aria-hidden="true"></i>
                    <span>
                        {{ __('otp.resend_in_prefix') }}
                        <strong id="otpTimerSeconds">{{ $resendInSeconds }}</strong>
                        {{ __('otp.resend_in_suffix') }}
                    </span>
                </div>

                <form method="POST" action="{{ route('otp.resend') }}" id="otp-resend-form" class="d-none hm-hope-otp-resend">
                    @csrf
                    <button type="submit" class="btn btn-link" id="otpResendButton">
                        {{ __('otp.resend') }}
                    </button>
                </form>
            </div>

            <div class="hm-hope-otp__footer">
                <a href="{{ route('otp.cancel') }}" class="hm-hope-otp__back">
                    <i class="bi bi-arrow-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>
                    {{ __('otp.back_to_login') }}
                </a>
            </div>
        </div>
    @endcomponent
@endsection

@push('scripts')
    <script>
        (function () {
            var inputs = document.querySelectorAll('.hm-hope-otp-digit');
            var form = document.getElementById('otp-form');
            var timerSeconds = document.getElementById('otpTimerSeconds');
            var timerWrap = document.getElementById('otpTimerWrap');
            var resendForm = document.getElementById('otp-resend-form');
            // Resend cooldown, not OTP expiry — these are separate windows.
            var remaining = {{ (int) $resendInSeconds }};
            var otpLength = {{ (int) $otpLength }};
            var isSubmitting = false;

            function allDigitsFilled() {
                return Array.prototype.every.call(inputs, function (input) {
                    return /^\d$/.test(String(input.value || '').replace(/\D/g, '').slice(0, 1));
                });
            }

            function submitWhenComplete() {
                if (isSubmitting || !form || !allDigitsFilled()) {
                    return;
                }

                isSubmitting = true;
                inputs.forEach(function (input) {
                    input.value = String(input.value || '').replace(/\D/g, '').slice(0, 1);
                    input.readOnly = true;
                });

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }

            function updateCountdown() {
                if (!timerSeconds) return;
                timerSeconds.textContent = String(Math.max(0, remaining));
            }

            function toggleResend() {
                if (!timerWrap || !resendForm) return;
                if (remaining <= 0) {
                    timerWrap.classList.add('d-none');
                    resendForm.classList.remove('d-none');
                } else {
                    timerWrap.classList.remove('d-none');
                    resendForm.classList.add('d-none');
                }
            }

            updateCountdown();
            toggleResend();

            inputs.forEach(function (input, index) {
                input.addEventListener('input', function () {
                    this.value = this.value.replace(/\D/g, '').slice(0, 1);
                    if (this.value && inputs[index + 1]) {
                        inputs[index + 1].focus();
                    }
                    submitWhenComplete();
                });

                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Backspace' && !this.value && inputs[index - 1]) {
                        inputs[index - 1].focus();
                    }
                });

                input.addEventListener('paste', function (e) {
                    e.preventDefault();
                    var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, otpLength);
                    pasted.split('').forEach(function (digit, i) {
                        if (inputs[i]) {
                            inputs[i].value = digit;
                        }
                    });
                    if (inputs[Math.min(pasted.length, otpLength) - 1]) {
                        inputs[Math.min(pasted.length, otpLength) - 1].focus();
                    }
                    submitWhenComplete();
                });
            });

            form.addEventListener('submit', function () {
                inputs.forEach(function (input) {
                    input.value = input.value.replace(/\D/g, '').slice(0, 1);
                });
                isSubmitting = true;
            });

            var timer = setInterval(function () {
                remaining--;
                updateCountdown();
                toggleResend();
                if (remaining <= 0) {
                    clearInterval(timer);
                }
            }, 1000);
        })();
    </script>
@endpush
