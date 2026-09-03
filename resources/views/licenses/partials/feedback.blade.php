@if (session('success'))
    <div class="lic-alert lic-alert--success" role="status"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="lic-alert lic-alert--danger" role="alert"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="lic-alert lic-alert--danger" role="alert" tabindex="-1">
        <strong>{{ __('licenses.validation_title') }}</strong>
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
