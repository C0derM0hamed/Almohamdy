<?php

namespace App\Http\Controllers\Module\GovAccounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\GovAccounts\StoreGovAccountLifecycleRequest;
use App\Repositories\GovAccounts\GovAccountRepository;
use App\Services\GovAccounts\GovAccountRequestService;
use Illuminate\Http\RedirectResponse;

class GovAccountLifecycleController extends Controller
{
    public function __construct(private readonly GovAccountRepository $repository, private readonly GovAccountRequestService $service) {}

    public function store(StoreGovAccountLifecycleRequest $request, int $account): RedirectResponse
    {
        $record = $this->service->createLifecycle($this->repository->accountOrFail($account), $request->payload());

        return redirect()->route('modules.gov-accounts.requests.show', $record)->with('success', __('gov_accounts.flash.request_saved'));
    }
}
