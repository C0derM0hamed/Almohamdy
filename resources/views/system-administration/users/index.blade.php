@extends('layouts.app')

@section('title', __('system_administration.users.title'))

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h1 class="h3 mb-1">{{ __('system_administration.users.title') }}</h1><p class="text-muted mb-0">{{ __('system_administration.users.scope_notice') }}</p></div>
        <a class="btn btn-primary" href="{{ route('modules.system-admin.users.create') }}"><i class="bi bi-person-plus" aria-hidden="true"></i> {{ __('system_administration.users.create') }}</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead><tr><th>{{ __('system_administration.users.name') }}</th><th>{{ __('system_administration.users.username') }}</th><th>{{ __('system_administration.users.level') }}</th><th>{{ __('system_administration.users.scope') }}</th><th>{{ __('system_administration.users.status') }}</th><th></th></tr></thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->displayName() }}</td><td dir="ltr">{{ $user->hr_username }}</td><td>{{ __('system_administration.users.levels.'.$user->hr_user_level) }}</td>
                    <td>{{ $user->companies_groups_id }} / {{ $user->branch_id }}</td><td>{{ $user->activated === '1' ? __('system_administration.users.active') : __('system_administration.users.inactive') }}</td>
                    <td class="text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="{{ route('modules.system-admin.users.show', $user->hr_id) }}"><i class="bi bi-eye" aria-hidden="true"></i></a> <a class="btn btn-sm btn-outline-primary" href="{{ route('modules.system-admin.users.edit', $user->hr_id) }}"><i class="bi bi-pencil" aria-hidden="true"></i></a></td>
                </tr>
            @empty<tr><td colspan="6" class="text-center text-muted">{{ __('system_administration.users.empty') }}</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</div>
@endsection
