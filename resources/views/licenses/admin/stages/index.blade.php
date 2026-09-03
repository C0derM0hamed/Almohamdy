@extends('layouts.app')
@section('title',__('licenses.admin.stages'))
@section('sidebar_heading',__('licenses.title'))
@push('styles')<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">@endpush
@section('content')<div class="hm-licenses">@include('licenses.admin._reference-index',['resource'=>'stages','resourceLabel'=>__('licenses.admin.stages'),'resourceIcon'=>'bi-signpost-split','items'=>$stages ?? $items ?? collect()])</div>@endsection
