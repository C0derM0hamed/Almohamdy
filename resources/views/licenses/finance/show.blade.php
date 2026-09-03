@extends('layouts.app')

@section('title', __('licenses.payments.detail'))
@section('sidebar_heading', __('licenses.title'))
@section('sidebar_subheading', __('licenses.payments.subtitle'))

@push('styles')
<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">
@endpush

@section('content')
@php
    $url = static fn ($name, $params = []) => \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : '#';
    $nameOf = static function($item){if(!$item)return '—';if(is_string($item))return $item;if(method_exists($item,'displayName'))return $item->displayName();if(method_exists($item,'localizedName'))return $item->localizedName();$f=app()->getLocale()==='ar'?'name_ar':'name_en';return data_get($item,$f)?:data_get($item,'name')?:data_get($item,'hr_name')?:'—';};
    $dateOf = static fn($v)=>$v?($v instanceof \DateTimeInterface?$v->format('Y-m-d H:i'):substr((string)$v,0,16)):'—';
    $payment = $paymentRequest ?? $request;
    $licenseRecord = $payment->license;
    $events = $paymentEvents ?? $payment->events ?? collect();
    $attachments = $paymentAttachments ?? $payment->attachments ?? collect();
    $paymentStatus = $payment->statusRelation ?? $payment->status ?? null;
