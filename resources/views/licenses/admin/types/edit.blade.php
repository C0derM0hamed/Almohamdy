@extends('layouts.app')
@section('title',__('licenses.admin.types'))
@section('sidebar_heading',__('licenses.title'))
@push('styles')<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">@endpush
@section('content')<div class="hm-licenses">@include('licenses.admin._reference-form',['resource'=>'types','resourceLabel'=>__('licenses.admin.types'),'item'=>$type ?? $item])</div>@endsection
