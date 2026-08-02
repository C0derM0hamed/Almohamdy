@extends('layouts.app')
@section('title', 'إضافة - '.$definition['title'])
@section('content')
@php
$fixed = ['language'=>[1=>'العربية',2=>'الإنجليزية'],'date_type'=>[1=>'ميلادي',2=>'هجري'],'report_type'=>[1=>'هروب الأم وترك المولود',2=>'بلاغ آخر'],'side_type'=>['الشرطة'=>'الشرطة','المرور'=>'المرور','الدوريات الأمنية'=>'الدوريات الأمنية','الدفاع المدني'=>'الدفاع المدني']];
@endphp
<div class="mb-4"><h1 class="h4 mb-1">إضافة - {{ $definition['title'] }}</h1><a href="{{ route('modules.emergency-reception.index', $type) }}">العودة إلى القائمة</a></div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="post" action="{{ route('modules.emergency-reception.store', $type) }}" class="card border-0 shadow-sm"><div class="card-body row g-3">@csrf
@foreach($definition['fields'] as $name => [$label,$kind,$required])
<div class="{{ $kind==='textarea' ? 'col-12' : 'col-md-6' }}"><label class="form-label">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
@if(isset($lookups[$kind]))<select class="form-select" name="{{ $name }}" @required($required)><option value="">اختر...</option>@foreach($lookups[$kind] as $option)<option value="{{ $option->id }}" @selected(old($name)==$option->id)>{{ $kind==='country' ? $option->country_nationality_ar : $option->name_ar }}</option>@endforeach</select>
@elseif($kind==='gender')<select class="form-select" name="{{ $name }}" @required($required)><option value="">اختر...</option>@foreach($lookups['gender'] as $option)<option value="{{ $option->id }}">{{ $option->name_ar }}</option>@endforeach</select>
@elseif(isset($fixed[$kind]))<select class="form-select" name="{{ $name }}" @required($required)><option value="">اختر...</option>@foreach($fixed[$kind] as $value=>$text)<option value="{{ $value }}" @selected(old($name)==$value)>{{ $text }}</option>@endforeach</select>
@elseif($kind==='textarea')<textarea class="form-control" rows="3" name="{{ $name }}" @required($required)>{{ old($name) }}</textarea>
@else<input class="form-control" type="{{ $kind }}" name="{{ $name }}" value="{{ old($name) }}" @required($required)>@endif</div>
@endforeach
</div><div class="card-footer bg-transparent"><button class="btn btn-primary">حفظ</button></div></form>
@endsection
