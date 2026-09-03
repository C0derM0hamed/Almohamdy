<?php

namespace App\Http\Controllers\Module\GovAccounts;

use App\Http\Controllers\Controller;
use App\Repositories\GovAccounts\GovAccountRepository;
use App\Support\GovAccounts\GovAccountPermissions;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GovAccountController extends Controller
{
    public function __construct(private readonly GovAccountRepository $repository) {}

    public function index(Request $request): View
    {
        $this->repository->authorizeAny(GovAccountPermissions::VIEW, GovAccountPermissions::REQUEST, GovAccountPermissions::PROCESS);
        $filters = $request->validate([
            'employee_user_id' => ['nullable', 'integer'], 'department_id' => ['nullable', 'integer'],
            'authority_id' => ['nullable', 'integer'], 'service_id' => ['nullable', 'integer'],
            'role_id' => ['nullable', 'integer'], 'status' => ['nullable', 'string', 'max:30'],
        ]);

        return view('gov-accounts.accounts.index', ['accounts' => $this->repository->accounts($filters), 'filters' => $filters] + $this->repository->options());
    }

    public function show(int $account): View
    {
        $record = $this->repository->accountOrFail($account);

        return view('gov-accounts.accounts.show', ['account' => $record, 'canCreateLifecycle' => $this->repository->canCreateLifecycle($record)] + $this->repository->options());
    }
}
