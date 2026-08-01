@extends('layouts.app')
@section('title', __('transferal.title'))
@section('content')
<div class="row g-3 justify-content-center"><div class="col-md-5"><a class="text-decoration-none" href="{{ route('modules.transferal.outgoing') }}"><div class="card border-0 shadow-sm h-100"><div class="card-body text-center p-5"><i class="bi bi-box-arrow-up fs-1 text-primary"></i><h1 class="h5 mt-3">{{ __('transferal.outgoing') }}</h1></div></div></a></div><div class="col-md-5"><a class="text-decoration-none" href="{{ route('modules.transferal.incoming') }}"><div class="card border-0 shadow-sm h-100"><div class="card-body text-center p-5"><i class="bi bi-box-arrow-in-down fs-1 text-secondary"></i><h1 class="h5 mt-3">{{ __('transferal.incoming') }}</h1></div></div></a></div></div>
@endsection
