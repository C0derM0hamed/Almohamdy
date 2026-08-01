@extends('layouts.app')
@section('title', __('training.details'))
@section('sidebar_heading', __('training.management'))
@section('content')
<div class="hm-module-page" data-module="training-detail">
 @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
 <div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3">{{ __('training.details') }} #{{ $training->id }}</h1><span class="badge bg-primary">{{ $training->currentStatus?->name_ar }}</span></div><a class="btn btn-outline-secondary" href="{{ route('modules.training.management.index') }}">{{ __('training.back') }}</a></div>
 <div class="row g-4"><div class="col-lg-7"><div class="card h-100"><div class="card-body"><dl class="row mb-0">
  <dt class="col-sm-4">{{ __('training.employee') }}</dt><dd class="col-sm-8">{{ $training->employee?->displayName() }} ({{ $training->employee?->hr_username }})</dd>
  <dt class="col-sm-4">{{ __('training.job_title') }}</dt><dd class="col-sm-8">{{ $training->employee?->jobTitle?->localizedName() ?? '—' }}</dd>
  <dt class="col-sm-4">{{ __('training.coordinator') }}</dt><dd class="col-sm-8">{{ $training->coordinator?->displayName() ?? '—' }}</dd>
  <dt class="col-sm-4">{{ __('training.branch') }}</dt><dd class="col-sm-8">{{ $training->branch?->localizedName() ?? '—' }}</dd>
  <dt class="col-sm-4">{{ __('training.begin_date') }}</dt><dd class="col-sm-8">{{ $training->begin_date?->format('Y-m-d') }}</dd><dt class="col-sm-4">{{ __('training.end_date') }}</dt><dd class="col-sm-8">{{ $training->endDate()?->format('Y-m-d') }}</dd>
  <dt class="col-sm-4">{{ __('training.schedule') }}</dt><dd class="col-sm-8">{{ $training->days }} {{ __('training.day') }}، {{ $training->training_hour }} {{ __('training.hour_daily') }}، {{ $training->time_from }} - {{ $training->time_to }}</dd>
 </dl></div></div></div>
 <div class="col-lg-5"><div class="card"><div class="card-header"><h2 class="h5 mb-0">{{ __('training.update_status') }}</h2></div><div class="card-body"><form method="POST" action="{{ route('modules.training.management.status', $training->id) }}">@csrf
  <label class="form-label">{{ __('training.status') }}</label><select class="form-select mb-3" name="status_id" id="managementStatus" required><option value="">—</option>@foreach($managementStatuses as $status)<option value="{{ $status->id }}" @disabled((int)$training->status === (int)$status->id)>{{ $status->name_ar }}</option>@endforeach</select>
  <div id="managementAck" class="form-check mb-3" hidden><input class="form-check-input" type="checkbox" name="acknowledgement" value="1" id="ack"><label class="form-check-label" for="ack">{{ __('training.manager_ack') }}</label></div>
  <div id="managementReason" hidden><label class="form-label">{{ __('training.reason') }}</label><textarea class="form-control mb-3" name="details" maxlength="200"></textarea></div><button class="btn btn-primary">{{ __('training.save') }}</button>
 </form></div></div></div></div>
 <div class="card mt-4"><div class="card-header"><h2 class="h5 mb-0">{{ __('training.documents') }}</h2></div><div class="card-body d-flex flex-wrap gap-2">
  <a class="btn btn-outline-dark" href="{{ route('modules.training.management.document', [$training->id, 'plan']) }}">{{ __('training.plan_pdf') }}</a>
  @if($training->hasSignedPdf())<a class="btn btn-outline-dark" href="{{ route('modules.training.management.signed-pdf', $training->id) }}">{{ __('training.signed_pdf') }}</a>@endif
  @foreach([3=>'coordinator-passed',4=>'coordinator-failed',6=>'manager-passed',7=>'manager-failed'] as $status=>$document)@if($timeline->contains('status_id',$status))<a class="btn btn-outline-dark" href="{{ route('modules.training.management.document', [$training->id,$document]) }}">{{ __('training.document_'.$document) }}</a>@endif @endforeach
  <a class="btn btn-outline-primary" href="{{ route('modules.training.management.timeline', $training->id) }}">{{ __('training.timeline') }}</a>
 </div></div>
</div>
@push('scripts')<script>document.getElementById('managementStatus')?.addEventListener('change',function(){document.getElementById('managementAck').hidden=this.value!=='6';document.getElementById('managementReason').hidden=!['7','8'].includes(this.value);});</script>@endpush
@endsection
