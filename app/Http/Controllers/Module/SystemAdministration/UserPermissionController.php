<?php

namespace App\Http\Controllers\Module\SystemAdministration;

use App\Http\Controllers\Controller;
use App\Http\Requests\SystemAdministration\SaveUserRequest;
use App\Http\Requests\SystemAdministration\SaveUserPermissionsRequest;
use App\Models\User;
use App\Services\SystemAdministration\UserPermissionAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserPermissionController extends Controller
{
    public function __construct(private readonly UserPermissionAdminService $service) {}

    public function index(): View
    {
        return view('system-administration.users.index', $this->service->listData());
    }

    public function create(): View
    {
        return view('system-administration.users.form', $this->service->formData());
    }

    public function store(SaveUserRequest $request): RedirectResponse
    {
        $user = $this->service->create($request->validated());

        return redirect()->route('modules.system-admin.users.show', $user)->with('status', __('system_administration.users.saved'));
    }

    public function show(int $user): View
    {
        $scopedUser = $this->service->scopedUser($user);

        return view('system-administration.users.show', $this->service->formData($scopedUser));
    }

    public function edit(int $user): View
    {
        $scopedUser = $this->service->scopedUser($user);

        return view('system-administration.users.form', $this->service->formData($scopedUser));
    }

    public function update(SaveUserRequest $request, int $user): RedirectResponse
    {
        $scopedUser = $this->service->scopedUser($user);
        $this->service->update($scopedUser, $request->validated());

        return redirect()->route('modules.system-admin.users.show', $scopedUser)->with('status', __('system_administration.users.saved'));
    }

    public function editPermissions(int $user): View
    {
        $scopedUser = $this->service->scopedUser($user);

        return view('system-administration.users.permissions', $this->service->permissionData($scopedUser));
    }

    public function updatePermissions(SaveUserPermissionsRequest $request, int $user): RedirectResponse
    {
        $scopedUser = $this->service->scopedUser($user);
        $this->service->updatePermissions($scopedUser, $request->validated(), [
            'request_id' => $request->header('X-Request-Id'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('modules.system-admin.users.permissions.edit', $scopedUser->hr_id)
            ->with('status', 'تم تحديث صلاحيات المستخدم بنجاح.');
    }

    public function permissionHistory(int $user): View
    {
        $scopedUser = $this->service->scopedUser($user);

        return view('system-administration.users.permission-history', $this->service->permissionHistory($scopedUser));
    }
}
