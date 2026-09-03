@extends('layouts.app')
@section('title', __('system_administration.users.title'))
@section('figma_page_header', 'true')
@push('workflow_styles')
    <link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">
@endpush
@push('scripts')
    <script defer src="{{ asset('js/hm-permissions-modal.js') }}?v={{ filemtime(public_path('js/hm-permissions-modal.js')) }}"></script>
@endpush
@section('content')
<div class="hm-fm hm-workflow">
    @include('layouts.partials.figma-module-header', ['crumbs' => [['label' => __('dashboard.modules')], ['label' => __('system_administration.users.title')]], 'title' => __('system_administration.users.title'), 'subtitle' => __('system_administration.users.scope_notice'), 'heroIconSrc' => asset('images/figma/workflows/system-users.svg'), 'heroIconSize' => 32, 'actionUrl' => route('modules.system-admin.users.create'), 'actionLabel' => __('system_administration.users.create'), 'actionIconSrc' => asset('images/figma/technical-failures/add.svg')])
    <section class="wf-table-panel wf-table-panel--contained">
        @include('layouts.partials.figma-workflow-table-head', ['title' => __('system_administration.users.title'), 'items' => $users])
        <div class="table-responsive">
            <table class="wf-table hm-users-table">
                <thead>
                    <tr>
                        <th>{{ __('system_administration.users.name') }}</th>
                        <th>{{ __('system_administration.users.mobile') }}</th>
                        <th>{{ __('system_administration.users.email') }}</th>
                        <th>{{ __('system_administration.users.department') }}</th>
                        <th>{{ __('system_administration.users.branch') }}</th>
                        <th>{{ __('system_administration.users.status') }}</th>
                        <th>{{ __('system_administration.users.permissions') }}</th>
                        <th aria-label="{{ __('system_administration.users.edit') }}"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $permissionsModalId = 'user-permissions-'.$user->hr_id;
                            $isActive = (string) $user->activated === '1';
                        @endphp
                        <tr>
                            <td>
                                <span class="wf-notice-cell">
                                    <span class="wf-avatar"><i class="bi bi-person" aria-hidden="true"></i></span>
                                    <strong>{{ $user->displayName() }}</strong>
                                </span>
                            </td>
                            <td dir="ltr">{{ $user->mobile ?: '—' }}</td>
                            <td dir="ltr"><span class="hm-user-email">{{ $user->hr_email_address ?: '—' }}</span></td>
                            <td>{{ $user->department_name ?: '—' }}</td>
                            <td>
                                <span class="hm-user-branch-list">
                                    @foreach($user->branch_names as $branchName)
                                        <span class="hm-user-branch-chip">{{ $branchName }}</span>
                                    @endforeach
                                </span>
                            </td>
                            <td>
                                <span class="wf-status {{ $isActive ? 'wf-status--active' : 'wf-status--inactive' }}">
                                    {{ $isActive ? __('system_administration.users.active') : __('system_administration.users.inactive') }}
                                </span>
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="wf-view hm-permissions-trigger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#{{ $permissionsModalId }}"
                                    aria-label="{{ __('system_administration.users.show_permissions') }}"
                                    title="{{ __('system_administration.users.show_permissions') }}"
                                >
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </td>
                            <td>
                                <div class="wf-actions">
                                    <a class="wf-view" href="{{ route('modules.system-admin.users.permissions.edit', $user->hr_id) }}" aria-label="إدارة الصلاحيات" title="إدارة الصلاحيات">
                                        <i class="bi bi-shield-lock" aria-hidden="true"></i>
                                    </a>
                                    <a class="wf-view" href="{{ route('modules.system-admin.users.edit', $user->hr_id) }}" aria-label="{{ __('system_administration.users.edit') }}" title="{{ __('system_administration.users.edit') }}">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">{{ __('system_administration.users.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $users->links() }}</div>
    </section>

</div>
@endsection

@push('modals')
    @foreach($users as $user)
        @php
            $permissionsModalId = 'user-permissions-'.$user->hr_id;
            $effectivePermissions = $user->effective_permissions ?? collect();
            $effectivePermissionItems = $user->effective_permission_items ?? collect();
            $legacyPermissionLabels = [
                'adm_country' => 'الدول',
                'adm_reg_branch' => 'الفروع الإقليمية',
                'adm_user_branch' => 'فروع المستخدمين',
                'change_my_pass' => 'تغيير كلمة المرور',
                'change_user_pass' => 'تغيير كلمات مرور المستخدمين',
                'city' => 'المدن',
                'currencies' => 'العملات',
                'order_status' => 'حالات الطلبات',
                'transactions_methods' => 'طرق المعاملات',
                'update_my_informations' => 'تحديث بياناتي',
            ];
            $permissionGroups = $effectivePermissionItems
                ->map(function (array $permission) use ($legacyPermissionLabels): array {
                    $code = (string) ($permission['code'] ?? '');
                    $label = trim((string) ($permission['label'] ?? ''));

                    if ($label === '' || $label === $code) {
                        $permission['label'] = $legacyPermissionLabels[$code] ?? 'صلاحية نظام قديمة';
                    }

                    return $permission + [
                        'category' => 'legacy',
                        'category_label' => 'صلاحيات قديمة',
                        'category_description' => 'صلاحيات محفوظة للتوافق مع النظام السابق',
                        'category_icon' => 'bi-clock-history',
                        'category_order' => 999,
                    ];
                })
                ->groupBy('category')
                ->sortBy(fn ($items) => (int) ($items->first()['category_order'] ?? 999));
        @endphp
        <div class="modal fade hm-permissions-modal" id="{{ $permissionsModalId }}" tabindex="-1" aria-labelledby="{{ $permissionsModalId }}-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <span class="hm-permissions-modal__eyebrow">{{ __('system_administration.users.permissions') }}</span>
                            <h2 class="modal-title" id="{{ $permissionsModalId }}-title">{{ $user->displayName() }}</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('system_administration.users.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="hm-permissions-summary">
                            <span>{{ __('system_administration.users.effective_permissions') }}</span>
                            <strong>{{ $effectivePermissions->count() }}</strong>
                        </div>
                        @if($permissionGroups->isNotEmpty())
                            <div class="hm-permissions-tree" role="tree" aria-label="{{ __('system_administration.users.effective_permissions') }}">
                                @foreach($permissionGroups as $permissions)
                                    @php($category = $permissions->first())
                                    <details class="hm-permissions-tree__group">
                                        <summary class="hm-permissions-tree__summary" aria-expanded="false">
                                            <span class="hm-permissions-tree__icon"><i class="bi {{ $category['category_icon'] }}" aria-hidden="true"></i></span>
                                            <span class="hm-permissions-tree__heading">
                                                <strong>{{ $category['category_label'] }}</strong>
                                                <small>{{ $category['category_description'] }}</small>
                                            </span>
                                            <span class="hm-permissions-tree__count">{{ $permissions->count() }}</span>
                                            <i class="bi bi-chevron-down hm-permissions-tree__chevron" aria-hidden="true"></i>
                                        </summary>
                                        <div class="hm-permissions-tree__children" role="group">
                                            @foreach($permissions as $permission)
                                                <div class="hm-permissions-tree__item">
                                                    <span class="hm-permission-chip" title="{{ $permission['code'] }}">
                                                        <i class="bi {{ $permission['icon'] ?? 'bi-shield-check' }}" aria-hidden="true"></i>
                                                        {{ $permission['label'] }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        @else
                            <div class="hm-permissions-empty">{{ __('system_administration.users.none') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endpush
