@extends('layouts.app')
@section('title', 'إدارة صلاحيات المستخدم')
@push('workflow_styles')
    <link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">
@endpush
@push('scripts')
    <script defer src="{{ asset('js/hm-user-permissions.js') }}?v={{ filemtime(public_path('js/hm-user-permissions.js')) }}"></script>
@endpush
@section('content')
@php
    $isSelf = (int) $user->hr_id === (int) session('hr_user_id');
@endphp
<div class="hm-fm hm-workflow hm-user-permissions" data-user-permissions>
    <header class="hm-up-head">
        <div>
            <a class="hm-up-back" href="{{ route('modules.system-admin.users.index') }}"><i class="bi bi-arrow-right"></i> المستخدمون</a>
            <h1>إدارة صلاحيات المستخدم</h1>
            <p>حدّد الوحدات والإجراءات المسموح بها مباشرةً للحساب {{ $user->displayName() }}.</p>
        </div>
        <div class="hm-up-user-card">
            <span class="wf-avatar"><i class="bi bi-person"></i></span>
            <div><strong>{{ $user->displayName() }}</strong><small>{{ $user->hr_username }} · {{ (string) $user->activated === '1' ? 'حساب نشط' : 'حساب غير نشط' }}</small></div>
        </div>
    </header>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if($isSelf)
        <div class="alert alert-info">هذا حسابك الحالي. المدير الأعلى يمتلك جميع الصلاحيات تلقائيًا، ولا يمكن تغيير صلاحيات الحساب نفسه من داخله.</div>
    @endif
    <span class="visually-hidden" aria-hidden="true">من المجموعة</span>

    <form method="post" action="{{ route('modules.system-admin.users.permissions.update', $user->hr_id) }}" data-permission-form>
        @csrf @method('PUT')
        <input type="hidden" name="permissions_version" value="{{ $permissionsVersion }}">
        <section class="hm-up-panel">
            <header class="hm-up-panel__head">
                <span class="hm-up-panel__icon"><i class="bi bi-grid-3x3-gap"></i></span>
                <span><strong>صلاحيات النظام</strong><small>حدد الأقسام والإجراءات المسموح بها لهذا الحساب مباشرةً</small></span>
            </header>
            <section class="hm-up-toolbar">
                <label class="hm-up-search"><i class="bi bi-search"></i><input type="search" placeholder="ابحث في الأقسام أو الصلاحيات..." data-permission-search></label>
                <div class="hm-up-count"><span data-selected-count>{{ $effectivePermissionCount }}</span> صلاحية فعالة</div>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('modules.system-admin.users.permissions.history', $user->hr_id) }}"><i class="bi bi-clock-history"></i> سجل التغييرات</a>
            </section>

            <div class="hm-up-categories">
            @foreach($categories as $categoryCode => $category)
                @php
                    $items = $permissionsByCategory->get($categoryCode, collect());
                    $categoryIsOpen = false;
                @endphp
                @continue($items->isEmpty())
                <section class="hm-up-category" data-permission-category>
                    <header class="hm-up-category__head">
                        <label class="hm-up-category__select-all">
                            <input type="checkbox" data-category-select-all aria-label="تحديد كل صلاحيات قسم {{ $category['label'] }}">
                        </label>
                        <button type="button" class="hm-up-category__toggle" aria-expanded="{{ $categoryIsOpen ? 'true' : 'false' }}" aria-label="فتح أو إغلاق قسم {{ $category['label'] }}" data-category-toggle>
                            <span class="hm-up-category__title">
                                <span class="hm-up-category__icon"><i class="bi {{ $category['icon'] }}"></i></span>
                                <span><strong>{{ $category['label'] }}</strong><small>{{ $category['description'] }}</small></span>
                            </span>
                            <span class="hm-up-category__meta"><span data-category-count>{{ $items->where('effective', true)->count() }}/{{ $items->count() }}</span><i class="bi bi-chevron-down"></i></span>
                        </button>
                    </header>
                    <div class="hm-up-category__body" data-category-body @hidden(! $categoryIsOpen)>
                        <div class="hm-up-permission-list">
                            @foreach($items as $permission)
                                @php
                                    $code = $permission['code'];
                                @endphp
                                <label class="hm-up-permission" data-permission-item data-search="{{ mb_strtolower($permission['label'].' '.$permission['description'].' '.$code) }}">
                                    <input class="hm-up-permission__check" type="checkbox" data-decision-toggle data-inherited="{{ $permission['inherited'] ? '1' : '0' }}" @checked($permission['effective'])>
                                    <input type="hidden" name="decisions[{{ $code }}]" value="{{ $permission['direct_decision'] }}" data-decision-input>
                                    <span class="hm-up-permission__icon"><i class="bi {{ $permission['icon'] ?? 'bi-shield-check' }}"></i></span>
                                    <span class="hm-up-permission__content"><strong>{{ $permission['label'] }}</strong><small>{{ $permission['description'] }}</small><span class="visually-hidden">{{ $code }}</span></span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endforeach
            </div>
        </section>

        <footer class="hm-up-actions">
            <span>سيتم تطبيق التغيير على المستخدم في طلبه التالي.</span>
            <div><a class="btn btn-outline-secondary" href="{{ route('modules.system-admin.users.index') }}">إلغاء</a><button class="btn btn-primary" type="submit" @disabled($isSelf)><i class="bi bi-check-lg"></i> حفظ الصلاحيات</button></div>
        </footer>
    </form>
</div>
@endsection
