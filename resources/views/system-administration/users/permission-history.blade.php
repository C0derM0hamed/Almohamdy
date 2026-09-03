@extends('layouts.app')
@section('title', 'سجل تغييرات الصلاحيات')
@section('content')
<div class="container-fluid py-4 hm-user-permission-history">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">سجل تغييرات الصلاحيات</h1><p class="text-muted mb-0">{{ $user->displayName() }}</p></div><a class="btn btn-outline-primary" href="{{ route('modules.system-admin.users.permissions.edit', $user->hr_id) }}">العودة للصلاحيات</a></div>
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>التاريخ</th><th>المنفذ</th><th>الإجراء</th><th>التغييرات</th></tr></thead><tbody>
    @forelse($history as $entry)<tr><td dir="ltr">{{ $entry->created_at }}</td><td>#{{ $entry->actor_user_id }}</td><td>{{ $entry->action }}</td><td><details><summary>عرض التفاصيل</summary><pre class="mb-0 mt-2" dir="ltr">{{ json_encode(['before' => json_decode($entry->before_state, true), 'after' => json_decode($entry->after_state, true)], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></details></td></tr>
    @empty<tr><td colspan="4" class="text-center text-muted py-5">لا توجد تغييرات مسجلة لهذا المستخدم.</td></tr>@endforelse
    </tbody></table></div><div class="p-3">{{ $history->links() }}</div></div>
</div>
@endsection
