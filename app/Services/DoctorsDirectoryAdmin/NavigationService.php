<?php

namespace App\Services\DoctorsDirectoryAdmin;

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

        foreach (config('hm.doctors_directory_admin.cards', []) as $card) {
            $routeName = (string) ($card['route'] ?? '');

            if ($routeName === '' || ! Route::has($routeName)) {
                continue;
            }

            $labelKey = (string) ($card['label_key'] ?? '');
            $descriptionKey = 'doctors_directory_admin.card_descriptions.'.$labelKey;
            $description = __($descriptionKey);

            $cards[] = new DashboardCard(
                title: __('doctors_directory_admin.cards.'.$labelKey),
                url: route($routeName),
                icon: (string) ($card['icon'] ?? 'bi-grid'),
                route: $routeName,
                description: $description === $descriptionKey ? '' : $description,
            );
        }

        return $cards;
    }
}
