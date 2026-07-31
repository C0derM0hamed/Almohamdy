<?php

namespace App\Services\EmployeeServices;

use App\Data\DashboardCard;
use App\Services\Auth\PermissionService;
use App\Support\WorkAbsenceNotification\WorkAbsenceNotificationPermissions;
use Illuminate\Support\Facades\Route;

class EmployeeServicesNavigationService
{
    public function __construct(
        private readonly PermissionService $permissions,
    ) {}

    /**
     * @return list<DashboardCard>
     */
    public function cards(): array
    {
        $cards = [];

        foreach (config('hm.employee_services.cards', []) as $card) {
            $routeName = (string) ($card['route'] ?? '');

            if ($routeName === '' || ! Route::has($routeName)) {
                continue;
            }

            if (in_array($routeName, ['modules.work-absence.dashboard', 'modules.work-absence.notifications.index'], true)
                && ! $this->permissions->can(WorkAbsenceNotificationPermissions::VIEW)) {
                continue;
            }

            $labelKey = (string) ($card['label_key'] ?? '');
            $descriptionKey = 'employee_services.card_descriptions.'.$labelKey;
            $description = __($descriptionKey);
            $comingSoon = in_array($routeName, [
                'modules.employee-services.training-management',
                'modules.employee-services.training-coordination',
            ], true);

            $cards[] = new DashboardCard(
                title: __('employee_services.cards.'.$labelKey),
                url: route($routeName),
                icon: (string) ($card['icon'] ?? 'bi-grid'),
                route: $routeName,
                description: $description === $descriptionKey ? '' : $description,
                badge: $comingSoon ? __('employee_services.coming_soon_badge') : '',
            );
        }

        return $cards;
    }
}
