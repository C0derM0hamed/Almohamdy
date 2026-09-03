@extends('layouts.app')

@section('title', __('transferal.details'))

@section('content')
    <div class="container-fluid py-3" dir="rtl">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
            <div>
                <h1 class="h4">{{ __('transferal.details') }} #{{ $record->id }}</h1>
                <p class="text-muted mb-0">{{ $record->patient_name }} — {{ $record->file_number }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('modules.transferal.pdf', $record->id) }}">
                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                    PDF
                </a>
                <a class="btn btn-light" href="{{ route('modules.transferal.outgoing') }}">
                    {{ __('transferal.back') }}
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-3">
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">{{ __('transferal.patient') }}</dt>
                            <dd class="col-sm-8">{{ $record->patient_name }}</dd>

                            <dt class="col-sm-4">{{ __('transferal.file_number') }}</dt>
                            <dd class="col-sm-8">{{ $record->file_number }}</dd>

                            <dt class="col-sm-4">{{ __('transferal.source') }}</dt>
                            <dd class="col-sm-8">{{ $record->source_name_ar }}</dd>

                            <dt class="col-sm-4">{{ __('transferal.target') }}</dt>
                            <dd class="col-sm-8">{{ $record->target_name_ar }}</dd>

                            <dt class="col-sm-4">{{ __('transferal.specialization') }}</dt>
                            <dd class="col-sm-8">{{ $record->specialty_name_ar }}</dd>

                            <dt class="col-sm-4">{{ __('transferal.reason') }}</dt>
                            <dd class="col-sm-8">{{ $record->reason_name_ar }}</dd>

                            <dt class="col-sm-4">{{ __('transferal.room') }}</dt>
                            <dd class="col-sm-8">{{ $record->room_name_ar }}</dd>

                            <dt class="col-sm-4">{{ __('transferal.payment') }}</dt>
                            <dd class="col-sm-8">{{ $record->payment_name_ar }}</dd>

                            <dt class="col-sm-4">{{ __('transferal.doctor') }}</dt>
                            <dd class="col-sm-8">{{ $record->referring_doctor }}</dd>
                        </dl>

                        @if ($record->file)
                            <a class="btn btn-sm btn-outline-secondary mt-3" href="{{ route('modules.transferal.attachment', [$record->id, 'request']) }}">
                                <i class="bi bi-paperclip" aria-hidden="true"></i>
                                {{ __('transferal.attachment') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6">{{ __('transferal.actions') }}</h2>

                        @if ((int) $record->companies_groups_id === (int) session('companies_groups_id') && ! $record->confirm)
                            <form method="post" enctype="multipart/form-data" action="{{ route('modules.transferal.confirm', $record->id) }}">
                                @csrf
                                <label class="form-label" for="transferalDateTime">{{ __('transferal.date_time') }}</label>
                                <input class="form-control" id="transferalDateTime" type="datetime-local" name="date_time" required>
                                <input class="form-control mt-2" type="file" name="file_a">
                                <button class="btn btn-primary mt-2" type="submit">{{ __('transferal.confirm_action') }}</button>
                            </form>
                        @endif

                        @if ((int) $record->transferal_to === (int) session('companies_groups_id') && ! $record->approval && ! $record->refusal)
                            <hr>
                            <form method="post" enctype="multipart/form-data" action="{{ route('modules.transferal.approve', $record->id) }}">
                                @csrf
                                <label class="visually-hidden" for="approveDoctor">{{ __('transferal.doctor') }}</label>
                                <input class="form-control mb-2" id="approveDoctor" name="doctor" placeholder="{{ __('transferal.doctor') }}" required>
                                <label class="visually-hidden" for="approveRoom">{{ __('transferal.room') }}</label>
                                <select class="form-select mb-2" id="approveRoom" name="room_type" required>
                                    <option value="">{{ __('transferal.room') }}</option>
                                    @foreach ($rooms as $room)
                                        <option value="{{ $room->id }}">{{ $room->name_ar }}</option>
                                    @endforeach
                                </select>
                                <label class="visually-hidden" for="bedRoomNumber">{{ __('transferal.bed') }}</label>
                                <input class="form-control mb-2" id="bedRoomNumber" name="bed_room_number" placeholder="{{ __('transferal.bed') }}" required>
                                <input class="form-control" type="file" name="file_a">
                                <button class="btn btn-success mt-2" type="submit">{{ __('transferal.approve_action') }}</button>
                            </form>

                            <form class="mt-2" method="post" enctype="multipart/form-data" action="{{ route('modules.transferal.refuse', $record->id) }}">
                                @csrf
                                <label class="visually-hidden" for="refuseDoctor">{{ __('transferal.doctor') }}</label>
                                <input class="form-control mb-2" id="refuseDoctor" name="doctor" placeholder="{{ __('transferal.doctor') }}" required>
                                <label class="visually-hidden" for="refusalReason">{{ __('transferal.refusal_reason') }}</label>
                                <input class="form-control" id="refusalReason" name="refusal_reason" placeholder="{{ __('transferal.refusal_reason') }}" required>
                                <input class="form-control mt-2" type="file" name="file_a">
                                <button class="btn btn-outline-danger mt-2" type="submit">{{ __('transferal.refuse_action') }}</button>
                            </form>
                        @endif

                        @if ($record->approval && ! $record->receive)
                            <hr>
                            <form method="post" enctype="multipart/form-data" action="{{ route('modules.transferal.receive', $record->id) }}">
                                @csrf
                                <label class="visually-hidden" for="receiveDoctor">{{ __('transferal.doctor') }}</label>
                                <input class="form-control mb-2" id="receiveDoctor" name="doctor" placeholder="{{ __('transferal.doctor') }}" required>
                                <label class="visually-hidden" for="receiveDateTime">{{ __('transferal.date_time') }}</label>
                                <input class="form-control mb-2" id="receiveDateTime" type="datetime-local" name="date_time" required>
                                <input class="form-control" type="file" name="file_a">
                                <button class="btn btn-primary mt-2" type="submit">{{ __('transferal.receive_action') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-body">
                <h2 class="h6">{{ __('transferal.timeline') }}</h2>
                <ol class="mb-0">
                    @foreach ($timeline as $event)
                        <li class="mb-2">
                            {{ $event->label }}
                            <small class="text-muted">
                                {{ is_numeric($event->date) ? date('Y-m-d H:i', (int) $event->date) : $event->date }}
                            </small>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
@endsection
