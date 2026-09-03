@extends('layouts.app')

@section('title', $received ? 'المذكرات الواردة' : 'المذكرات')

@push('workflow_styles')
    <style>
        /* The approval form contains the complete legacy memo fields. Keep the
           dialog anchored to the viewport so the action bar is never pushed
           below the screen when the form is taller than the available height. */
        .memo-approval-modal .modal-dialog {
            width: min(960px, calc(100% - 1rem));
            max-width: none;
            height: calc(100% - 1rem);
            margin: .5rem auto;
        }

        .memo-approval-modal {
            z-index: 1060;
        }

        .memo-approval-modal .modal-content {
            min-height: 0;
            max-height: 100%;
        }

        .memo-approval-modal .modal-body {
            min-height: 0;
            overflow-y: auto !important;
        }

        .memo-approval-modal .modal-footer {
            flex: 0 0 auto;
            background: var(--hm-surface, #fff);
        }

        @media (max-width: 575.98px) {
            .memo-approval-modal .modal-dialog {
                width: calc(100% - .5rem);
                height: calc(100% - .5rem);
                margin: .25rem auto;
            }
        }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">{{ $received ? 'المذكرات الواردة' : 'المذكرات' }}</h1>
            <p class="text-muted mb-0">{{ $received ? 'المذكرات المرسلة إليك وحالة الاطلاع' : 'إنشاء واعتماد وإرسال المذكرات للموظفين' }}</p>
        </div>
        @unless($received)
            <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#memo-create">
                إضافة مذكرة
            </button>
        @endunless
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @unless($received)
        <div class="collapse mb-4" id="memo-create">
            <form method="post" action="{{ route('modules.legacy-office.memos.store') }}" class="card card-body border-0 shadow-sm">
                @csrf
                @include('legacy-office.partials.memo-form', ['record' => null])
                <button type="submit" class="btn btn-primary mt-3">حفظ وإرسال</button>
            </form>
        </div>
    @endunless

    @include('legacy-office.partials.filters', ['filterTypes' => $types])

    <section class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>تاريخ الإصدار</th>
                        <th>نوع المذكرة</th>
                        <th>أرسل إلى</th>
                        <th>اعتماد المذكرة</th>
                        <th>PDF</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td>{{ is_numeric($record->date) ? date('Y-m-d H:i', (int) $record->date) : $record->date }}</td>
                            <td>{{ $record->type_name }}</td>
                            <td><span class="badge bg-secondary">{{ $record->recipient_count }}</span></td>
                            <td>
                                <span>{{ $record->activated_at ?: 'غير معتمد' }}</span>
                                @unless($received)
                                    <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#memo-{{ $record->id }}" aria-controls="memo-{{ $record->id }}">
                                        تعديل واعتماد
                                    </button>
                                @endunless
                            </td>
                            <td>
                                <a target="_blank" class="btn btn-sm btn-outline-secondary" href="{{ route('modules.legacy-office.memos.pdf', $record->id) }}">PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">لا توجد نتائج</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent">{{ $records->links() }}</div>
    </section>

@endsection

{{-- Bootstrap modals must live outside .hm-page-root. That container is animated
     with transform/filter during navigation, creating a stacking context that
     can place the modal underneath its own backdrop and make it look disabled. --}}
@push('modals')
    @unless($received)
        @foreach($records as $record)
            <div class="modal fade memo-approval-modal" id="memo-{{ $record->id }}" tabindex="-1" aria-labelledby="memo-title-{{ $record->id }}" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <form method="post" action="{{ route('modules.legacy-office.memos.update', $record->id) }}" class="modal-content">
                        @csrf
                        @method('put')
                        <div class="modal-header">
                            <h2 class="h5 mb-0" id="memo-title-{{ $record->id }}">اعتماد المذكرة #{{ $record->id }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                        </div>
                        <div class="modal-body">
                            @include('legacy-office.partials.memo-form', [
                                'record' => $record,
                                'selectedRecipients' => $recipientIds->get($record->id, []),
                            ])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">اعتماد</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    @endunless
@endpush
