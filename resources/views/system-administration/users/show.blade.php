@extends('layouts.app')

@section('title', __('system_administration.users.details'))
@section('figma_page_header', true)

@push('workflow_styles')
    <link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">
@endpush

@php
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

    $permissionCatalogByCode = collect($permissionCatalog ?? [])->mapWithKeys(function ($permission) {
        $permission = (array) $permission;
        $code = (string) ($permission['code'] ?? '');

        return $code !== '' ? [$code => $permission] : [];
    });

    $permissionItems = static function ($permissions) use ($permissionCatalogByCode, $legacyPermissionLabels) {
        return collect($permissions ?? [])->map(function ($code) use ($permissionCatalogByCode, $legacyPermissionLabels) {
            $code = (string) $code;
            $permission = $permissionCatalogByCode->get($code, []);
            $catalogLabel = trim((string) ($permission['label'] ?? ''));

            return [
                'code' => $code,
                'label' => $catalogLabel !== '' && $catalogLabel !== $code
                    ? $catalogLabel
                    : (string) ($legacyPermissionLabels[$code] ?? 'صلاحية نظام قديمة'),
                'description' => (string) ($permission['description'] ?? ''),
                'icon' => (string) ($permission['icon'] ?? 'bi-shield-check'),
            ];
        })->values();
    };

    $permissionSections = [
        [
            'title' => __('system_administration.users.direct_permissions'),
            'description' => 'صلاحيات تم منحها لهذا الحساب مباشرة.',
            'items' => $permissionItems($directPermissions),
            'icon' => 'bi-shield-check',
            'class' => 'is-direct',
        ],
        [
            'title' => __('system_administration.users.inherited_permissions'),
            'description' => 'صلاحيات مصدرها مجموعة المستخدم.',
            'items' => $permissionItems($inheritedPermissions),
            'icon' => 'bi-diagram-3',
            'class' => 'is-inherited',
        ],
        [
            'title' => __('system_administration.users.effective_permissions'),
            'description' => 'إجمالي الصلاحيات الفعالة بعد تطبيق المنح والمنع.',
            'items' => $permissionItems($effectivePermissions),
            'icon' => 'bi-check2-circle',
            'class' => 'is-effective',
        ],
    ];
@endphp

@section('content')
    <div class="hm-fm hm-workflow hm-user-details">
        @include('layouts.partials.figma-module-header', [
            'crumbs' => [
                ['label' => __('dashboard.modules')],
                ['label' => __('system_administration.users.title'), 'url' => route('modules.system-admin.users.index')],
                ['label' => $user->displayName()],
            ],
            'title' => $user->displayName(),
            'subtitle' => 'تفاصيل الحساب والصلاحيات الممنوحة له.',
            'heroIconSrc' => asset('images/figma/workflows/system-users.svg'),
            'heroIconSize' => 32,
        ])

        @if(session('status'))
            <div class="hm-user-details__alert alert alert-success" role="status">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <section class="hm-user-details__profile" aria-labelledby="userDetailsProfileTitle">
            <div class="hm-user-details__identity">
                <span class="hm-user-details__avatar" aria-hidden="true"><i class="bi bi-person"></i></span>
                <div>
                    <span class="hm-user-details__eyebrow">بيانات الحساب</span>
                    <h2 id="userDetailsProfileTitle">{{ $user->displayName() }}</h2>
                    <p dir="ltr">{{ $user->hr_username }}</p>
                </div>
            </div>
            <div class="hm-user-details__facts">
                <div class="hm-user-details__fact">
                    <span>الفرع / القسم</span>
                    <strong>{{ $user->companies_groups_id }} / {{ $user->branch_id }}</strong>
                </div>
                <div class="hm-user-details__fact">
                    <span>المجموعة</span>
                    <strong>{{ $user->groupid ?: __('system_administration.users.no_group') }}</strong>
                </div>
                <div class="hm-user-details__fact">
                    <span>حالة الحساب</span>
                    <strong class="hm-user-details__status {{ (string) $user->activated === '1' ? 'is-active' : 'is-inactive' }}">
                        <i class="bi bi-circle-fill" aria-hidden="true"></i>
                        {{ (string) $user->activated === '1' ? __('system_administration.users.active') : __('system_administration.users.inactive') }}
                    </strong>
                </div>
            </div>
            <div class="hm-user-details__actions">
                <a class="fm-btn fm-btn--primary" href="{{ route('modules.system-admin.users.edit', $user->hr_id) }}">
                    <i class="bi bi-pencil" aria-hidden="true"></i>
                    {{ __('system_administration.users.edit') }}
                </a>
                <a class="fm-btn fm-btn--ghost" href="{{ route('modules.system-admin.users.permissions.edit', $user->hr_id) }}">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i>
                    إدارة الصلاحيات
                </a>
            </div>
        </section>

        <section class="hm-user-details__permissions" aria-labelledby="userPermissionsTitle">
            <div class="hm-user-details__section-head">
                <div>
                    <span class="hm-user-details__eyebrow">الوصول إلى النظام</span>
                    <h2 id="userPermissionsTitle">ملخص الصلاحيات</h2>
                </div>
                <span class="hm-user-details__total">
                    <strong>{{ $effectivePermissions->count() }}</strong>
                    صلاحية فعالة
                </span>
            </div>

            <div class="hm-user-details__permission-grid">
                @foreach($permissionSections as $section)
                    <article class="hm-user-permission-card {{ $section['class'] }}">
                        <header class="hm-user-permission-card__head">
                            <span class="hm-user-permission-card__icon"><i class="bi {{ $section['icon'] }}" aria-hidden="true"></i></span>
                            <div>
                                <h3>{{ $section['title'] }}</h3>
                                <p>{{ $section['description'] }}</p>
                            </div>
                            <strong class="hm-user-permission-card__count">{{ $section['items']->count() }}</strong>
                        </header>

                        @if($section['items']->isNotEmpty())
                            <div class="hm-user-permission-card__list">
                                @foreach($section['items'] as $permission)
                                    <span class="hm-user-permission-chip" title="{{ $permission['code'] }}">
                                        <i class="bi {{ $permission['icon'] }}" aria-hidden="true"></i>
                                        <span>{{ $permission['label'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <div class="hm-user-permission-card__empty">
                                <i class="bi bi-inbox" aria-hidden="true"></i>
                                <span>{{ __('system_administration.users.none') }}</span>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    </div>
@endsection
