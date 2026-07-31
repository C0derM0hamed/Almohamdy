<?php

namespace App\Services\HospitalServices;

use App\Data\DashboardCard;
use Illuminate\Support\Facades\Route;

class HospitalServicesNavigationService
{
    /**
     * @return list<DashboardCard>
     */
    public function cards(): array
    {
        $cards = [];

        foreach (config('hm.hospital_services.cards', []) as $card) {
            $routeName = (string) ($card['route'] ?? '');

            if ($routeName === '' || ! Route::has($routeName)) {
                continue;
            }

            $labelKey = (string) ($card['label_key'] ?? '');

            $cards[] = new DashboardCard(
                title: __('hospital_services.cards.'.$labelKey),
                url: route($routeName),
                icon: (string) ($card['icon'] ?? 'bi-grid'),
                route: $routeName,
            );
        }

        return $cards;
    }
}
