<?php

namespace App\Http\Middleware;

use App\Repositories\UserRepository;
use App\Services\Auth\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthSession
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PermissionService $permissions,
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

        if (! $request->session()->has('hm_permissions')) {
            $this->permissions->refreshSessionPermissions((int) $user->hr_id, (int) $user->groupid);
        }

        return $next($request);
    }
}
