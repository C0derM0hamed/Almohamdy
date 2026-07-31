<section class="cp-summary-strip" aria-label="{{ __('complaints.summary.aria_label') }}">
    @foreach ($cards as $card)
        @php
            $value = (int) ($card['value'] ?? 0);
            $zeroClass = $value > 0 ? '' : 'cp-summary-card--zero';
        @endphp
        <article class="cp-summary-card {{ $zeroClass }}">
            <div class="cp-summary-card__content">
                @if (! empty($card['label']))
                    <p class="cp-summary-card__label">{{ $card['label'] }}</p>
                @endif
                <p class="cp-summary-card__value">{{ number_format($value) }}</p>
            </div>
            @if (! empty($card['icon']))
                <div class="cp-summary-card__icon {{ $card['icon_class'] ?? '' }}" aria-hidden="true">
                    <i class="bi bi-{{ $card['icon'] }}"></i>
                </div>
            @endif
            @if (! empty($card['progress']))
                @php($progress = (float) $card['progress'])
                <div class="cp-summary-card__ring" aria-hidden="true">
                    <svg viewBox="0 0 48 48">
                        <circle cx="24" cy="24" r="20" class="cp-summary-ring__track"></circle>
                        <circle
                            cx="24"
                            cy="24"
                            r="20"
                            class="cp-summary-ring__fill"
                            style="--cp-progress: {{ min(100, max(0, $progress)) }}"
                        ></circle>
                    </svg>
                    <span class="cp-summary-ring__text">{{ round($progress) }}%</span>
                </div>
            @endif
        </article>
    @endforeach
</section>
