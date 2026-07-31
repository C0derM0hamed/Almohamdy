<?php

namespace App\Services\Dashboard;

use App\Data\DashboardWidget;

class DashboardWidgetService
{
    public function __construct(
        private readonly DashboardCardService $cards,
        private readonly NavigationService $navigation,
    ) {}

    /**
     * @return list<DashboardWidget>
     */
    public function resolve(): array
    {
        $widgets = [];

        foreach (config('hm.dashboard.widgets', []) as $widget) {
            $labelKey = (string) ($widget['label_key'] ?? '');
            $source = (string) ($widget['source'] ?? '');
            $value = $this->resolveValue($source);

            if ($value === null) {
                continue;
            }

            $widgets[] = new DashboardWidget(
                label: __('dashboard.widgets.'.$labelKey),
                value: $value,
                icon: (string) ($widget['icon'] ?? 'bi-grid'),
                variant: (string) ($widget['variant'] ?? 'primary'),
            );
        }

        return $widgets;
    }

    private function resolveValue(string $source): ?string
    {
        return match ($source) {
            'module_count' => (string) count($this->cards->resolve()),
            'nav_count' => (string) count($this->navigation->sidebar()),
            'user_level' => $this->userLevelLabel(),
            default => null,
        };
    }

    private function userLevelLabel(): ?string
    {
        $level = session('hr_user_level');

        if ($level === null) {
            return null;
        }

        $key = 'dashboard.levels.'.(string) $level;

        return __($key) !== $key ? __($key) : (string) $level;
    }
}
