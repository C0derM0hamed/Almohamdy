<?php

namespace App\Http\Middleware;

use App\Repositories\UserRepository;
use App\Services\Auth\PermissionService;
use App\Services\Auth\PermissionRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthSession
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PermissionService $permissions,
        private readonly PermissionRegistry $registry,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('hr_user_id')) {
            return redirect()->route('login');
        }

        $user = $this->users->findById((int) $request->session()->get('hr_user_id'));

        if ($user === null) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        $securityState = [
            'hr_user_level' => (int) $user->hr_user_level,
            'hr_branch_id' => (int) $user->branch_id,
            'companies_groups_id' => (int) $user->companies_groups_id,
            'groupid' => (int) $user->groupid,
        ];

        foreach ($securityState as $key => $value) {
            if ((int) $request->session()->get($key) !== $value) {
                $request->session()->put($key, $value);
                $request->session()->forget('hm_permissions');
            }
        }

        // Security changes made by an administrator must be active on the
        // target user's next request, not only after they log in again.
        $this->permissions->refreshSessionPermissions((int) $user->hr_id, (int) $user->groupid);

        // Approval-status and rejection-reason lists are lookup data required
        // while preparing an inpatient medical approval.  They are readable
        // by every signed-in operator; do not let an optional strict module
        // permission turn these two GET pages into a 403.
        $isMedicalApprovalLookup = $request->route()?->getName() === 'modules.admission-inpatient.reference.index'
            && in_array((string) $request->route('type'), [
                'medical-approval-statuses',
                'medical-approval-rejection-reasons',
            ], true);

        if (! $isMedicalApprovalLookup && config('hm.permissions.enforcement_mode', 'compat') === 'strict') {
            $required = $this->registry->codesForRoute($request->route()?->getName());
            if ($required !== [] && ! collect($required)->contains(fn (string $permission): bool => $this->permissions->can($permission))) {
                abort(403, __('system_administration.users.errors.unauthorized'));
            }
        }

        return $next($request);
    }
}
