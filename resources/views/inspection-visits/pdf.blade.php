<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #182433; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 15px; margin: 18px 0 6px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; vertical-align: top; }
        th { background: #e8eef5; }
    </style>
</head>
<body>
    <h1>{{ __('inspection_visits.detail') }} {{ $visit->displayNumber() }}</h1>
    <div class="muted">{{ $visit->visitNumberRecord?->subject ?: '—' }}</div>

    <h2>{{ __('inspection_visits.fields.visit_date') }}</h2>
    <table>
        <tr><th>{{ __('inspection_visits.fields.authority') }}</th><td>{{ $visit->authority?->localizedName() ?: '—' }}</td></tr>
        <tr><th>{{ __('inspection_visits.fields.visit_type') }}</th><td>{{ $visit->visitType?->localizedName() ?: '—' }}</td></tr>
        <tr><th>{{ __('inspection_visits.fields.visit_date') }}</th><td>{{ optional($visit->visit_date)->format('Y-m-d H:i') ?: '—' }}</td></tr>
        <tr><th>{{ __('inspection_visits.fields.reply_time') }}</th><td>{{ optional($visit->reply_time)->format('Y-m-d H:i') ?: '—' }}</td></tr>
        <tr><th>{{ __('inspection_visits.fields.section') }}</th><td>{{ $visit->section?->localizedName() ?: '—' }}</td></tr>
        <tr><th>{{ __('inspection_visits.fields.report') }}</th><td>{{ $visit->visitNumberRecord?->report ?: '—' }}</td></tr>
    </table>

    <h2>{{ __('inspection_visits.findings_list.title') }}</h2>
    <table>
        <thead><tr><th>{{ __('inspection_visits.fields.finding_type') }}</th><th>{{ __('inspection_visits.fields.finding_title') }}</th><th>{{ __('inspection_visits.department_reply.reply') }}</th></tr></thead>
        <tbody>
        @forelse ($visit->findings as $finding)
            <tr>
                <td>{{ $finding->isViolation() ? __('inspection_visits.fields.violation') : __('inspection_visits.fields.note') }}</td>
                <td>{{ $finding->abuse_note_title ?: '—' }}</td>
                <td>{{ $finding->reply ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="3">—</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>{{ __('inspection_visits.replies.title') }}</h2>
    <table>
        <thead><tr><th>{{ __('inspection_visits.replies.reply') }}</th><th>{{ __('inspection_visits.replies.date') }}</th></tr></thead>
        <tbody>
        @forelse ($visit->replies->sortByDesc('id') as $reply)
            <tr><td>{{ $reply->reply }}</td><td>{{ optional($reply->created_at)->format('Y-m-d H:i') ?: '—' }}</td></tr>
        @empty
            <tr><td colspan="2">—</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>{{ __('inspection_visits.timeline.title') }}</h2>
    <table>
        <thead><tr><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        @forelse ($visit->timelineEntries->sortByDesc('id') as $entry)
            <tr>
                <td>{{ $entry->statusRelation?->localizedName() ?: __('inspection_visits.status_unknown') }}</td>
                <td>{{ is_numeric($entry->date) ? date('Y-m-d H:i', (int) $entry->date) : ($entry->date ?: '—') }}</td>
            </tr>
        @empty
            <tr><td colspan="2">—</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
