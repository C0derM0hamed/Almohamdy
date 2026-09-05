@php
    $labelOf = $nameOf ?? static function ($item) {
        if (! $item) return null;
        if (is_string($item)) return trim($item) ?: null;
        if (method_exists($item, 'localizedName')) return $item->localizedName() ?: null;
        $field = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';

        return data_get($item, $field) ?: data_get($item, 'name') ?: null;
    };
    $departmentNames = collect($departments ?? [])->map($labelOf)->filter()->values();
    $visibleLimit = max(1, (int) ($visibleLimit ?? 1));
    $visibleDepartmentNames = $departmentNames->take($visibleLimit);
    $remainingDepartmentNames = $departmentNames->slice($visibleLimit)->values();
@endphp
<div class="lic-chip-list lic-chip-list--compact">
    @forelse($visibleDepartmentNames as $departmentName)
        <span class="lic-chip"><i class="bi bi-diagram-3" aria-hidden="true"></i>{{ $departmentName }}</span>
    @empty
        <span class="text-muted">—</span>
    @endforelse
    @if($remainingDepartmentNames->isNotEmpty())
        <button type="button" class="lic-chip lic-chip--more" data-lic-departments="{{ $remainingDepartmentNames->toJson() }}" data-bs-toggle="modal" data-bs-target="#licenseDepartmentsModal" aria-label="{{ __('licenses.departments.show_remaining', ['count' => $remainingDepartmentNames->count()]) }}">
            +{{ $remainingDepartmentNames->count() }}
        </button>
    @endif
</div>
