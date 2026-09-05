@php
    $rows = collect($items ?? [])->map(fn ($total, $key) => ['key' => (string) $key, 'total' => (int) $total])->sortByDesc('total')->values();
    $sum = max(1, (int) $rows->sum('total'));
    $filter = $filterKey ?? 'status';
    $meta = $meta ?? [];
@endphp
<section class="lic-panel gov-breakdown-card">
    <div class="lic-panel__head">
        <h2 class="lic-panel__title"><i class="bi {{ $icon }}"></i>{{ $title }}</h2>
        <span class="gov-breakdown__total">{{ __('gov_accounts.dashboard.breakdown_total', ['count' => (int) $rows->sum('total')]) }}</span>
    </div>
    <div class="gov-breakdown" role="list">
        @forelse ($rows as $row)
            @php
                $tone = data_get($meta, $row['key'].'.tone', 'blue');
                $rowIcon = data_get($meta, $row['key'].'.icon', 'bi-circle');
                $percent = round(($row['total'] / $sum) * 100);
                $href = route('modules.gov-accounts.requests.index', [$filter => $row['key']]);
            @endphp
            <a class="gov-breakdown__row gov-breakdown__row--{{ $tone }}" href="{{ $href }}" role="listitem">
                <div class="gov-breakdown__meta">
                    <span class="gov-breakdown__icon" aria-hidden="true"><i class="bi {{ $rowIcon }}"></i></span>
                    <span class="gov-breakdown__copy">
                        <strong>{{ $filter === 'type' ? __('gov_accounts.types.'.$row['key']) : __('gov_accounts.statuses.'.$row['key']) }}</strong>
                        <small>{{ $percent }}%</small>
                    </span>
                    <span class="gov-breakdown__count">{{ $row['total'] }}</span>
                </div>
                <span class="gov-breakdown__bar" aria-hidden="true"><span style="width: {{ $percent }}%"></span></span>
            </a>
        @empty
            <div class="lic-empty">{{ __('gov_accounts.dashboard.none') }}</div>
        @endforelse
    </div>
</section>
