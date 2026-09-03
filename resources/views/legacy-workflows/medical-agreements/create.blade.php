@extends('layouts.app')
@section('title', 'اتفاقية تقديم خدمات طبية جديدة')
@section('content')
<div class="mb-3"><a class="text-decoration-none" href="{{ route('modules.medical-agreements.index', $variant) }}"><i class="bi bi-arrow-right"></i> العودة للاتفاقيات</a></div>
<div class="card border-0 shadow-sm"><div class="card-body"><h1 class="h5 mb-4">اتفاقية تقديم خدمات طبية جديدة</h1>@include('legacy-workflows.medical-agreements._form', ['modal' => false])</div></div>
@endsection
