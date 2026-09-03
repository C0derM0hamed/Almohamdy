<?php

namespace App\Http\Controllers\Module\GovAccounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\GovAccounts\AcceptEmployeeUndertakingRequest;
use App\Models\GovAccount;
use App\Repositories\GovAccounts\GovAccountRepository;
use App\Services\GovAccounts\GovAccountRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GovAccountSelfServiceController extends Controller
{
    public function __construct(private readonly GovAccountRepository $repository, private readonly GovAccountRequestService $service) {}

    public function undertakings(): View
    {
        return view('gov-accounts.self.undertakings', ['requests' => $this->repository->pendingUndertakingsForCurrentUser()]);
    }

    public function undertaking(int $request): View
    {
        return view('gov-accounts.self.undertaking', ['accountRequest' => $this->repository->employeeRequestOrFail($request)]);
    }

    public function accept(AcceptEmployeeUndertakingRequest $form, int $request): RedirectResponse
    {
        $this->service->acceptEmployeeUndertaking($this->repository->employeeRequestOrFail($request), (string) $form->ip(), $form->userAgent());

        return redirect()->route('modules.gov-accounts.undertakings.index')->with('success', __('gov_accounts.flash.undertaking_accepted'));
    }

    public function accounts(): View
    {
        return view('gov-accounts.self.accounts', ['accounts' => $this->repository->accountsForCurrentUser()]);
    }

    public function account(int $account): View
    {
        $record = GovAccount::query()->where('companies_groups_id', (int) session('companies_groups_id', 0))->where('employee_user_id', (int) session('hr_user_id', 0))->with(['authority', 'service', 'role'])->findOrFail($account);

        return view('gov-accounts.accounts.show', ['account' => $record, 'canCreateLifecycle' => false]);
    }
}
