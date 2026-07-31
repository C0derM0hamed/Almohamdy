<?php

namespace App\Services\Dashboard;

use App\Data\DashboardCard;
use App\Services\Auth\PermissionService;
use Illuminate\Support\Facades\Route;

class DashboardCardService
{
    public function __construct(
        private readonly PermissionService $permissions,
    ) {}

    /**
     * @return list<DashboardCard>
     */
    public function resolve(): array
    {
        $cards = [];

        foreach (config('hm.dashboard.cards', []) as $card) {
            if (! empty($card['admin_only']) && ! $this->permissions->isAdmin()) {
                continue;
            }

            $routeName = (string) ($card['route'] ?? '');

            if ($routeName === '' || ! Route::has($routeName)) {
                continue;
            }

            $labelKey = (string) ($card['label_key'] ?? '');

            $cards[] = new DashboardCard(
                title: __('dashboard.cards.'.$labelKey),
                url: route($routeName),
                icon: (string) ($card['icon'] ?? 'bi-grid'),
                route: $routeName,
            );
        }

        return $cards;
    }
}
