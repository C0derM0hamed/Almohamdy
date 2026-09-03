<?php

namespace App\Http\Controllers\Module\GovAccounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\GovAccounts\StoreGovAccountLifecycleRequest;
use App\Repositories\GovAccounts\GovAccountRepository;
use App\Services\GovAccounts\GovAccountRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GovAccountHrController extends Controller
{
    public function __construct(private readonly GovAccountRepository $repository, private readonly GovAccountRequestService $service) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();

        return view('gov-accounts.hr.index', ['accounts' => $this->repository->hrAccounts($search), 'search' => $search]);
    }

    public function store(StoreGovAccountLifecycleRequest $request, int $account): RedirectResponse
    {
        abort_unless(in_array((string) $request->input('type'), ['suspend', 'close'], true), 403);
        $record = $this->service->createLifecycle($this->repository->accountOrFail($account), $request->payload(), 'hr');

        return redirect()->route('modules.gov-accounts.requests.show', $record)->with('success', __('gov_accounts.flash.request_saved'));
    }
}
