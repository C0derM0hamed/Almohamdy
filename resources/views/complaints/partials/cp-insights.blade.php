<section class="cp-insights" aria-label="{{ __('complaints.insights.aria_label') }}">
    <article class="cp-insight-card">
        <div>
            <p class="cp-insight-card__label">{{ __('complaints.insights.processing_rate') }}</p>
            <p class="cp-insight-card__value">{{ $insights['processing_rate'] }}%</p>
        </div>
        <div class="cp-ring" style="--cp-progress: {{ $insights['processing_rate'] }}" aria-hidden="true">
            <span>{{ $insights['processing_rate'] }}%</span>
        </div>
    </article>

    <article class="cp-insight-card">
        <div>
            <p class="cp-insight-card__label">{{ __('complaints.insights.most_active_department') }}</p>
            <p class="cp-insight-card__value cp-insight-card__value--text">{{ $insights['most_active_department'] }}</p>
        </div>
        <span class="cp-insight-card__icon" aria-hidden="true">
            <i class="bi bi-graph-up-arrow"></i>
        </span>
    </article>

    <article class="cp-insight-card">
        <div>
            <p class="cp-insight-card__label">{{ __('complaints.insights.latest_update') }}</p>
            <p class="cp-insight-card__value cp-insight-card__value--text">{{ $insights['latest_update_label'] }}</p>
        </div>
        <span class="cp-insight-card__icon" aria-hidden="true">
            <i class="bi bi-clock-history"></i>
        </span>
    </article>
</section>
