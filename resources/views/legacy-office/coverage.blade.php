@extends('layouts.app')

@section('title', app()->getLocale() === 'ar' ? 'إشعار حالة الموافقة على تغطية الخدمات الطبية' : 'Medical services coverage approvals')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4">{{ app()->getLocale() === 'ar' ? 'إشعار حالة الموافقة على تغطية الخدمات الطبية' : 'Medical services coverage approvals' }}</h1>
    <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#coverage-create">{{ app()->getLocale() === 'ar' ? 'إضافة إشعار' : 'Add notice' }}</button>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="collapse mb-4" id="coverage-create">
    <form method="post" action="{{ route('modules.legacy-office.coverage.store') }}" class="card card-body border-0 shadow-sm">
        @csrf
        @include('legacy-office.partials.coverage-form', ['record' => null])
        <button class="btn btn-primary mt-3" type="submit">{{ app()->getLocale() === 'ar' ? 'حفظ' : 'Save' }}</button>
    </form>
</div>

@include('legacy-office.partials.filters', ['filterTypes' => $types])

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>{{ app()->getLocale() === 'ar' ? 'تاريخ الإصدار' : 'Issue date' }}</th>
                <th>{{ app()->getLocale() === 'ar' ? 'اسم المريض' : 'Patient name' }}</th>
                <th>{{ app()->getLocale() === 'ar' ? 'رقم الملف الطبي' : 'Medical file number' }}</th>
                <th>{{ app()->getLocale() === 'ar' ? 'نوع الإشعار' : 'Notice type' }}</th>
                <th>{{ app()->getLocale() === 'ar' ? 'أرسل إلى' : 'Recipients' }}</th>
                <th>{{ app()->getLocale() === 'ar' ? 'اعتماد الإشعار' : 'Approval' }}</th>
                <th>PDF</th>
            </tr></thead>
            <tbody>
            @forelse($records as $record)
                @php($noticeType = $types->firstWhere('id', $record->memo_types_id))
                <tr>
                    <td>{{ is_numeric($record->date) ? date('Y-m-d H:i', (int) $record->date) : $record->date }}</td>
                    <td>{{ $record->patient_name }}</td>
                    <td>{{ $record->file_number }}</td>
                    <td>{{ \App\Support\LocaleText::localizedValue($noticeType?->name_ar ?? null, $noticeType?->name_en ?? null) }}</td>
                    <td><span class="badge bg-secondary">{{ $record->recipient_count }}</span></td>
                    <td>
                        {{ $record->activated_at ?: (app()->getLocale() === 'ar' ? 'غير معتمد' : 'Not approved') }}
                        <button class="btn btn-sm btn-dark" type="button" data-bs-toggle="modal" data-bs-target="#coverage-{{ $record->id }}">{{ app()->getLocale() === 'ar' ? 'اعتماد' : 'Approve' }}</button>
                    </td>
                    <td><a target="_blank" class="btn btn-sm btn-outline-secondary" href="{{ route('modules.legacy-office.coverage.pdf', $record->id) }}">PDF</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-5">{{ app()->getLocale() === 'ar' ? 'لا توجد نتائج' : 'No records found' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-transparent">{{ $records->links() }}</div>
</div>

{{-- Dialogs must be outside the table and the animated page root. A div
     directly inside tbody is invalid HTML, and a modal inside a transformed
     page root can leave only Bootstrap's dark backdrop visible. --}}
@push('modals')
@foreach($records as $record)
    <div class="modal fade" id="coverage-{{ $record->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="post" action="{{ route('modules.legacy-office.coverage.update', $record->id) }}" class="modal-content">
                @csrf
                @method('put')
                <div class="modal-header"><h2 class="h5">{{ app()->getLocale() === 'ar' ? 'اعتماد الإشعار' : 'Approve notice' }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">@include('legacy-office.partials.coverage-form', ['record' => $record])</div>
                <div class="modal-footer"><button class="btn btn-primary" type="submit">{{ app()->getLocale() === 'ar' ? 'اعتماد' : 'Approve' }}</button></div>
            </form>
        </div>
    </div>
@endforeach
@endpush
@endsection
