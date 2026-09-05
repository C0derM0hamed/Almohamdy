@php
    $files = collect($files ?? []);
    $empty = $empty ?? __('licenses.attachments.empty');
    $downloadLabel = $downloadLabel ?? __('licenses.download');
    $nameAsLink = (bool) ($nameAsLink ?? false);
    $downloadUrl = $downloadUrl ?? static fn () => '#';
    $subtitle = $subtitle ?? null;
    $dateOf = $dateOf ?? static fn ($value) => $value ? ($value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i') : substr((string) $value, 0, 16)) : '—';
    $fileIcon = static function (?string $name): string {
        $ext = strtolower((string) pathinfo((string) $name, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'bi-file-earmark-pdf',
            'xls', 'xlsx' => 'bi-file-earmark-spreadsheet',
            'jpg', 'jpeg', 'png' => 'bi-file-earmark-image',
            default => 'bi-file-earmark',
        };
    };
    $fileSize = static function ($bytes): string {
        $bytes = (int) $bytes;
        if ($bytes <= 0) {
            return '—';
        }
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / 1048576, 1).' MB';
    };
@endphp
<div class="lic-file-list gov-file-list">
    @forelse($files as $file)
        @php
            $href = $downloadUrl($file);
            $name = $file->original_name ?: __('licenses.attachments.file');
            $meta = is_callable($subtitle) ? $subtitle($file) : $subtitle;
        @endphp
        <article class="lic-file-card gov-file-card">
            <span class="lic-file-card__icon gov-file-card__icon" aria-hidden="true"><i class="bi {{ $fileIcon($file->original_name) }}"></i></span>
            <div class="lic-file-card__copy gov-file-card__copy">
                @if($nameAsLink)
                    <a class="lic-table__primary" href="{{ $href }}" download="{{ $name }}">{{ $name }}</a>
                @else
                    <strong>{{ $name }}</strong>
                @endif
                <small>{{ $fileSize($file->size ?? 0) }} · {{ $dateOf($file->uploaded_at ?? $file->created_at) }}</small>
                @if(filled($meta))<small>{{ $meta }}</small>@endif
            </div>
            <a class="lic-btn lic-btn--sm" href="{{ $href }}" download="{{ $name }}">
                <i class="bi bi-download" aria-hidden="true"></i>{{ $downloadLabel }}
            </a>
        </article>
    @empty
        <p class="lic-file-empty gov-file-empty">{{ $empty }}</p>
    @endforelse
</div>
