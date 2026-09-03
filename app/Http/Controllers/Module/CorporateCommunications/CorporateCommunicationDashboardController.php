<?php

namespace App\Http\Controllers\Module\CorporateCommunications;

use App\Http\Controllers\Controller;
use App\Services\Auth\PermissionService;
use App\Support\CorporateCommunications\CorporateCommunicationPermissions;
use App\Support\GovAccounts\GovAccountPermissions;
use Illuminate\Http\RedirectResponse;

class CorporateCommunicationDashboardController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissions,
    ) {}

    /**
     * Hub cards were replaced by sidebar sub-items; keep the route as a redirect.
     */
    public function index(): RedirectResponse
    {
        foreach ($this->targets() as $permission => $route) {
            if ($this->permissions->can($permission)) {
                return redirect()->route($route);
            }
        }

        abort(403);
    }

    /**
     * @return array<string, string>
     */
    private function targets(): array
    {
        return [
            GovAccountPermissions::VIEW => 'modules.gov-accounts.dashboard',
            GovAccountPermissions::PROCESS => 'modules.gov-accounts.requests.index',
            GovAccountPermissions::REQUEST => 'modules.gov-accounts.requests.index',
            GovAccountPermissions::ADMIN => 'modules.gov-accounts.admin.index',
            CorporateCommunicationPermissions::GOVERNMENT_CIRCULARS => 'modules.government-circulars.index',
            CorporateCommunicationPermissions::INSPECTION_VISITS => 'modules.inspection-visits.index',
            CorporateCommunicationPermissions::DATA_REQUESTS => 'modules.data-requests.index',
            CorporateCommunicationPermissions::CORRESPONDENCE => 'modules.correspondence.index',
            CorporateCommunicationPermissions::OUTGOING_CORRESPONDENCE => 'modules.outgoing-correspondence.index',
        ];
    }
}
