@extends('layouts.app')

@section('title', __('password_change.title'))

@section('content')
    <div class="row justify-content-center mx-0">
        <div class="col-xl-7 col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="hm-hope-stat-icon hm-hope-stat-icon--primary"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
                        <div><h1 class="h4 mb-1">{{ __('password_change.title') }}</h1><p class="text-muted mb-0">{{ __('password_change.subtitle') }}</p></div>
                    </div>
                    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
                    <form method="post" action="{{ route('profile.password.update') }}" novalidate>
                        @csrf
                        <div class="mb-3"><label class="form-label" for="current_password">{{ __('password_change.current_password') }}</label><input class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" type="password" required autocomplete="current-password">@error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="mb-3"><label class="form-label" for="password">{{ __('password_change.new_password') }}</label><input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" required minlength="6" autocomplete="new-password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="mb-4"><label class="form-label" for="password_confirmation">{{ __('password_change.confirm_password') }}</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required minlength="6" autocomplete="new-password"></div>
                        <div class="d-flex flex-wrap gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i>{{ __('password_change.save') }}</button><a class="btn btn-light" href="{{ route('dashboard') }}">{{ __('dashboard.back_to_dashboard') }}</a></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
