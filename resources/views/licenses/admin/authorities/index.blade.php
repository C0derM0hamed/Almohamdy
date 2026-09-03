@extends('layouts.app')
@section('title',__('licenses.admin.authorities'))
@section('sidebar_heading',__('licenses.title'))
@push('styles')<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">@endpush
@section('content')<div class="hm-licenses">@include('licenses.admin._reference-index',['resource'=>'authorities','resourceLabel'=>__('licenses.admin.authorities'),'resourceIcon'=>'bi-bank','items'=>$authorities ?? $items ?? collect()])</div>@endsection