@endphp
<div class="hm-licenses">
    @php $financeHeaderActions='<a class="lic-btn" href="'.e($url('modules.licenses.finance.index')).'"><i class="bi bi-arrow-left"></i>'.e(__('licenses.back')).'</a>'; if(($canViewLicense ?? false) && $licenseRecord){$financeHeaderActions.='<a class="lic-btn" href="'.e($url('modules.licenses.show',$licenseRecord->getRouteKey())).'"><i class="bi bi-patch-check"></i>'.e(__('licenses.show')).'</a>';} @endphp
    @include('licenses.partials.page-header', ['title'=>__('licenses.payments.detail').' #'.$payment->id,'subtitle'=>$licenseRecord?->title ?: $licenseRecord?->license_number ?: __('licenses.index'),'icon'=>'bi-receipt-cutoff','actions'=>new \Illuminate\Support\HtmlString($financeHeaderActions)])
    @include('licenses.partials.feedback')

    <section class="lic-panel"><div class="lic-panel__head"><h2 class="lic-panel__title"><i class="bi bi-cash-stack"></i>{{ __('licenses.payments.detail') }}</h2><span class="lic-status">{{ $nameOf($paymentStatus) }}</span></div>
        <div class="lic-summary-grid">
            @foreach ([__('licenses.payments.request_number')=>'#'.$payment->id,__('licenses.payments.amount')=>number_format((float)$payment->amount,2).' '.($payment->currency?:'SAR'),__('licenses.payments.bank_name')=>$payment->bank_name?:'—',__('licenses.payments.account_iban')=>$payment->account_iban?:'—',__('licenses.payments.invoice_number')=>$payment->invoice_number?:'—',__('licenses.payments.requested_by')=>$nameOf($payment->requester ?? $payment->requestedBy ?? null),__('licenses.payments.requested_at')=>$dateOf($payment->created_at),__('licenses.payments.closed_at')=>$dateOf($payment->closed_at)] as $label=>$value)
                <div class="lic-summary-item"><span class="lic-summary-item__label">{{ $label }}</span><span class="lic-summary-item__value {{ str_contains($label,'IBAN') ? 'lic-sensitive':'' }}">{{ $value }}</span></div>
            @endforeach
        </div>
        @if($payment->transfer_details)<div class="mt-3"><span class="lic-label">{{ __('licenses.payments.transfer_details') }}</span><p>{{ $payment->transfer_details }}</p></div>@endif
        @if($payment->notes)<div class="mt-3"><span class="lic-label">{{ __('licenses.fields.notes') }}</span><p>{{ $payment->notes }}</p></div>@endif
    </section>

    <div class="lic-two-column">
        <div class="lic-stack">
            <section class="lic-panel"><h2 class="lic-panel__title"><i class="bi bi-clock-history"></i>{{ __('licenses.payments.events') }}</h2><ol class="lic-timeline">
                @forelse($events->sortByDesc('id') as $event)<li class="lic-timeline__item"><span class="lic-timeline__dot"></span><div class="lic-timeline__head"><strong class="lic-timeline__title">{{ __('licenses.timeline.events.'.($event->event_type ?: 'payment_status_changed')) }}</strong><time class="lic-timeline__date">{{ $dateOf($event->created_at) }}</time></div><div class="lic-timeline__body">{{ $event->comment ?: $nameOf($event->status ?? null) }}<br>{{ __('licenses.timeline.actor',['name'=>$nameOf($event->creator ?? $event->user ?? null)]) }}</div></li>@empty<li class="lic-empty">{{ __('licenses.timeline.empty') }}</li>@endforelse
            </ol></section>
            <section class="lic-panel"><h2 class="lic-panel__title"><i class="bi bi-paperclip"></i>{{ __('licenses.sections.attachments') }}</h2><div class="lic-table-wrap"><table class="lic-table"><thead><tr><th>{{ __('licenses.attachments.file') }}</th><th>{{ __('licenses.attachments.context') }}</th><th>{{ __('licenses.attachments.uploaded_at') }}</th><th>{{ __('licenses.actions') }}</th></tr></thead><tbody>
                @forelse($attachments->sortByDesc('id') as $attachment)<tr><td>{{ $attachment->original_name }}</td><td>{{ __('licenses.attachments.contexts.'.($attachment->context?:'payment')) }}</td><td>{{ $dateOf($attachment->uploaded_at ?? $attachment->created_at) }}</td><td><a class="lic-btn lic-btn--sm" href="{{ $url('modules.licenses.finance.attachments.download',[$payment->getRouteKey(),$attachment->getRouteKey()]) }}"><i class="bi bi-download"></i>{{ __('licenses.download') }}</a></td></tr>@empty<tr><td colspan="4" class="lic-empty">{{ __('licenses.attachments.empty') }}</td></tr>@endforelse
            </tbody></table></div></section>
        </div>
        <aside class="lic-stack">
            <section class="lic-panel"><h2 class="lic-panel__title"><i class="bi bi-arrow-repeat"></i>{{ __('licenses.payments.update_status') }}</h2>
                <form method="POST" action="{{ $url('modules.licenses.finance.status',$payment->getRouteKey()) }}" enctype="multipart/form-data" data-payment-status-form>@csrf
                    <div class="lic-field mb-2"><label for="payment_status">{{ __('licenses.payments.status') }}</label><select id="payment_status" name="status" required class="form-select" data-payment-status>@foreach(($paymentStatusOptions ?? collect()) as $status)@php $statusCode=data_get($status,'code')?:data_get($status,'key'); @endphp<option value="{{ $statusCode }}" data-status-key="{{ $statusCode }}" @selected((string)(data_get($paymentStatus,'code')?:data_get($paymentStatus,'key'))===(string)$statusCode)>{{ $nameOf($status) }}</option>@endforeach</select></div>
                    <div class="lic-field mb-2"><label for="status_comment">{{ __('licenses.comments.body') }}</label><textarea id="status_comment" name="comment" class="form-control"></textarea></div>
                    <div class="lic-field mb-2" data-proof-wrap hidden><label for="payment_proof">{{ __('licenses.payments.proof') }} <span class="lic-required">*</span></label><input id="payment_proof" type="file" name="proof" accept=".pdf,.png,.jpg,.jpeg" class="form-control"><p class="lic-help">{{ __('licenses.payments.proof_required') }}</p></div>
                    <button class="lic-btn lic-btn--primary" type="submit">{{ __('licenses.save_changes') }}</button>
                </form>
            </section>
            <section class="lic-panel"><h2 class="lic-panel__title"><i class="bi bi-file-earmark-plus"></i>{{ __('licenses.payments.request_documents') }}</h2><form method="POST" action="{{ $url('modules.licenses.finance.request-documents',$payment->getRouteKey()) }}">@csrf<div class="lic-field"><label for="documents_message">{{ __('licenses.payments.documents_message') }}</label><textarea id="documents_message" name="comment" required class="form-control"></textarea></div><button class="lic-btn mt-2" type="submit">{{ __('licenses.payments.request_documents') }}</button></form></section>
            <section class="lic-panel"><h2 class="lic-panel__title"><i class="bi bi-chat-left-text"></i>{{ __('licenses.payments.add_comment') }}</h2><form method="POST" action="{{ $url('modules.licenses.finance.comments.store',$payment->getRouteKey()) }}">@csrf<div class="lic-field"><label for="finance_comment">{{ __('licenses.comments.body') }}</label><textarea id="finance_comment" name="comment" required class="form-control"></textarea></div><button class="lic-btn mt-2" type="submit">{{ __('licenses.payments.add_comment') }}</button></form></section>
            <section class="lic-panel"><h2 class="lic-panel__title"><i class="bi bi-upload"></i>{{ __('licenses.attachments.upload') }}</h2><form method="POST" action="{{ $url('modules.licenses.finance.attachments.store',$payment->getRouteKey()) }}" enctype="multipart/form-data">@csrf<div class="lic-field"><label for="finance_attachment">{{ __('licenses.attachments.file') }}</label><input id="finance_attachment" type="file" name="file" required accept=".pdf,.png,.jpg,.jpeg,.xls,.xlsx" class="form-control"></div><div class="lic-field mt-2"><label for="finance_attachment_comment">{{ __('licenses.comments.body') }}</label><input id="finance_attachment_comment" name="comment" maxlength="10000" class="form-control"></div><button class="lic-btn mt-2" type="submit">{{ __('licenses.attachments.upload') }}</button></form></section>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){var select=document.querySelector('[data-payment-status]');var wrap=document.querySelector('[data-proof-wrap]');if(!select||!wrap)return;function sync(){var option=select.options[select.selectedIndex];var paid=option&&option.dataset.statusKey==='paid';wrap.hidden=!paid;var file=wrap.querySelector('input[type=file]');if(file)file.required=paid;}select.addEventListener('change',sync);sync();})();
</script>
@endpush
