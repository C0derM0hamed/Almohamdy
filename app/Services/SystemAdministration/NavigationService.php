<?php

namespace App\Services\SystemAdministration;

use App\Data\DashboardCard;
use Illuminate\Support\Facades\Route;

class NavigationService
{
    /**
     * @return list<DashboardCard>
     */
    public function cards(): array
    {
        $cards = [];

        foreach (config('hm.system_administration.cards', []) as $card) {
            $routeName = (string) ($card['route'] ?? '');

            if ($routeName === '' || ! Route::has($routeName)) {
                continue;
            }

            $labelKey = (string) ($card['label_key'] ?? '');
            $descriptionKey = 'system_administration.card_descriptions.'.$labelKey;
            $description = __($descriptionKey);

            $cards[] = new DashboardCard(
                title: __('system_administration.cards.'.$labelKey),
                url: route($routeName, (array) ($card['route_params'] ?? [])),
                icon: (string) ($card['icon'] ?? 'bi-grid'),
                route: $routeName,
                description: $description === $descriptionKey ? '' : $description,
            );
        }

        return $cards;
    }
}
