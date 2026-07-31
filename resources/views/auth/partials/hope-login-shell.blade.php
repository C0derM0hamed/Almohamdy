@php
    $authImage = $authImage ?? asset('assets/images/auth/01.png');
    $centered = (bool) ($centered ?? false);
@endphp

<section class="login-content hm-hope-auth{{ $centered ? ' hm-hope-auth--centered' : '' }}">
    <div class="row m-0 align-items-stretch bg-white hm-hope-auth__row">
        <div class="col-md-6 order-md-1 order-2 hm-hope-auth__form-col">
            @if ($centered)
                <div class="hm-hope-auth__center-wrap">
                    <div class="card card-transparent shadow-none mb-0 auth-card hm-hope-auth__card">
                        <div class="card-body p-0">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            @else
                <div class="row justify-content-center h-100 m-0">
                    <div class="col-11 col-sm-10 col-lg-9 col-xl-8">
                        <div class="card card-transparent shadow-none d-flex justify-content-center mb-0 auth-card hm-hope-auth__card">
                            <div class="card-body p-0">
                                {{ $slot }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <div class="sign-bg" aria-hidden="true">
                <svg width="280" height="230" viewBox="0 0 431 398" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g opacity="0.05">
                        <rect x="-157.085" y="193.773" width="543" height="77.5714" rx="38.7857" transform="rotate(-45 -157.085 193.773)" fill="#3B8AFF"/>
                        <rect x="7.46875" y="358.327" width="543" height="77.5714" rx="38.7857" transform="rotate(-45 7.46875 358.327)" fill="#3B8AFF"/>
                        <rect x="61.9355" y="138.545" width="310.286" height="77.5714" rx="38.7857" transform="rotate(45 61.9355 138.545)" fill="#3B8AFF"/>
                        <rect x="62.3154" y="-190.173" width="543" height="77.5714" rx="38.7857" transform="rotate(45 62.3154 -190.173)" fill="#3B8AFF"/>
                    </g>
                </svg>
            </div>
        </div>
        <div class="col-md-6 order-md-2 order-1 d-none d-md-block bg-primary p-0 hm-hope-auth__visual-col">
            <img src="{{ $authImage }}" class="img-fluid gradient-main animated-scaleX w-100 h-100 hm-hope-auth__visual" alt="">
        </div>
    </div>
</section>
