@extends('layouts.public-reply')
@section('title', $title)
@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body p-4 text-center">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @elseif(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <h1 class="h4 mb-0">{{ $title }}</h1>
    </div>
</div>
@endsection
